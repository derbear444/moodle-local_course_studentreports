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
 * @param global_navigation $navigation a global_navigation object
 * @return void
 */
function local_course_studentreports_extend_navigation($navigation) {
    // Add a link to the custom report in the course navigation for teachers
    global $PAGE, $USER, $COURSE;

    // If for some reason there is no user ID, just return.
    if (empty($USER->id)) {
        return;
    }

    // Check the current page context.  If the context is not of a course or module then we are in another area of Moodle and return void.
    $context = context::instance_by_id($PAGE->context->id);
    $isvalidcontext = ($context instanceof context_course || $context instanceof context_module) ? true : false;

    // If the context of a module then get the parent context.
    $coursecontext = $context;
    if ($context instanceof context_module) {
        $coursecontext = $context->get_course_context();
    }

    // Also checks to make sure the user is a teacher (has manageactivites capability).
    if (!($isvalidcontext && has_capability('moodle/course:manageactivities', $coursecontext))) {
        return;
    }

    $icon = new pix_icon('i/report', '');
    $linkName = "Student reports"; #get_string('nav_course_studentsreports', 'course_studentreports');
    $linkUrl = new moodle_url('/local/course_studentreports/course_studentreports.php', array('courseid' => $COURSE->id));

    $currentCourseNode = $navigation->find('currentcourse', $navigation::TYPE_ROOTNODE);
    if (isNodeNotEmpty($currentCourseNode)) {
        // we have a 'current course' node, add the link to it.
        $currentCourseNode->add($linkName, $linkUrl, navigation_node::TYPE_SETTING, $linkName, 'studentreports-currentcourse', $icon);
    }

    $myCoursesNode = $navigation->find('mycourses', $navigation::TYPE_ROOTNODE);
    if(isNodeNotEmpty($myCoursesNode)) {
        $currentCourseInMyCourses = $myCoursesNode->find($coursecontext->instanceid, navigation_node::TYPE_COURSE);
        if($currentCourseInMyCourses) {
            // we found the current course in 'my courses' node, add the link to it.
            $currentCourseInMyCourses->add($linkName, $linkUrl, navigation_node::TYPE_SETTING, $linkName, 'studentreports-mycourses', $icon);
        }
    }

    $coursesNode = $navigation->find('courses', $navigation::TYPE_ROOTNODE);
    if (isNodeNotEmpty($coursesNode)) {
        $currentCourseInCourses = $coursesNode->find($coursecontext->instanceid, navigation_node::TYPE_COURSE);
        if ($currentCourseInCourses) {
            // we found the current course in the 'courses' node, add the link to it.
            $currentCourseInCourses->add($linkName, $linkUrl, navigation_node::TYPE_SETTING, $linkName, 'studentreports-allcourses', $icon);
        }
    }
}
