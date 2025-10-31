# Meetings Attendance Plugin for Moodle

A unified Moodle activity module for tracking attendance across **Microsoft Teams** and **Zoom** meetings with exact participant identification and accurate completion rules.

## Overview

This plugin addresses the critical need for **certified attendance tracking** across modern video conferencing platforms. Unlike existing solutions that use fuzzy matching (name/email similarity), this plugin implements **exact email-based matching** to ensure attendance records are accurate and audit-compliant.

### Key Features

- **Multi-platform support**: Teams and Zoom meetings in a single activity
- **Exact participant matching**: Email-based identification (no fuzzy matching)
- **Manual assignment override**: Unassigned participants can be manually linked to Moodle users
- **Completion rules**: Configurable attendance thresholds for activity completion
- **Audit trail**: Complete history of attendance data with join/leave timestamps
- **Role tracking**: Records participant role in the meeting (Organizer, Presenter, Attendee)
- **Duration calculation**: Automatic percentage calculation based on meeting duration

## Requirements

- **Moodle**: 4.4+ (tested on 4.4 and 4.5)
- **PHP**: 7.4+
- **Database**: MySQL, PostgreSQL, or other Moodle-supported databases

### Platform-Specific Requirements

#### Microsoft Teams
- Azure AD authentication configured (`auth_oidc` plugin)
- Microsoft Graph API access
- Teams meeting organizer email
- Tenant ID configured in plugin settings

#### Zoom
- Zoom Business or Education account
- Server-to-Server OAuth app configured
- Zoom Client ID and Client Secret
- Meeting organizer email

## Installation

1. Extract the plugin to: `moodle/mod/meetingsattendance`
2. Visit Administration > Notifications to install the plugin
3. Configure API credentials for your platform(s):
   - **Teams**: Settings > Plugins > Authentication > OIDC
   - **Zoom**: Settings > Plugins > Mod > Meetings Attendance > Zoom Settings

## Configuration

### For Teams Meetings

1. Set up Azure AD OIDC integration
2. Configure Microsoft Graph API scopes:
   - `meeting:read:meeting:admin`
   - `onlineMeetings.ReadWrite.All`
3. Enter organizer email in activity form

### For Zoom Meetings

1. Create Server-to-Server OAuth app at https://marketplace.zoom.us
2. Configure required scopes:
   - `meeting:read`
   - `report:read:user:admin`
3. Enter Zoom meeting ID and organizer email in activity form

## Database Schema

### Tables

- **meetingsattendance**: Meeting session configuration
  - `platform`: 'teams' or 'zoom'
  - `meeting_url`, `meeting_id`: Platform-specific identifiers
  - `expected_duration`, `required_attendance`: Completion criteria
  - `status`: 'open' or 'closed' for register management

- **meetingsattendance_data**: Individual participant attendance records
  - `userid`: Moodle user ID (exact match via email)
  - `platform_user_id`: Platform-specific identifier
  - `attendance_duration`: Seconds present in meeting
  - `actual_attendance`: Calculated percentage
  - `manually_assigned`: Flag for manual overrides

- **meetingsattendance_reports**: Audit trail of attendance events
  - `join_time`, `leave_time`: Timestamps
  - `attendance_duration`: Calculated duration

## Usage

### Creating a Meeting Activity

1. Add activity > Meetings Attendance
2. Select platform (Teams or Zoom)
3. Enter meeting details:
   - Meeting URL/ID
   - Organizer email
   - Expected duration
   - Required attendance percentage
4. Configure completion rules
5. Save

### Fetching Attendance

1. View the activity
2. Click "Fetch Attendance" (requires API credentials)
3. System matches participants by email:
   - **Match found**: Record created with `manually_assigned = 0`
   - **No match**: Record marked as "Unassigned"

### Managing Unassigned Participants

1. Click "Manage Unassigned Participants"
2. Review platform-reported participants
3. Manually assign to Moodle users or skip
4. System updates record with `manually_assigned = 1`

### Viewing Reports

- **Participants report**: Lists all attendees with join/leave times
- **Completion status**: Shows who met attendance threshold
- **Export options**: CSV/Excel export of attendance data

## API Matching Logic

### Teams

```
1. Get attendees from Microsoft Graph API
2. For each attendee:
   - Extract email from attendee record
   - Query Moodle: SELECT userid FROM mdl_user WHERE email = ?
   - If found: Create/update attendance record with userid
   - If not found: Mark as unassigned
```

### Zoom

```
1. Get participants from Zoom Report API
2. For each participant:
   - Extract email from participant record
   - Query Moodle: SELECT userid FROM mdl_user WHERE email = ?
   - If found: Create/update attendance record with userid
   - If not found: Mark as unassigned
```

## Capabilities

- `mod/meetingsattendance:addinstance` - Create activity instances
- `mod/meetingsattendance:view` - View activity
- `mod/meetingsattendance:viewreports` - View attendance reports
- `mod/meetingsattendance:manageattendance` - Manage attendance records

## Troubleshooting

### No participants found after fetching

1. Check API credentials are correctly configured
2. Verify organizer email is correct
3. Ensure meeting occurred within date/time filters
4. Check platform API error logs in Moodle logs

### Participants marked as unassigned

1. Verify participant email matches exactly with Moodle user email
2. Check for case sensitivity issues
3. Use manual assignment feature to link participant

### Completion not triggering

1. Verify `completionattendance` is enabled in activity settings
2. Check `required_attendance` percentage threshold
3. Check `expected_duration` is set correctly
4. Review participant's `attendance_duration` vs threshold

## Development

### Architecture

The plugin uses a **platform adapter pattern** for extensibility:

- `platform_adapter.php`: Abstract interface
- `teams_adapter.php`: Teams API implementation
- `zoom_adapter.php`: Zoom API implementation

### Adding a new platform

1. Create `classes/platform/new_platform_adapter.php`
2. Implement `platform_adapter` interface
3. Add to platform selection in form
4. Update language strings

### Testing

Run unit tests:
```bash
php admin/tool/phpunit/cli/run.php --testcase=mod_meetingsattendance_*
```

## Version History

- **1.0.0** (Oct 2025): Initial release with Teams and Zoom support

## License

GNU General Public License v3 or later

See LICENSE file for details.

## Support

For issues, feature requests, or contributions:
- GitHub Issues: https://github.com/ccomincini/moodle-mod_meetingsattendance/issues
- Documentation: See /docs folder

## Credits

Developed for educational institutions requiring certified attendance tracking across modern video conferencing platforms.
