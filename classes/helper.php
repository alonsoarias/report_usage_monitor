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
 * Helper functions for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for report_usage_monitor.
 */
class helper {
    
    /**
     * Validate a timestamp.
     *
     * @param mixed $timestamp The timestamp to validate
     * @param int $default Default value if invalid
     * @return int Valid timestamp
     */
    public static function validate_timestamp($timestamp, $default = null) {
        if ($default === null) {
            $default = time();
        }
        
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return $default;
        }
        
        return (int)$timestamp;
    }
    
    /**
     * Get the start of day timestamp.
     *
     * @param int $timestamp Optional timestamp
     * @return int Timestamp at start of day
     */
    public static function get_day_start($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $timestamp = self::validate_timestamp($timestamp);
        return strtotime('midnight', $timestamp);
    }
    
    /**
     * Get the end of day timestamp.
     *
     * @param int $timestamp Optional timestamp
     * @return int Timestamp at end of day
     */
    public static function get_day_end($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $timestamp = self::validate_timestamp($timestamp);
        return strtotime('tomorrow', $timestamp) - 1;
    }
    
    /**
     * Calculate percentage safely avoiding division by zero.
     *
     * @param float $value The current value
     * @param float $total The total/threshold value
     * @param int $precision Decimal precision
     * @return float Percentage
     */
    public static function calculate_percentage($value, $total, $precision = 2) {
        if (!is_numeric($value)) {
            $value = 0;
        }
        
        if (!is_numeric($total) || $total <= 0) {
            return 0;
        }
        
        return round(($value / $total) * 100, $precision);
    }
    
    /**
     * Get directory size using best available method.
     *
     * @param string $directory Directory path
     * @return int Size in bytes
     */
    public static function get_directory_size($directory) {
        global $CFG;
        
        // Try using 'du' command if available.
        if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
            $escapeddir = escapeshellarg($directory);
            $command = trim($CFG->pathtodu) . ' -sk ' . $escapeddir . ' 2>/dev/null';
            
            if (PHP_OS_FAMILY === 'Linux') {
                // Use nice and ionice on Linux to reduce priority.
                $command = 'nice -n 19 ionice -c3 ' . $command;
            }
            
            $output = shell_exec($command);
            if ($output && preg_match('/^(\d+)/', $output, $matches)) {
                // Convert from KB to bytes.
                return (int)$matches[1] * 1024;
            }
        }
        
        // Fallback to recursive calculation.
        return get_directory_size($directory);
    }
    
    /**
     * Get users logged in during a specific time period.
     *
     * @param int $from Start timestamp
     * @param int $to End timestamp
     * @return int Number of unique users
     */
    public static function get_users_logged_in($from, $to) {
        global $DB;
        
        $from = self::validate_timestamp($from);
        $to = self::validate_timestamp($to);
        
        $sql = "SELECT COUNT(DISTINCT userid) 
                FROM {logstore_standard_log} 
                WHERE action = :action 
                  AND timecreated BETWEEN :from AND :to";
                  
        $params = [
            'action' => 'loggedin',
            'from' => $from,
            'to' => $to
        ];
        
        return (int)$DB->get_field_sql($sql, $params);
    }
    
    /**
     * Get database size.
     *
     * @return int Size in bytes
     */
    public static function get_database_size() {
        global $DB, $CFG;
        
        // This is database-agnostic using Moodle's API.
        $sql = "SELECT SUM(data_length + index_length) AS size
                FROM information_schema.tables
                WHERE table_schema = :dbname";
        
        try {
            $size = $DB->get_field_sql($sql, ['dbname' => $CFG->dbname]);
            return $size ?: 0;
        } catch (\Exception $e) {
            // Fallback method - count records and estimate.
            debugging('Could not get database size: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 0;
        }
    }
    
    /**
     * Get top courses by size.
     *
     * @param int $limit Number of courses to return
     * @return array Array of course objects with size information
     */
    public static function get_largest_courses($limit = 5) {
        global $DB;
        
        $sql = "SELECT c.id, c.fullname, c.shortname, 
                       COALESCE(SUM(f.filesize), 0) as filesize
                FROM {course} c
                LEFT JOIN {context} ctx ON (ctx.contextlevel = :contextlevel AND ctx.instanceid = c.id)
                LEFT JOIN {files} f ON f.contextid = ctx.id
                WHERE c.id != :siteid
                GROUP BY c.id, c.fullname, c.shortname
                ORDER BY filesize DESC";
        
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'siteid' => SITEID
        ];
        
        return $DB->get_records_sql($sql, $params, 0, $limit);
    }
    
    /**
     * Format bytes to human readable size.
     *
     * @param int $bytes Size in bytes
     * @param int $precision Decimal precision
     * @return string Formatted size
     */
    public static function format_bytes($bytes, $precision = 2) {
        if (!is_numeric($bytes) || $bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get the auto-detected path to 'du' command.
     *
     * @return string|null Path to du or null if not found
     */
    public static function auto_detect_du_path() {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }
        
        if (!function_exists('shell_exec')) {
            return null;
        }
        
        $path = trim(shell_exec('which du 2>/dev/null') ?? '');
        
        if (!empty($path) && file_exists($path) && is_executable($path)) {
            return $path;
        }
        
        // Try common locations.
        $common_paths = ['/usr/bin/du', '/bin/du'];
        foreach ($common_paths as $testpath) {
            if (file_exists($testpath) && is_executable($testpath)) {
                return $testpath;
            }
        }
        
        return null;
    }
}