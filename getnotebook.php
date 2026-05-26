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
 * getnotebook.php - Fetches a presigned URL for viewing a student's graded TomaGrade notebook
 *                   and redirects the student to it.
 *
 * @package    mod_tomaetest
 * @copyright  2025 Tomax ltd <roy@tomax.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
global $CFG, $DB, $USER;

require_once($CFG->dirroot . '/local/tomax/classes/TGConnection.php');
require_once($CFG->dirroot . '/local/tomax/classes/Utils.php');

require_login();

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('tomaetest', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_capability('mod/tomaetest:attempt', $context);

$moduleinstance = $DB->get_record('tomaetest', array('id' => $cm->instance), '*', MUST_EXIST);

if (!$moduleinstance->is_graded) {
    echo "<script>alert('" . get_string('notebooknotready', 'mod_tomaetest') . "');</script>";
    echo "<script>window.close();</script>";
    exit;
}

$graderecord = $DB->get_record('tomaetest_grades', array('activity' => $moduleinstance->id, 'userid' => $USER->id));
if (!$graderecord) {
    echo "<script>alert('" . get_string('notebooknotready', 'mod_tomaetest') . "');</script>";
    echo "<script>window.close();</script>";
    exit;
}

$id = tomax_utils::get_external_id_for_participant($USER);
$examid = "TET-{$moduleinstance->tet_id}-ext";

$postdata = array('id' => "", 'examid' => $examid);
$response = tg_connection::tg_post_request("GetMoodleExamLink", $postdata, [], true);
$response = trim($response);

if ($response === "deleted") {
    echo "<script>alert('" . get_string('notebookdeleted', 'mod_tomaetest') . "');</script>";
    echo "<script>window.close();</script>";
    exit;
}

if ($response === "0" || strpos($response, "Notice") !== false) {
    if (strpos($id, ' --- ') !== false) {
        $array = explode(' --- ', $id);
        $id = rtrim($array[0]);

        $postdata = array('id' => $id, 'examid' => $examid);
        $response = tg_connection::tg_post_request("GetMoodleExamLink", $postdata, [], true);
        $response = trim($response);
    } else {
        $id = $id . ' --- ' . strip_tags($USER->firstname) . ' ' . strip_tags($USER->lastname);

        $postdata = array('id' => $id, 'examid' => $examid);
        $response = tg_connection::tg_post_request("GetMoodleExamLink", $postdata, [], true);
        $response = trim($response);
    }
}

if ($response === "deleted") {
    echo "<script>alert('" . get_string('notebookdeleted', 'mod_tomaetest') . "');</script>";
    echo "<script>window.close();</script>";
    exit;
}

if ($response === "0" || strpos($response, "Notice") !== false || empty($response)) {
    echo "<script>alert('" . get_string('contactadmin', 'mod_tomaetest') . "');</script>";
    echo "<script>window.close();</script>";
    exit;
}

header('Location: ' . $response);
exit;
