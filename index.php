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
 * Plugin administration pages are defined here.
 *
 * @package     local_course_studentreports
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');

use local_course_studentreports\table;
use core\report_helper;
use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;

// Sets default page size.
$participantsperpage = intval(get_config('moodlecourse', 'participantsperpage'));
define('DEFAULT_PAGE_SIZE', (!empty($participantsperpage) ? $participantsperpage : 20));

// Parameters for the page
$courseid = required_param('courseid', PARAM_INT);
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.

// CSV format
$format = optional_param('format','',PARAM_ALPHA);
$csv = $format == 'csv';

// Sets the page url.
$url = new moodle_url('/local/course_studentreports/index.php', array(
        'courseid' => $courseid,
        'perpage' => $perpage));
$PAGE->set_url($url);
// Sets page layout to a report.
$PAGE->set_pagelayout('report');

// If a course is specified, list the users for that course.
if ($courseid != $SITE->id) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    require_login($course);

    // Set up renderer
    $output = $PAGE->get_renderer('local_course_studentreports');

    // No longer needed since we have the course
    unset($courseid);

    // Grabs the course context
    $context = context_course::instance($course->id);
    // Only allows someone able to manageactivities in the course to view this page
    require_capability('moodle/course:manageactivities', $context);

    // Sets page title and heading
    $PAGE->set_title($course->shortname. ': ' . get_string('nav_course_studentreports', 'local_course_studentreports'));
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();

    // Print selector dropdown.
    $navname = get_string('nav_course_studentreports', 'local_course_studentreports');
    report_helper::print_report_selector($navname);

    echo $OUTPUT->heading($course->fullname);

    $participanttable = new table\users("user-index-studentreports-{$course->id}");

    // Adds user button to navigation.
    $adduserurl = new moodle_url( '/local/course_studentreports/add.php', array('enrolid'=>1, 'id'=>$course->id));

    // Adds adduser JS script and creates Add User button
    $adduseroptions = (object) [
            'contextid' => $context->id,
    ];
    $PAGE->requires->js_call_amd('local_course_studentreports/adduser', 'init', [$adduseroptions]);
    $adduserbutton = new add_user_button($adduserurl, get_string('adduser', 'local_course_studentreports'), 'get');
    $adduserbuttonout = $output->render($adduserbutton);

    echo html_writer::div($adduserbuttonout, 'addusersbutton', [
            'data-region' => 'wrapper',
            'data-table-uniqueid' => $participanttable->uniqueid,
    ]);

    // Render the user filters.
    $userrenderer = $PAGE->get_renderer('core_user');
    echo $userrenderer->participants_filter($context, $participanttable->uniqueid);

    // Grabs 'student' role id
    $student_role_id = intval($DB->get_record('role', array('shortname' => 'student'), 'id', MUST_EXIST)->id);

    // Define the filters.
    $filterset = new \core_user\table\participants_filterset();
    // Selects only this course.
    $filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));
    // Only displays students.
    $filterset->add_filter(new integer_filter('roles', filter::JOINTYPE_DEFAULT, [$student_role_id]));

    echo '<div class="userlist">';

    // Do this so we can get the total number of rows.
    ob_start();
    $participanttable->set_filterset($filterset);
    $participanttable->out($perpage, true);
    $participanttablehtml = ob_get_contents();
    ob_end_clean();

    echo html_writer::start_tag('form', [
            'action' => 'action.php',
            'method' => 'post',
            'id' => 'studentreportsform',
            'data-course-id' => $course->id,
            'data-table-unique-id' => $participanttable->uniqueid,
    ]);
    echo '<div>';

    echo html_writer::tag(
            'p',
            get_string('course_studentreports_participantsfound', 'local_course_studentreports', $participanttable->totalrows),
            [
                    'data-region' => 'participant-count',
            ]
    );

    echo $participanttablehtml;

    echo '<br /><div class="buttons"><div class="form-inline">';

    // Defines selection for type of report generated.
    // Each option gets set with a unique index of its own name for use in the POST request (since the index is the only thing sent).
    $displaylist = array(
            get_string('coursegradeoption', 'local_course_studentreports') => get_string('coursegradeoption', 'local_course_studentreports'),
            get_string('lastattendanceoption', 'local_course_studentreports') => get_string('lastattendanceoption', 'local_course_studentreports'),
            get_string('daysmissedoption', 'local_course_studentreports') => get_string('daysmissedoption', 'local_course_studentreports')
    );
    $selectactionparams = array(
            'id' => 'formactionid',
            'class' => 'ml-2',
            'data-action' => 'toggle',
            'data-togglegroup' => 'participants-table',
            'data-toggle' => 'action',
            'disabled' => 'disabled',
            'multiple' => 'multiple'
    );
    $label = html_writer::tag('label', get_string('withselectedusers', 'local_course_studentreports'),
            ['for' => 'formactionid', 'class' => 'col-form-label d-inline']);
    $select = html_writer::select($displaylist, 'formaction[]', '', null, $selectactionparams);
    echo html_writer::tag('div', $label . $select);

    // Sends extra data along with the selection element to the POST
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';
    echo '<input type="hidden" name="id" value="' . $course->id . '" />';

    echo '</div></div></div>';

    // Need to re-generate the buttons to avoid having elements with duplicate ids on the page.
    $adduserbutton = new add_user_button($adduserurl, get_string('adduser', 'local_course_studentreports'));
    $adduserbuttonout = $output->render($adduserbutton);

    // Add buttons.
    $downloadstring = get_string('csvdownload', 'local_course_studentreports');
    $buttonclass = 'btn btn-primary ml-2';
    $downloadButton = html_writer::tag('button', $downloadstring, ['type' => 'submit', 'class' => $buttonclass]);

    echo $downloadButton;

    echo '</form>';

    // Writes add user button
    echo html_writer::div( $adduserbuttonout, 'addusersbutton d-flex justify-content-end', [
            'data-region' => 'wrapper',
            'data-table-uniqueid' => $participanttable->uniqueid,
    ]);

    echo '</div>';  // Userlist.
}

echo $OUTPUT->footer();
