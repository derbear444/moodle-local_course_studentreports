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
 * Defines actions for buttons in plugin.
 *
 * @package     local_course_studentreports
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/attendance/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Check if data is submitted
if ($data = data_submitted()) {
    // Grabs the courseid and returns automatically if there is not one
    if ($courseid = $data->id) {
        // Grabs report options requested
        $reports = $data->formaction;

        // Userid hack from user/action_redir.php
        $userids = array();
        foreach ($data as $k => $v) {
            if (preg_match('/^user(\d+)$/', $k, $m)) {
                $userids[] = $m[1];
            }
        }

        // If there are no userids or reports still, exit early
        if (!($userids && $reports)) {
            // Returns to previous page
            redirect($data->returnto);
        }

        // Grabs course information
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $context = context_course::instance($courseid);
        $PAGE->set_context($context);

        // Gets user information from userids.
        // Note that this can grab any user, regardless of context, but will just get blank entries later in the report if not part of the course.
        $users = user_get_users_by_id($userids);

        // Sets up csv export
        $csv_writer = new csv_export_writer();
        $shortname = format_string($course->shortname, true, array('context' => $context));
        $downloadfilename = clean_filename(get_string('csvfilename', 'local_course_studentreports', array('shortname' => $shortname, 'time' => time())));
        $csv_writer->set_filename($downloadfilename);

        // Adds student info titles
        $csvtitles = array(
                get_string('studentnametitle', 'local_course_studentreports'),
                get_string('studentemailtitle', 'local_course_studentreports')
        );
        // Adds titles for reports needing to be generated
        $csvtitles = array_merge($csvtitles, $reports);
        $csv_writer->add_data($csvtitles);

        // Defines preliminary records needed later when generated the report for each user.

        // Attempts to get attendance summary information
        $attendance_instance = $DB->get_record('attendance', array('course' => $courseid), 'id');
        // If the course has an attendance module, grab the rest of the information.
        // If it doesn't have the module, there's no point in getting the rest of the info.
        if ($attendance_instance) {
            // Generates summary for attendance module
            $summary = new mod_attendance_summary($attendance_instance->id, $userids);
            // Grabs the needed attendance sessions for the course
            $attendance_sessions = array_reverse($DB->get_records('attendance_sessions', array('attendanceid' => $attendance_instance->id),'sessdate', 'id,sessdate'));
            // Grabs the id for the 'Present' and 'Late' statuses in the course
            $attendance_status_ids = array(
                    $DB->get_record('attendance_statuses', array('attendanceid' => $attendance_instance->id, 'description' => 'Present'), 'id')->id,
                    $DB->get_record('attendance_statuses', array('attendanceid' => $attendance_instance->id, 'description' => 'Late'), 'id')->id
            );
        }
        // Gets grade information
        $course_final_grades = $DB->get_record('grade_items', array('courseid' => $courseid, 'itemtype' => 'course'), 'id');

        // Loops through the users for csv generation
        foreach ($users as $user) {
            // Adds student info to csv string
            $csvdata = array("$user->firstname $user->lastname", $user->email);

            // Attempts to add 'Course grade' column if requested
            if (in_array(get_string('coursegradeoption', 'local_course_studentreports'), $csvtitles)) {
                $user_final_grade = get_string('nodata', 'local_course_studentreports');
                // Double-checks that the grade_items record exists
                if ($course_final_grades) {
                    // Gets the final grade for the user
                    $user_final_grade = $DB->get_record('grade_grades', array('itemid' => $course_final_grades->id, 'userid' => $user->id), 'finalgrade')->finalgrade;
                }
                // Adds csv column
                $csvdata[] = $user_final_grade;
            }

            // Attempts to add 'Last attendance' column if requested
            if (in_array(get_string('lastattendanceoption', 'local_course_studentreports'), $csvtitles)) {
                $last_present_date = get_string('nodata', 'local_course_studentreports');
                // Double-checks to make sure the attendance module exists
                if ($attendance_instance) {
                    // Loops over the sessions from most recent to earlier and stops when it finds a present record
                    foreach ($attendance_sessions as $session) {
                        // Grabs logs for current attendance session
                        $attendance_log = $DB->get_record('attendance_log', array('sessionid' => $session->id, 'studentid' => $user->id), 'statusid');
                        if ($attendance_log && in_array($attendance_log->statusid, $attendance_status_ids)) {
                            // Uses userdate function to format the session date
                            $last_present_date = userdate($session->sessdate, get_string('strftime', 'local_course_studentreports'));
                            break;
                        }
                    }
                }
                // Adds csv column
                $csvdata[] = $last_present_date;
            }

            // Attempts to add 'Days missed' column if requested
            if (in_array(get_string('daysmissedoption', 'local_course_studentreports'), $csvtitles)) {
                $missed_days = get_string('nodata', 'local_course_studentreports');
                // Double-checks to make sure the attendance module exists
                if ($attendance_instance) {
                    // Grabs the attendance summary for the specified user
                    $attendance_summary = $summary->get_all_sessions_summary_for($user->id);
                    // Retrieves the number of absences reported by mod_attendance and defaults to 0 if null
                    $missed_days = $attendance_summary->userstakensessionsbyacronym[0]['A'] ?? '0';
                }
                // Adds csv column
                $csvdata[] = $missed_days;
            }

            // Adds row of data to csv
            $csv_writer->add_data($csvdata);
        }

        // Downloads csv file
        $csv_writer->download_file();
    }
    // Returns to previous page
    redirect($data->returnto);
} else {
    // Throw error if accessed any other way (GET, etc.)
    throw new moodle_exception('nopermissions', 'local_course_studentreports', '', get_string('actionerror', 'local_course_studentreports'));
}