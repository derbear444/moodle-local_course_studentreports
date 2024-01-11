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

    /**
     * Renderers the add users button.
     *
     * @param add_user_button $button
     * @return string XHTML
     */
    protected function render_add_user_button(add_user_button $button) {
        $attributes = array('type' => 'submit',
                'value'    => $button->label,
                'disabled' => $button->disabled ? 'disabled' : null,
                'title'    => $button->tooltip,
                'class'    => 'btn btn-primary');

        if ($button->actions) {
            $id = html_writer::random_id('single_button');
            $attributes['id'] = $id;
            foreach ($button->actions as $action) {
                $this->add_action_handler($action, $id);
            }
        }
        $button->initialise_js($this->page);

        // first the input element
        $output = html_writer::empty_tag('input', $attributes);

        // then hidden fields
        $params = $button->url->params();
        if ($button->method === 'post') {
            $params['sesskey'] = sesskey();
        }
        foreach ($params as $var => $val) {
            $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        // then div wrapper for xhtml strictness
        $output = html_writer::tag('div', $output);

        // now the form itself around it
        if ($button->method === 'get') {
            $url = $button->url->out_omit_querystring(true); // url without params, the anchor part allowed
        } else {
            $url = $button->url->out_omit_querystring();     // url without params, the anchor part not allowed
        }
        if ($url === '') {
            $url = '#'; // there has to be always some action
        }
        $attributes = array('method' => $button->method,
                'action' => $url,
                'id'     => $button->formid);
        $output = html_writer::tag('form', $output, $attributes);

        // and finally one more wrapper with class
        return html_writer::tag('div', $output, array('class' => $button->class));
    }
}
