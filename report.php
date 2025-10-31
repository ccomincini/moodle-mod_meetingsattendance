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
 * Meetings Attendance - Attendance Report
 *
 * Displays detailed attendance report with participant information
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();

$cmid = required_param('id', PARAM_INT);

// Get course module and context
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'meetingsattendance');
$context = context_module::instance($cm->id);

// Check capability
require_capability('mod/meetingsattendance:viewreports', $context);

$PAGE->set_url('/mod/meetingsattendance/report.php', array('id' => $cmid));
$PAGE->set_title(get_string('participants', 'mod_meetingsattendance'));
$PAGE->set_heading($course->fullname);

// Get session
$session = $DB->get_record('meetingsattendance', array('id' => $cm->instance), '*', MUST_EXIST);

echo $OUTPUT->header();
echo $OUTPUT->heading($cm->name . ' - ' . get_string('participants', 'mod_meetingsattendance'), 3);

// Get all attendance records
$sql = "SELECT ad.*, u.firstname, u.lastname, u.email,
               GROUP_CONCAT(ar.join_time) as join_times,
               GROUP_CONCAT(ar.leave_time) as leave_times
        FROM {meetingsattendance_data} ad
        LEFT JOIN {user} u ON ad.userid = u.id
        LEFT JOIN {meetingsattendance_reports} ar ON ad.id = ar.data_id
        WHERE ad.sessionid = ?
        GROUP BY ad.id
        ORDER BY ad.userid DESC, ad.platform_user_id ASC";

$records = $DB->get_records_sql($sql, array($session->id));

if (empty($records)) {
    echo $OUTPUT->notification(get_string('no_participants', 'mod_meetingsattendance'), 'notifymessage');
} else {
    // Build attendance table
    $table = new html_table();
    $table->head = array(
        get_string('name'),
        get_string('email'),
        get_string('role', 'mod_meetingsattendance'),
        get_string('attendance_duration', 'mod_meetingsattendance'),
        get_string('actual_attendance', 'mod_meetingsattendance'),
        get_string('completion_met', 'mod_meetingsattendance'),
        get_string('jointime', 'mod_meetingsattendance'),
        get_string('leavetime', 'mod_meetingsattendance')
    );
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->data = array();

    foreach ($records as $record) {
        // Calculate actual attendance percentage
        $attendance_percentage = ($session->expected_duration > 0) ?
            round(($record->attendance_duration / $session->expected_duration) * 100, 2) : 0;

        // Format participant name or use platform ID if not assigned
        if ($record->userid > 0) {
            $participant_name = fullname($record);
            $participant_email = $record->email;
        } else {
            $participant_name = html_writer::tag('em', get_string('unassigned', 'mod_meetingsattendance'));
            $participant_email = $record->platform_user_id;
        }

        // Format times
        $join_times = !empty($record->join_times) ? explode(',', $record->join_times) : array();
        $leave_times = !empty($record->leave_times) ? explode(',', $record->leave_times) : array();

        $join_display = '';
        $leave_display = '';

        if (!empty($join_times) && $join_times[0]) {
            $join_display = userdate(intval($join_times[0]), get_string('strftimedatetimeshort', 'langconfig'));
        }

        if (!empty($leave_times) && $leave_times[0]) {
            $leave_display = userdate(intval($leave_times[0]), get_string('strftimedatetimeshort', 'langconfig'));
        }

        // Completion status
        $completion_status = $record->completion_met ? 
            $OUTPUT->pix_icon('i/valid', get_string('yes')) :
            $OUTPUT->pix_icon('i/invalid', get_string('no'));

        $table->data[] = array(
            $participant_name,
            $participant_email,
            $record->role,
            round($record->attendance_duration / 60) . ' min',
            $attendance_percentage . '%',
            $completion_status,
            $join_display,
            $leave_display
        );
    }

    echo html_writer::table($table);
}

// Back link
$back_url = new moodle_url('/mod/meetingsattendance/view.php', array('id' => $cmid));
echo html_writer::link($back_url, get_string('back', 'core'), array('class' => 'btn btn-secondary'));

echo $OUTPUT->footer();
