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
    $examid=$moduleinstance->tet_id;
    $courseid=tet_utils::get_course_tet_id($course->id);
    $location='activity-settings';
    $examdatetime = '';
    if (isset($moduleinstance->extradata)) {
        $decodedextradata = json_decode($moduleinstance->extradata, true);
        $examdatetime = isset($decodedextradata["TETExamPublishTime"]) ? $decodedextradata["TETExamPublishTime"] : '';
    }
    $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('examid' => $examid, 'courseid' => $courseid, 'location' => $location));
    if (!$moduleinstance->is_ready) {
        echo "<style>
            @media (min-width: 768px) {
                .toma-container {
                    max-width: 830px;
                }
            }

            .toma-container {
                align-self: stretch;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 32px;

                font-family: Inter, sans-serif;
            }

            [dir='rtl'] .toma-warning-box {
                border-right: 6px solid #FDB022;
            }

            .toma-warning-box {
                border-left: 6px solid #FDB022;
                align-self: stretch;
                padding: 12px 20px;
                background: #FEF0C7;
                border-radius: 6px;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                gap: 8px;
            }

            .toma-status-container {
                display: flex;
                gap: 20px;
                align-items: center;

                color: black;
                font-size: 16px;
                line-height: 24px;
            }

            .toma-status-item {
                display: flex;
                flex-direction: row;
                justify-content: center;
                gap: 6px;
            }

            .toma-status-label {
                font-weight: 600;
            }

            .toma-status-text,
            .toma-status-message {
                font-weight: 400;
            }

            .toma-management-box {
                align-self: stretch;
                padding: 24px;
                border-radius: 12px;
                border: 1px solid #D5D7DA;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 20px;
            }

            .toma-management-text {
                display: flex;
                flex-direction: column;
                gap: 6px;
                color: black;
            }

            .toma-management-title {
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-management-description {
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }

            .toma-button-container {
                display: flex;
                justify-content: start;
            }

            .toma-primary-button {
                padding: 10px 16px;
                background: #1570EF;
                border: 2px solid var(--Colors-Border-border-tertiary, #F5F5F5);
                border-radius: 8px;
                box-shadow: 0px 0px 0px 1px rgba(10, 13, 18, 0.18) inset, 0px -2px 0px 0px rgba(10, 13, 18, 0.05) inset, 0px 1px 2px 0px rgba(10, 13, 18, 0.05);
                color: white;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }

            .toma-notes-box {
                align-self: stretch;
                padding: 24px;
                background: #F5F5F5;
                border-radius: 12px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                color: #181D27;
            }

            .toma-notes-title {
                font-size: 18px;
                font-weight: 500;
                line-height: 28px;
            }

            .toma-notes-text {
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }
        </style>

        <div class='toma-container'>
            <div class='toma-warning-box'>
                <div class='toma-status-container'>
                    <div class='toma-status-item'>
                        <span class='toma-status-label'>".get_string('activitystatus', 'mod_tomaetest').": </span>
                        <span class='toma-status-text'>".get_string('notreadyvalue', 'mod_tomaetest')."</span>
                    </div>
                    <div class='toma-status-item'>
                        <span class='toma-status-label'>".get_string('due', 'mod_tomaetest').": </span>
                        <span id='due-date' class='toma-status-text'>-</span>
                    </div>
                </div>
                <div class='toma-status-message'>
                    ".get_string('notreadydescription', 'mod_tomaetest')."
                </div>
            </div>
            <div class='toma-management-box'>
                <div class='toma-management-text'>
                    <div class='toma-management-title'>".get_string('activitymanagementtitle', 'mod_tomaetest')."</div>
                    <div class='toma-management-description'>
                        ".get_string('activitymanagementnotreadydescription', 'mod_tomaetest')."
                    </div>
                </div>
                <div class='toma-button-container'>
                    <button class='toma-primary-button' onclick=\"window.open('$url', '_blank'
                        )\">".get_string('openinassessmentstudio', 'mod_tomaetest')."</button>
                </div>
            </div>
            <div class='toma-notes-box'>
                <div class='toma-notes-title'>".get_string('importantnotestitle', 'mod_tomaetest')."</div>
                <div class='toma-notes-text'>
                    <li>
                        ".get_string('completebeforeaccess', 'mod_tomaetest')."
                    </li>
                    <li>
                        ".get_string('availableafterready', 'mod_tomaetest')."
                    </li>
                </div>
            </div>
        </div>

        <script>
            function formatToLocalTime(utcDateString) {
                const date = new Date(utcDateString);
                // Get individual parts of the date
                const weekday = date.toLocaleString(undefined, { weekday: 'long' });  // Sunday
                const day = date.getDate().toString().padStart(2, '0');  // 11
                const month = date.toLocaleString(undefined, { month: 'long' });  // February
                const year = date.getFullYear();  // 2024
                const time = date.toLocaleString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                }).replace(/^0/, '');  // Remove leading zero from hours

                // Construct the final formatted string
                return `\${weekday}, \${day} \${month} \${year}, \${time}`;
            }

            document.addEventListener('DOMContentLoaded', function () {
                const utcString = '$examdatetime';
                console.log('hello');
                console.log(utcString);
                if (utcString) {
                    document.getElementById('due-date').textContent = formatToLocalTime(utcString);
                }
            });
        </script>";
    }
    else if (!$moduleinstance->is_finished) {
        echo "<style>
            @media (min-width: 768px) {
                .toma-container {
                    max-width: 830px;
                }
            }
            .toma-container {
                align-self: stretch;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 32px;

                font-family: Inter, sans-serif;
            }

            [dir='rtl'] .toma-success-box {
                border-right: 6px solid #47CD89;
            }

            .toma-success-box {
                border-left: 6px solid #47CD89;
                align-self: stretch;
                padding: 12px 20px;
                background: #ECFDF3;
                border-radius: 6px;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                gap: 8px;
            }

            .toma-status-container {
                display: flex;
                gap: 20px;
                align-items: center;

                color: black;
                font-size: 16px;
                line-height: 24px;
            }

            .toma-status-item {
                display: flex;
                flex-direction: row;
                justify-content: center;
                gap: 6px;
            }

            .toma-status-label {
                font-weight: 600;
            }

            .toma-status-text, .toma-status-message {
                font-weight: 400;
            }

            .toma-management-box {
                align-self: stretch;
                padding: 24px;
                border-radius: 12px;
                border: 1px solid #D5D7DA;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 20px;
            }

            .toma-management-text {
                display: flex;
                flex-direction: column;
                gap: 6px;
                color: black;
            }

            .toma-management-title {
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-management-description {
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }

            .toma-button-container {
                display: flex;
                justify-content: start;
            }

            .toma-primary-button {
                padding: 10px 16px;
                background: #1570EF;
                border: 2px solid var(--Colors-Border-border-tertiary, #F5F5F5);
                border-radius: 8px;
                box-shadow: 0px 0px 0px 1px rgba(10, 13, 18, 0.18) inset, 0px -2px 0px 0px  rgba(10, 13, 18, 0.05) inset, 0px 1px 2px 0px rgba(10, 13, 18, 0.05);
                color: white;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }

            .toma-notes-box {
                align-self: stretch;
                padding: 24px;
                background: #F5F5F5;
                border-radius: 12px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                color: #181D27;
            }

            .toma-notes-title {
                font-size: 18px;
                font-weight: 500;
                line-height: 28px;
            }

            .toma-notes-text {
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-success-box'>
                <div class='toma-status-container'>
                    <div class='toma-status-item'>
                        <span class='toma-status-label'>".get_string('activitystatus', 'mod_tomaetest').": </span>
                        <span class='toma-status-text'>".get_string('readyvalue', 'mod_tomaetest')."</span>
                    </div>
                </div>
                <div class='toma-status-message'>
                    ".get_string('readydescription', 'mod_tomaetest')."
                </div>
            </div>
            <div class='toma-management-box'>
                <div class='toma-management-text'>
                    <div class='toma-management-title'>".get_string('activitymanagementtitle', 'mod_tomaetest')."</div>
                    <div class='toma-management-description'>
                        ".get_string('activitymanagementreadydescription', 'mod_tomaetest')."
                    </div>
                </div>
                <div>
                    <!-- TODORON: add design here -->
                </div>
                <div class='toma-button-container'>
                    <button class='toma-primary-button' onclick=\"window.open('$url', '_blank')\">".get_string('openinassessmentstudio', 'mod_tomaetest')."</button>
                </div>
            </div>
            <div class='toma-notes-box'>
                <div class='toma-notes-title'>".get_string('importantnotestitle', 'mod_tomaetest')."</div>
                <div class='toma-notes-text'>
                    <li>
                        ".get_string('activitynowavailable', 'mod_tomaetest')."
                    </li>
                    <li>
                        ".get_string('proctoringtoolsnote', 'mod_tomaetest')."
                    </li>
                </div>
            </div>
        </div>

        <script>
            function formatToLocalTime(utcDateString) {
                const date = new Date(utcDateString);
                // Get individual parts of the date
                const weekday = date.toLocaleString(undefined, { weekday: 'long' });  // Sunday
                const day = date.getDate().toString().padStart(2, '0');  // 11
                const month = date.toLocaleString(undefined, { month: 'long' });  // February
                const year = date.getFullYear();  // 2024
                const time = date.toLocaleString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                }).replace(/^0/, '');  // Remove leading zero from hours

                // Construct the final formatted string
                return `\${weekday}, \${day} \${month} \${year}, \${time}`;
            }
            
            document.addEventListener('DOMContentLoaded', function () {
                // const utcString = '$examdatetime';
                // if (utcString) {
                //     document.getElementById('due-date').textContent = formatToLocalTime(utcString);
                // }
            });
        </script>";
    }
}
else if (has_capability("mod/tomaetest:preview", $modulecontext)) {
    echo "<br><br><br>";
    echo "<p>can preview</p>";
    $examid=$moduleinstance->tet_id;
    $location='monitor';
    if ($moduleinstance->is_ready) {
        $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('examid' => $examid, 'location' => $location));
        // TODORON: change to get_string and add to lang file
        echo "<a target='_blank' href='$url'>Click here to open Monitor</a>";
    }
    else {
        // TODORON: change to get_string and add to lang file
        echo "<p>Activity is not yet ready</p>";
    }
    // echo "<p>" . json_encode(tet_utils::get_course_teachers($course->id)) . "</p>";
}
else if (has_capability("mod/tomaetest:attempt", $modulecontext)) {
    echo "<br><br><br>";
    echo "<p>can attempt</p>";
    if (tet_utils::is_activity_available($moduleinstance)) {
        if (!$moduleinstance->is_finished) {
            $activityid = $moduleinstance->id;
            $cmid = $cm->id;
            $vixurl = new moodle_url('/mod/tomaetest/misc/openVIX.php', array('activityid' => $activityid, 'cmid' => $cmid));
            echo "<br>
            <p> Make sure to install TomaETest first by <a target='_blank' href='https://setup.tomaetest.com/TomaETest/setup.html'>clicking here</a>.</p>
            After installation, please <a target='_blank' href='$vixurl'>Click here </a>to launch TomaETest client";
            // $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('examid' => $examid, 'location' => $location));
            // // TODORON: change to get_string and add to lang file
            // echo "<a target='_blank' href='$url'>Click here to open Monitor</a>";
        } else {
            echo "<p>Activity is finished</p>";
        }
    }
    else {
        // TODORON: change to get_string and add to lang file
        echo "<p>Activity is not yet ready</p>";
    }
    // echo "<p>" . json_encode(tet_utils::get_course_students($course->id)) . "</p>";
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
