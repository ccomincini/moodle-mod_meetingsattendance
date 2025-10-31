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
 * Meetings Attendance - Main Activity View
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
require_capability('mod/meetingsattendance:view', $context);

$PAGE->set_url('/mod/meetingsattendance/view.php', array('id' => $cmid));
$PAGE->set_title($cm->name);
$PAGE->set_heading($course->fullname);

// Get session
$session = $DB->get_record('meetingsattendance', array('id' => $cm->instance), '*', MUST_EXIST);

echo $OUTPUT->header();
echo $OUTPUT->heading($cm->name, 2);

// Display session information
$info_table = new html_table();
$info_table->align = array('right', 'left');
$info_table->data = array();

$info_table->data[] = array(
    get_string('platform', 'mod_meetingsattendance') . ':',
    strtoupper($session->platform)
);

$info_table->data[] = array(
    get_string('organizer_email', 'mod_meetingsattendance') . ':',
    $session->organizer_email
);

$info_table->data[] = array(
    get_string('start_datetime', 'mod_meetingsattendance') . ':',
    userdate($session->start_datetime, get_string('strftimedatetimeshort', 'langconfig'))
);

$info_table->data[] = array(
    get_string('end_datetime', 'mod_meetingsattendance') . ':',
    userdate($session->end_datetime, get_string('strftimedatetimeshort', 'langconfig'))
);

$duration_minutes = round($session->expected_duration / 60);
$info_table->data[] = array(
    get_string('expected_duration', 'mod_meetingsattendance') . ':',
    $duration_minutes . ' ' . get_string('minutes', 'core')
);

$info_table->data[] = array(
    get_string('required_attendance', 'mod_meetingsattendance') . ':',
    $session->required_attendance . '%'
);

$info_table->data[] = array(
    get_string('status', 'mod_meetingsattendance') . ':',
    ucfirst($session->status)
);

echo html_writer::table($info_table);

// Display intro
if (!empty($session->intro)) {
    echo format_module_intro('meetingsattendance', $session, $cm->id);
}

// Display action buttons for teachers
if (has_capability('mod/meetingsattendance:manageattendance', $context)) {
    echo $OUTPUT->heading(get_string('actions', 'core'), 4);

    $actions = array();

    // Fetch attendance button
    $fetch_url = new moodle_url('/mod/meetingsattendance/fetch_attendance.php', array('id' => $cmid));
    $actions[] = html_writer::link($fetch_url, 
        get_string('fetch_attendance', 'mod_meetingsattendance'), 
        array('class' => 'btn btn-primary'));

    // View report button
    $report_url = new moodle_url('/mod/meetingsattendance/report.php', array('id' => $cmid));
    $actions[] = html_writer::link($report_url,
        get_string('participants', 'mod_meetingsattendance'),
        array('class' => 'btn btn-info'));

    // Manage unassigned button
    $unassigned_count = $DB->count_records('meetingsattendance_data', array(
        'sessionid' => $session->id,
        'userid' => 0
    ));
    if ($unassigned_count > 0) {
        $manage_url = new moodle_url('/mod/meetingsattendance/manage_unassigned.php', array('id' => $cmid));
        $actions[] = html_writer::link($manage_url,
            get_string('manage_unassigned', 'mod_meetingsattendance') . " ($unassigned_count)",
            array('class' => 'btn btn-warning'));
    }

    echo html_writer::start_tag('div', array('style' => 'margin-top: 15px; margin-bottom: 15px;'));
    foreach ($actions as $action) {
        echo $action . ' ';
    }
    echo html_writer::end_tag('div');
}

// Display attendance summary
if (has_capability('mod/meetingsattendance:viewreports', $context)) {
    echo $OUTPUT->heading(get_string('attendance_summary', 'mod_meetingsattendance'), 4);

    $total_records = $DB->count_records('meetingsattendance_data', array('sessionid' => $session->id));
    $assigned_records = $DB->count_records('meetingsattendance_data', array(
        'sessionid' => $session->id
    ), 'userid > 0');
    $unassigned_records = $total_records - $assigned_records;
    $completion_met = $DB->count_records('meetingsattendance_data', array(
        'sessionid' => $session->id,
        'completion_met' => 1
    ));

    $summary_table = new html_table();
    $summary_table->align = array('right', 'center');
    $summary_table->data = array();

    $summary_table->data[] = array(
        get_string('total_participants', 'mod_meetingsattendance') . ':',
        $total_records
    );

    $summary_table->data[] = array(
        get_string('assigned', 'mod_meetingsattendance') . ':',
        $assigned_records
    );

    $summary_table->data[] = array(
        get_string('unassigned', 'mod_meetingsattendance') . ':',
        $unassigned_records
    );

    $summary_table->data[] = array(
        get_string('completion_met', 'mod_meetingsattendance') . ':',
        $completion_met
    );

    echo html_writer::table($summary_table);
}

echo $OUTPUT->footer();
