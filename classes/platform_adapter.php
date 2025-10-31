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
 * Meetings Attendance - Platform Adapter Interface
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_meetingsattendance;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract interface for platform adapters (Teams, Zoom, etc.)
 *
 * Each platform implementation must extend this class and implement
 * the required methods for fetching and parsing attendance data.
 */
abstract class platform_adapter {

    /**
     * Session/meeting configuration
     * @var stdClass
     */
    protected $session;

    /**
     * Constructor
     *
     * @param stdClass $session Meeting session record from database
     */
    public function __construct(\stdClass $session) {
        $this->session = $session;
    }

    /**
     * Get platform name
     *
     * @return string Platform identifier ('teams', 'zoom', etc.)
     */
    abstract public function get_platform_name();

    /**
     * Validate that all required configuration is present
     *
     * @return bool True if valid, false otherwise
     * @throws moodle_exception If validation fails
     */
    abstract public function validate_configuration();

    /**
     * Fetch attendance data from platform API
     *
     * @param int $start_datetime Unix timestamp for report start (optional)
     * @param int $end_datetime Unix timestamp for report end (optional)
     * @return array Array of participant records with attendance data
     * @throws moodle_exception If fetch fails
     */
    abstract public function fetch_attendance_data($start_datetime = 0, $end_datetime = 0);

    /**
     * Extract email from platform participant record
     *
     * Used for exact matching with Moodle users
     *
     * @param array $participant Platform-specific participant record
     * @return string|null Email address if found, null otherwise
     */
    abstract public function extract_participant_email($participant);

    /**
     * Extract platform user ID from participant record
     *
     * Used to track the platform identifier for audit purposes
     *
     * @param array $participant Platform-specific participant record
     * @return string|null Platform user ID if found, null otherwise
     */
    abstract public function extract_platform_user_id($participant);

    /**
     * Extract duration (in seconds) from participant record
     *
     * @param array $participant Platform-specific participant record
     * @return int Duration in seconds
     */
    abstract public function extract_duration($participant);

    /**
     * Extract join and leave times from participant record
     *
     * @param array $participant Platform-specific participant record
     * @return array Array with keys 'join_time' and 'leave_time' (Unix timestamps)
     */
    abstract public function extract_times($participant);

    /**
     * Extract participant role
     *
     * @param array $participant Platform-specific participant record
     * @return string Role (e.g., 'Organizer', 'Presenter', 'Attendee')
     */
    abstract public function extract_role($participant);

    /**
     * Match Moodle user by email
     *
     * Performs exact email matching to link platform participant to Moodle user
     *
     * @param string $email Participant email
     * @return int|null Moodle user ID if found, null otherwise
     */
    protected function match_user_by_email($email) {
        global $DB;

        if (empty($email)) {
            return null;
        }

        // Exact email match - case insensitive for email standards
        $user = $DB->get_record('user', array('email' => strtolower($email)), 'id', IGNORE_MISSING);

        return $user ? $user->id : null;
    }

    /**
     * Get session data
     *
     * @return stdClass Session record
     */
    public function get_session() {
        return $this->session;
    }
}
