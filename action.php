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
 * Processes downloading the requested report to the requesting user's machine.
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

// Check if data is submitted.
if ($data = data_submitted()) {
    // Grabs the courseid and returns automatically if there is not one.
    if ($courseid = $data->id) {
        // Ensure login.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        require_login($course);

        // Grabs report options requested.
        $reports = $data->formaction;

        // Userid hack from user/action_redir.php.
        $userids = [];
        foreach ($data as $k => $v) {
            if (preg_match('/^user(\d+)$/', $k, $m)) {
                $userids[] = $m[1];
            }
        }

        // If there are no userids or reports still, exit early.
        if (!($userids && $reports)) {
            // Returns to previous page.
            redirect($data->returnto);
        }

        // Grabs course information.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = context_course::instance($courseid);
        $PAGE->set_context($context);

        // Gets user information from userids.
        // Note that this can grab any user, regardless of context, but will just get blank entries later in the report
        // if not part of the course.
        $users = user_get_users_by_id($userids);

        // Sets up csv export.
        $csvwriter = new csv_export_writer();
        $shortname = format_string($course->shortname, true, ['context' => $context]);
        $downloadfilename = clean_filename(get_string('csvfilename', 'local_course_studentreports',
            ['shortname' => $shortname, 'time' => time()]));
        $csvwriter->set_filename($downloadfilename);

        // Adds student info titles.
        $csvtitles = [
                get_string('studentnametitle', 'local_course_studentreports'),
                get_string('studentemailtitle', 'local_course_studentreports'),
        ];
        // Adds titles for reports needing to be generated.
        $csvtitles = array_merge($csvtitles, $reports);
        $csvwriter->add_data($csvtitles);

        // Defines preliminary records needed later when generated the report for each user.

        // Attempts to get attendance summary information.
        $attendanceinstance = $DB->get_record('attendance', ['course' => $courseid], 'id');
        // If the course has an attendance module, grab the rest of the information.
        // If it doesn't have the module, there's no point in getting the rest of the info.
        if ($attendanceinstance) {
            // Generates summary for attendance module.
            $summary = new mod_attendance_summary($attendanceinstance->id, $userids);
            // Grabs the needed attendance sessions for the course.
            $attendancesessions = array_reverse($DB->get_records('attendance_sessions',
                ['attendanceid' => $attendanceinstance->id], 'sessdate', 'id,sessdate'));
            // Grabs the id for the 'Present' and 'Late' statuses in the course.
            $attendancestatusids = [
                    $DB->get_record('attendance_statuses',
                        ['attendanceid' => $attendanceinstance->id, 'description' => 'Present'], 'id')->id,
                    $DB->get_record('attendance_statuses',
                        ['attendanceid' => $attendanceinstance->id, 'description' => 'Late'], 'id')->id,
            ];
        }
        // Gets grade information.
        $coursefinalgrades = $DB->get_record('grade_items', ['courseid' => $courseid, 'itemtype' => 'course'], 'id');

        // Loops through the users for csv generation.
        foreach ($users as $user) {
            // Adds student info to csv string.
            $csvdata = ["$user->firstname $user->lastname", $user->email];

            // Attempts to add 'Course grade' column if requested.
            if (in_array(get_string('coursegradeoption', 'local_course_studentreports'), $csvtitles)) {
                $userfinalgrade = get_string('nodata', 'local_course_studentreports');
                // Double-checks that the grade_items record exists.
                if ($coursefinalgrades) {
                    // Gets the final grade for the user by grabbing all aggregation records for the user in the course,
                    // and grabbing the one most recently modified.
                    $userfinalgrade = array_reverse($DB->get_records('grade_grades_history',
                        [
                            'itemid' => $coursefinalgrades->id,
                            'userid' => $user->id,
                            'source' => 'aggregation',
                        ],
                        'timemodified')
                    );
                    // Gets the final grade if it exists in the gradebook.
                    if ($userfinalgrade) {
                        $userfinalgrade = $userfinalgrade[0]->finalgrade;
                    } else {
                        $userfinalgrade = 'No Data';
                    }
                }
                // Adds csv column.
                $csvdata[] = $userfinalgrade;
            }

            // Attempts to add 'Last attendance' column if requested.
            if (in_array(get_string('lastattendanceoption', 'local_course_studentreports'), $csvtitles)) {
                $lastpresentdate = get_string('nodata', 'local_course_studentreports');
                // Double-checks to make sure the attendance module exists.
                if ($attendanceinstance) {
                    // Loops over the sessions from most recent to earlier and stops when it finds a present record.
                    foreach ($attendancesessions as $session) {
                        // Grabs logs for current attendance session.
                        $attendancelog = $DB->get_record('attendance_log',
                            ['sessionid' => $session->id, 'studentid' => $user->id], 'statusid');
                        if ($attendancelog && in_array($attendancelog->statusid, $attendancestatusids)) {
                            // Uses userdate function to format the session date.
                            $lastpresentdate = userdate($session->sessdate,
                                get_string('strftime', 'local_course_studentreports'));
                            break;
                        }
                    }
                }
                // Adds csv column.
                $csvdata[] = $lastpresentdate;
            }

            // Attempts to add 'Days missed' column if requested.
            if (in_array(get_string('daysmissedoption', 'local_course_studentreports'), $csvtitles)) {
                $misseddays = get_string('nodata', 'local_course_studentreports');
                // Double-checks to make sure the attendance module exists.
                if ($attendanceinstance) {
                    // Grabs the attendance summary for the specified user.
                    $attendancesummary = $summary->get_all_sessions_summary_for($user->id);
                    // Retrieves the number of absences reported by mod_attendance and defaults to 0 if null.
                    $misseddays = $attendancesummary->userstakensessionsbyacronym[0]['A'] ?? '0';
                }
                // Adds csv column.
                $csvdata[] = $misseddays;
            }

            // Adds row of data to csv.
            $csvwriter->add_data($csvdata);
        }

        // Downloads csv file.
        $csvwriter->download_file();
    }
    // Returns to previous page.
    redirect($data->returnto);
} else {
    // Throw error if accessed any other way (GET, etc.).
    throw new moodle_exception('nopermissions', 'local_course_studentreports',
        '', get_string('actionerror', 'local_course_studentreports'));
}
