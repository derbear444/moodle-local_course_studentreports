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

defined('MOODLE_INTERNAL') || die();

/**
 * This function adds Student reports link to the course navigation block.
 * @param navigation_node $navigation a course navigation object
 * @param stdClass $course current course object
 * @param context_course $context current course context
 * @return void
 */
function local_course_studentreports_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    // Add a link to the custom report in the course navigation for teachers
    global $PAGE, $USER, $COURSE;

    // If for some reason there is no user ID, just return.
    if (empty($USER->id)) {
        return;
    }

    // Checks to make sure the user is a teacher (has manageactivites capability).
    if (!has_capability('moodle/course:manageactivities', $context, $USER)) {
        return;
    }

    // Find the node associated with the "Reports" page.
    $reportsNode = $navigation->find("coursereports", navigation_node::TYPE_CONTAINER);

    // If the "Reports" node is found, add the link as a child.
    if ($reportsNode) {
        $icon = new pix_icon('i/report', '');
        $linkName = get_string('nav_course_studentreports', 'local_course_studentreports');
        $linkUrl = new moodle_url('/local/course_studentreports/course_studentreports.php', array('courseid' => $course->id));

        // Add the link as a child to the "Reports" node.
        $reportsNode->add($linkName, $linkUrl, navigation_node::TYPE_CUSTOM, $linkName, 'studentreports-link', $icon);
    }
}
