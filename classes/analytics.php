<?php
// This file is part of Moodle - http://moodle.org/
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
 * Analytics and projection calculations
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

/**
 * Analytics class for growth calculations and projections
 */
class analytics {

    /**
     * Calculate growth rate for users or disk usage
     *
     * @param string $type Type of data ('users' or 'disk')
     * @param int $days Number of days to analyze
     * @return float Growth rate percentage
     */
    public static function calculate_growth_rate($type = 'users', $days = 30) {
        global $DB;
        
        if (!in_array($type, ['users', 'disk'])) {
            debugging('Invalid type for growth rate calculation: ' . $type, DEBUG_DEVELOPER);
            return 0;
        }
        
        if ($type === 'users') {
            return self::calculate_user_growth_rate($days);
        } else {
            return self::calculate_disk_growth_rate($days);
        }
    }

    /**
     * Calculate user growth rate
     *
     * @param int $days Number of days to analyze
     * @return float Growth rate percentage
     */
    private static function calculate_user_growth_rate($days) {
        global $DB;
        
        $sql = "SELECT 
                  (SELECT COUNT(DISTINCT userid) 
                   FROM {logstore_standard_log} 
                   WHERE action = 'loggedin' 
                     AND timecreated BETWEEN :start1 AND :end1) as first_day_users,
                  (SELECT COUNT(DISTINCT userid) 
                   FROM {logstore_standard_log} 
                   WHERE action = 'loggedin' 
                     AND timecreated BETWEEN :start2 AND :end2) as last_day_users";
        
        $now = time();
        $day_seconds = 86400;
        
        $params = [
            'start1' => $now - ($days * $day_seconds),
            'end1' => $now - (($days - 1) * $day_seconds),
            'start2' => $now - $day_seconds,
            'end2' => $now
        ];
        
        $result = $DB->get_record_sql($sql, $params);
        
        if (!$result || $result->first_day_users == 0) {
            return 0;
        }
        
        $growth_rate = (($result->last_day_users - $result->first_day_users) / $result->first_day_users) * 100;
        return round($growth_rate, 2);
    }

    /**
     * Calculate disk growth rate
     *
     * @param int $days Number of days to analyze
     * @return float Growth rate percentage
     */
    private static function calculate_disk_growth_rate($days) {
        global $DB;
        
        $sql = "SELECT MIN(timecreated) AS oldest_time, MAX(timecreated) AS newest_time, 
                       MIN(value) AS oldest_size, MAX(value) AS newest_size
                FROM {report_usage_monitor_history}
                WHERE type = 'disk' 
                AND timecreated > :time_threshold";
                
        $time_threshold = time() - ($days * 86400);
        $result = $DB->get_record_sql($sql, ['time_threshold' => $time_threshold]);
        
        if ($result && $result->oldest_time && $result->oldest_size > 0) {
            $time_diff = $result->newest_time - $result->oldest_time;
            $size_diff = $result->newest_size - $result->oldest_size;
            
            if ($time_diff > 0 && $size_diff != 0) {
                $days_diff = $time_diff / 86400;
                $daily_change = $size_diff / $days_diff;
                $daily_percent = ($daily_change / $result->oldest_size) * 100;
                $growth_rate = $daily_percent * 30;
                return round($growth_rate, 2);
            }
        }
        
        return 5; // Default 5% monthly growth
    }

    /**
     * Project when a limit will be reached
     *
     * @param int $current_value Current value
     * @param int $threshold_value Threshold to reach
     * @param float $growth_rate Growth rate percentage
     * @return int Days to reach threshold or special code
     */
    public static function project_limit_date($current_value, $threshold_value, $growth_rate) {
        if (!is_numeric($current_value) || !is_numeric($threshold_value) || !is_numeric($growth_rate)) {
            return null;
        }
        
        if ($current_value >= $threshold_value) {
            return -1; // Already exceeded
        }
        
        if ($growth_rate <= 0) {
            return PHP_INT_MAX; // Will never reach
        }
        
        $daily_growth_rate = ($growth_rate / 100) / 30;
        
        if ($daily_growth_rate < 0.000001) {
            return PHP_INT_MAX;
        }
        
        try {
            $ratio = $threshold_value / $current_value;
            $log_ratio = log($ratio);
            $log_growth = log(1 + $daily_growth_rate);
            
            if ($log_growth == 0) {
                return PHP_INT_MAX;
            }
            
            $days = $log_ratio / $log_growth;
            
            if (!is_finite($days)) {
                return PHP_INT_MAX;
            }
            
            return max(1, ceil($days));
        } catch (\Exception $e) {
            debugging('Error in projection calculation: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return PHP_INT_MAX;
        }
    }

    /**
     * Calculate threshold percentage
     *
     * @param int $current_value Current value
     * @param int $threshold Threshold value
     * @return float Percentage
     */
    public static function calculate_threshold_percentage($current_value, $threshold) {
        if (!is_numeric($current_value)) {
            debugging('Non-numeric current value: ' . var_export($current_value, true), DEBUG_DEVELOPER);
            $current_value = 0;
        }
        
        if (!is_numeric($threshold) || $threshold <= 0) {
            debugging('Invalid threshold: ' . var_export($threshold, true), DEBUG_DEVELOPER);
            return 0;
        }
        
        return ($current_value / $threshold) * 100;
    }
}