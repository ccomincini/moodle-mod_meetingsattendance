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
 * Meetings Attendance - Synchronization Logic
 *
 * Handles fetching attendance data from platform APIs and syncing with Moodle database
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_meetingsattendance;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/platform_factory.php');

/**
 * Attendance synchronization manager
 */
class attendance_sync {

    /**
     * Meeting session record
     * @var stdClass
     */
    private $session;

    /**
     * Course module record
     * @var stdClass
     */
    private $cm;

    /**
     * Platform adapter instance
     * @var platform_adapter
     */
    private $adapter;

    /**
     * Sync statistics
     * @var array
     */
    private $stats = array(
        'processed' => 0,
        'matched' => 0,
        'unassigned' => 0,
        'errors' => array()
    );

    /**
     * Constructor
     *
     * @param stdClass $session Meeting session record
     * @param stdClass $cm Course module record
     * @throws moodle_exception
     */
    public function __construct(\stdClass $session, \stdClass $cm) {
        $this->session = $session;
        $this->cm = $cm;
        $this->adapter = platform_factory::create_adapter($session);
        $this->adapter->validate_configuration();
    }

    /**
     * Synchronize attendance from platform
     *
     * Main entry point for attendance synchronization
     *
     * @param int $start_datetime Optional start time filter (Unix timestamp)
     * @param int $end_datetime Optional end time filter (Unix timestamp)
     * @return array Sync statistics
     * @throws moodle_exception
     */
    public function sync_attendance($start_datetime = 0, $end_datetime = 0) {
        global $DB;

        try {
            // Fetch attendance data from platform API
            $participants = $this->adapter->fetch_attendance_data($start_datetime, $end_datetime);

            if (empty($participants)) {
                $this->stats['errors'][] = get_string('no_participants', 'mod_meetingsattendance');
                return $this->stats;
            }

            // Process each participant
            foreach ($participants as $participant) {
                $this->process_participant($participant);
            }

            // Log sync event
            $this->log_sync_event();

        } catch (\Exception $e) {
            $this->stats['errors'][] = $e->getMessage();
            throw $e;
        }

        return $this->stats;
    }

    /**
     * Process a single participant record
     *
     * Implements exact email matching logic:
     * 1. Extract email from platform record
     * 2. Search for exact email match in Moodle users
     * 3. If found: Create/update attendance record with userid
     * 4. If not found: Create record as "unassigned" for manual review
     *
     * @param array $participant Platform participant record
     */
    private function process_participant($participant) {
        global $DB;

        $this->stats['processed']++;

        try {
            // Extract participant data from platform-specific record
            $email = $this->adapter->extract_participant_email($participant);
            $platform_user_id = $this->adapter->extract_platform_user_id($participant);
            $duration = $this->adapter->extract_duration($participant);
            $times = $this->adapter->extract_times($participant);
            $role = $this->adapter->extract_role($participant);

            // Validate required fields
            if (empty($platform_user_id)) {
                $this->stats['errors'][] = "Missing platform user ID for participant";
                return;
            }

            // Attempt exact email match with Moodle user
            $userid = $this->match_user_by_email($email);

            // If no match found, userid will be null (unassigned record)
            if ($userid === null) {
                $this->stats['unassigned']++;
                // Create unassigned record for manual review
                $this->create_attendance_record(null, $platform_user_id, $duration, $times, $role, 0);
            } else {
                // Email match found - create/update attendance record
                $this->stats['matched']++;
                $this->create_attendance_record($userid, $platform_user_id, $duration, $times, $role, 0);
            }

        } catch (\Exception $e) {
            $this->stats['errors'][] = "Error processing participant: " . $e->getMessage();
        }
    }

