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
 * Enhanced scheduled task for calculating disk usage - Uses centralized manager
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
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
            mtrace("Starting enhanced disk usage calculation task...");
        }

        $start_time = microtime(true);
        
        try {
            // Use centralized manager for configuration
            $config = usage_monitor_manager::get_config();
            
            // Calculate database size using centralized method
            $db_size = $this->get_database_size();
            set_config('totalusagereadabledb', $db_size, 'report_usage_monitor');

            // Calculate filesystem sizes using optimized methods
            $dataroot_size = $this->get_directory_size($CFG->dataroot);
            $dirroot_size = $this->get_directory_size($CFG->dirroot);
            $total_disk_usage = $dataroot_size + $dirroot_size;
            
            set_config('totalusagereadable', $total_disk_usage, 'report_usage_monitor');

            // Calculate enhanced metrics
            $quotadisk_bytes = ((int) $config->disk_quota * 1024) * 1024 * 1024;
            $total_usage_with_db = $total_disk_usage + $db_size;
            
            // Store precomputed values using centralized functions
            $disk_percent = calculate_threshold_percentage($total_usage_with_db, $quotadisk_bytes);
            $warning_level = (float)($config->disk_warning_level ?? 90);
            $warning_class = $this->get_warning_class($disk_percent, $warning_level);
            
            set_config('disk_percent', $disk_percent, 'report_usage_monitor');
            set_config('disk_warning_class', $warning_class, 'report_usage_monitor');
            set_config('disk_usage_gb', $this->format_size_gb($total_usage_with_db), 'report_usage_monitor');
            set_config('quotadisk_gb', $this->format_size_gb($quotadisk_bytes), 'report_usage_monitor');

            // Enhanced directory analysis
            $dir_analysis = $this->analyze_directories($CFG->dataroot);
            $dir_analysis['database'] = $db_size;
            set_config('dir_analysis', json_encode($dir_analysis), 'report_usage_monitor');

            // Get largest courses with enhanced data
            $largest_courses = $this->get_largest_courses(10);
            set_config('largest_courses', json_encode($largest_courses), 'report_usage_monitor');

            // Save execution timestamp
            $execution_time = time();
            set_config('lastexecutioncalculate', $execution_time, 'report_usage_monitor');

            // Record in history with enhanced data
            $this->record_history($disk_percent, $total_usage_with_db, $quotadisk_bytes, $execution_time);

            // Clear cache to ensure fresh data
            usage_monitor_manager::clear_cache();

            // Check for notifications
            $this->check_disk_notifications($disk_percent, $total_usage_with_db, $quotadisk_bytes);

            $execution_time_seconds = round(microtime(true) - $start_time, 2);
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Enhanced disk usage calculation completed in {$execution_time_seconds} seconds.");
                mtrace("Database: " . usage_monitor_manager::format_bytes($db_size));
                mtrace("Total: " . usage_monitor_manager::format_bytes($total_usage_with_db));
                mtrace("Percentage: {$disk_percent}%");
            }

        } catch (Exception $e) {
            mtrace("Error in disk usage calculation: " . $e->getMessage());
            debugging('Disk usage task error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }

        return true;
    }

    /**
     * Get database size with enhanced detection
     */
    private function get_database_size() {
        global $DB, $CFG;
        
        try {
            if ($CFG->dbtype === 'mysqli' || $CFG->dbtype === 'mariadb') {
                $sql = "SELECT SUM(DATA_LENGTH + INDEX_LENGTH) as size
                        FROM information_schema.TABLES
                        WHERE TABLE_SCHEMA = ?";
                $result = $DB->get_field_sql($sql, [$CFG->dbname]);
                return $result ? (int)$result : 0;
            } elseif ($CFG->dbtype === 'pgsql') {
                $sql = "SELECT pg_database_size(?) as size";
                $result = $DB->get_field_sql($sql, [$CFG->dbname]);
                return $result ? (int)$result : 0;
            } else {
                // Fallback estimation
                $table_count = count($DB->get_tables());
                return $table_count * 1024 * 1024; // 1MB per table estimate
            }
        } catch (Exception $e) {
            debugging('Error getting database size: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }

    /**
     * Enhanced directory size calculation
     */
    private function get_directory_size($directory) {
        global $CFG;
        
        // Use system du command if available (much faster)
        if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
            $command = trim($CFG->pathtodu) . ' -sb ' . escapeshellarg($directory) . ' 2>/dev/null';
            
            // Add nice/ionice for better system performance
            if (PHP_OS_FAMILY === 'Linux') {
                $command = 'nice -n 19 ionice -c3 ' . $command;
            }
            
            $output = shell_exec($command);
            if ($output && preg_match('/^(\d+)/', $output, $matches)) {
                return (int)$matches[1];
            }
        }
        
        // Fallback to PHP calculation with optimization
        return $this->calculate_directory_size_php($directory);
    }

    /**
     * Optimized PHP directory size calculation
     */
    private function calculate_directory_size_php($directory) {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $size = 0;
        $count = 0;
        $max_files = 50000; // Increased limit for better accuracy
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $count++;
                    
                    // Performance limit with estimation
                    if ($count > $max_files) {
                        $estimated_total = $this->estimate_total_size($directory, $size, $count);
                        return (int)$estimated_total;
                    }
                }
            }
        } catch (Exception $e) {
            debugging('Error calculating directory size: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
        
        return $size;
    }

    /**
     * Estimate total directory size based on sample
     */
    private function estimate_total_size($directory, $sample_size, $sample_count) {
        try {
            // Get total file count estimate
            $output = shell_exec('find ' . escapeshellarg($directory) . ' -type f | wc -l 2>/dev/null');
            $total_files = $output ? (int)trim($output) : $sample_count * 2;
            
            // Calculate average file size and estimate total
            $avg_file_size = $sample_count > 0 ? $sample_size / $sample_count : 1024;
            return $total_files * $avg_file_size;
        } catch (Exception $e) {
            // Conservative estimate
            return $sample_size * 2;
        }
    }

    /**
     * Enhanced directory analysis
     */
    private function analyze_directories($dataroot) {
        $directories = [
            'filedir' => $dataroot . '/filedir',
            'cache' => $dataroot . '/cache',
            'temp' => $dataroot . '/temp',
            'sessions' => $dataroot . '/sessions',
            'trashdir' => $dataroot . '/trashdir',
            'localcache' => $dataroot . '/localcache'
        ];
        
        $sizes = [];
        $total_analyzed = 0;
        
        foreach ($directories as $key => $path) {
            if (is_dir($path)) {
                $size = $this->get_directory_size($path);
                $sizes[$key] = $size;
                $total_analyzed += $size;
            } else {
                $sizes[$key] = 0;
            }
        }
        
        // Calculate others
        $total_dataroot = $this->get_directory_size($dataroot);
        $sizes['others'] = max(0, $total_dataroot - $total_analyzed);
        
        return $sizes;
    }

    /**
     * Enhanced largest courses calculation
     */
    private function get_largest_courses($limit = 10) {
        global $DB;
        
        try {
            // Enhanced SQL with more metrics
            $sql = "SELECT c.id, c.fullname, c.shortname, c.category, c.timecreated, c.timemodified,
                           COALESCE(cf.filesize, 0) as filesize,
                           COALESCE(cb.backupsize, 0) as backupsize,
                           COALESCE(cf.filesize, 0) + COALESCE(cb.backupsize, 0) as totalsize,
                           COALESCE(cb.backupcount, 0) as backupcount,
                           COALESCE(ce.enrolled_users, 0) as enrolled_users,
                           COALESCE(ca.last_activity, 0) as last_activity
                    FROM {course} c
                    LEFT JOIN (
                        SELECT ctx.instanceid as courseid, SUM(f.filesize) as filesize
                        FROM {context} ctx
                        JOIN {files} f ON f.contextid = ctx.id
                        WHERE ctx.contextlevel = " . CONTEXT_COURSE . "
                          AND f.filesize > 0
                          AND f.component != 'backup'
                        GROUP BY ctx.instanceid
                    ) cf ON cf.courseid = c.id
                    LEFT JOIN (
                        SELECT ctx.instanceid as courseid, 
                               SUM(f.filesize) as backupsize,
                               COUNT(f.id) as backupcount
                        FROM {context} ctx
                        JOIN {files} f ON f.contextid = ctx.id
                        WHERE ctx.contextlevel = " . CONTEXT_COURSE . "
                          AND f.component = 'backup'
                          AND f.filesize > 0
                        GROUP BY ctx.instanceid
                    ) cb ON cb.courseid = c.id
                    LEFT JOIN (
                        SELECT e.courseid, COUNT(DISTINCT ue.userid) as enrolled_users
                        FROM {enrol} e
                        JOIN {user_enrolments} ue ON ue.enrolid = e.id
                        WHERE ue.status = 0
                        GROUP BY e.courseid
                    ) ce ON ce.courseid = c.id
                    LEFT JOIN (
                        SELECT courseid, MAX(timecreated) as last_activity
                        FROM {logstore_standard_log}
                        WHERE courseid > 1
                        GROUP BY courseid
                    ) ca ON ca.courseid = c.id
                    WHERE c.id != :siteid
                    ORDER BY totalsize DESC";
            
            $courses = $DB->get_records_sql($sql, ['siteid' => SITEID], 0, $limit);
            
            // Calculate additional metrics
            $total_files_size = $DB->get_field_sql("SELECT SUM(filesize) FROM {files} WHERE filesize > 0");
            
            foreach ($courses as $course) {
                $course->percentage = $total_files_size > 0 
                    ? round(($course->totalsize / $total_files_size) * 100, 2) 
                    : 0;
                $course->size_per_user = $course->enrolled_users > 0 
                    ? round($course->totalsize / $course->enrolled_users) 
                    : 0;
                $course->efficiency_score = $this->calculate_efficiency_score($course);
            }
            
            return array_values($courses);
            
        } catch (Exception $e) {
            debugging('Error getting largest courses: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    /**
     * Calculate course efficiency score
     */
    private function calculate_efficiency_score($course) {
        if ($course->enrolled_users == 0) return 0;
        
        // Score based on size per user and activity
        $size_score = min(100, max(0, 100 - ($course->totalsize / (100 * 1024 * 1024)))); // Penalty for >100MB
        $user_score = min(100, $course->enrolled_users * 2); // Bonus for more users
        $activity_score = $course->last_activity > (time() - 30 * 86400) ? 20 : 0; // Recent activity bonus
        
        return max(0, round(($size_score + $user_score + $activity_score) / 3));
    }

    /**
     * Record enhanced history data
     */
    private function record_history($percentage, $usage, $quota, $timestamp) {
        global $DB;
        
        if (!$DB->get_manager()->table_exists('report_usage_monitor_history')) {
            return;
        }
        
        $record = new \stdClass();
        $record->type = 'disk';
        $record->percentage = $percentage;
        $record->value = $usage;
        $record->threshold = $quota;
        $record->timecreated = $timestamp;
        
        try {
            $DB->insert_record('report_usage_monitor_history', $record);
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Disk usage recorded in history: {$percentage}%");
            }
        } catch (Exception $e) {
            debugging('Error recording disk usage history: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Check and send notifications if needed
     */
    private function check_disk_notifications($percentage, $usage, $quota) {
        $config = usage_monitor_manager::get_config();
        $warning_level = (float)($config->disk_warning_level ?? 90);
        
        if ($percentage >= $warning_level) {
            $notification_data = [
                'percentage' => $percentage,
                'current_usage' => usage_monitor_manager::format_bytes($usage),
                'quota' => usage_monitor_manager::format_bytes($quota),
                'value' => $usage,
                'threshold' => $quota
            ];
            
            try {
                usage_monitor_manager::send_notification('disk', $notification_data);
            } catch (Exception $e) {
                debugging('Error sending disk notification: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Helper methods
     */
    private function get_warning_class($percentage, $warning_level) {
        $caution_level = max(70, $warning_level - 20);
        
        if ($percentage < $caution_level) {
            return 'success';
        } elseif ($percentage < $warning_level) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    private function format_size_gb($bytes) {
        return round($bytes / (1024 * 1024 * 1024), 2);
    }
}