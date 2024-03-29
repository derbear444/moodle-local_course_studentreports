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
 * This file contains the button classes used in the plugin.
 *
 * @package     local_course_studentreports
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_studentreports\button;

use moodle_page;
use single_button;
use moodle_url;
use stdClass;

/**
 * A button that is used to add a user to the participants table.
 *
 * @author      2023 Derek Wilson <wilsondc5@appstate.edu>
 * @copyright   (c) 2023 Appalachian State University, Boone, NC
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_user_button extends single_button {

    /**
     * An array containing JS YUI modules required by this button
     * @var array
     */
    protected $jsyuimodules = [];

    /**
     * An array containing JS initialisation calls required by this button
     * @var array
     */
    protected $jsinitcalls = [];

    /**
     * An array strings required by JS for this button
     * @var array
     */
    protected $jsstrings = [];

    /**
     * Initialises the new add_user_button
     *
     * @param moodle_url $url
     * @param string $label The text to display in the button
     * @param string $method Either post or get
     */
    public function __construct(moodle_url $url, $label, $method = 'post') {
        static $count = 0;
        $count ++;
        parent::__construct($url, $label, $method);
        $this->class = 'singlebutton addusersbutton';
        $this->formid = 'addusersbutton-'.$count;
    }

    /**
     * Adds a YUI module call that will be added to the page when the button is used.
     *
     * @param string|array $modules One or more modules to require
     * @param string $function The JS function to call
     * @param array $arguments An array of arguments to pass to the function
     * @param string $galleryversion Deprecated: The gallery version to use
     * @param bool $ondomready If true the call is postponed until the DOM is finished loading
     */
    public function require_yui_module($modules, $function, array $arguments = null, $galleryversion = null, $ondomready = false) {
        if ($galleryversion != null) {
            debugging('The galleryversion parameter to yui_module has been deprecated since Moodle 2.3.', DEBUG_DEVELOPER);
        }

        $js = new stdClass;
        $js->modules = (array)$modules;
        $js->function = $function;
        $js->arguments = $arguments;
        $js->ondomready = $ondomready;
        $this->jsyuimodules[] = $js;
    }

    /**
     * Adds a JS initialisation call to the page when the button is used.
     *
     * @param string $function The function to call
     * @param array $extraarguments An array of arguments to pass to the function
     * @param bool $ondomready If true the call is postponed until the DOM is finished loading
     * @param array $module A module definition
     */
    public function require_js_init_call($function, array $extraarguments = null, $ondomready = false, array $module = null) {
        $js = new stdClass;
        $js->function = $function;
        $js->extraarguments = $extraarguments;
        $js->ondomready = $ondomready;
        $js->module = $module;
        $this->jsinitcalls[] = $js;
    }

    /**
     * Requires strings for JS that will be loaded when the button is used.
     *
     * @param type $identifiers
     * @param string $component
     * @param mixed $a
     */
    public function strings_for_js(type $identifiers, string $component = 'moodle', mixed $a = null) {
        $string = new stdClass;
        $string->identifiers = (array)$identifiers;
        $string->component = $component;
        $string->a = $a;
        $this->jsstrings[] = $string;
    }

    /**
     * Initialises the JS that is required by this button
     *
     * @param moodle_page $page
     */
    public function initialise_js(moodle_page $page) {
        foreach ($this->jsyuimodules as $js) {
            $page->requires->yui_module($js->modules, $js->function, $js->arguments, null, $js->ondomready);
        }
        foreach ($this->jsinitcalls as $js) {
            $page->requires->js_init_call($js->function, $js->extraarguments, $js->ondomready, $js->module);
        }
        foreach ($this->jsstrings as $string) {
            $page->requires->strings_for_js($string->identifiers, $string->component, $string->a);
        }
    }
}
