<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_tomaetest.
 *
 * @package     mod_tomaetest
 * @copyright   2024 Tomax ltd <roy@tomax.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/classes/Utils.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$t = optional_param('t', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('tomaetest', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('tomaetest', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('tomaetest', array('id' => $t), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('tomaetest', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

// $event = \mod_tomaetest\event\course_module_viewed::create(array(
//     'objectid' => $moduleinstance->id,
//     'context' => $modulecontext
// ));
// $event->add_record_snapshot('course', $course);
// $event->add_record_snapshot('tomaetest', $moduleinstance);
// $event->trigger();

$PAGE->set_url('/mod/tomaetest/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

if (has_capability("mod/tomaetest:manage", $modulecontext)) {
    echo "<p>can manage</p>";
}
if (has_capability("mod/tomaetest:preview", $modulecontext)) {
    echo "<p>can preview</p>";
    echo "<p>" . json_encode(tet_utils::get_course_teachers($course->id)) . "</p>";
}
if (has_capability("mod/tomaetest:attempt", $modulecontext)) {
    echo "<p>can attempt</p>";
    echo "<p>" . json_encode(tet_utils::get_course_students($course->id)) . "</p>";
}

echo $OUTPUT->footer();

// if (quizaccess_tomaetest_utils::is_on_going($this->extradata["TETID"])) {
//     $vixurl = new moodle_url('/mod/quiz/accessrule/tomaetest/openVIX.php', array('quizID' => $this->quiz->id));
//     return "<br>
//         <p> Make sure to install TomaETest first by <a target='_blank' href='https://setup.tomaetest.com/TomaETest/setup.html'>clicking here</a>.</p>
//         After installation, please <a target='_blank' href='$vixurl'>Click here </a>to launch TomaETest client";
// } else {
//     return "Please come back in 30 minutes before the exam start date";
// }
