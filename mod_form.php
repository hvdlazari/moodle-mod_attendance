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
 * Forms for updating/adding attendance
 *
 * @package    mod_attendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * class for displaying add/update form.
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_attendance_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        $attendanceconfig = get_config('attendance');
        if (!isset($attendanceconfig->subnet)) {
            $attendanceconfig->subnet = '';
        }
        $mform    =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setDefault('name', get_string('modulename', 'attendance'));

        $this->standard_intro_elements();
        
        $mform->addElement('duration', 'duration', get_string('duration', 'attendance'));

        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements(true);

        // IP address.
        if (get_config('attendance', 'subnetactivitylevel')) {
            $mform->addElement('header', 'security', get_string('extrarestrictions', 'attendance'));
            $mform->addElement('text', 'subnet', get_string('defaultsubnet', 'attendance'), array('size' => '164'));
            $mform->setType('subnet', PARAM_TEXT);
            $mform->addHelpButton('subnet', 'defaultsubnet', 'attendance');
            $mform->setDefault('subnet', $attendanceconfig->subnet);
        } else {
            $mform->addElement('hidden', 'subnet', '');
            $mform->setType('subnet', PARAM_TEXT);
        }

        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        if (empty($this->_instance) || !empty($default_values['completionattendance'])) {
            $default_values['completionattendanceenabled'] = 1;
        } else {
            $default_values['completionattendanceenabled'] = 0;
        }
        if (empty($default_values['completionattendance'])) {
            $default_values['completionattendance'] = 1;
        }
    }

    public function add_completion_rules() {
        $mform = &$this->_form;

        $group = array();
        $group[] = &$mform->createElement('checkbox', 'completionattendanceenabled', '', get_string('completionattendance', 'attendance'));
        $group[] = &$mform->createElement('text', 'completionattendance', '', array('size' => 3));
        $mform->setType('completionattendance', PARAM_INT);
        $mform->addGroup($group, 'completionattendancegroup', get_string('completionattendancegroup', 'attendance'), array(' '), false);
        $mform->disabledIf('completionattendance', 'completionattendanceenabled', 'notchecked');

        return array('completionattendancegroup');
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completionattendanceenabled']) && $data['completionattendance'] != 0);
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        // Turn off completion settings if the checkboxes aren't ticked
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionattendanceenabled) || !$autocompletion) {
                $data->completionattendance = 0;
            }
        }
    }
}