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
 * Scheduled task for calculating disk usage.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

class disk_usage extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('calculatediskusagetask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting disk usage calculation task...");
        }

        // Calculate database size
        $db_size = usage_monitor_disk_analyzer::get_database_size();
        set_config('totalusagereadabledb', $db_size, 'report_usage_monitor');

        // Calculate dataroot and dirroot sizes
        $dataroot_size = usage_monitor_disk_analyzer::directory_size($CFG->dataroot);
        $dirroot_size = usage_monitor_disk_analyzer::directory_size($CFG->dirroot);
        $total_disk_usage = $dataroot_size + $dirroot_size;
        
        set_config('totalusagereadable', $total_disk_usage, 'report_usage_monitor');

        // Get configuration and calculate percentages
        $config = usage_monitor_data_manager::get_config();
        $quotadisk_bytes = ((int) $config->disk_quota * 1024) * 1024 * 1024;
        $total_usage_with_db = $total_disk_usage + $db_size;
        
        // Calculate and store precomputed values
        $disk_percent = calculate_threshold_percentage($total_usage_with_db, $quotadisk_bytes);
        $warning_level = (float)($config->disk_warning_level ?? 90);
        $caution_level = max(70, $warning_level - 20);
        
        $warning_class = ($disk_percent < $caution_level) ? 'bg-success' : 
                        (($disk_percent < $warning_level) ? 'bg-warning' : 'bg-danger');
        
        set_config('disk_percent', $disk_percent, 'report_usage_monitor');
        set_config('disk_warning_class', $warning_class, 'report_usage_monitor');
        set_config('disk_usage_gb', display_size_in_gb($total_usage_with_db, 2), 'report_usage_monitor');
        set_config('quotadisk_gb', display_size_in_gb($quotadisk_bytes, 2), 'report_usage_monitor');

        // Analyze directories
        $dir_analysis = usage_monitor_disk_analyzer::analyze_disk_usage_by_directory($CFG->dataroot);
        $dir_analysis['database'] = $db_size;
        set_config('dir_analysis', json_encode($dir_analysis), 'report_usage_monitor');

        // Get largest courses
        $largest_courses = usage_monitor_disk_analyzer::get_largest_courses(5);
        set_config('largest_courses', json_encode($largest_courses), 'report_usage_monitor');

        // Save execution timestamp
        $execution_time = time();
        set_config('lastexecutioncalculate', $execution_time, 'report_usage_monitor');

        // Record in history
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'disk';
            $record->percentage = $disk_percent;
            $record->value = $total_usage_with_db;
            $record->threshold = $quotadisk_bytes;
            $record->timecreated = $execution_time;
            
            try {
                $DB->insert_record('report_usage_monitor_history', $record);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Disk usage recorded in history.");
                }
            } catch (\Exception $e) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Error recording disk usage: " . $e->getMessage());
                }
            }
        }

        // Clear cache
        usage_monitor_data_manager::clear_cache();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Disk usage calculated. Database: $db_size bytes, Total: $total_usage_with_db bytes.");
            mtrace("Directory analysis and largest courses saved.");
            mtrace("Disk usage calculation task completed.");
        }

        return true;
    }
}