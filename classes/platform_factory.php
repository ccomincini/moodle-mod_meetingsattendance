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
 * Meetings Attendance - Platform Factory
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_meetingsattendance;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/teams_adapter.php');
require_once(__DIR__ . '/zoom_adapter.php');

/**
 * Factory for creating platform adapter instances
 */
class platform_factory {

    /**
     * Create platform adapter for session
     *
     * @param stdClass $session Meeting session record
     * @return platform_adapter Appropriate adapter for the platform
     * @throws moodle_exception If platform is not recognized
     */
    public static function create_adapter(\stdClass $session) {
        if (empty($session->platform)) {
            throw new \moodle_exception('invaliddata', 'mod_meetingsattendance');
        }

        $platform = strtolower($session->platform);

        switch ($platform) {
            case 'teams':
                return new teams_adapter($session);
            case 'zoom':
                return new zoom_adapter($session);
            default:
                throw new \moodle_exception('invaliddata', 'mod_meetingsattendance');
        }
    }

    /**
     * Get list of supported platforms
     *
     * @return array Array of platform names
     */
    public static function get_supported_platforms() {
        return array('teams', 'zoom');
    }

    /**
     * Check if platform is supported
     *
     * @param string $platform Platform name
     * @return bool True if supported, false otherwise
     */
    public static function is_platform_supported($platform) {
        return in_array(strtolower($platform), self::get_supported_platforms());
    }
}
