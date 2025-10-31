<?php
// This file is part of the Meetings Attendance plugin for Moodle - http://moodle.org/
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
 * Meetings Attendance Module - Activity Form
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_meetingsattendance_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Add standard intro elements
        $this->standard_intro_elements();

        // Platform selection (Teams or Zoom)
        $platform_options = array(
            'teams' => get_string('teams', 'mod_meetingsattendance'),
            'zoom' => get_string('zoom', 'mod_meetingsattendance')
        );
        $mform->addElement('select', 'platform', get_string('platform', 'mod_meetingsattendance'), $platform_options);
        $mform->setType('platform', PARAM_ALPHA);
        $mform->addRule('platform', null, 'required', null, 'client');
        $mform->addHelpButton('platform', 'platform', 'mod_meetingsattendance');
        $mform->setDefault('platform', 'teams');

        // Meeting URL
        $mform->addElement('text', 'meeting_url', get_string('meeting_url', 'mod_meetingsattendance'), array('size' => '64'));
        $mform->setType('meeting_url', PARAM_URL);
        $mform->addRule('meeting_url', null, 'required', null, 'client');
        $mform->addHelpButton('meeting_url', 'meeting_url', 'mod_meetingsattendance');

        // Meeting ID (platform-specific)
        $mform->addElement('text', 'meeting_id', get_string('meeting_id', 'mod_meetingsattendance'), array('size' => '64'));
        $mform->setType('meeting_id', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('meeting_id', 'meeting_id', 'mod_meetingsattendance');

        // Organizer email
        $mform->addElement('email', 'organizer_email', get_string('organizer_email', 'mod_meetingsattendance'));
        $mform->setType('organizer_email', PARAM_EMAIL);
        $mform->addRule('organizer_email', null, 'required', null, 'client');
        $mform->addHelpButton('organizer_email', 'organizer_email', 'mod_meetingsattendance');

        // Expected duration (in seconds)
        $mform->addElement('duration', 'expected_duration', get_string('expected_duration', 'mod_meetingsattendance'));
        $mform->setDefault('expected_duration', 3600);
        $mform->addHelpButton('expected_duration', 'expected_duration', 'mod_meetingsattendance');

        // Required attendance percentage
        $attendance_options = array();
        for ($i = 0; $i <= 100; $i += 5) {
            $attendance_options[$i] = $i . '%';
        }
        $mform->addElement('select', 'required_attendance', get_string('required_attendance', 'mod_meetingsattendance'), $attendance_options);
        $mform->setDefault('required_attendance', 75);
        $mform->addHelpButton('required_attendance', 'required_attendance', 'mod_meetingsattendance');

        // Completion settings
        $this->standard_completion_elements();

        // Add custom completion rule for attendance
        $mform->addElement('checkbox', 'completionattendance', get_string('completionattendance', 'mod_meetingsattendance'));
        $mform->addHelpButton('completionattendance', 'completionattendance', 'mod_meetingsattendance');

        // Standard course module elements
        $this->standard_coursemodule_elements();

        // Add form buttons
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate required attendance is between 0 and 100
        if (isset($data['required_attendance'])) {
            if ($data['required_attendance'] < 0 || $data['required_attendance'] > 100) {
                $errors['required_attendance'] = get_string('invalidpercentage', 'core');
            }
        }

        // Validate expected duration
        if (isset($data['expected_duration']) && $data['expected_duration'] <= 0) {
            $errors['expected_duration'] = get_string('invaliddata', 'core');
        }

        return $errors;
    }
}
