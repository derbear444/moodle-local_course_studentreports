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
 * Main renderer for plugin.
 *
 * @package     local_course_studentreports
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The core renderer
 *
 * @package     local_course_studentreports
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_course_studentreports_renderer extends plugin_renderer_base {

    /**
     * Render download buttons.
     *
     * @param \moodle_url $url The base url.
     * @return string HTML
     * @throws \coding_exception
     */
    public function render_download_buttons(\moodle_url $url): string {
        $downloadurl = fullclone($url);
        $downloadurl->remove_params(['page']);
        $downloadurl->param('format', 'csv');

        // Create a form with hidden inputs for parameters
        $form = html_writer::start_tag('form', ['method' => 'post', 'action' => $downloadurl]);
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'format', 'value' => 'csv']);
        $form .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('csvdownload', 'local_course_studentreports')]);
        $form .= html_writer::end_tag('form');

        $downloadhtml = html_writer::tag('div', $form);

        return $downloadhtml;
    }
}