    /**
     * Match Moodle user by email with exact matching
     *
     * Performs case-insensitive exact email match as per email standards
     *
     * @param string $email Participant email
     * @return int|null Moodle user ID if found, null otherwise
     */
    private function match_user_by_email($email) {
        global $DB;

        if (empty($email)) {
            return null;
        }

        // Exact email match - case insensitive
        $email_lower = strtolower(trim($email));
        $user = $DB->get_record_sql(
            'SELECT id FROM {user} WHERE LOWER(email) = ?',
            array($email_lower),
            IGNORE_MISSING
        );

        return $user ? $user->id : null;
    }

    /**
     * Create or update attendance record
     *
     * If userid is provided, links to Moodle user (exact match).
     * If userid is null, creates unassigned record for manual review.
     *
     * @param int|null $userid Moodle user ID (null for unassigned)
     * @param string $platform_user_id Platform-specific user identifier
     * @param int $duration Duration in seconds
     * @param array $times Array with 'join_time' and 'leave_time'
     * @param string $role Participant role
     * @param int $manually_assigned Flag for manual assignment
     */
    private function create_attendance_record($userid, $platform_user_id, $duration, $times, $role, $manually_assigned) {
        global $DB;

        $record = new \stdClass();
        $record->sessionid = $this->session->id;
        $record->userid = $userid ?? 0; // Use 0 for unassigned records
        $record->platform_user_id = $platform_user_id;
        $record->attendance_duration = intval($duration);
        $record->actual_attendance = 0; // Will be calculated during completion check
        $record->completion_met = 0;
        $record->role = $role;
        $record->manually_assigned = $manually_assigned;

        // Check if record already exists for this platform user
        $existing = $DB->get_record('meetingsattendance_data', array(
            'sessionid' => $this->session->id,
            'platform_user_id' => $platform_user_id
        ));

        if ($existing) {
            // Update existing record
            $record->id = $existing->id;
            // Only update if userid was newly matched (don't override manual assignments)
            if (!$existing->manually_assigned || $userid !== null) {
                $record->userid = $userid ?? $existing->userid;
                $DB->update_record('meetingsattendance_data', $record);
            }
        } else {
            // Create new record
            $data_id = $DB->insert_record('meetingsattendance_data', $record);

            // Create report record with timing details
            $report = new \stdClass();
            $report->data_id = $data_id;
            $report->report_id = $platform_user_id . '_' . time();
            $report->join_time = intval($times['join_time'] ?? 0);
            $report->leave_time = intval($times['leave_time'] ?? 0);
            $report->attendance_duration = intval($duration);

            $DB->insert_record('meetingsattendance_reports', $report);
        }
    }

    /**
     * Log synchronization event
     *
     * Records sync event in Moodle logs for audit trail
     */
    private function log_sync_event() {
        $context = \context_module::instance($this->cm->id);

        \core\event\course_module_viewed::create(array(
            'objectid' => $this->cm->id,
            'context' => $context,
            'other' => array(
                'action' => 'attendance_sync',
                'platform' => $this->session->platform,
                'processed' => $this->stats['processed'],
                'matched' => $this->stats['matched'],
                'unassigned' => $this->stats['unassigned']
            )
        ))->trigger();
    }

    /**
     * Get synchronization statistics
     *
     * @return array Statistics array
     */
    public function get_stats() {
        return $this->stats;
    }

    /**
     * Get list of unassigned participants for manual review
     *
     * @return array Array of unassigned attendance records
     */
    public function get_unassigned_participants() {
        global $DB;

        return $DB->get_records('meetingsattendance_data', array(
            'sessionid' => $this->session->id,
            'userid' => 0
        ));
    }

    /**
     * Manually assign participant to Moodle user
     *
     * Used when automatic email matching fails but manual review identifies the user
     *
     * @param int $data_id Attendance data record ID
     * @param int $userid Target Moodle user ID
     * @return bool True on success
     */
    public function manual_assign_user($data_id, $userid) {
        global $DB;

        $record = $DB->get_record('meetingsattendance_data', array('id' => $data_id));
        if (!$record) {
            return false;
        }

        $record->userid = $userid;
        $record->manually_assigned = 1;

        return $DB->update_record('meetingsattendance_data', $record);
    }
}
