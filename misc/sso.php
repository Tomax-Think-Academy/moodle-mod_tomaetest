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

require(__DIR__.'/../../../config.php');
defined('MOODLE_INTERNAL') || die();
require_login();
global $CFG, $USER;
require_once($CFG->dirroot . "/local/tomax/classes/TETConnection.php");


$userid = $USER->id;
$examid = isset($_GET["examid"]) ? $_GET["examid"] : null;
$courseid = isset($_GET["courseid"]) ? $_GET["courseid"] : null;
$location = isset($_GET["location"]) ? $_GET["location"] : null;

$result = tomaetest_connection::sso($userid, $examid, $courseid, $location);

if ($result === false) {
    // Check if admin privileges.
    echo "<script>alert('No Permission.')</script>";
    echo "<script>window.close();</script>";
    exit;
}

header("location: $result");
