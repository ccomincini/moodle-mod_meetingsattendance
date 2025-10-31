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
 * Meetings Attendance - Plugin Settings
 *
 * @package    mod_meetingsattendance
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Create a new settings page under Plugins > Activity modules > Meetings Attendance
    $settings = new admin_settingpage('mod_meetingsattendance_settings',
        new lang_string('pluginname', 'mod_meetingsattendance'));

    // ===== TEAMS CONFIGURATION SECTION =====
    $settings->add(new admin_setting_heading('teams_settings',
        new lang_string('teams', 'mod_meetingsattendance'),
        new lang_string('teams_settings_help', 'mod_meetingsattendance')));

    // Teams Tenant ID
    $settings->add(new admin_setting_configtext('mod_meetingsattendance/teams_tenant_id',
        new lang_string('teams_tenant_id', 'mod_meetingsattendance'),
        new lang_string('teams_tenant_id_help', 'mod_meetingsattendance'),
        '',
        PARAM_ALPHANUMEXT));

    // Teams Info
    $settings->add(new admin_setting_description('mod_meetingsattendance/teams_info',
        new lang_string('teams_info', 'mod_meetingsattendance'),
        new lang_string('teams_info_text', 'mod_meetingsattendance')));

    // ===== ZOOM CONFIGURATION SECTION =====
    $settings->add(new admin_setting_heading('zoom_settings',
        new lang_string('zoom', 'mod_meetingsattendance'),
        new lang_string('zoom_settings_help', 'mod_meetingsattendance')));

    // Zoom Client ID
    $settings->add(new admin_setting_configtext('mod_meetingsattendance/zoom_client_id',
        new lang_string('zoom_client_id', 'mod_meetingsattendance'),
        new lang_string('zoom_client_id_help', 'mod_meetingsattendance'),
        '',
        PARAM_ALPHANUMEXT));

    // Zoom Client Secret
    $settings->add(new admin_setting_configpassword('mod_meetingsattendance/zoom_client_secret',
        new lang_string('zoom_client_secret', 'mod_meetingsattendance'),
        new lang_string('zoom_client_secret_help', 'mod_meetingsattendance'),
        ''));

    // Zoom Account ID
    $settings->add(new admin_setting_configtext('mod_meetingsattendance/zoom_account_id',
        new lang_string('zoom_account_id', 'mod_meetingsattendance'),
        new lang_string('zoom_account_id_help', 'mod_meetingsattendance'),
        '',
        PARAM_ALPHANUMEXT));

    // Zoom Info
    $settings->add(new admin_setting_description('mod_meetingsattendance/zoom_info',
        new lang_string('zoom_info', 'mod_meetingsattendance'),
        new lang_string('zoom_info_text', 'mod_meetingsattendance')));

    // ===== GENERAL SETTINGS SECTION =====
    $settings->add(new admin_setting_heading('general_settings',
        new lang_string('general', 'core'),
        new lang_string('general_settings_help', 'mod_meetingsattendance')));

    // Enable attendance report caching
    $settings->add(new admin_setting_configcheckbox('mod_meetingsattendance/enable_caching',
        new lang_string('enable_caching', 'mod_meetingsattendance'),
        new lang_string('enable_caching_help', 'mod_meetingsattendance'),
        1));

    // Cache duration (in hours)
    $settings->add(new admin_setting_configtext('mod_meetingsattendance/cache_duration',
        new lang_string('cache_duration', 'mod_meetingsattendance'),
        new lang_string('cache_duration_help', 'mod_meetingsattendance'),
        '24',
        PARAM_INT));

    // Add settings page to the admin menu
    $ADMIN->add('modsettings', $settings);
}

// Possibly add default settings or perform maintenance tasks here if needed
// when the plugin is being installed or upgraded.

