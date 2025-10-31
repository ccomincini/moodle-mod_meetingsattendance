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
 * Manage unassigned participants
 *
 * Allows manual assignment of platform participants to Moodle users
 * when automatic email matching fails
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_meetingsattendance\attendance_sync;

require_login();

$cmid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$dataid = optional_param('dataid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

// Get course module and context
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'meetingsattendance');
$context = context_module::instance($cm->id);

// Check capability
require_capability('mod/meetingsattendance:manageattendance', $context);

$PAGE->set_url('/mod/meetingsattendance/manage_unassigned.php', array('id' => $cmid));
$PAGE->set_title(get_string('manage_unassigned', 'mod_meetingsattendance'));
$PAGE->set_heading($course->fullname);

// Get session
$session = $DB->get_record('meetingsattendance', array('id' => $cm->instance), '*', MUST_EXIST);

echo $OUTPUT->header();

// Handle assignment action
if ($action === 'assign' && $dataid && $userid) {
    // Verify nonce for security
    require_sesskey();

    $sync = new attendance_sync($session, $cm);
    if ($sync->manual_assign_user($dataid, $userid)) {
        redirect(new moodle_url('/mod/meetingsattendance/manage_unassigned.php', array('id' => $cmid)),
            get_string('attendance_updated', 'mod_meetingsattendance'), 3);
    }
}

// Get unassigned participants
$sync = new attendance_sync($session, $cm);
$unassigned = $sync->get_unassigned_participants();

echo $OUTPUT->heading(get_string('manage_unassigned', 'mod_meetingsattendance'), 3);

if (empty($unassigned)) {
    echo $OUTPUT->notification(get_string('no_participants', 'mod_meetingsattendance'), 'notifysuccess');
} else {
    // Get enrolled users for dropdown
    $coursecontext = context_course::instance($course->id);
    $enrolled_users = get_enrolled_users($coursecontext);
    $user_options = array(0 => get_string('choose', 'core'));
    foreach ($enrolled_users as $user) {
        $user_options[$user->id] = fullname($user) . ' (' . $user->email . ')';
    }

    // Display unassigned participants
    $table = new html_table();
    $table->head = array(
        get_string('participant_email', 'mod_meetingsattendance'),
        get_string('duration', 'mod_meetingsattendance'),
        get_string('role', 'mod_meetingsattendance'),
        get_string('select_moodle_user', 'mod_meetingsattendance'),
        ''
    );
    $table->data = array();

    foreach ($unassigned as $record) {
        // Get report data for this participant
        $report = $DB->get_record('meetingsattendance_reports',
            array('data_id' => $record->id), '*', IGNORE_MISSING);

        $duration_min = round($record->attendance_duration / 60);

        // Create form for this participant
        $form_id = 'assign_form_' . $record->id;
        $select_html = html_writer::select($user_options, 'userid_' . $record->id, 0);

        $assign_url = new moodle_url('/mod/meetingsattendance/manage_unassigned.php', array(
            'id' => $cmid,
            'action' => 'assign',
            'dataid' => $record->id,
            'sesskey' => sesskey()
        ));

        $button = html_writer::start_tag('form', array('method' => 'POST', 'style' => 'display:inline'));
        $button .= html_writer::hidden_field('sesskey', sesskey());
        $button .= html_writer::hidden_field('dataid', $record->id);
        $button .= html_writer::select($user_options, 'userid', 0, false);
        $button .= ' ' . html_writer::submit_button(get_string('assign_user', 'mod_meetingsattendance'));
        $button .= html_writer::end_tag('form');

        $table->data[] = array(
            $record->platform_user_id,
            $duration_min . ' min',
            $record->role,
            $button,
            html_writer::link(new moodle_url('/mod/meetingsattendance/manage_unassigned.php',
                array('id' => $cmid, 'action' => 'skip', 'dataid' => $record->id, 'sesskey' => sesskey())),
                get_string('skip', 'core'), array('class' => 'btn btn-sm btn-secondary'))
        );
    }

    echo html_writer::table($table);
}

// Back link
$back_url = new moodle_url('/mod/meetingsattendance/view.php', array('id' => $cmid));
echo html_writer::link($back_url, get_string('back', 'core'), array('class' => 'btn btn-secondary'));

echo $OUTPUT->footer();
