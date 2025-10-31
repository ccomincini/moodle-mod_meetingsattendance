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
 * Meetings Attendance - Zoom Platform Adapter
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_meetingsattendance;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/platform_adapter.php');

/**
 * Zoom platform adapter - Implements Zoom integration
 */
class zoom_adapter extends platform_adapter {

    /**
     * Get platform name
     *
     * @return string
     */
    public function get_platform_name() {
        return 'zoom';
    }

    /**
     * Validate Zoom configuration
     *
     * Checks for required Zoom OAuth credentials
     *
     * @return bool
     * @throws moodle_exception
     */
    public function validate_configuration() {
        // Check Zoom OAuth credentials
        $client_id = get_config('mod_meetingsattendance', 'zoom_client_id');
        $client_secret = get_config('mod_meetingsattendance', 'zoom_client_secret');
        $account_id = get_config('mod_meetingsattendance', 'zoom_account_id');

        if (empty($client_id) || empty($client_secret) || empty($account_id)) {
            throw new \moodle_exception('missingapicredentials', 'mod_meetingsattendance');
        }

        // Check meeting ID is provided
        if (empty($this->session->meeting_id)) {
            throw new \moodle_exception('missingrequiredfield', 'mod_meetingsattendance');
        }

        return true;
    }

    /**
     * Fetch attendance data from Zoom Report API
     *
     * @param int $start_datetime Unix timestamp for report start (optional)
     * @param int $end_datetime Unix timestamp for report end (optional)
     * @return array Array of participant records
     * @throws moodle_exception
     */
    public function fetch_attendance_data($start_datetime = 0, $end_datetime = 0) {
        // Get Zoom credentials
        $client_id = get_config('mod_meetingsattendance', 'zoom_client_id');
        $client_secret = get_config('mod_meetingsattendance', 'zoom_client_secret');
        $account_id = get_config('mod_meetingsattendance', 'zoom_account_id');

        // Get Zoom access token
        $access_token = $this->get_zoom_access_token($client_id, $client_secret, $account_id);
        if (!$access_token) {
            throw new \moodle_exception('invalidaccesstoken', 'mod_meetingsattendance');
        }

        // Get meeting ID
        $meeting_id = $this->session->meeting_id;

        // Fetch participant report from Zoom API
        $endpoint = "https://api.zoom.us/v2/report/meetings/{$meeting_id}/participants";

        $curl = new \curl();
        $curl->setHeader(array(
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ));

        $response = $curl->get($endpoint);
        $data = json_decode($response, true);

        if (isset($data['code']) && $data['code'] != 200) {
            throw new \moodle_exception('invalidaccesstoken', 'mod_meetingsattendance');
        }

        if (!isset($data['participants']) || !is_array($data['participants'])) {
            throw new \moodle_exception('invalidattendanceformat', 'mod_meetingsattendance');
        }

        return $data['participants'];
    }

    /**
     * Extract email from Zoom participant record
     *
     * @param array $participant Zoom participant record
     * @return string|null
     */
    public function extract_participant_email($participant) {
        // Zoom provides email in 'user_email' field
        if (isset($participant['user_email'])) {
            return trim(strtolower($participant['user_email']));
        }
        return null;
    }

    /**
     * Extract Zoom user ID from participant record
     *
     * @param array $participant Zoom participant record
     * @return string|null
     */
    public function extract_platform_user_id($participant) {
        if (isset($participant['id'])) {
            return $participant['id'];
        }
        return null;
    }

    /**
     * Extract duration from Zoom participant record
     *
     * Zoom provides duration in minutes, convert to seconds
     *
     * @param array $participant Zoom participant record
     * @return int Duration in seconds
     */
    public function extract_duration($participant) {
        if (isset($participant['duration'])) {
            // Zoom duration is in minutes, convert to seconds
            return intval($participant['duration']) * 60;
        }
        return 0;
    }

    /**
     * Extract join and leave times from Zoom participant record
     *
     * @param array $participant Zoom participant record
     * @return array
     */
    public function extract_times($participant) {
        $times = array(
            'join_time' => 0,
            'leave_time' => 0
        );

        // Zoom provides join_time and leave_time in ISO 8601 format
        if (isset($participant['join_time'])) {
            $times['join_time'] = strtotime($participant['join_time']);
        }

        if (isset($participant['leave_time'])) {
            $times['leave_time'] = strtotime($participant['leave_time']);
        }

        return $times;
    }

    /**
     * Extract participant role from Zoom record
     *
     * @param array $participant Zoom participant record
     * @return string
     */
    public function extract_role($participant) {
        // Zoom provides user_name which can indicate role
        if (isset($participant['user_name'])) {
            // Zoom doesn't have explicit role field, default to Attendee
            return 'Attendee';
        }
        return 'Attendee';
    }

    /**
     * Get Zoom Server-to-Server OAuth access token
     *
     * @param string $client_id Zoom Client ID
     * @param string $client_secret Zoom Client Secret
     * @param string $account_id Zoom Account ID
     * @return string|null Access token if successful, null otherwise
     */
    private function get_zoom_access_token($client_id, $client_secret, $account_id) {
        $token_url = 'https://zoom.us/oauth/token';

        // Prepare credentials for OAuth
        $credentials = base64_encode("{$client_id}:{$client_secret}");

        $curl = new \curl();
        $curl->setHeader(array(
            "Authorization: Basic {$credentials}",
            "Content-Type: application/x-www-form-urlencoded"
        ));

        $post_data = array(
            'grant_type' => 'account_credentials',
            'account_id' => $account_id
        );

        $response = $curl->post($token_url, http_build_query($post_data));
        $data = json_decode($response, true);

        return isset($data['access_token']) ? $data['access_token'] : null;
    }
}
