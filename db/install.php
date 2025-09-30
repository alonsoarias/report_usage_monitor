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

defined('MOODLE_INTERNAL') || die();

/**
 * Installation script for the report_usage_monitor plugin.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install function for report_usage_monitor.
 */
function xmldb_report_usage_monitor_install() {
    global $CFG;
    
    // Check if shell_exec is available and provide guidance.
    if (function_exists('shell_exec')) {
        // Try to auto-detect 'du' command on Linux systems.
        if (PHP_OS_FAMILY === 'Linux') {
            $pathtodu = trim(shell_exec('which du') ?? '');
            
            if (!empty($pathtodu) && file_exists($pathtodu) && is_executable($pathtodu)) {
                // Set the detected path.
                set_config('pathtodu', $pathtodu);
                mtrace(get_string('pathtodu_autodetected', 'report_usage_monitor', $pathtodu));
            } else {
                mtrace(get_string('pathtodurecommendation', 'report_usage_monitor'));
            }
        } else {
            mtrace(get_string('pathtodunote', 'report_usage_monitor'));
        }
    } else {
        mtrace(get_string('activateshellexec', 'report_usage_monitor'));
    }
}