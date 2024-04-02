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


function update_relevant_participants() {
    global $DB;
    $relevantactivities = $DB->get_records_sql("SELECT * FROM {tomaetest} WHERE is_ready=0 OR is_finished=0");
    $coursesids = array();
    foreach ($relevantactivities as $id => $activity) {
        if (!in_array($activity->course, $coursesids)) {
            array_push($coursesids, $activity->course);
        }
    }
    foreach ($coursesids as $courseid) {
        tet_utils::upsert_tet_course_participants($courseid);
    }
}


update_relevant_participants();