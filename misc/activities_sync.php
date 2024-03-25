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
require_once($CFG->dirroot . "/local/tomax/classes/TETConnection.php");


function log_and_print($msg, &$log = null) {
    echo $msg;
    echo "\n";

    $log .= "\n" . $msg;
}

function check_activities_changes() {
    global $DB;
    $relevantactivities = $DB->get_records_sql("SELECT * FROM {tomaetest} WHERE is_ready=0 OR is_finished=0");
    $coursesids = array();
    foreach ($relevantactivities as $id => $activity) {
        if (!in_array($activity->course, $coursesids)) {
            array_push($coursesids, $activity->course);
        }
    }
    $markready = array();
    $markfinished = array();
    foreach ($coursesids as $courseid) {
        $tetcourseid = tet_utils::get_course_tet_id($courseid);
        // TODORON: handle case of no tetcourseid
        $res = tomaetest_connection::tet_get_request("course/getCourseActivities", ["CourseID" => $tetcourseid]);
        if (isset($res["success"]) && $res["success"]) {
            $activities = $res['data']['activities'];
            foreach ($activities as $activity) {
                if ($activity["status"] != "draft") {
                    array_push($markready, $activity["ID"]);
                }
                if (in_array($activity["status"], ["exported", "published"])) {
                    array_push($markfinished, $activity["ID"]);
                }
            }
        }
    }
    if (!empty($markready)) {
        $readyids = "(" . implode(', ', $markready) . ")";
        $markreadysql = "UPDATE {tomaetest} SET is_ready=1 WHERE tet_id IN $readyids";
        $DB->execute($markreadysql);
    }
    if (!empty($markfinished)) {
        $finishedids = "(" . implode(', ', $markfinished) . ")";
        $markfinishedsql = "UPDATE {tomaetest} SET is_finished=1 WHERE tet_id IN $finishedids";
        $DB->execute($markfinishedsql);
    }
}


check_activities_changes();