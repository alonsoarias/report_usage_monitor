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
 * Usage Monitor Manager - Central business logic handler
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

use stdClass;
use moodle_exception;

/**
 * Central manager class for usage monitoring operations
 */
class manager {

    /** @var array Cache for configuration values */
    private static $config_cache = [];

    /** @var array Cache for calculated values */
    private static $calculation_cache = [];

    /**
     * Get plugin configuration with caching
     *
     * @param string|null $name Specific config name or null for all
     * @return mixed Configuration value(s)
     */
    public static function get_config($name = null) {
        if (empty(self::$config_cache)) {
            self::$config_cache = get_config('report_usage_monitor');
        }
        
        return $name ? (self::$config_cache->$name ?? null) : self::$config_cache;
    }

    /**
     * Get current disk usage statistics
     *
     * @return stdClass Disk usage data
     */
    public static function get_disk_usage() {
        $cache_key = 'disk_usage';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            return self::$calculation_cache[$cache_key];
        }

        $config = self::get_config();
        
        $usage = new stdClass();
        $usage->current_bytes = ((int)($config->totalusagereadable ?? 0)) + ((int)($config->totalusagereadabledb ?? 0));
        $usage->quota_bytes = ((int)($config->disk_quota ?? 0)) * 1024 * 1024 * 1024;
        $usage->percentage = $usage->quota_bytes > 0 ? ($usage->current_bytes / $usage->quota_bytes) * 100 : 0;
        $usage->current_readable = display_size($usage->current_bytes);
        $usage->quota_readable = display_size($usage->quota_bytes);
        $usage->last_calculated = $config->lastexecutioncalculate ?? time();
        
        // Validate timestamp
        if (!is_numeric($usage->last_calculated) || $usage->last_calculated <= 0) {
            $usage->last_calculated = time();
        }

        self::$calculation_cache[$cache_key] = $usage;
        return $usage;
    }

    /**
     * Get current user usage statistics
     *
     * @return stdClass User usage data
     */
    public static function get_user_usage() {
        $cache_key = 'user_usage';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            return self::$calculation_cache[$cache_key];
        }

        $config = self::get_config();
        
        $usage = new stdClass();
        $usage->current = (int)($config->totalusersdaily ?? 0);
        $usage->threshold = (int)($config->max_daily_users_threshold ?? 100);
        $usage->percentage = $usage->threshold > 0 ? ($usage->current / $usage->threshold) * 100 : 0;
        $usage->last_calculated = $config->lastexecution ?? time();
        $usage->max_90_days = (int)($config->max_userdaily_for_90_days_users ?? 0);
        $usage->max_90_days_date = $config->max_userdaily_for_90_days_date ?? time();
        
        // Validate timestamps
        if (!is_numeric($usage->last_calculated) || $usage->last_calculated <= 0) {
            $usage->last_calculated = time();
        }
        if (!is_numeric($usage->max_90_days_date) || $usage->max_90_days_date <= 0) {
            $usage->max_90_days_date = time();
        }

        self::$calculation_cache[$cache_key] = $usage;
        return $usage;
    }

    /**
     * Get growth projections
     *
     * @return stdClass Growth projection data
     */
    public static function get_projections() {
        $cache_key = 'projections';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            return self::$calculation_cache[$cache_key];
        }

        $disk_usage = self::get_disk_usage();
        $user_usage = self::get_user_usage();
        
        $projections = new stdClass();
        $projections->disk_growth_rate = analytics::calculate_growth_rate('disk');
        $projections->users_growth_rate = analytics::calculate_growth_rate('users');
        $projections->days_to_disk_threshold = analytics::project_limit_date(
            $disk_usage->current_bytes,
            $disk_usage->quota_bytes * 0.9,
            $projections->disk_growth_rate
        );
        $projections->days_to_users_threshold = analytics::project_limit_date(
            $user_usage->current,
            $user_usage->threshold * 0.9,
            $projections->users_growth_rate
        );

        self::$calculation_cache[$cache_key] = $projections;
        return $projections;
    }

    /**
     * Get directory analysis
     *
     * @return array Directory usage breakdown
     */
    public static function get_directory_analysis() {
        $config = self::get_config();
        $dir_analysis_json = $config->dir_analysis ?? '{}';
        $dir_analysis = json_decode($dir_analysis_json, true);
        
        if (empty($dir_analysis) || !is_array($dir_analysis)) {
            global $CFG;
            $dir_analysis = disk_analyzer::analyze_disk_usage_by_directory($CFG->dataroot);
        }
        
        return $dir_analysis;
    }

    /**
     * Get largest courses
     *
     * @param int $limit Number of courses to return
     * @return array Course data
     */
    public static function get_largest_courses($limit = 5) {
        $config = self::get_config();
        $largest_courses_json = $config->largest_courses ?? '[]';
        $largest_courses = json_decode($largest_courses_json);
        
        if (empty($largest_courses)) {
            $largest_courses = disk_analyzer::get_largest_courses($limit);
        }
        
        return $largest_courses;
    }

    /**
     * Update configuration thresholds
     *
     * @param array $thresholds Array of threshold values
     * @return array Result of update operation
     */
    public static function update_thresholds($thresholds) {
        global $DB;
        
        $result = [
            'success' => true,
            'user_threshold_updated' => false,
            'disk_threshold_updated' => false,
            'messages' => []
        ];
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            if (isset($thresholds['user_threshold'])) {
                if ($thresholds['user_threshold'] > 0) {
                    set_config('max_daily_users_threshold', $thresholds['user_threshold'], 'report_usage_monitor');
                    $result['user_threshold_updated'] = true;
                    $result['messages'][] = get_string('user_threshold_updated', 'report_usage_monitor');
                    
                    // Clear cache
                    self::$config_cache = [];
                    self::$calculation_cache = [];
                } else {
                    $result['success'] = false;
                    $result['messages'][] = get_string('error_user_threshold_negative', 'report_usage_monitor');
                }
            }
            
            if (isset($thresholds['disk_threshold'])) {
                if ($thresholds['disk_threshold'] > 0) {
                    set_config('disk_quota', $thresholds['disk_threshold'], 'report_usage_monitor');
                    $result['disk_threshold_updated'] = true;
                    $result['messages'][] = get_string('disk_threshold_updated', 'report_usage_monitor');
                    
                    // Clear cache
                    self::$config_cache = [];
                    self::$calculation_cache = [];
                } else {
                    $result['success'] = false;
                    $result['messages'][] = get_string('error_disk_threshold_negative', 'report_usage_monitor');
                }
            }
            
            if (!isset($thresholds['user_threshold']) && !isset($thresholds['disk_threshold'])) {
                $result['success'] = false;
                $result['messages'][] = get_string('error_no_thresholds_provided', 'report_usage_monitor');
            }
            
            if ($result['success']) {
                $transaction->allow_commit();
            } else {
                $transaction->rollback(new moodle_exception('thresholds_update_failed', 'report_usage_monitor'));
            }
        } catch (\Exception $e) {
            $transaction->rollback($e);
            $result['success'] = false;
            $result['messages'][] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Clear all caches
     */
    public static function clear_cache() {
        self::$config_cache = [];
        self::$calculation_cache = [];
    }
}