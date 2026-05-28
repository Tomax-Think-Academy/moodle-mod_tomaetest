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

require_once(__DIR__ . '/../classes/GradesSyncHelper.php');

$relevantactivities = $DB->get_records_sql(
    "SELECT * FROM {tomaetest} WHERE is_ready=1 AND is_finished=1 AND is_graded=0"
);

$externalidsmap = array();
foreach ($relevantactivities as $id => $activity) {
    $tetid = $activity->tet_id;
    $externalidsmap[$activity->id] = "TET-$tetid-ext";
}
mtrace("externalIDs Map: " . json_encode($externalidsmap));

foreach ($externalidsmap as $id => $externalid) {
    grades_sync_helper::check_activity($id, $externalid);
}
