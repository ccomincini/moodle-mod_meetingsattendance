<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_meetingsattendance_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025103001) {
        upgrade_mod_savepoint(true, 2025103001, 'meetingsattendance');
    }

    return true;
}
