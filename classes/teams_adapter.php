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
 * Meetings Attendance - Teams Platform Adapter
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_meetingsattendance;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/platform_adapter.php');

/**
 * Teams platform adapter - Implements Microsoft Teams integration
 */
class teams_adapter extends platform_adapter {

    /**
     * Get platform name
     *
     * @return string
     */
    public function get_platform_name() {
        return 'teams';
    }

    /**
     * Validate Teams configuration
     *
     * Checks for required OIDC and Teams settings
     *
     * @return bool
     * @throws moodle_exception
     */
    public function validate_configuration() {
        global $CFG;

        // Check OIDC plugin is configured
        $client_id = get_config('auth_oidc', 'clientid');
        $client_secret = get_config('auth_oidc', 'clientsecret');

        if (empty($client_id) || empty($client_secret)) {
            throw new \moodle_exception('missingapicredentials', 'mod_meetingsattendance');
        }

        // Check Teams-specific tenant ID
        $tenant_id = get_config('mod_meetingsattendance', 'teams_tenant_id');
        if (empty($tenant_id)) {
            throw new \moodle_exception('missingtenantid', 'mod_meetingsattendance');
        }

        // Validate meeting URL/ID
        if (empty($this->session->meeting_url) && empty($this->session->meeting_id)) {
            throw new \moodle_exception('missingrequiredfield', 'mod_meetingsattendance');
        }

        return true;
    }

    /**
     * Fetch attendance data from Microsoft Graph API
     *
     * @param int $start_datetime Unix timestamp for report start (optional)
     * @param int $end_datetime Unix timestamp for report end (optional)
     * @return array Array of participant records
     * @throws moodle_exception
     */
    public function fetch_attendance_data($start_datetime = 0, $end_datetime = 0) {
        // Get API credentials
        $client_id = get_config('auth_oidc', 'clientid');
        $client_secret = get_config('auth_oidc', 'clientsecret');
        $tenant_id = get_config('mod_meetingsattendance', 'teams_tenant_id');

        // Get access token
        $access_token = $this->get_graph_access_token($client_id, $client_secret, $tenant_id);
        if (!$access_token) {
            throw new \moodle_exception('invalidaccesstoken', 'mod_meetingsattendance');
        }

        // Extract meeting ID from URL or use provided ID
        $meeting_id = $this->extract_meeting_id();
        if (!$meeting_id) {
            throw new \moodle_exception('invaliddata', 'mod_meetingsattendance');
        }

        // Fetch attendee report from Microsoft Graph
        $endpoint = "https://graph.microsoft.com/v1.0/me/onlineMeetings/{$meeting_id}/attendanceReports";

        $curl = new \curl();
        $curl->setHeader(array(
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ));

        $response = $curl->get($endpoint);
        $data = json_decode($response, true);

        if (!isset($data['value']) || !is_array($data['value'])) {
            throw new \moodle_exception('invalidattendanceformat', 'mod_meetingsattendance');
        }

        // Parse attendance records
        $participants = array();
        foreach ($data['value'] as $report) {
            if (isset($report['attendanceRecords']) && is_array($report['attendanceRecords'])) {
                foreach ($report['attendanceRecords'] as $record) {
                    $participants[] = $record;
                }
            }
        }

        return $participants;
    }

    /**
     * Extract email from Teams participant record
     *
     * @param array $participant Teams participant record
     * @return string|null
     */
    public function extract_participant_email($participant) {
        // Teams provides email in 'emailAddress' field
        if (isset($participant['emailAddress'])) {
            return trim(strtolower($participant['emailAddress']));
        }
        return null;
    }

    /**
     * Extract Teams user ID from participant record
     *
     * @param array $participant Teams participant record
     * @return string|null
     */
    public function extract_platform_user_id($participant) {
        if (isset($participant['id'])) {
            return $participant['id'];
        }
        return null;
    }

    /**
     * Extract duration from Teams participant record
     *
     * @param array $participant Teams participant record
     * @return int Duration in seconds
     */
    public function extract_duration($participant) {
        if (isset($participant['totalAttendanceInSeconds'])) {
            return intval($participant['totalAttendanceInSeconds']);
        }
        return 0;
    }

    /**
     * Extract join and leave times from Teams participant record
     *
     * @param array $participant Teams participant record
     * @return array
     */
    public function extract_times($participant) {
        $times = array(
            'join_time' => 0,
            'leave_time' => 0
        );

        if (isset($participant['attendanceIntervals']) && is_array($participant['attendanceIntervals'])) {
            if (count($participant['attendanceIntervals']) > 0) {
                $first_interval = $participant['attendanceIntervals'][0];
                $last_interval = end($participant['attendanceIntervals']);

                // Convert ISO 8601 to Unix timestamp
                if (isset($first_interval['joinDateTime'])) {
                    $times['join_time'] = strtotime($first_interval['joinDateTime']);
                }

                if (isset($last_interval['leaveDateTime'])) {
                    $times['leave_time'] = strtotime($last_interval['leaveDateTime']);
                }
            }
        }

        return $times;
    }

    /**
     * Extract participant role from Teams record
     *
     * @param array $participant Teams participant record
     * @return string
     */
    public function extract_role($participant) {
        // Teams doesn't consistently provide role, default to Attendee
        if (isset($participant['role'])) {
            return ucfirst(strtolower($participant['role']));
        }
        return 'Attendee';
    }

    /**
     * Get Microsoft Graph access token
     *
     * @param string $client_id
     * @param string $client_secret
     * @param string $tenant_id
     * @return string|null Access token if successful, null otherwise
     */
    private function get_graph_access_token($client_id, $client_secret, $tenant_id) {
        $token_url = "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token";

        $curl = new \curl();
        $post_data = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials'
        );

        $response = $curl->post($token_url, http_build_query($post_data));
        $data = json_decode($response, true);

        return isset($data['access_token']) ? $data['access_token'] : null;
    }

    /**
     * Extract meeting ID from URL or use provided ID
     *
     * @return string|null
     */
    private function extract_meeting_id() {
        // If meeting_id is explicitly set, use it
        if (!empty($this->session->meeting_id)) {
            return $this->session->meeting_id;
        }

        // Try to extract from meeting URL
        // Teams meeting URLs format: https://teams.microsoft.com/l/meetup-join/...
        if (!empty($this->session->meeting_url)) {
            // Parse Teams URL to extract meeting ID (simplified)
            // This would need more robust parsing based on actual URL format
            preg_match('/meetup-join\/([^\/]+)/', $this->session->meeting_url, $matches);
            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        return null;
    }
}
