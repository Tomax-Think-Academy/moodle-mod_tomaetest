<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *
 * @package     mod_tomaetest
 * @copyright   2024 Tomax ltd <roy@tomax.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . "/local/tomax/classes/Utils.php");
require_once($CFG->dirroot . "/local/tomax/classes/TETConnection.php");

class tet_utils
{
    public static function moodle_participants_to_tet_participants($moodlearray) {
        return
            array_map(function ($student) {
                $newstudent = new stdClass();
                $newstudent->TETParticipantFirstName = $student->firstname;
                $newstudent->TETParticipantLastName = $student->lastname;
                $newstudent->TETParticipantPhone = $student->phone1;
                $newstudent->TETParticipantEmail = $student->email;
                $newstudent->TETParticipantIdentity = tomax_utils::get_external_id_for_participant($student);
                return $newstudent;
            }, $moodlearray);
    }

    public static function moodle_users_to_tet_users($moodlearray) {
        return array_map(function ($user) {
            $newuser = new stdClass();

            $newuser->EtestRole = "ROLE_MOODLE";
            // TODORON: change to role based on role in moodle
            $newuser->TETExternalID = tomax_utils::get_external_id_for_teacher($user);
            $newuser->UserName = tomax_utils::get_external_id_for_teacher($user);
            $newuser->TETUserLastName = $user->lastname;
            $newuser->TETUserEmail = $user->email;
            $newuser->TETUserFirstName = $user->firstname;
            $newuser->TETUserPhone = $user->phone1;

            return $newuser;
        }, $moodlearray);
    }

    public static function create_tet_user($id) {
        global $DB;

        $user = $DB->get_record("user", array("id" => $id));
        $user = static::moodle_users_to_tet_users([$user])[0];

        $tetuserresponse = tomaetest_connection::tet_post_request("user/getByExternalID/view", ["ExternalID" => $user->TETExternalID]);

        if (!$tetuserresponse["success"]) {
            $sendingobject = [
                "UserName" => $user->UserName,
                "Attributes" => $user
            ];
            unset($sendingobject["Attributes"]->UserName);
            unset($sendingobject["Attributes"]->EtestRole);
            $tetuserresponse = tomaetest_connection::tet_post_request("user/insert", $sendingobject);
            if (!$tetuserresponse['success']) {
                return "Duplicate ExternalID/UserName - " . $sendingobject["UserName"] . " Please check for duplicate data.";
            }
            $tetuserid = $tetuserresponse["data"];
        } else {
            $tetuserid = $tetuserresponse["data"]["Entity"];
        }
        $rolename = $user->EtestRole;
        $tetroleresponse = tomaetest_connection::tet_post_request("role/getByName/view", ["Name" => $rolename]);

        if (!$tetroleresponse["success"]) {
            return "Could not find role in TET.";
        }
        $roleid = $tetroleresponse["data"]["Entity"]["ID"];
        $responseconnect = tomaetest_connection::tet_post_request("user/edit?ID=" . $tetuserid, [
            "ID" => $tetuserid,
            "Attributes" => new stdClass(),
            "Roles" => ["Delete" => [], "Insert" => [$roleid]]
        ]);
        if (!$responseconnect["success"]) {
            return "could not add role for user.";
        }
        return true;
    }

    public static function get_activity_by_exam_code($code) {
        global $DB;
        $record = $DB->get_record_sql(
            "select * from {tomaetest} where extradata like ?",
            ["%\"TETExamLink\":\"$code\"%"]
        );
        return $record;
    }

    public static function update_record($record) {
        global $DB;
        $record->extradata = json_encode($record->extradata);
        return $DB->update_record('tomaetest', $record);
    }

    public static function get_etest_activity($activityid) {
        global $DB;
        try {
            $record = $DB->get_record('tomaetest', array('id' => $activityid));
        } catch (Exception $e) {
            return false;
        }
        if ($record != false) {
            if (isset($record->extradata)) {
                $record->extradata = json_decode($record->extradata, true);
            } else {
                $record->extradata = [];
            }
        }
        return $record;
    }

    public static function get_cmid($activityid) {
        global $DB;
        $record = $DB->get_record_sql("SELECT {course_modules}.ID from {course_modules}
        join {modules} on module = {modules}.id
        where {modules}.name = 'tomaetest' and {course_modules}.instance = ?", [$activityid]);

        return ($record != false) ? $record->id : null;
    }

    public static function get_activity_students($activityid) {
        $cm = get_coursemodule_from_id('tomaetest', $activityid, 0, false, MUST_EXIST);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);

        $students = get_users_by_capability($context, "mod/tomaetest:attempt");
        $students = static::moodle_participants_to_tet_participants($students);

        return $students;
    }

    public static function get_activity_teachers($activityid) {
        $cm = get_coursemodule_from_id('tomaetest', $activityid, 0, false, MUST_EXIST);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);

        $teachers = get_users_by_capability($context, "mod/tomaetest:preview");
        $teachers = static::moodle_users_to_tet_users($teachers);

