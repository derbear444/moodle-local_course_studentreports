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
 * This file processes AJAX enrolment actions and returns JSON for the course studentreports plugin.
 *
 * The general idea behind this file is that any errors should throw exceptions
 * which will be returned and acted upon by the calling AJAX script.
 *
 * @package     local_course_studentreports
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/enrol/manual/locallib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot . '/enrol/manual/classes/enrol_users_form.php');

$id      = required_param('id', PARAM_INT); // Course id.
$action  = required_param('action', PARAM_ALPHANUMEXT);

$PAGE->set_url(new moodle_url('/local/course_studentreports/ajax.php', array('id'=>$id, 'action'=>$action)));

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id);

if ($course->id == SITEID) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);
require_sesskey();

echo $OUTPUT->header(); // Send headers.

$manager = new course_enrolment_manager($PAGE, $course);

$outcome = new stdClass();
$outcome->success = true;
$outcome->response = new stdClass();
$outcome->error = '';
$outcome->count = 0;

$searchanywhere = get_user_preferences('userselector_searchtype') === USER_SEARCH_CONTAINS;

switch ($action) {
    case 'add':
        $enrolid = required_param('enrolid', PARAM_INT);
        $cohorts = $users = [];

        $userids = optional_param_array('userlist', [], PARAM_SEQUENCE);
        $userid = optional_param('userid', 0, PARAM_INT);
        if ($userid) {
            $userids[] = $userid;
        }
        if ($userids) {
            foreach ($userids as $userid) {
                $users[] = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
                $outcome->count++;
            }
        }

        // Adds necessary user data to the cache for the dynamic table.
        $cache = cache::make('local_course_studentreports', 'users');
        $cache->set('users', $users);

//        $roleid = intval($DB->get_record('role', array('shortname' => 'student'), 'id', MUST_EXIST)->id);
//        $duration = optional_param('duration', 0, PARAM_INT);
//        $startdate = optional_param('startdate', 0, PARAM_INT);
//        $recovergrades = optional_param('recovergrades', 0, PARAM_INT);
//        $timeend = optional_param_array('timeend', [], PARAM_INT);
//
//        if (empty($roleid)) {
//            $roleid = null;
//        } else {
//            if (!array_key_exists($roleid, get_assignable_roles($context, ROLENAME_ALIAS, false))) {
//                throw new enrol_ajax_exception('invalidrole');
//            }
//        }
//
//        if (empty($startdate)) {
//            if (!$startdate = get_config('enrol_manual', 'enrolstart')) {
//                // Default to now if there is no system setting.
//                $startdate = 4;
//            }
//        }
//
//        switch($startdate) {
//            case 2:
//                $timestart = $course->startdate;
//                break;
//            case 4:
//                // We mimic get_enrolled_sql round(time(), -2) but always floor as we want users to always access their
//                // courses once they are enrolled.
//                $timestart = intval(substr(time(), 0, 8) . '00') - 1;
//                break;
//            case 3:
//            default:
//                $today = time();
//                $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
//                $timestart = $today;
//                break;
//        }
//        if ($timeend) {
//            $timeend = make_timestamp($timeend['year'], $timeend['month'], $timeend['day'], $timeend['hour'], $timeend['minute']);
//        } else if ($duration <= 0) {
//            $timeend = 0;
//        } else {
//            $timeend = $timestart + $duration;
//        }

        $outcome->success = true;
        break;

    default:
        throw new enrol_ajax_exception('unknowajaxaction');
}

echo json_encode($outcome);
