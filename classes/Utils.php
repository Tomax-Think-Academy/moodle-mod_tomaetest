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
        where {modules}.name = 'CHANGE' and {course_modules}.instance = ?", [$activityid]);

        return ($record != false) ? $record->id : null;
    }

    public static function get_activity_students($activityid) {
        $cm = get_coursemodule_from_id('tomaetest', $activityid, 0, false, MUST_EXIST);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);

        $students = get_users_by_capability($context, "mod/tomaetest:attempt");
        $students = tomax_utils::moodle_participants_to_tet_participants($students);

        return $students;
    }

    public static function get_activity_teachers($activityid) {
        $cm = get_coursemodule_from_id('tomaetest', $activityid, 0, false, MUST_EXIST);
        $cmid = $cm->id;
        $context = context_module::instance($cmid);

        $teachers = get_users_by_capability($context, "mod/tomaetest:preview");
        $teachers = tomax_utils::moodle_users_to_tet_users($teachers);

        return $teachers;
    }

    public static function get_course_students($courseid) {
        $context = context_course::instance($courseid);

        $students = get_users_by_capability($context, "mod/tomaetest:attempt");
        $students = tomax_utils::moodle_participants_to_tet_participants($students);

        return $students;
    }

    public static function get_course_teachers($courseid) {
        $context = context_course::instance($courseid);

        $teachers = get_users_by_capability($context, "mod/tomaetest:preview");
        $teachers = tomax_utils::moodle_users_to_tet_users($teachers);

        return $teachers;
    }

    public static function get_course_tet_id($courseid) {
        global $DB;
        $record = $DB->get_record_sql("SELECT tet_course from {tet_courses} where {tet_courses}.mdl_course = '?'", [$courseid]);

        return ($record != false) ? $record->tet_course : null;
    }

    public static function is_activity_available($activity) {
        return $activity["is_ready"] == 1 && $activity["is_finished"] == 0;
    }

    public static function upsert_tet_course($course, $user) {
        $tetcourseobject = [];
        $tetcourseobject["CourseName"] = $course->fullname;
        $tetcourseobject["CourseNumber"] = $course->id;
        $tetcourseobject["CourseStartDate"] = date("d/m/Y", $course->startdate);
        // TODORON: decide what to do if there is no end date
        $tetcourseobject["CourseEndDate"] = (isset($course->enddate) && $course->enddate != 0) ? date("d/m/Y", $course->enddate) : null;
        $tetcourseobject["CourseExternalID"] = "mdl-" . $course->id;
        $tetcourseobject["UserExternalID"] = tomax_utils::get_external_id_for_teacher($user);
        // TODORON: figure out year&term
        $tetcourseobject["CourseTerm"] = "0";
        $tetcourseobject["CourseYear"] = intval(date("Y", $course->startdate));

        return tomaetest_connection::tet_post_request("course/createCourses", ["NewCoursesAttributes" => $tetcourseobject]);
    }

    public static function update_tet_course_participants($courseid) {
        $tetcourseexternalid = "mdl-" . $courseid;
        $parsarr = [];
        $participants = self::get_course_students($courseid);
        foreach ($participants as $key => $par) {
            $obj = [];
            $obj["username"] = $par->TETParticipantIdentity;
            $obj["courseExternalID"] = $tetcourseexternalid;
            array_push($parsarr, $obj);
        }

        return tomaetest_connection::tet_post_request("courseparticipant/addParticipantsToCourses", ["NewCoursesParticipants" => $parsarr]);
    }

    public static function create_tet_activity($activityname, $courseid) {
        $tetcourseid = self::get_course_tet_id($courseid);
        if (!isset($tetcourseid)) {
            // TODORON:handle course creation here
        }

        return tomaetest_connection::tet_post_request("exam/mdl/insert", ["CourseID" => $tetcourseid, "ActivityName" => $activityname]);
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
