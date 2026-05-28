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
 * Helper class for TG grade sync logic, shared between the scheduled task
 * and the manual "Reset Grading Status" action.
 *
 * @package     mod_tomaetest
 * @copyright   2024 Tomax ltd <roy@tomax.co.il>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/tomax/classes/Utils.php');
require_once($CFG->dirroot . '/local/tomax/classes/TGConnection.php');
require_once(__DIR__ . '/../lib.php');

class grades_sync_helper {

    /**
     * Fetch grades from TG for a single activity and push them to the Moodle
     * gradebook when the exam is published.
     *
     * @param int    $activityid  ID of the {tomaetest} record.
     * @param string $externalid  External ID used by TG (e.g. "TET-123-ext").
     * @param bool   $trace       Whether to emit mtrace() output (true for cron, false for web).
     */
    public static function check_activity($activityid, $externalid, $trace = true) {
        global $DB, $CFG;

        $response = tg_connection::tg_get_request("MoodleGetExamDetails", [$externalid]);
        if ($trace) {
            mtrace("response for $externalid: " . json_encode($response));
        }

        if (!isset($response["Response"]) || $response["Response"] == "Failed") {
            return;
        }

        if (isset($response["CourseParticipant"])) {
            foreach ($response["CourseParticipant"] as $value) {
                if ($value["ParGrade"] != "" || isset($value["ParGrade"]) ||
                    $value["ParGradeNoFactor"] != "" || isset($value["ParGradeNoFactor"])) {

                    $currentuser = tomax_utils::get_participant_by_external_id($value["ParId"]);
                    if ($currentuser && $value["ParExamStatus"] == 2) { // status - 'checked'
                        $userid = $currentuser->id;
                        if (isset($value["ParGrade"])) {
                            self::set_grade($activityid, $userid, $value["ParGrade"]);
                        } else {
                            self::set_grade($activityid, $userid, $value["ParGradeNoFactor"]);
                        }
                    }
                }
            }
        }

        if ($response["GetExamDetail"]["ExamStatus"] == "3") { // status - 'published'
            $activity = $DB->get_record('tomaetest', array('id' => $activityid));
            $updatestatus = tomaetest_update_grades($activity);
            if ($updatestatus == 0) { // GRADE_UPDATE_OK
                $DB->execute('UPDATE {tomaetest} SET is_graded=1 WHERE id = ?', array($activityid));
            }
        }
    }

    /**
     * Insert or update a single participant grade in {tomaetest_grades}.
     *
     * @param int   $activityid
     * @param int   $userid
     * @param mixed $grade
     */
    public static function set_grade($activityid, $userid, $grade) {
        global $DB;

        $dbrec = $DB->get_record('tomaetest_grades', array('activity' => $activityid, 'userid' => $userid));
        if (empty($dbrec)) {
            $data               = new stdClass();
            $data->activity     = $activityid;
            $data->userid       = $userid;
            $data->grade        = $grade;
            $data->timemodified = time();
            $DB->insert_record('tomaetest_grades', $data);
        } else {
            $DB->execute(
                'UPDATE {tomaetest_grades} SET grade = :grade WHERE activity = :activityid AND userid = :userid',
                array('grade' => $grade, 'activityid' => $activityid, 'userid' => $userid)
            );
        }
    }
}
