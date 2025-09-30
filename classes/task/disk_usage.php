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

namespace report_usage_monitor\task;

use report_usage_monitor\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to calculate disk usage.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class disk_usage extends \core\task\scheduled_task {
    
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('calculatediskusagetask', 'report_usage_monitor');
    }
    
    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        global $DB, $CFG;
        
        mtrace("Starting disk usage calculation...");
        
        // Calculate database size.
        $dbsize = helper::get_database_size();
        set_config('totalusagereadabledb', $dbsize, 'report_usage_monitor');
        
        // Calculate dataroot size.
        $datarootsize = helper::get_directory_size($CFG->dataroot);
        
        // Calculate dirroot size.
        $dirrootsize = helper::get_directory_size($CFG->dirroot);
        
        // Total file system usage.
        $totalusage = $datarootsize + $dirrootsize;
        set_config('totalusagereadable', $totalusage, 'report_usage_monitor');
        
        // Get configured quota and calculate percentage.
        $reportconfig = get_config('report_usage_monitor');
        $quotabytes = ((int)($reportconfig->disk_quota ?? 10) * 1024 * 1024 * 1024);
        $totaldiskusage = $totalusage + $dbsize;
        
        // Calculate and store percentage.
        $diskpercent = helper::calculate_percentage($totaldiskusage, $quotabytes);
        set_config('disk_percent', $diskpercent, 'report_usage_monitor');
        
        // Determine warning class.
        if ($diskpercent < 70) {
            $warningclass = 'bg-success';
        } else if ($diskpercent < 90) {
            $warningclass = 'bg-warning';
        } else {
            $warningclass = 'bg-danger';
        }
        set_config('disk_warning_class', $warningclass, 'report_usage_monitor');
        
        // Store formatted sizes.
        set_config('disk_usage_gb', round($totaldiskusage / (1024 * 1024 * 1024), 2), 'report_usage_monitor');
        set_config('quotadisk_gb', round($quotabytes / (1024 * 1024 * 1024), 2), 'report_usage_monitor');
        
        // Analyze disk usage by directory.
        $diranalysis = $this->analyze_disk_usage_by_directory();
        set_config('dir_analysis', json_encode($diranalysis), 'report_usage_monitor');
        
        // Get largest courses.
        $largestcourses = helper::get_largest_courses(5);
        $courses = [];
        foreach ($largestcourses as $course) {
            $courses[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'filesize' => $course->filesize,
                'percentage' => helper::calculate_percentage($course->filesize, $totaldiskusage),
                'totalsize' => $course->filesize,
                'backupsize' => 0,
                'backupcount' => 0
            ];
        }
        set_config('largest_courses', json_encode($courses), 'report_usage_monitor');
        
        // Store last execution timestamp.
        set_config('lastexecutioncalculate', time(), 'report_usage_monitor');
        set_config('lastexecutioncalculatedisk', time(), 'report_usage_monitor');
        
        // Log to history.
        $this->log_to_history($diskpercent, $totaldiskusage, $quotabytes);
        
        mtrace("Disk usage calculation completed. Total: " . helper::format_bytes($totaldiskusage) . 
               " (" . $diskpercent . "% of quota)");
        
        return true;
    }
    
    /**
     * Analyze disk usage by directory.
     *
     * @return array
     */
    private function analyze_disk_usage_by_directory() {
        global $CFG;
        
        $analysis = [];
        
        // Key directories to analyze.
        $directories = [
            'filedir' => $CFG->dataroot . '/filedir',
            'cache' => $CFG->dataroot . '/cache',
            'temp' => $CFG->dataroot . '/temp',
            'trashdir' => $CFG->dataroot . '/trashdir'
        ];
        
        $total = 0;
        foreach ($directories as $key => $path) {
            if (is_dir($path)) {
                $size = helper::get_directory_size($path);
                $analysis[$key] = $size;
                $total += $size;
            } else {
                $analysis[$key] = 0;
            }
        }
        
        // Get total dataroot size.
        $datarootsize = helper::get_directory_size($CFG->dataroot);
        
        // Calculate "others".
        $analysis['others'] = max(0, $datarootsize - $total);
        
        // Add database size.
        $analysis['database'] = helper::get_database_size();
        
        return $analysis;
    }
    
    /**
     * Log disk usage to history.
     *
     * @param float $percentage Current usage percentage
     * @param int $value Current usage in bytes
     * @param int $threshold Quota in bytes
     */
    private function log_to_history($percentage, $value, $threshold) {
        global $DB;
        
        if (!$DB->get_manager()->table_exists('report_usage_monitor_history')) {
            return;
        }
        
        $record = new \stdClass();
        $record->type = 'disk';
        $record->percentage = $percentage;
        $record->value = $value;
        $record->threshold = $threshold;
        $record->timecreated = time();
        
        try {
            $DB->insert_record('report_usage_monitor_history', $record);
        } catch (\Exception $e) {
            debugging('Failed to log disk usage to history: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}