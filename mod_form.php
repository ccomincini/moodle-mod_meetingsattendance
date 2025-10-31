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
        global $CFG, $PAGE;

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

        // Meeting schedule header
        $mform->addElement('header', 'meeting_schedule', get_string('meeting_schedule', 'mod_meetingsattendance'));

        // Start date/time of meeting
        $mform->addElement('date_time_selector', 'start_datetime', get_string('start_datetime', 'mod_meetingsattendance'));
        $mform->addHelpButton('start_datetime', 'start_datetime', 'mod_meetingsattendance');
        $mform->setDefault('start_datetime', time());

        // End date/time of meeting
        $mform->addElement('date_time_selector', 'end_datetime', get_string('end_datetime', 'mod_meetingsattendance'));
        $mform->addHelpButton('end_datetime', 'end_datetime', 'mod_meetingsattendance');
        $mform->setDefault('end_datetime', time() + 3600);

        // Expected duration (calculated, displayed in minutes)
        $mform->addElement('text', 'expected_duration_display', get_string('expected_duration', 'mod_meetingsattendance'), array('size' => '10', 'readonly' => 'readonly'));
        $mform->setType('expected_duration_display', PARAM_TEXT);
        $mform->setDefault('expected_duration_display', '60 minutes');
        $mform->addHelpButton('expected_duration_display', 'expected_duration', 'mod_meetingsattendance');

        // Hidden field for actual duration in seconds
        $mform->addElement('hidden', 'expected_duration');
        $mform->setType('expected_duration', PARAM_INT);
        $mform->setDefault('expected_duration', 3600);

        // Add JavaScript to calculate duration
        $this->add_duration_calculation_js($PAGE);

        // Completion settings header
        $mform->addElement('header', 'completion_settings', get_string('completion_settings', 'mod_meetingsattendance'));

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

    /**
     * Add JavaScript for automatic duration calculation
     *
     * @param moodle_page $page
     */
    private function add_duration_calculation_js($page) {
        $js = <<<'JS'
require(['jquery'], function($) {
    $(document).ready(function() {
        function calculateDuration() {
            var startFields = {
                day: $('select[name="start_datetime[day]"]').val(),
                month: $('select[name="start_datetime[month]"]').val(),
                year: $('select[name="start_datetime[year]"]').val(),
                hour: $('select[name="start_datetime[hour]"]').val(),
                minute: $('select[name="start_datetime[minute]"]').val()
            };

            var endFields = {
                day: $('select[name="end_datetime[day]"]').val(),
                month: $('select[name="end_datetime[month]"]').val(),
                year: $('select[name="end_datetime[year]"]').val(),
                hour: $('select[name="end_datetime[hour]"]').val(),
                minute: $('select[name="end_datetime[minute]"]').val()
            };

            if (!startFields.day || !endFields.day) {
                return;
            }

            var startDate = new Date(
                parseInt(startFields.year),
                parseInt(startFields.month) - 1,
                parseInt(startFields.day),
                parseInt(startFields.hour) || 0,
                parseInt(startFields.minute) || 0,
                0
            );

            var endDate = new Date(
                parseInt(endFields.year),
                parseInt(endFields.month) - 1,
                parseInt(endFields.day),
                parseInt(endFields.hour) || 0,
                parseInt(endFields.minute) || 0,
                0
            );

            if (endDate > startDate) {
                var durationSeconds = Math.floor((endDate - startDate) / 1000);
                var durationMinutes = Math.floor(durationSeconds / 60);
                
                $('input[name="expected_duration"]').val(durationSeconds);
                $('input[name="expected_duration_display"]').val(durationMinutes + ' minuti');
            }
        }

        // Add event listeners
        $('select[name*="start_datetime"], select[name*="end_datetime"]').on('change', calculateDuration);

        // Calculate on page load
        calculateDuration();
    });
});
JS;

        $page->requires->js_init_code($js);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate required attendance is between 0 and 100
        if (isset($data['required_attendance'])) {
            if ($data['required_attendance'] < 0 || $data['required_attendance'] > 100) {
                $errors['required_attendance'] = get_string('invalidpercentage', 'core');
            }
        }

        // Validate start and end dates
        if (!empty($data['start_datetime']) && !empty($data['end_datetime'])) {
            if ($data['start_datetime'] >= $data['end_datetime']) {
                $errors['end_datetime'] = get_string('endbeforestart', 'mod_meetingsattendance');
            }
        }

        // Validate expected duration is positive
        if (isset($data['expected_duration']) && $data['expected_duration'] <= 0) {
            $errors['expected_duration'] = get_string('invaliddata', 'core');
        }

        return $errors;
    }
}
