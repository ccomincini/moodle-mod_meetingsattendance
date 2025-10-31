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
 * Meetings Attendance Module - Language Strings (English)
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Meetings Attendance';
$string['modulename'] = 'Meetings Attendance';
$string['modulename_help'] = 'The Meetings Attendance module enables tracking attendance for Teams and Zoom meetings with accurate participant identification and completion rules.';
$string['modulenameplural'] = 'Meetings Attendance';

// Settings
$string['platform'] = 'Meeting Platform';
$string['platform_help'] = 'Select the platform where the meeting will be held: Teams or Zoom.';
$string['teams'] = 'Microsoft Teams';
$string['zoom'] = 'Zoom';

$string['meeting_url'] = 'Meeting URL or Identifier';
$string['meeting_url_help'] = 'Enter the meeting URL or identifier for the selected platform.';

$string['meeting_id'] = 'Meeting ID';
$string['meeting_id_help'] = 'Enter the platform-specific meeting ID (optional for Teams, required for Zoom).';

$string['organizer_email'] = 'Organizer Email';
$string['organizer_email_help'] = 'Email address of the meeting organizer. This is used to retrieve attendance reports from the platform.';

$string['expected_duration'] = 'Expected Duration (minutes)';
$string['expected_duration_help'] = 'Expected duration of the meeting in minutes. This is used to calculate attendance percentages.';

$string['required_attendance'] = 'Required Attendance (%)';
$string['required_attendance_help'] = 'Minimum attendance percentage required for completion (0-100).';

$string['completionattendance'] = 'Require attendance for completion';
$string['completionattendance_desc'] = 'User must meet the required attendance percentage to complete this activity.';

// Actions and operations
$string['attendance_register'] = 'Attendance Register';
$string['close_register'] = 'Close Register';
$string['reopen_register'] = 'Reopen Register';
$string['manage_unassigned'] = 'Manage Unassigned Participants';
$string['manage_manual_assignments'] = 'Manage Manual Assignments';

// Reports
$string['participants'] = 'Participants';
$string['sessions'] = 'Meeting Sessions';
$string['jointime'] = 'Join Time';
$string['leavetime'] = 'Leave Time';
$string['duration'] = 'Duration';
$string['attendance_duration'] = 'Attendance Duration';
$string['actual_attendance'] = 'Actual Attendance (%)';

// Errors
$string['missingrequiredfield'] = 'Missing required field';
$string['sessionnotfound'] = 'Meeting session not found';
$string['invalidaccesstoken'] = 'Invalid or missing access token for platform API';
$string['invalidattendanceformat'] = 'Invalid attendance data format received from platform API';
$string['missingapicredentials'] = 'Missing API credentials for the selected platform';
$string['missingtenantid'] = 'Missing Microsoft tenant ID for Teams integration';
$string['participantdatanotavailable'] = 'Participant data not available';

// Messages
$string['attendance_updated'] = 'Attendance records have been updated';
$string['no_participants'] = 'No participants found for this meeting';
$string['unassigned_participants'] = 'Unassigned participants from the platform';

// Sync results
$string['processed'] = 'Participants Processed';
$string['matched'] = 'Successfully Matched';
$string['unassigned'] = 'Unassigned (Manual Review Required)';
$string['attendance_updated'] = 'Attendance data has been synchronized';
$string['fetch_attendance'] = 'Fetch Attendance from Platform';
$string['back'] = 'Back to Activity';

// Manual assignment
$string['manual_assignment'] = 'Manual User Assignment';
$string['assign_user'] = 'Assign User';
$string['participant_email'] = 'Participant Email';
$string['select_moodle_user'] = 'Select Moodle User';

$string['meeting_schedule'] = 'Meeting Schedule';
$string['completion_settings'] = 'Completion Settings';
$string['start_datetime'] = 'Start date and time';
$string['start_datetime_help'] = 'Date and time when the meeting officially starts (used for attendance validation).';
$string['end_datetime'] = 'End date and time';
$string['end_datetime_help'] = 'Date and time when the meeting officially ends. The expected duration will be calculated automatically.';
$string['endbeforestart'] = 'The end date/time must be after the start date/time.';
