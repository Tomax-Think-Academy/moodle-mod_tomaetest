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
require_login();
require_once(__DIR__.'/../classes/Utils.php');
require_once($CFG->dirroot . "/local/tomax/classes/Utils.php");
require_once($CFG->dirroot . "/local/tomax/classes/TETConnection.php");

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}


$activityid = isset($_GET["activityid"]) ? $_GET["activityid"] : false;
$cmid = isset($_GET["cmid"]) ? $_GET["cmid"] : false;

if ($activityid === false) {
    echo 'window.close()';
}

$activity = tet_utils::get_etest_activity($activityid);
$code = $activity->extradata["TETExamLink"];

$domain = tomax_utils::$config->domain;

if (!$cmid) {
    $cmid = tet_utils::get_cmid($activityid);
}
$context = context_module::instance($cmid);
if (has_capability("mod/tomaetest:attempt", $context)) {

    if ($domain == "tomaxdev") {
        $code = 'dev-' . $code;
    } else if ($domain == "tomaxtst") {
        $code = 'tst-' . $code;
    }

    $externalid = tomax_utils::get_external_id_for_participant($USER);
    $participant = tomaetest_connection::tet_post_request("participant/getByUserName/view", ["UserName" => $externalid]);
    if ($participant["success"]) {
        $tokenrequest = tomaetest_connection::tet_post_request(
            "exam/thirdPartySSOMoodle/view",
            ["examID" => $activity->tet_id, "parID" => $participant["data"]]
        );
        if ($tokenrequest["success"]) {
            $token = $tokenrequest["data"]["token"];
            $parid = $tokenrequest["data"]["parID"];
            $url = "vix://?examCode=$code&token=$token&parID=$parid";
            header("location: $url");
            exit;
        }
    }
}
echo "<script>alert('You are not a student of this exam.');window.close();</script>";
