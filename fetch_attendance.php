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
 * Fetch attendance data from platform
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

// Get course module and context
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'meetingsattendance');
$context = context_module::instance($cm->id);

// Check capability
require_capability('mod/meetingsattendance:manageattendance', $context);

$PAGE->set_url('/mod/meetingsattendance/fetch_attendance.php', array('id' => $cmid));
$PAGE->set_title(get_string('attendance_register', 'mod_meetingsattendance'));
$PAGE->set_heading($course->fullname);

// Get session
$session = $DB->get_record('meetingsattendance', array('id' => $cm->instance), '*', MUST_EXIST);

echo $OUTPUT->header();

try {
    // Create sync instance and fetch attendance
    $sync = new attendance_sync($session, $cm);

    $start_datetime = $session->start_datetime ?? 0;
    $end_datetime = $session->end_datetime ?? 0;

    // Perform synchronization
    $stats = $sync->sync_attendance($start_datetime, $end_datetime);

    // Display results
    echo $OUTPUT->heading(get_string('attendance_updated', 'mod_meetingsattendance'), 3);

    $results = array(
        get_string('processed', 'mod_meetingsattendance') => $stats['processed'],
        get_string('matched', 'mod_meetingsattendance') => $stats['matched'],
        get_string('unassigned', 'mod_meetingsattendance') => $stats['unassigned']
    );

    $table = new html_table();
    $table->head = array('Metric', 'Count');
    $table->data = array();

    foreach ($results as $label => $count) {
        $table->data[] = array($label, $count);
    }

    echo html_writer::table($table);

    // Show errors if any
    if (!empty($stats['errors'])) {
        echo $OUTPUT->heading('Errors', 4);
        echo html_writer::start_tag('ul');
        foreach ($stats['errors'] as $error) {
            echo html_writer::tag('li', $error);
        }
        echo html_writer::end_tag('ul');
    }

    // Show link to manage unassigned
    if ($stats['unassigned'] > 0) {
        $manage_url = new moodle_url('/mod/meetingsattendance/manage_unassigned.php', array('id' => $cmid));
        echo html_writer::link($manage_url, get_string('manage_unassigned', 'mod_meetingsattendance'), array('class' => 'btn btn-primary'));
    }

    // Back link
    $back_url = new moodle_url('/mod/meetingsattendance/view.php', array('id' => $cmid));
    echo html_writer::link($back_url, get_string('back', 'core'), array('class' => 'btn btn-secondary'));

} catch (Exception $e) {
    echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
}

echo $OUTPUT->footer();