        return $teachers;
    }

    public static function get_course_students($courseid) {
        $context = context_course::instance($courseid);

        $students = get_users_by_capability($context, "mod/tomaetest:attempt");
        $students = static::moodle_participants_to_tet_participants($students);

        return $students;
    }

    public static function get_course_teachers($courseid) {
        $context = context_course::instance($courseid);

        $teachers = get_users_by_capability($context, "mod/tomaetest:preview");
        $teachers = static::moodle_users_to_tet_users($teachers);

        return $teachers;
    }

    public static function get_course_tet_id($courseid) {
        global $DB;
        $record = $DB->get_record_sql("SELECT tet_course from {tet_courses} where {tet_courses}.mdl_course = :courseid", ["courseid" => $courseid]);

        return ($record != false) ? $record->tet_course : null;
    }

    public static function is_activity_available($activity) {
        return $activity["is_ready"] == 1 && $activity["is_finished"] == 0;
    }

    public static function upsert_tet_course($course, $user) {
        $tetcourseobject = [];
        $tetcourseobject["UserExternalID"] = tomax_utils::get_external_id_for_teacher($user);
        $attributes = [];
        $attributes["TETCourseName"] = $course->fullname;
        $attributes["TETCourseNumber"] = intval($course->id);
        $attributes["TETCourseStartDate"] = date("d/m/Y", $course->startdate);
        // TODORON: decide what to do if there is no end date
        $attributes["TETCourseEndDate"] = (isset($course->enddate) && $course->enddate != 0) ? date("d/m/Y", $course->enddate) : null;
        $attributes["TETCourseExternalID"] = "mdl-" . $course->id;
        // TODORON: figure out year&term
        $attributes["TETCourseTerm"] = "0";
        $attributes["TETCourseYear"] = intval(date("Y", $course->startdate));
        $tetcourseobject["Attributes"] = $attributes;

        $res = tomaetest_connection::tet_post_request("course/createCourses", ["NewCoursesAttributes" => [$tetcourseobject]]);
        if (!isset($res["success"]) || !$res["success"]) {
            throw new moodle_exception('tetgeneralerror', 'mod_tomaetest', '', '', json_encode($res));
        }
        if(!self::get_course_tet_id($course->id)) {
            $res = tomaetest_connection::tet_get_request("course/view", ["externalID" => "mdl-" . $course->id]);
            if (!isset($res["success"]) || !$res["success"]) {
                throw new moodle_exception('tetgeneralerror', 'mod_tomaetest', '', '', json_encode($res));
            }
            global $DB;
            $record = [];
            $record["mdl_course"] = $course->id;
            $record["tet_course"] = $res["data"]["Entity"]["ID"];
            $DB->insert_record('tet_courses', $record);
        }
    }

    public static function upsert_tet_course_participants($courseid) {
        $tetcourseexternalid = "mdl-" . $courseid;
        $parsarr = [];
        $participants = self::get_course_students($courseid);
        foreach ($participants as $key => $par) {
            $obj = [];
            $obj["username"] = $par->TETParticipantIdentity;
            $obj["Attributes"]["TETParticipantFirstName"] = isset($par->TETParticipantFirstName) ? $par->TETParticipantFirstName : "";
            $obj["Attributes"]["TETParticipantLastName"] = isset($par->TETParticipantLastName) ? $par->TETParticipantLastName : "";
            $obj["Attributes"]["TETParticipantEmail"] = isset($par->TETParticipantEmail) ? $par->TETParticipantEmail : "";
            $obj["Attributes"]["TETParticipantPhone"] = isset($par->TETParticipantPhone) ? $par->TETParticipantPhone : "";
            array_push($parsarr, $obj);
        }

        $res = tomaetest_connection::tet_post_request("courseparticipant/mdl/addParticipantsToCourse",
            ["CourseExternalID" => $tetcourseexternalid, "CoursesParticipants" => $parsarr]
        );
        if (isset($res["success"]) && $res["success"]) {
            return true;
        }
        else {
            if (!isset($res["success"]) || !$res["success"]) {
                throw new moodle_exception('tetgeneralerror', 'mod_tomaetest', '', '', json_encode($res));
            }
            // TODORON: add better error handling
            return false;
        }
    }

    public static function create_tet_activity($activityname, $courseid, $tetid = null) {
        global $USER;

        $tetcourseid = self::get_course_tet_id($courseid);
        if (!isset($tetcourseid)) {
            $currentcourse = get_course($courseid);
            self::upsert_tet_course($currentcourse, $USER);
            $tetcourseid = self::get_course_tet_id($courseid);
        }
        self::upsert_tet_course_participants($courseid);

        $payload = ["CourseID" => $tetcourseid, "ActivityName" => $activityname];
        if (isset($tetid)) {
            $payload["ExamID"] = $tetid;
        }
        $res = tomaetest_connection::tet_post_request("exam/mdl/insert", $payload);
        return $res;
    }

    public static function delete_tet_activity($tetid) {
        $payload = ["ActivityID" => $tetid];
        $res = tomaetest_connection::tet_post_request("course/mdl/deleteCourseActivity", $payload);
        return $res;
    }
    

















    public static function get_moodle_allowed_integrity_management($userid = null) {
        global $DB;
        $systemcontext = context_system::instance();
        $teachers = [];
        if (has_capability("mod/quizaccess_tomaetest:viewtomaetestair", $systemcontext, $userid)) {
            array_push($teachers, $DB->get_record('user', array("id" => $userid)));
        }
        return $teachers;
    }
 
}
