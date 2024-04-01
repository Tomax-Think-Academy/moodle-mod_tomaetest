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

    $moduleinstance->id = $DB->insert_record('tomaetest', $moduleinstance);

    // Do the processing required after an add or an update.
    tomaetest_after_add_or_update($moduleinstance);

    return $moduleinstance->id;
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

    // Do the processing required after an add or an update.
    tomaetest_after_add_or_update($moduleinstance);

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

    tomaetest_grade_item_delete($activity);

    $DB->delete_records('tomaetest', array('id' => $id));

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $activityid id of activity
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none. These are raw grades. They should
 * be processed with tomaetest_format_grade for display.
 */
function tomaetest_get_user_grades($activityid, $userid = 0) {
    global $CFG, $DB;

    $params = [$activityid];
    $usertest = '';
    if ($userid) {
        $params[] = $userid;
        $usertest = 'AND u.id = ?';
    }
    return $DB->get_records_sql("
            SELECT
                u.id,
                u.id AS userid,
                tg.grade AS rawgrade,
                tg.timemodified AS dategraded

            FROM {user} u
            JOIN {tomaetest_grades} tg ON u.id = tg.userid

            WHERE tg.activity = ?
            $usertest
            GROUP BY u.id, tg.grade, tg.timemodified", $params);
}

/**
 * Round a grade to the correct number of decimal places, and format it for display.
 *
 * @param float $grade The grade to round.
 * @return string
 */
function tomaetest_format_grade($grade) {
    if (is_null($grade)) {
        return get_string('notyetgraded', 'mod_tomaetest');
    }
    return format_float($grade, 2);
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param stdClass $activity the activity settings.
 * @param int $userid specific user only, 0 means all users.
 * @param bool $nullifnone If a single user is specified and $nullifnone is true a grade item with a null rawgrade will be inserted
 */
function tomaetest_update_grades($activity, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if ($activity->grade == 0) {
        return tomaetest_grade_item_update($activity);

    } else if ($grades = tomaetest_get_user_grades($activity, $userid)) {
        return tomaetest_grade_item_update($activity, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        return tomaetest_grade_item_update($activity, $grade);

    } else {
        return tomaetest_grade_item_update($activity);
    }
}

/**
 * Create or update the grade item for given activity
 *
 * @category grade
 * @param stdClass $activity object
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function tomaetest_grade_item_update($activity, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = ['itemname' => $activity->name];

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = 100;
    // $params['grademax']  = $activity->grade;
    // TODORON: maybe add activity max grade value
    $params['grademin']  = 0;

    $params['hidden'] = 0;

    if (!$params['hidden']) {
        // we need to hide it if the activity is hidden from students.
        if (property_exists($activity, 'visible')) {
            // Saving the activity form, and cm not yet updated in the database.
            $params['hidden'] = !$activity->visible;
        } else {
            $cm = get_coursemodule_from_instance('activity', $activity->id);
            $params['hidden'] = !$cm->visible;
        }
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/tomaetest', $activity->course, 'mod', 'tomaetest', $activity->id, 0, $grades, $params);
}

/**
 * Delete grade item for given activity
 *
 * @category grade
 * @param stdClass $activity object
 * @return int
 */
function tomaetest_grade_item_delete($activity) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/tomaetest', $activity->course, 'mod', 'tomaetest', $activity->id, 0,
            null, ['deleted' => 1]);
}

/**
 * This function is called at the end of tomaetest_add_instance
 * and tomaetest_update_instance, to do the common processing.
 *
 * @param stdClass $activity the activity object.
 */
function tomaetest_after_add_or_update($activity) {

    // Update related grade item.
    tomaetest_grade_item_update($activity);
}
