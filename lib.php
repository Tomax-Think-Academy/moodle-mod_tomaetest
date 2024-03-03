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
 * Library of interface functions and constants.
 *
 * @package     mod_tomaetest
 * @copyright   2024 Tomax ltd <roy@tomax.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 require_once(__DIR__.'/classes/Utils.php');


/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function tomaetest_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_MOD_PURPOSE:
            return 'tomax';
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_tomaetest into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_tomaetest_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function tomaetest_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $res = tet_utils::create_tet_activity($moduleinstance->name, $moduleinstance->course);
    if (!isset($res["success"]) || !$res["success"]) {
        throw new moodle_exception('tetgeneralerror', 'mod_tomaetest', '', '', json_encode($res));
    }
    $moduleinstance->tet_id = $res["data"]["examID"];
    $moduleinstance->is_ready = 0;
    $moduleinstance->is_finished = 0;
    $moduleinstance->timecreated = time();
    $examlink = $res["data"]["examLink"];
    $moduleinstance->extradata = json_encode(["TETExamLink" => $examlink]);

    $id = $DB->insert_record('tomaetest', $moduleinstance);

    // TODORON: redirect doesn't work because pop-ups are blocked
    // $examid=$moduleinstance->tet_id;
    // $courseid=tet_utils::get_course_tet_id($moduleinstance->course);
    // $location='activity-settings';
    // $url = new moodle_url('/mod/tomaetest/misc/sso.php', array('examid' => $examid, 'courseid' => $courseid, 'location' => $location));
    // echo "
    //     <script>
    //         const link = document.createElement('a');
    //         link.setAttribute('href', '$url');
    //         link.setAttribute('target', '_blank');
    //         link.click();
    //     </script>
    // ";

    return $id;
}

/**
 * Updates an instance of the mod_tomaetest in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_tomaetest_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function tomaetest_update_instance($moduleinstance, $mform = null) {
    global $DB;
    
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $activity = tet_utils::get_etest_activity($moduleinstance->instance);
    if ($activity->name != $moduleinstance->name) {
        $res = tet_utils::create_tet_activity($moduleinstance->name, $moduleinstance->course, $activity->tet_id);
        if (!isset($res["success"]) || !$res["success"]) {
            throw new moodle_exception('tetgeneralerror', 'mod_tomaetest', '', '', json_encode($res));
        }
    }

    return $DB->update_record('tomaetest', $moduleinstance);
}

/**
 * Removes an instance of the mod_tomaetest from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function tomaetest_delete_instance($id) {
    global $DB;

    $activity = tet_utils::get_etest_activity($id);
    if (!$activity) {
        return false;
    }
    $res = tet_utils::delete_tet_activity($activity->tet_id);
    if (!isset($res["success"]) || !$res["success"]) {
        throw new moodle_exception('tetgeneralerror', 'mod_tomaetest', '', '', json_encode($res));
    }

    $DB->delete_records('tomaetest', array('id' => $id));

    return true;
}
