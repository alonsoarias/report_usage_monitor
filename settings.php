<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration settings.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add the report to the reports menu.
    $ADMIN->add('reports', new admin_externalpage(
        'report_usage_monitor',
        get_string('pluginname', 'report_usage_monitor'),
        new moodle_url('/report/usage_monitor/index.php'),
        'report/usage_monitor:view'
    ));
}

if ($ADMIN->fulltree) {
    // Main settings section.
    $settings->add(new admin_setting_heading(
        'report_usage_monitor/mainsettings',
        get_string('mainsettings', 'report_usage_monitor'),
        ''
    ));
    
    // Maximum daily users threshold.
    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/max_daily_users_threshold',
        get_string('max_daily_users_threshold', 'report_usage_monitor'),
        get_string('configmax_daily_users_threshold', 'report_usage_monitor'),
        100,
        PARAM_INT
    ));

    // Disk quota in GB.
    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/disk_quota',
        get_string('disk_quota', 'report_usage_monitor'),
        get_string('configdisk_quota', 'report_usage_monitor'),
        10,
        PARAM_INT
    ));

    // Email for notifications.
    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/email',
        get_string('email', 'report_usage_monitor'),
        get_string('configemail', 'report_usage_monitor'),
        '',
        PARAM_EMAIL,
        50
    ));
    
    // Notification settings section.
    $settings->add(new admin_setting_heading(
        'report_usage_monitor/notificationsettings',
        get_string('notificationsettings', 'report_usage_monitor'),
        get_string('notificationsettingsinfo', 'report_usage_monitor')
    ));
    
    // Disk warning level options.
    $diskoptions = [
        70 => '70%',
        80 => '80%',
        90 => '90%',
        95 => '95%'
    ];
    
    $settings->add(new admin_setting_configselect(
        'report_usage_monitor/disk_warning_level',
        get_string('disk_warning_level', 'report_usage_monitor'),
        get_string('configdisk_warning_level', 'report_usage_monitor'),
        90,
        $diskoptions
    ));
    
    // User warning level options.
    $useroptions = [
        70 => '70%',
        80 => '80%',
        90 => '90%',
        95 => '95%'
    ];
    
    $settings->add(new admin_setting_configselect(
        'report_usage_monitor/users_warning_level',
        get_string('users_warning_level', 'report_usage_monitor'),
        get_string('configusers_warning_level', 'report_usage_monitor'),
        90,
        $useroptions
    ));
    
    // Path to 'du' command configuration.
    if (function_exists('shell_exec')) {
        $settings->add(new admin_setting_configexecutable(
            'pathtodu', 
            get_string('pathtodu', 'report_usage_monitor'),
            get_string('configpathtodu', 'report_usage_monitor'),
            '/usr/bin/du',
            PARAM_PATH,
            255
        ));
    }
    
    // Enable API.
    $settings->add(new admin_setting_configcheckbox(
        'report_usage_monitor/enable_api',
        get_string('enable_api', 'report_usage_monitor'),
        get_string('configenable_api', 'report_usage_monitor'),
        1
    ));
    
    // Credits.
    $settings->add(new admin_setting_heading(
        'report_usage_monitor/reportinfotext',
        '',
        get_string('reportinfotext', 'report_usage_monitor')
    ));
}