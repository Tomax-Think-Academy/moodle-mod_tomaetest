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
    $location='assessment-studio';
    $checkintime = '';
    $starttime = '';
    $closetime = '';
    if (isset($moduleinstance->extradata)) {
        $decodedextradata = json_decode($moduleinstance->extradata, true);
        $checkintime = isset($decodedextradata["TETExamPublishTime"]) ? $decodedextradata["TETExamPublishTime"] : '';
        $starttime = isset($decodedextradata["TETExamStartTime"]) ? $decodedextradata["TETExamStartTime"] : '';
        $closetime = isset($decodedextradata["TETExamEnd"]) ? $decodedextradata["TETExamEnd"] : '';
    }
    $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('examid' => $examid, 'courseid' => $courseid, 'location' => $location));
    if (!$moduleinstance->is_ready) { // activity is not ready
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
                border: 2px solid #F5F5F5;
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
                    <button class='toma-primary-button' onclick=\"window.open('$url', '_blank')\">".get_string('openinassessmentstudio', 'mod_tomaetest')."</button>
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
                const utcString = '$checkintime';
                if (utcString) {
                    document.getElementById('due-date').textContent = formatToLocalTime(utcString);
                }
            });
        </script>";
    }
    else if (!$moduleinstance->is_finished) { // activity is ready and not finished
        if (tet_utils::is_activity_available($moduleinstance)) {
            $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('courseid' => $courseid, 'location' => $location));
        }
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

            .toma-management-cards {
                display: flex;
                align-items: flex-start;
                gap: 20px;
                align-self: stretch;
                color: black;
            }

            .toma-card {
                align-self: stretch;
                display: flex;
                padding: 12px;
                align-items: flex-start;
                gap: 20px;
                flex: 1 0 0;
                border-radius: 8px;
                border: 1px solid #D5D7DA;
                background:#FAFAFA;
            }

            .toma-info-group {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .toma-info-label {
                font-size: 14px;
                font-weight: 400;
                line-height: 20px;
            }

            .toma-info-value {
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }

            .toma-info-note {
                color: #717680;
                font-size: 14px;
                font-weight: 400;
                line-height: 20px;
            }
            
            .toma-button-container {
                display: flex;
                justify-content: start;
            }

            .toma-primary-button {
                padding: 10px 16px;
                background: #1570EF;
                border: 2px solid #F5F5F5;
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
                <div class='toma-management-cards'>
                    <div class='toma-card'>
                        <div class='toma-info-group'>
                            <div class='toma-info-label'>".get_string('checkinstarttime', 'mod_tomaetest').":</div>
                            <div id='toma-check-in-time' class='toma-info-value'>-</div>
                        </div>
                        <div class='toma-info-group'>
                            <div class='toma-info-label'>".get_string('actualstarttime', 'mod_tomaetest').":</div>
                            <div id='toma-start-time' class='toma-info-value'>-</div>
                        </div>
                    </div>
                    <div class='toma-card'>
                        <div class='toma-info-group'>
                            <div class='toma-info-label'>".get_string('dateclosed', 'mod_tomaetest').":</div>
                            <div id='toma-close-time' class='toma-info-value'>-</div>
                            <div class='toma-info-note'>".get_string('dateclosednote', 'mod_tomaetest')."</div>
                        </div>
                    </div>
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
                const checkinTime = '$checkintime';
                if (checkinTime) {
                    document.getElementById('toma-check-in-time').textContent = formatToLocalTime(checkinTime);
                }
                const startTime = '$starttime';
                if (startTime) {
                    document.getElementById('toma-start-time').textContent = formatToLocalTime(startTime);
                }
                const closeTime = '$closetime';
                if (closeTime) {
                    document.getElementById('toma-close-time').textContent = formatToLocalTime(closeTime);
                }
            });
        </script>";
    }
    else { // activity is finished
        $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('courseid' => $courseid, 'location' => $location));
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
                gap: 20px;

                font-family: Inter, sans-serif;
            }

            .toma-icon-wrapper {
                margin: 0 auto;   
            }

            .toma-finished-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-finished-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-finished-subtitle {
                text-align: center;
                color: #535862;
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
                border: 2px solid #F5F5F5;
                border-radius: 8px;
                box-shadow: 0px 0px 0px 1px rgba(10, 13, 18, 0.18) inset, 0px -2px 0px 0px  rgba(10, 13, 18, 0.05) inset, 0px 1px 2px 0px rgba(10, 13, 18, 0.05);
                color: white;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-icon-wrapper'>
                <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#DCFAE6'/>
                    <path d='M22.7502 28L26.2502 31.5L33.2502 24.5M39.6668 28C39.6668 34.4433 34.4435 39.6666 28.0002 39.6666C21.5568 39.6666 16.3335 34.4433 16.3335 28C16.3335 21.5567 21.5568 16.3333 28.0002 16.3333C34.4435 16.3333 39.6668 21.5567 39.6668 28Z' stroke='#079455' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                </svg>
            </div>
            <div class='toma-finished-box'>
                <span class='toma-finished-title'>".get_string('activityfinished', 'mod_tomaetest')."</span>
                <span class='toma-finished-subtitle'>".get_string('activityfinisheddescription', 'mod_tomaetest')."</span>
            </div>
            <div class='toma-button-container'>
                <button class='toma-primary-button' onclick=\"window.open('$url', '_blank')\">".get_string('openinassessmentstudio', 'mod_tomaetest')."</button>
            </div>
        </div>";
    }
}
else if (has_capability("mod/tomaetest:preview", $modulecontext)) {
    $examid=$moduleinstance->tet_id;
    $location='monitor';
    $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('examid' => $examid, 'location' => $location));
    if (!$moduleinstance->is_ready) { // activity is not ready
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
                gap: 20px;

                font-family: Inter, sans-serif;
            }

            .toma-icon-wrapper {
                margin: 0 auto;   
            }

            .toma-not-ready-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-not-ready-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-not-ready-subtitle {
                text-align: center;
                color: #535862;
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }

            .toma-note-wrapper {
                margin-top: 12px;
            }

            .toma-note-text {
                text-align: center;
                color: #535862;
                font-size: 12px;
                font-weight: 400;
                line-height: 18px;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-icon-wrapper'>
                <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#F5F5F5'/>
                    <path d='M28.0002 21V28L32.6668 30.3333M39.6668 28C39.6668 34.4433 34.4435 39.6666 28.0002 39.6666C21.5568 39.6666 16.3335 34.4433 16.3335 28C16.3335 21.5567 21.5568 16.3333 28.0002 16.3333C34.4435 16.3333 39.6668 21.5567 39.6668 28Z' stroke='#717680' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                </svg>                
            </div>
            <div class='toma-not-ready-box'>
                <span class='toma-not-ready-title'>".get_string('activitynotready', 'mod_tomaetest')."</span>
                <span class='toma-not-ready-subtitle'>".get_string('activitynotreadydescription', 'mod_tomaetest')."</span>
            </div>
            <div class='toma-note-wrapper'>
                <span class='toma-note-text'>".get_string('activitynotreadynote', 'mod_tomaetest')."</span>
            </div>
        </div>";
    }
    else if (!$moduleinstance->is_finished) { // activity is ready and not finished
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

            .toma-sub-container {
                align-self: stretch;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 20px;
            }

            .toma-icon-wrapper {
                margin: 0 auto;   
            }

            .toma-ready-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-ready-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-ready-subtitle {
                text-align: center;
                color: #000000;
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
                border: 2px solid #F5F5F5;
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
            <div class='toma-sub-container'>
                <div class='toma-icon-wrapper'>
                    <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                        <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#D1E9FF'/>
                        <path d='M30.3332 16.6478V21.4667C30.3332 22.1201 30.3332 22.4468 30.4603 22.6964C30.5722 22.9159 30.7507 23.0944 30.9702 23.2063C31.2197 23.3334 31.5464 23.3334 32.1998 23.3334H37.0188M30.3332 33.8333H23.3332M32.6665 29.1667H23.3332M37.3332 25.6529V34.0667C37.3332 36.0268 37.3332 37.0069 36.9517 37.7556C36.6161 38.4142 36.0807 38.9496 35.4221 39.2852C34.6734 39.6667 33.6934 39.6667 31.7332 39.6667H24.2665C22.3063 39.6667 21.3262 39.6667 20.5775 39.2852C19.919 38.9496 19.3835 38.4142 19.048 37.7556C18.6665 37.0069 18.6665 36.0268 18.6665 34.0667V21.9333C18.6665 19.9731 18.6665 18.9931 19.048 18.2444C19.3835 17.5858 19.919 17.0504 20.5775 16.7148C21.3262 16.3333 22.3063 16.3333 24.2665 16.3333H28.0136C28.8696 16.3333 29.2977 16.3333 29.7005 16.43C30.0576 16.5158 30.399 16.6572 30.7122 16.8491C31.0654 17.0655 31.368 17.3682 31.9734 17.9735L35.693 21.6931C36.2983 22.2985 36.601 22.6011 36.8174 22.9543C37.0093 23.2675 37.1507 23.6089 37.2365 23.966C37.3332 24.3688 37.3332 24.7969 37.3332 25.6529Z' stroke='#1570EF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                    </svg>
                </div>
                <div class='toma-ready-box'>
                    <span class='toma-ready-title'>".get_string('activityready', 'mod_tomaetest')."</span>
                    <span class='toma-ready-subtitle'>".get_string('activityreadydescription', 'mod_tomaetest')."</span>
                </div>
            </div>
            <div class='toma-button-container'>
                <button class='toma-primary-button' onclick=\"window.open('$url', '_blank')\">".get_string('openmonitor', 'mod_tomaetest')."</button>
            </div>
            <div class='toma-notes-box'>
                <div class='toma-notes-title'>".get_string('importantnotestitle', 'mod_tomaetest')."</div>
                <div class='toma-notes-text'>
                    <li>
                        ".get_string('noneditingnote1', 'mod_tomaetest')."
                    </li>
                    <li>
                        ".get_string('noneditingnote2', 'mod_tomaetest')."
                    </li>
                    <li>
                        ".get_string('noneditingnote3', 'mod_tomaetest')."
                    </li>
                    <li>
                        ".get_string('noneditingnote4', 'mod_tomaetest')."
                    </li>
                </div>
            </div>
        </div>";
    }
    else { // activity is finished
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
                gap: 20px;

                font-family: Inter, sans-serif;
            }

            .toma-icon-wrapper {
                margin: 0 auto;   
            }

            .toma-finished-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-finished-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-finished-subtitle {
                text-align: center;
                color: #535862;
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
                border: 2px solid #F5F5F5;
                border-radius: 8px;
                box-shadow: 0px 0px 0px 1px rgba(10, 13, 18, 0.18) inset, 0px -2px 0px 0px  rgba(10, 13, 18, 0.05) inset, 0px 1px 2px 0px rgba(10, 13, 18, 0.05);
                color: white;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-icon-wrapper'>
                <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#DCFAE6'/>
                    <path d='M22.7502 28L26.2502 31.5L33.2502 24.5M39.6668 28C39.6668 34.4433 34.4435 39.6666 28.0002 39.6666C21.5568 39.6666 16.3335 34.4433 16.3335 28C16.3335 21.5567 21.5568 16.3333 28.0002 16.3333C34.4435 16.3333 39.6668 21.5567 39.6668 28Z' stroke='#079455' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                </svg>
            </div>
            <div class='toma-finished-box'>
                <span class='toma-finished-title'>".get_string('activityfinished', 'mod_tomaetest')."</span>
                <span class='toma-finished-subtitle'>".get_string('activityfinisheddescription', 'mod_tomaetest')."</span>
            </div>
            <div class='toma-button-container'>
                <button class='toma-primary-button' onclick=\"window.open('$url', '_blank')\">".get_string('openmonitor', 'mod_tomaetest')."</button>
            </div>
        </div>";
    }
}
else if (has_capability("mod/tomaetest:attempt", $modulecontext)) {
    if ($moduleinstance->is_finished) { // activity is finished
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
                gap: 20px;

                font-family: Inter, sans-serif;
            }

            .toma-icon-wrapper {
                margin: 0 auto;   
            }

            .toma-finished-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-finished-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-finished-subtitle {
                text-align: center;
                color: #535862;
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-icon-wrapper'>
                <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#DCFAE6'/>
                    <path d='M22.7502 28L26.2502 31.5L33.2502 24.5M39.6668 28C39.6668 34.4433 34.4435 39.6666 28.0002 39.6666C21.5568 39.6666 16.3335 34.4433 16.3335 28C16.3335 21.5567 21.5568 16.3333 28.0002 16.3333C34.4435 16.3333 39.6668 21.5567 39.6668 28Z' stroke='#079455' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                </svg>
            </div>
            <div class='toma-finished-box'>
                <span class='toma-finished-title'>".get_string('activityfinished', 'mod_tomaetest')."</span>
                <span class='toma-finished-subtitle'>".get_string('activityfinisheddescription', 'mod_tomaetest')."</span>
            </div>
        </div>";
    }
    else if (!tet_utils::is_activity_available($moduleinstance)) { // activity not started
        $checkintime = '';
        if (isset($moduleinstance->extradata)) {
            $decodedextradata = json_decode($moduleinstance->extradata, true);
            $checkintime = isset($decodedextradata["TETExamPublishTime"]) ? $decodedextradata["TETExamPublishTime"] : '';
        }
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
                gap: 20px;

                font-family: Inter, sans-serif;
            }

            .toma-icon-wrapper {
                margin: 0 auto;
            }

            .toma-not-ready-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-not-ready-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-not-ready-subtitle {
                text-align: center;
                color: #535862;
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }

            .toma-available-from-wrapper {
                display: none;
                margin-top: 16px;
                padding: 10px 16px;
                align-items: flex-start;
                gap: 6px;
                border-radius: 8px;
                background: #F5F5F5;
                color: #181D27;
                text-align: center;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }

            .toma-prompt-wrapper {
                margin-top: 12px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 24px;
                border-top: 1px solid #D5D7DA;
            }

            .toma-prompt-text {
                color: #252B37;
                text-align: center;
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }

            .toma-primary-button {
                padding: 10px 16px;
                background: #1570EF;
                border: 2px solid #F5F5F5;
                border-radius: 8px;
                box-shadow: 0px 0px 0px 1px rgba(10, 13, 18, 0.18) inset, 0px -2px 0px 0px  rgba(10, 13, 18, 0.05) inset, 0px 1px 2px 0px rgba(10, 13, 18, 0.05);
                color: white;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-icon-wrapper'>
                <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#F5F5F5'/>
                    <path d='M28.0002 21V28L32.6668 30.3333M39.6668 28C39.6668 34.4433 34.4435 39.6666 28.0002 39.6666C21.5568 39.6666 16.3335 34.4433 16.3335 28C16.3335 21.5567 21.5568 16.3333 28.0002 16.3333C34.4435 16.3333 39.6668 21.5567 39.6668 28Z' stroke='#717680' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                </svg>
            </div>
            <div class='toma-not-ready-box'>
                <span class='toma-not-ready-title'>".get_string('activitynotavailable', 'mod_tomaetest')."</span>
                <span class='toma-not-ready-subtitle'>".get_string('activitynotavailabledescription', 'mod_tomaetest')."</span>
                <div class='toma-available-from-wrapper'>
                    <span class='toma-available-from-text'>".get_string('availablefrom', 'mod_tomaetest').": </span>
                    <span id='start-date' class='toma-available-from-text'>-</span>
                </div>
            </div>
            <div class='toma-prompt-wrapper'>
                <span class='toma-prompt-text'>".get_string('downloadvixprompt', 'mod_tomaetest')."</span>
                <button class='toma-primary-button' onclick=\"window.open('https://setup.tomaetest.com/TomaETest/setup.html', '_blank')\">".get_string('downloadvix', 'mod_tomaetest')."</button>
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
                const utcString = '$checkintime';
                if (utcString) {
                    document.getElementById('start-date').textContent = formatToLocalTime(utcString);
                    const element = document.querySelector('.toma-available-from-wrapper');
                    if (element) {
                        element.style.display = 'flex';
                    }
                }
            });
        </script>";
    }
    else { // activity is active
        $activityid = $moduleinstance->id;
        $cmid = $cm->id;
        $vixurl = new moodle_url('/mod/tomaetest/misc/openVIX.php', array('activityid' => $activityid, 'cmid' => $cmid));
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

            .toma-sub-container {
                align-self: stretch;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 20px;
            }

            .toma-icon-wrapper {
                margin: 0 auto;   
            }

            .toma-ready-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .toma-ready-title {
                text-align: center;
                color: #181D27;
                font-size: 20px;
                font-weight: 600;
                line-height: 30px;
            }

            .toma-ready-subtitle {
                text-align: center;
                color: #000000;
                font-size: 16px;
                font-weight: 400;
                line-height: 24px;
            }

            [dir='rtl'] .toma-steps-box {
                border-right: 6px solid #1570EF;
            }

            .toma-steps-box {
                border-left: 6px solid #1570EF;
                align-self: stretch;
                padding: 20px 24px;
                background: #EFF8FF;
                border-radius: 6px;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                gap: 24px;
            }

            .toma-step-wrapper {
                display: flex;
                align-items: flex-start;
                gap: 16px;
            }

            .toma-step-number {
                display: flex;
                flex-shrink: 0;
                width: 24px;
                height: 24px;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border-radius: 20px;
                background: #1570EF;
                color: #FFF;
                text-align: center;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }
            
            .toma-step-content {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
                font-size: 16px;
                line-height: 24px;
            }

            .toma-step-title {
                color: #181D27;
                font-weight: 600;
            }

            .toma-step-subtitle {
                color: #000;
                font-weight: 400;
            }

            .toma-buttons-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .toma-primary-button {
                padding: 10px 16px;
                background: #1570EF;
                border: 2px solid #F5F5F5;
                border-radius: 8px;
                box-shadow: 0px 0px 0px 1px rgba(10, 13, 18, 0.18) inset, 0px -2px 0px 0px  rgba(10, 13, 18, 0.05) inset, 0px 1px 2px 0px rgba(10, 13, 18, 0.05);
                color: white;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
            }

            .toma-plain-button {
                color: #175CD3;
                font-size: 16px;
                font-weight: 600;
                line-height: 24px;
                background: transparent;
                border: none;
                cursor: pointer;
            }
        </style>
        
        <div class='toma-container'>
            <div class='toma-sub-container'>
                <div class='toma-icon-wrapper'>
                    <svg width='56' height='56' viewBox='0 0 56 56' fill='none' xmlns='http://www.w3.org/2000/svg'>
                        <path d='M0 28C0 12.536 12.536 0 28 0C43.464 0 56 12.536 56 28C56 43.464 43.464 56 28 56C12.536 56 0 43.464 0 28Z' fill='#D1E9FF'/>
                        <path d='M30.3332 16.6478V21.4667C30.3332 22.1201 30.3332 22.4468 30.4603 22.6964C30.5722 22.9159 30.7507 23.0944 30.9702 23.2063C31.2197 23.3334 31.5464 23.3334 32.1998 23.3334H37.0188M30.3332 33.8333H23.3332M32.6665 29.1667H23.3332M37.3332 25.6529V34.0667C37.3332 36.0268 37.3332 37.0069 36.9517 37.7556C36.6161 38.4142 36.0807 38.9496 35.4221 39.2852C34.6734 39.6667 33.6934 39.6667 31.7332 39.6667H24.2665C22.3063 39.6667 21.3262 39.6667 20.5775 39.2852C19.919 38.9496 19.3835 38.4142 19.048 37.7556C18.6665 37.0069 18.6665 36.0268 18.6665 34.0667V21.9333C18.6665 19.9731 18.6665 18.9931 19.048 18.2444C19.3835 17.5858 19.919 17.0504 20.5775 16.7148C21.3262 16.3333 22.3063 16.3333 24.2665 16.3333H28.0136C28.8696 16.3333 29.2977 16.3333 29.7005 16.43C30.0576 16.5158 30.399 16.6572 30.7122 16.8491C31.0654 17.0655 31.368 17.3682 31.9734 17.9735L35.693 21.6931C36.2983 22.2985 36.601 22.6011 36.8174 22.9543C37.0093 23.2675 37.1507 23.6089 37.2365 23.966C37.3332 24.3688 37.3332 24.7969 37.3332 25.6529Z' stroke='#1570EF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/>
                    </svg>
                </div>
                <div class='toma-ready-box'>
                    <span class='toma-ready-title'>".get_string('activityready', 'mod_tomaetest')."</span>
                    <span class='toma-ready-subtitle'>".get_string('activityreadystudentdescription', 'mod_tomaetest')."</span>
                </div>
            </div>
            <div class='toma-steps-box'>
                <div class='toma-step-wrapper'>
                    <div class='toma-step-number'>
                        1
                    </div>
                    <div class='toma-step-content'>
                        <span class='toma-step-title'>".get_string('activityreadystep1title', 'mod_tomaetest')."</span>
                        <span class='toma-step-subtitle'>".get_string('activityreadystep1subtitle', 'mod_tomaetest')."</span>
                    </div>
                </div>
                <div class='toma-step-wrapper'>
                    <div class='toma-step-number'>
                        2
                    </div>
                    <div class='toma-step-content'>
                        <span class='toma-step-title'>".get_string('activityreadystep2title', 'mod_tomaetest')."</span>
                        <span class='toma-step-subtitle'>".get_string('activityreadystep2subtitle', 'mod_tomaetest')."</span>
                    </div>
                </div>
                <div class='toma-step-wrapper'>
                    <div class='toma-step-number'>
                        3
                    </div>
                    <div class='toma-step-content'>
                        <span class='toma-step-title'>".get_string('activityreadystep3title', 'mod_tomaetest')."</span>
                        <span class='toma-step-subtitle'>".get_string('activityreadystep3subtitle', 'mod_tomaetest')."</span>
                    </div>
                </div>
            </div>
            <div class='toma-buttons-container'>
                <button class='toma-primary-button' onclick=\"window.open('$vixurl', '_blank')\">".get_string('openinvix', 'mod_tomaetest')."</button>
                <button class='toma-plain-button' onclick=\"window.open('https://setup.tomaetest.com/TomaETest/setup.html', '_blank')\">".get_string('downloadvix', 'mod_tomaetest')."</button>
            </div>
        </div>";
    }
}

echo $OUTPUT->footer();

