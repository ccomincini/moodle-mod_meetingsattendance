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
 * Meetings Attendance Module - Library Functions
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Feature support for this module
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if unknown
 */
function meetingsattendance_supports($feature) {
    switch ($feature) {
        case MOD_PURPOSE_ADMINISTRATION:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Check if plugin supports custom completion rules
 *
 * @param string $rulename Name of the completion rule
 * @return bool True if rule is supported
 */
function meetingsattendance_supports_specific_completion_rule($rulename) {
    return $rulename === 'completionattendance';
}

/**
 * Get descriptions of completion rules
 *
 * @return array Completion rule descriptions
 */
function meetingsattendance_get_completion_rule_descriptions() {
    return array(
        'completionattendance' => get_string('completionattendance_desc', 'mod_meetingsattendance')
    );
}

/**
 * Check if completion rule is enabled
 *
 * @param stdClass $data Completion data
 * @return bool True if rule is enabled
 */
function meetingsattendance_completion_rule_enabled($data) {
    return !empty($data->completionattendance);
}

/**
 * Add a new instance of the Meetings Attendance module
 *
 * @param stdClass $data Form data
 * @param mod_meetingsattendance_mod_form $mform Form instance
 * @return int ID of newly created instance
 * @throws moodle_exception
 */
function meetingsattendance_add_instance($data, $mform) {
    global $DB;

    // Validate required fields
    if (empty($data->name) || empty($data->meeting_url) || empty($data->organizer_email) || empty($data->platform)) {
        throw new moodle_exception('missingrequiredfield', 'mod_meetingsattendance');
    }

    // Prepare record for insertion
    $record = new stdClass();
    $record->course = $data->course;
    $record->name = $data->name;
    $record->intro = $data->intro;
    $record->introformat = $data->introformat;
    $record->platform = $data->platform;
    $record->meeting_url = $data->meeting_url;
    $record->meeting_id = isset($data->meeting_id) ? $data->meeting_id : '';
    $record->organizer_email = $data->organizer_email;
    $record->expected_duration = $data->expected_duration;
    $record->required_attendance = $data->required_attendance;
    $record->completionattendance = isset($data->completionattendance) ? $data->completionattendance : 0;
    $record->status = 'open';
    $record->start_datetime = isset($data->start_datetime) ? $data->start_datetime : 0;
    $record->end_datetime = isset($data->end_datetime) ? $data->end_datetime : 0;
    $record->timecreated = time();
    $record->timemodified = time();

    return $DB->insert_record('meetingsattendance', $record);
}

/**
 * Update an existing instance of the Meetings Attendance module
 *
 * @param stdClass $data Form data
 * @param mod_meetingsattendance_mod_form $mform Form instance
 * @return bool True on success
 * @throws moodle_exception
 */
function meetingsattendance_update_instance($data, $mform) {
    global $DB;

    // Validate required fields
    if (empty($data->name) || empty($data->meeting_url) || empty($data->organizer_email) || empty($data->platform)) {
        throw new moodle_exception('missingrequiredfield', 'mod_meetingsattendance');
    }

    // Prepare record for update
    $record = new stdClass();
    $record->id = $data->instance;
    $record->course = $data->course;
    $record->name = $data->name;
    $record->intro = $data->intro;
    $record->introformat = $data->introformat;
    $record->platform = $data->platform;
    $record->meeting_url = $data->meeting_url;
    $record->meeting_id = isset($data->meeting_id) ? $data->meeting_id : '';
    $record->organizer_email = $data->organizer_email;
    $record->expected_duration = $data->expected_duration;
    $record->required_attendance = $data->required_attendance;
    $record->completionattendance = isset($data->completionattendance) ? $data->completionattendance : 0;
    $record->start_datetime = isset($data->start_datetime) ? $data->start_datetime : 0;
    $record->end_datetime = isset($data->end_datetime) ? $data->end_datetime : 0;
    $record->timemodified = time();

    return $DB->update_record('meetingsattendance', $record);
}

/**
 * Delete an instance of the Meetings Attendance module
 *
 * @param int $id Instance ID
 * @return bool Success status
 */
function meetingsattendance_delete_instance($id) {
    global $DB;

    // Get the instance
    if (!$instance = $DB->get_record('meetingsattendance', array('id' => $id))) {
        return false;
    }

    // Delete all attendance records
    $DB->delete_records('meetingsattendance_data', array('sessionid' => $id));
    $DB->delete_records('meetingsattendance_reports', array('sessionid' => $id));

    // Delete the instance
    $DB->delete_records('meetingsattendance', array('id' => $id));

    return true;
}

/**
 * Check if user has met attendance completion criteria
 *
 * @param stdClass $cm Course module object
 * @param int $userid User ID
 * @return bool True if criteria met
 */
function meetingsattendance_check_completion($cm, $userid) {
    global $DB;

    // Fetch the session and user attendance data
    $session = $DB->get_record('meetingsattendance', array('id' => $cm->instance), '*', MUST_EXIST);
    if (!$session) {
        return false;
    }

    $attendance = $DB->get_record('meetingsattendance_data', array(
        'sessionid' => $cm->instance,
        'userid' => $userid
    ));

    if (!$attendance) {
        return false;
    }

    // Calculate attendance percentage
    if ($session->expected_duration <= 0) {
        return false;
    }

    $attendance_percentage = ($attendance->attendance_duration / $session->expected_duration) * 100;

    // Check if meets required percentage
    $completion_met = $attendance_percentage >= $session->required_attendance;

    // Update record
    $attendance->actual_attendance = round($attendance_percentage, 2);
    $attendance->completion_met = $completion_met ? 1 : 0;
    $DB->update_record('meetingsattendance_data', $attendance);

    // Log completion event if met
    if ($completion_met) {
        $context = context_module::instance($cm->id);
        \core\event\course_module_completion_updated::create(array(
            'objectid' => $cm->id,
            'context' => $context,
            'relateduserid' => $userid,
            'other' => array(
                'completionstate' => 1,
                'attendance_duration' => $attendance->attendance_duration,
                'actual_attendance' => $attendance->actual_attendance
            )
        ))->trigger();
    }

    return $completion_met;
}

/**
 * Extend settings navigation
 *
 * @param settings_navigation $settingsnav Settings navigation object
 * @param context $context Context object
 */
function mod_meetingsattendance_extend_settings_navigation($settingsnav, $context) {
    global $DB, $PAGE;

    if (has_capability('mod/meetingsattendance:manageattendance', $context)) {
        $node = $settingsnav->add(get_string('attendance_register', 'mod_meetingsattendance'));

        $cmid = $PAGE->cm->id;
        $session = $DB->get_record('meetingsattendance', array('id' => $PAGE->cm->instance), '*', MUST_EXIST);

        if ($session->status === 'open') {
            $node->add(get_string('close_register', 'mod_meetingsattendance'),
                new moodle_url('/mod/meetingsattendance/close_register.php', array('id' => $cmid)));
        } else {
            $node->add(get_string('reopen_register', 'mod_meetingsattendance'),
                new moodle_url('/mod/meetingsattendance/reopen_register.php', array('id' => $cmid)));
        }

        // Add link to manage unassigned records
        $node->add(get_string('manage_unassigned', 'mod_meetingsattendance'),
            new moodle_url('/mod/meetingsattendance/manage_unassigned.php', array('id' => $cmid)));
    }
}
