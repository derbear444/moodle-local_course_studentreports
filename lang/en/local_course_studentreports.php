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
 * Plugin strings are defined here.
 *
 * @package     local_course_studentreports
 * @category    string
 * @copyright   2023 Derek Wilson <wilsondc5@appstate.edu>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// Index strings.
$string['pluginname'] = 'Course Student Reports';
$string['nav_course_studentreports'] = 'Student reports';
$string['course_studentreports_courseheading'] = 'Student reports: {$a}';
$string['course_studentreports_participantsfound'] = '{$a} total users found';
$string['withselectedusers'] = 'Select reports to generate with selected users...';
$string['csvdownload'] = 'Download .csv';
$string['adduser'] = 'Add students';
$string['strftime'] = "%m/%d/%Y";
// Name of reports.
$string['coursegradeoption'] = 'Course grade';
$string['lastattendanceoption'] = 'Last date of attendance';
$string['daysmissedoption'] = 'Number of days missed';
// Action strings.
$string['studentnametitle'] = 'First name/Last name';
$string['studentemailtitle'] = 'Email address';
$string['nodata'] = 'No Data';
$string['csvfilename'] = 'studentreports.{$a->shortname}.{$a->time}';
// Error strings.
$string['actionerror'] = 'You do not have permission to view this page.';
// Modal strings.
$string['studentselect'] = 'Student selection';
$string['totalusers'] = '{$a} user(s) added';
// Cache strings.
$string['cachedef_userids'] = 'Stores userids from the user selector to add to the dynamic table.';
