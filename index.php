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
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;

// Sets default page size.
$participantsperpage = intval(get_config('moodlecourse', 'participantsperpage'));
define('DEFAULT_PAGE_SIZE', (!empty($participantsperpage) ? $participantsperpage : 20));

const STUDENT_ROLE_ID = 20;

// Parameters for the page
$courseid = required_param('courseid', PARAM_INT);
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.

// Sets the page url.
$PAGE->set_url('/local/course_studentreports/index.php', array(
        'courseid' => $courseid,
        'perpage' => $perpage));
// Sets page layout to a report.
$PAGE->set_pagelayout('report');

// If a course is specified, list the users for that course. Otherwise, display a list of courses to generate a report for.
if ($courseid != $SITE->id) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    require_login($course);

    // No longer needed since we have the course
    unset($courseid);

    // Grabs the course context
    $context = context_course::instance($course->id);
    // Only allows someone able to manageactivities in the course to view this page
    require_capability('moodle/course:manageactivities', $context);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('course_studentreports_courseheading', 'local_course_studentreports', $course->fullname));

    $participanttable = new \core_user\table\participants("user-index-studentreports-{$course->id}");

    // Render the user filters.
    $userrenderer = $PAGE->get_renderer('core_user');
    echo $userrenderer->participants_filter($context, $participanttable->uniqueid);

    // Define the filters.
    $filterset = new \core_user\table\participants_filterset();
    // Selects only this course.
    $filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));
    // Only displays students.
    $filterset->add_filter(new integer_filter('roles', filter::JOINTYPE_DEFAULT, [STUDENT_ROLE_ID]));

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
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';

    echo html_writer::tag(
            'p',
            get_string('course_studentreports_participantsfound', 'local_course_studentreports', $participanttable->totalrows),
            [
                    'data-region' => 'participant-count',
            ]
    );

    echo $participanttablehtml;

    $bulkoptions = (object) [
            'uniqueid' => $participanttable->uniqueid,
    ];

    echo '<br /><div class="buttons"><div class="form-inline">';

    // Defines selection for type of report generated.
    $displaylist = array("Course grade", "Number of days missed", "Last date of attendance");
    $selectactionparams = array(
            'id' => 'formactionid',
            'class' => 'ml-2',
            'data-action' => 'toggle',
            'data-togglegroup' => 'participants-table',
            'data-toggle' => 'action',
            'disabled' => 'disabled',
            'multiple' => 'multiple'
    );
    $label = html_writer::tag('label', get_string("withselectedusers"),
            ['for' => 'formactionid', 'class' => 'col-form-label d-inline']);
    $select = html_writer::select($displaylist, 'formaction', '', null, $selectactionparams);
    echo html_writer::tag('div', $label . $select);

    echo '<input type="hidden" name="id" value="' . $course->id . '" />';
    echo '<div class="d-none" data-region="state-help-icon">' . $OUTPUT->help_icon('publishstate', 'notes') . '</div>';

    echo '</div></div></div>';

    $bulkoptions->noteStateNames = note_get_state_names();

    echo '</form>';

    echo '</div>';  // Userlist.
}

echo $OUTPUT->footer();
