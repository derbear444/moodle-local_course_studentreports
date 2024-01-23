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
 * Enrol users form.
 *
 * Simple form to search for users and add them using a manual enrolment to this course.
 *
 * @package enrol_manual
 * @copyright 2016 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class local_course_studentreports_add_users_form extends moodleform {

    /**
     * Form definition.
     * @return void
     */
    public function definition() {
        global $PAGE, $DB, $CFG;


        require_once($CFG->dirroot . '/enrol/locallib.php');

        $context = $this->_customdata->context;

        // Get the course and enrolment instance.
        $coursecontext = $context->get_course_context();
        $course = $DB->get_record('course', ['id' => $coursecontext->instanceid]);
        $manager = new course_enrolment_manager($PAGE, $course);

        $instance = null;
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'manual') {
                if ($instance === null) {
                    $instance = $tempinstance;
                    break;
                }
            }
        }

        $mform = $this->_form;
        $mform->setDisableShortforms();
        $mform->disable_form_change_checker();
        $periodmenu = enrol_get_period_list();
        // Work out the appropriate default settings.
        $defaultperiod = $instance->enrolperiod;
        if ($instance->enrolperiod > 0 && !isset($periodmenu[$instance->enrolperiod])) {
            $periodmenu[$instance->enrolperiod] = format_time($instance->enrolperiod);
        }
        if (empty($extendbase)) {
            if (!$extendbase = get_config('enrol_manual', 'enrolstart')) {
                // Default to now if there is no system setting.
                $extendbase = 4;
            }
        }

        // Build the list of options for the starting from dropdown.
        $now = time();
        $today = make_timestamp(date('Y', $now), date('m', $now), date('d', $now), 0, 0, 0);
        $dateformat = get_string('strftimedatefullshort');

        // Adds student select
        $mform->addElement('header', 'main', get_string('studentselect', 'local_course_studentreports'));
        $options = array(
                'ajax' => 'enrol_manual/form-potential-user-selector',
                'multiple' => true,
                'courseid' => $course->id,
                'enrolid' => $instance->id,
                'perpage' => $CFG->maxusersperpage,
                'userfields' => implode(',', \core_user\fields::get_identity_fields($context, true))
        );
        $mform->addElement('autocomplete', 'userlist', get_string('selectusers', 'enrol_manual'), array(), $options);
    }

    /**
     * Validate the submitted form data.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['startdate']) && !empty($data['timeend'])) {
            if ($data['startdate'] >= $data['timeend']) {
                $errors['timeend'] = get_string('enroltimeendinvalid', 'enrol');
            }
        }
        return $errors;
    }
}