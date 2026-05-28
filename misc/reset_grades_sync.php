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
 * Resets the grading sync status of a TomaETest activity and immediately
 * re-syncs grades for that activity from TG.
 *
 * @package     mod_tomaetest
 * @copyright   2024 Tomax ltd <roy@tomax.co.il>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/GradesSyncHelper.php');

// Parameters.
$cmid = required_param('cmid', PARAM_INT);

// Load course module.
$cm             = get_coursemodule_from_id('tomaetest', $cmid, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('tomaetest', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();

require_capability('mod/tomaetest:manage', context_module::instance($cm->id));

$returnurl = new moodle_url('/mod/tomaetest/view.php', array('id' => $cmid));

if (!$moduleinstance->is_graded) {
    redirect($returnurl);
}

// Reset the grading status flag so the sync re-fetches grades.
$DB->execute('UPDATE {tomaetest} SET is_graded=0 WHERE id = ?', array($moduleinstance->id));

// Immediately sync grades for this specific activity.
$externalid = 'TET-' . $moduleinstance->tet_id . '-ext';
grades_sync_helper::check_activity($moduleinstance->id, $externalid, false);

redirect($returnurl, get_string('resetgradingstatussuccess', 'mod_tomaetest'), null,
    \core\output\notification::NOTIFY_SUCCESS);
