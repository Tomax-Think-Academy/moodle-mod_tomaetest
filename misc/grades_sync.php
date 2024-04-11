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


defined('MOODLE_INTERNAL') || die();
global $DB, $CFG;

require_once(__DIR__.'/../classes/Utils.php');
require_once($CFG->dirroot . "/local/tomax/classes/TGConnection.php");

function get_activities_to_check() {
    global $DB;
    $relevantactivities = $DB->get_records_sql("SELECT * FROM {tomaetest} WHERE is_ready=1 AND is_finished=1 AND is_graded=0");
    $externalidsmap = array();
    foreach ($relevantactivities as $id => $activity) {
        $tetid = $activity->tet_id;
        $externalid = "TET-$tetid-ext";
        $externalidsmap[$activity->id] = $externalid;
    }
    mtrace("externalIDs Map: " . json_encode($externalidsmap));
    return $externalidsmap;
}

function check_activity($activityid, $externalid) {
    $response = tg_connection::tg_get_request("MoodleGetExamDetails", [$externalid]);
    mtrace("response for $externalid: " . json_encode($response));
    if (!isset($response["Response"]) || $response["Response"] == "Failed") {
        return;
    }
    if (isset($response["CourseParticipant"])) {
        foreach ($response["CourseParticipant"] as $value) {
            if ($value["ParGrade"] != "" || isset($value["ParGrade"]) || $value["ParGradeNoFactor"] != "" || isset($value["ParGradeNoFactor"])) {
                $currentuser = tomax_utils::get_participant_by_external_id($value["ParId"]);
                if ($currentuser && $value["ParExamStatus"] == 2) { // status - 'checked'
                    $userid = $currentuser->id;
                    if (isset($value["ParGrade"])) {
                        set_grade($activityid, $userid, $value["ParGrade"]);
                    } else {
                        set_grade($activityid, $userid, $value["ParGradeNoFactor"]);
                    }
                }
            }
        }
    }
    if ($response["GetExamDetail"]["ExamStatus"] == "3") { // status - 'published'
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/tomaetest/lib.php');
        $activity = $DB->get_record('tomaetest', array('id' => $activityid));
        $updatestatus = tomaetest_update_grades($activity);
        if ($updatestatus == 0) { // GRADE_UPDATE_OK
            $DB->execute('UPDATE {tomaetest} SET is_graded=1 WHERE id = ?', array($activityid));
        }
    }
}

function set_grade($activityid, $userid, $grade) {
    global $DB;

    // Temp grade.
    $dbrec = $DB->get_record("tomaetest_grades", array("activity" => $activityid, "userid" => $userid));
    if (empty($dbrec)) {
        $data = new stdClass();
        $data->activity = $activityid;
        $data->userid = $userid;
        $data->grade = $grade;
        $data->timemodified = time();
        $DB->insert_record('tomaetest_grades', $data);

    } else {
        $DB->execute("UPDATE {tomaetest_grades} SET grade = :grade WHERE activity = :activityid AND userid = :userid",
         array('grade' => $grade, 'activityid' => $activityid, 'userid' => $userid));
    }
}

$activitiestocheck = get_activities_to_check();

foreach ($activitiestocheck as $id => $externalid) {
    check_activity($id, $externalid);
}
