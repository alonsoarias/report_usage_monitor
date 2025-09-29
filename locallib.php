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
 * Centralized library for Usage Monitor plugin - All functions consolidated
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main Usage Monitor Manager - Centralized functionality
 */
class usage_monitor_manager {
    
    /** @var array Static cache for configuration */
    private static $config_cache = null;
    
    /** @var array Static cache for calculations */
    private static $calculation_cache = [];
    
    /** @var int Cache TTL in seconds */
    const CACHE_TTL = 300; // 5 minutes
    
    /** @var string Cache prefix */
    const CACHE_PREFIX = 'usage_monitor_';

    /**
     * Get plugin configuration with enhanced caching
     *
     * @param string|null $name Specific config name
     * @return mixed Configuration value(s)
     */
    public static function get_config($name = null) {
        if (self::$config_cache === null) {
            self::$config_cache = get_config('report_usage_monitor');
            
            // Set intelligent defaults
            $defaults = [
                'disk_quota' => 10,
                'max_daily_users_threshold' => 100,
                'disk_warning_level' => 90,
                'users_warning_level' => 90,
                'data_retention_days' => 90,
                'email' => 'admin@example.com',
                'enable_api' => 1
            ];
            
            foreach ($defaults as $key => $default) {
                if (!isset(self::$config_cache->$key)) {
                    self::$config_cache->$key = $default;
                }
            }
        }
        
        return $name ? (self::$config_cache->$name ?? null) : self::$config_cache;
    }

    /**
     * Clear all caches
     */
    public static function clear_cache() {
        global $CFG;
        
        self::$config_cache = null;
        self::$calculation_cache = [];
        
        // Clear Moodle cache if available
        if (isset($CFG->cachedir)) {
            $cache_files = glob($CFG->cachedir . '/' . self::CACHE_PREFIX . '*');
            foreach ($cache_files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Get comprehensive usage statistics with intelligent caching
     *
     * @param bool $force_refresh Force cache refresh
     * @return stdClass Complete usage data
     */
    public static function get_usage_statistics($force_refresh = false) {
        $cache_key = 'complete_stats';
        
        if (!$force_refresh && isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < self::CACHE_TTL) {
                return $cached['data'];
            }
        }
        
        $stats = new stdClass();
        
        // Core statistics
        $stats->disk = self::get_disk_usage($force_refresh);
        $stats->users = self::get_user_usage($force_refresh);
        $stats->system = self::get_system_info();
        $stats->projections = self::calculate_projections($stats->disk, $stats->users);
        
        // Enhanced data
        $stats->directories = self::analyze_disk_directories();
        $stats->courses = self::get_largest_courses();
        $stats->history = self::get_usage_history();
        $stats->recommendations = self::generate_recommendations($stats);
        $stats->health_score = self::calculate_health_score($stats);
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $stats,
            'timestamp' => time()
        ];
        
        return $stats;
    }

    /**
     * Get disk usage with enhanced analysis
     *
     * @param bool $force_refresh Force refresh
     * @return stdClass Disk usage data
     */
    public static function get_disk_usage($force_refresh = false) {
        $cache_key = 'disk_usage';
        
        if (!$force_refresh && isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < self::CACHE_TTL) {
                return $cached['data'];
            }
        }
        
        $config = self::get_config();
        
        $usage = new stdClass();
        $usage->current_bytes = self::validate_numeric($config->totalusagereadable, 0) + 
                               self::validate_numeric($config->totalusagereadabledb, 0);
        $usage->quota_bytes = self::validate_numeric($config->disk_quota, 10) * 1024 * 1024 * 1024;
        $usage->percentage = $usage->quota_bytes > 0 ? ($usage->current_bytes / $usage->quota_bytes) * 100 : 0;
        $usage->current_readable = self::format_bytes($usage->current_bytes);
        $usage->quota_readable = self::format_bytes($usage->quota_bytes);
        $usage->available_bytes = max(0, $usage->quota_bytes - $usage->current_bytes);
        $usage->available_readable = self::format_bytes($usage->available_bytes);
        $usage->last_calculated = self::validate_timestamp($config->lastexecutioncalculate ?? time());
        $usage->warning_level = (float)($config->disk_warning_level ?? 90);
        $usage->warning_class = self::get_warning_class($usage->percentage, $usage->warning_level);
        $usage->trend = self::calculate_disk_trend();
        $usage->growth_rate = self::calculate_growth_rate('disk');
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $usage,
            'timestamp' => time()
        ];
        
        return $usage;
    }

    /**
     * Get user usage with enhanced metrics
     *
     * @param bool $force_refresh Force refresh
     * @return stdClass User usage data
     */
    public static function get_user_usage($force_refresh = false) {
        $cache_key = 'user_usage';
        
        if (!$force_refresh && isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < self::CACHE_TTL) {
                return $cached['data'];
            }
        }
        
        $config = self::get_config();
        
        $usage = new stdClass();
        $usage->current = self::validate_numeric($config->totalusersdaily, 0);
        $usage->threshold = self::validate_numeric($config->max_daily_users_threshold, 100);
        $usage->percentage = $usage->threshold > 0 ? ($usage->current / $usage->threshold) * 100 : 0;
        $usage->last_calculated = self::validate_timestamp($config->lastexecution ?? time());
        $usage->max_90_days = self::validate_numeric($config->max_userdaily_for_90_days_users, 0);
        $usage->max_90_days_date = self::validate_timestamp($config->max_userdaily_for_90_days_date ?? time());
        $usage->warning_level = (float)($config->users_warning_level ?? 90);
        $usage->warning_class = self::get_warning_class($usage->percentage, $usage->warning_level);
        $usage->trend = self::calculate_user_trend();
        $usage->growth_rate = self::calculate_growth_rate('users');
        $usage->peak_hours = self::get_peak_usage_hours();
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $usage,
            'timestamp' => time()
        ];
        
        return $usage;
    }

    /**
     * Get enhanced system information
     *
     * @return stdClass System info data
     */
    public static function get_system_info() {
        global $CFG, $DB, $SITE;
        
        $cache_key = 'system_info';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < 3600) { // Cache for 1 hour
                return $cached['data'];
            }
        }
        
        $info = new stdClass();
        $info->site_name = format_string($SITE->fullname);
        $info->site_shortname = format_string($SITE->shortname);
        $info->moodle_version = $CFG->version;
        $info->moodle_release = $CFG->release;
        $info->php_version = PHP_VERSION;
        $info->server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        
        // Database statistics
        $info->course_count = $DB->count_records('course') - 1; // Exclude site course
        $info->user_count = $DB->count_records('user', ['deleted' => 0]) - 1; // Exclude guest
        $info->active_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
        $info->suspended_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
        $info->total_files = $DB->count_records_select('files', 'filesize > ?', [0]);
        $info->backup_auto_max_kept = get_config('backup', 'backup_auto_max_kept') ?? 0;
        
        // Performance metrics
        $info->memory_limit = ini_get('memory_limit');
        $info->max_execution_time = ini_get('max_execution_time');
        $info->upload_max_filesize = ini_get('upload_max_filesize');
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $info,
            'timestamp' => time()
        ];
        
        return $info;
    }

    /**
     * Calculate intelligent projections
     *
     * @param stdClass $disk_usage Disk usage data
     * @param stdClass $user_usage User usage data
     * @return stdClass Projection data
     */
    public static function calculate_projections($disk_usage, $user_usage) {
        $projections = new stdClass();
        
        // Growth rates
        $projections->disk_growth_rate = $disk_usage->growth_rate;
        $projections->users_growth_rate = $user_usage->growth_rate;
        
        // Time to thresholds
        $projections->days_to_disk_threshold = self::project_threshold_date(
            $disk_usage->current_bytes,
            $disk_usage->quota_bytes * ($disk_usage->warning_level / 100),
            $projections->disk_growth_rate
        );
        
        $projections->days_to_users_threshold = self::project_threshold_date(
            $user_usage->current,
            $user_usage->threshold * ($user_usage->warning_level / 100),
            $projections->users_growth_rate
        );
        
        // Critical thresholds
        $projections->days_to_disk_critical = self::project_threshold_date(
            $disk_usage->current_bytes,
            $disk_usage->quota_bytes * 0.95,
            $projections->disk_growth_rate
        );
        
        $projections->days_to_users_critical = self::project_threshold_date(
            $user_usage->current,
            $user_usage->threshold,
            $projections->users_growth_rate
        );
        
        // Confidence levels
        $projections->disk_confidence = self::calculate_projection_confidence('disk');
        $projections->users_confidence = self::calculate_projection_confidence('users');
        
        return $projections;
    }

    /**
     * Analyze disk usage by directories with enhanced details
     *
     * @return array Directory analysis
     */
    public static function analyze_disk_directories() {
        global $CFG;
        
        $cache_key = 'directory_analysis';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < 1800) { // Cache for 30 minutes
                return $cached['data'];
            }
        }
        
        $config = self::get_config();
        $dir_analysis_json = $config->dir_analysis ?? '{}';
        $dir_analysis = json_decode($dir_analysis_json, true);
        
        if (empty($dir_analysis) || !is_array($dir_analysis)) {
            $dir_analysis = self::calculate_directory_sizes($CFG->dataroot);
        }
        
        // Add database size
        $dir_analysis['database'] = self::get_database_size();
        
        // Calculate percentages and trends
        $total_size = array_sum($dir_analysis);
        foreach ($dir_analysis as $key => $size) {
            $dir_analysis[$key] = [
                'bytes' => $size,
                'readable' => self::format_bytes($size),
                'percentage' => $total_size > 0 ? round(($size / $total_size) * 100, 2) : 0,
                'trend' => self::get_directory_trend($key)
            ];
        }
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $dir_analysis,
            'timestamp' => time()
        ];
        
        return $dir_analysis;
    }

    /**
     * Get largest courses with enhanced metrics
     *
     * @param int $limit Number of courses
     * @return array Course data
     */
    public static function get_largest_courses($limit = 10) {
        global $DB;
        
        $cache_key = "largest_courses_{$limit}";
        
        if (isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < 3600) { // Cache for 1 hour
                return $cached['data'];
            }
        }
        
        // Enhanced SQL query for course file sizes
        $sql = "SELECT c.id, c.fullname, c.shortname, c.category, c.timecreated, c.timemodified,
                       COALESCE(cf.filesize, 0) as filesize,
                       COALESCE(cb.backupsize, 0) as backupsize,
                       COALESCE(cf.filesize, 0) + COALESCE(cb.backupsize, 0) as totalsize,
                       COALESCE(cb.backupcount, 0) as backupcount,
                       COALESCE(ce.enrolled_users, 0) as enrolled_users
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
            $course->efficiency_score = self::calculate_course_efficiency($course);
            $course->last_activity = self::get_course_last_activity($course->id);
        }
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => array_values($courses),
            'timestamp' => time()
        ];
        
        return array_values($courses);
    }

    /**
     * Get usage history with enhanced analytics
     *
     * @param int $days Number of days
     * @return array History data
     */
    public static function get_usage_history($days = 30) {
        global $DB;
        
        $cache_key = "history_{$days}";
        
        if (isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < 1800) { // Cache for 30 minutes
                return $cached['data'];
            }
        }
        
        $time_threshold = time() - ($days * 86400);
        
        // Enhanced disk history with trends
        $disk_sql = "SELECT timecreated, value, percentage, threshold
                     FROM {report_usage_monitor_history} 
                     WHERE type = 'disk' AND timecreated > ? 
                     ORDER BY timecreated ASC";
        $disk_history = $DB->get_records_sql($disk_sql, [$time_threshold]);
        
        // Enhanced user history with daily breakdown
        $user_sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key,
                            COUNT(DISTINCT userid) as users,
                            AVG(timecreated) as avg_time
                     FROM {logstore_standard_log}
                     WHERE action = 'loggedin' AND timecreated > ?
                     GROUP BY DATE(FROM_UNIXTIME(timecreated))
                     ORDER BY date_key ASC";
        $user_history = $DB->get_records_sql($user_sql, [$time_threshold]);
        
        // Calculate trends and patterns
        $history = [
            'disk' => self::enhance_disk_history($disk_history),
            'users' => self::enhance_user_history($user_history),
            'patterns' => self::analyze_usage_patterns($disk_history, $user_history)
        ];
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $history,
            'timestamp' => time()
        ];
        
        return $history;
    }

    /**
     * Generate intelligent recommendations
     *
     * @param stdClass $stats Usage statistics
     * @return array Recommendations
     */
    public static function generate_recommendations($stats) {
        $recommendations = [];
        
        // Disk recommendations
        if ($stats->disk->percentage > 70) {
            $recommendations['disk'] = self::generate_disk_recommendations($stats);
        }
        
        // User recommendations
        if ($stats->users->percentage > 70) {
            $recommendations['users'] = self::generate_user_recommendations($stats);
        }
        
        // Performance recommendations
        $recommendations['performance'] = self::generate_performance_recommendations($stats);
        
        // Security recommendations
        $recommendations['security'] = self::generate_security_recommendations($stats);
        
        return $recommendations;
    }

    /**
     * Calculate overall health score
     *
     * @param stdClass $stats Usage statistics
     * @return array Health score data
     */
    public static function calculate_health_score($stats) {
        $scores = [];
        
        // Disk health (40% weight)
        $disk_score = max(0, 100 - $stats->disk->percentage);
        $scores['disk'] = ['score' => $disk_score, 'weight' => 0.4];
        
        // User load health (30% weight)
        $user_score = max(0, 100 - $stats->users->percentage);
        $scores['users'] = ['score' => $user_score, 'weight' => 0.3];
        
        // Growth trend health (20% weight)
        $growth_score = self::calculate_growth_health($stats);
        $scores['growth'] = ['score' => $growth_score, 'weight' => 0.2];
        
        // System health (10% weight)
        $system_score = self::calculate_system_health($stats);
        $scores['system'] = ['score' => $system_score, 'weight' => 0.1];
        
        // Calculate weighted average
        $total_score = 0;
        foreach ($scores as $component) {
            $total_score += $component['score'] * $component['weight'];
        }
        
        return [
            'overall' => round($total_score),
            'components' => $scores,
            'status' => self::get_health_status($total_score),
            'trend' => self::get_health_trend($stats)
        ];
    }

    /**
     * Enhanced user activity queries
     */
    public static function get_daily_users($days = 10) {
        global $DB;
        
        $today_start = strtotime('today midnight');
        $days_ago = strtotime("-{$days} days midnight");
        
        $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key,
                       COUNT(DISTINCT userid) as user_count,
                       COUNT(*) as total_actions,
                       AVG(timecreated) as avg_time
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated BETWEEN ? AND ?
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY date_key DESC";
        
        return $DB->get_records_sql($sql, [$days_ago, $today_start]);
    }

    public static function get_peak_usage_hours() {
        global $DB;
        
        $sql = "SELECT HOUR(FROM_UNIXTIME(timecreated)) as hour,
                       COUNT(DISTINCT userid) as user_count
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated > ?
                GROUP BY HOUR(FROM_UNIXTIME(timecreated))
                ORDER BY user_count DESC
                LIMIT 5";
        
        $week_ago = time() - (7 * 86400);
        return $DB->get_records_sql($sql, [$week_ago]);
    }

    /**
     * Enhanced notification system
     */
    public static function send_notification($type, $data) {
        global $CFG;
        
        $config = self::get_config();
        $email = $config->email;
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            debugging('Invalid email configuration for notifications', DEBUG_DEVELOPER);
            return false;
        }
        
        // Check notification frequency
        if (!self::should_send_notification($type, $data)) {
            return false;
        }
        
        $template_data = self::prepare_notification_data($type, $data);
        $result = self::send_email_notification($email, $type, $template_data);
        
        if ($result) {
            self::log_notification($type, $data);
        }
        
        return $result;
    }

    /**
     * API management functions
     */
    public static function update_thresholds($thresholds) {
        global $DB;
        
        $result = [
            'success' => true,
            'updated' => [],
            'errors' => []
        ];
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            foreach ($thresholds as $key => $value) {
                if (self::validate_threshold($key, $value)) {
                    set_config($key, $value, 'report_usage_monitor');
                    $result['updated'][] = $key;
                } else {
                    $result['errors'][] = "Invalid value for {$key}: {$value}";
                }
            }
            
            if (empty($result['errors'])) {
                $transaction->allow_commit();
                self::clear_cache();
            } else {
                $result['success'] = false;
                $transaction->rollback(new moodle_exception('invalid_thresholds', 'report_usage_monitor'));
            }
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Utility functions
     */
    
    /**
     * Validate and sanitize numeric values
     */
    private static function validate_numeric($value, $default = 0) {
        if (!is_numeric($value) || $value < 0) {
            debugging("Invalid numeric value: " . var_export($value, true), DEBUG_DEVELOPER);
            return $default;
        }
        return (int)$value;
    }

    /**
     * Validate timestamp
     */
    private static function validate_timestamp($timestamp) {
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            debugging('Invalid timestamp: ' . var_export($timestamp, true), DEBUG_DEVELOPER);
            return time();
        }
        return (int)$timestamp;
    }

    /**
     * Enhanced byte formatting
     */
    private static function format_bytes($bytes, $precision = 2) {
        if (!is_numeric($bytes) || $bytes < 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $base = log($bytes, 1024);
        $unit_index = min(floor($base), count($units) - 1);
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[$unit_index];
    }

    /**
     * Get warning class based on percentage
     */
    private static function get_warning_class($percentage, $warning_level) {
        $caution_level = max(70, $warning_level - 20);
        
        if ($percentage < $caution_level) {
            return 'success';
        } elseif ($percentage < $warning_level) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Calculate growth rate with enhanced algorithm
     */
    private static function calculate_growth_rate($type, $days = 30) {
        global $DB;
        
        if ($type === 'disk') {
            return self::calculate_disk_growth_rate($days);
        } else {
            return self::calculate_user_growth_rate($days);
        }
    }

    private static function calculate_disk_growth_rate($days) {
        global $DB;
        
        $sql = "SELECT MIN(timecreated) as start_time, MAX(timecreated) as end_time,
                       MIN(value) as start_size, MAX(value) as end_size
                FROM {report_usage_monitor_history}
                WHERE type = 'disk' AND timecreated > ?";
        
        $threshold = time() - ($days * 86400);
        $result = $DB->get_record_sql($sql, [$threshold]);
        
        if ($result && $result->start_size > 0 && $result->end_time > $result->start_time) {
            $time_diff = $result->end_time - $result->start_time;
            $size_diff = $result->end_size - $result->start_size;
            
            if ($time_diff > 0) {
                $daily_growth = ($size_diff / $result->start_size) / ($time_diff / 86400);
                return round($daily_growth * 30 * 100, 2); // Monthly percentage
            }
        }
        
        return 2.5; // Default conservative estimate
    }

    private static function calculate_user_growth_rate($days) {
        global $DB;
        
        $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key,
                       COUNT(DISTINCT userid) as users
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' AND timecreated > ?
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY date_key ASC";
        
        $threshold = time() - ($days * 86400);
        $records = $DB->get_records_sql($sql, [$threshold]);
        
        if (count($records) >= 7) {
            $values = array_values($records);
            $first_week = array_slice($values, 0, 7);
            $last_week = array_slice($values, -7);
            
            $first_avg = array_sum(array_column($first_week, 'users')) / 7;
            $last_avg = array_sum(array_column($last_week, 'users')) / 7;
            
            if ($first_avg > 0) {
                $growth = (($last_avg - $first_avg) / $first_avg) * 100;
                return round($growth * 4, 2); // Monthly estimate
            }
        }
        
        return 1.5; // Default conservative estimate
    }

    /**
     * Project when threshold will be reached
     */
    private static function project_threshold_date($current, $threshold, $growth_rate) {
        if ($current >= $threshold) {
            return -1; // Already exceeded
        }
        
        if ($growth_rate <= 0) {
            return PHP_INT_MAX; // Will never reach
        }
        
        $monthly_rate = $growth_rate / 100;
        $daily_rate = $monthly_rate / 30;
        
        if ($daily_rate < 0.000001) {
            return PHP_INT_MAX;
        }
        
        try {
            $ratio = $threshold / $current;
            $days = log($ratio) / log(1 + $daily_rate);
            
            return is_finite($days) ? max(1, ceil($days)) : PHP_INT_MAX;
        } catch (Exception $e) {
            debugging('Error in projection: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return PHP_INT_MAX;
        }
    }

    /**
     * Calculate directory sizes with optimization
     */
    private static function calculate_directory_sizes($dataroot) {
        global $CFG;
        
        $directories = [
            'filedir' => $dataroot . '/filedir',
            'cache' => $dataroot . '/cache',
            'temp' => $dataroot . '/temp',
            'sessions' => $dataroot . '/sessions',
            'trashdir' => $dataroot . '/trashdir'
        ];
        
        $sizes = [];
        $total_analyzed = 0;
        
        foreach ($directories as $key => $path) {
            if (is_dir($path)) {
                $size = self::get_directory_size($path);
                $sizes[$key] = $size;
                $total_analyzed += $size;
            } else {
                $sizes[$key] = 0;
            }
        }
        
        // Calculate others
        $total_dataroot = self::get_directory_size($dataroot);
        $sizes['others'] = max(0, $total_dataroot - $total_analyzed);
        
        return $sizes;
    }

    /**
     * Optimized directory size calculation
     */
    private static function get_directory_size($directory) {
        global $CFG;
        
        // Use system du command if available (much faster)
        if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
            $command = trim($CFG->pathtodu) . ' -sb ' . escapeshellarg($directory) . ' 2>/dev/null';
            $output = shell_exec($command);
            
            if ($output && preg_match('/^(\d+)/', $output, $matches)) {
                return (int)$matches[1];
            }
        }
        
        // Fallback to PHP calculation with optimization
        return self::calculate_directory_size_php($directory);
    }

    private static function calculate_directory_size_php($directory) {
        $size = 0;
        $count = 0;
        $max_files = 10000; // Limit for performance
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $count++;
                    
                    // Performance limit
                    if ($count > $max_files) {
                        // Estimate based on sample
                        $estimated_total = $size * (self::count_files_estimate($directory) / $count);
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

    private static function count_files_estimate($directory) {
        try {
            $output = shell_exec('find ' . escapeshellarg($directory) . ' -type f | wc -l 2>/dev/null');
            return $output ? (int)trim($output) : 1000;
        } catch (Exception $e) {
            return 1000; // Conservative estimate
        }
    }

    /**
     * Get database size with caching
     */
    private static function get_database_size() {
        global $DB, $CFG;
        
        $cache_key = 'database_size';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            $cached = self::$calculation_cache[$cache_key];
            if (time() - $cached['timestamp'] < 3600) { // Cache for 1 hour
                return $cached['data'];
            }
        }
        
        $size = 0;
        
        try {
            if ($CFG->dbtype === 'mysqli' || $CFG->dbtype === 'mariadb') {
                $sql = "SELECT SUM(DATA_LENGTH + INDEX_LENGTH) as size
                        FROM information_schema.TABLES
                        WHERE TABLE_SCHEMA = ?";
                $result = $DB->get_field_sql($sql, [$CFG->dbname]);
                $size = $result ? (int)$result : 0;
            } else {
                // For other database types, estimate based on table count
                $table_count = count($DB->get_tables());
                $size = $table_count * 1024 * 1024; // Rough estimate: 1MB per table
            }
        } catch (Exception $e) {
            debugging('Error getting database size: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        
        // Cache the result
        self::$calculation_cache[$cache_key] = [
            'data' => $size,
            'timestamp' => time()
        ];
        
        return $size;
    }

    /**
     * Enhanced recommendation generators
     */
    private static function generate_disk_recommendations($stats) {
        $recommendations = [];
        
        if ($stats->disk->percentage > 90) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Critical Disk Usage',
                'message' => 'Immediate action required to free disk space',
                'actions' => [
                    'Clean up old backup files',
                    'Remove unused course files',
                    'Clear system cache',
                    'Archive old courses'
                ]
            ];
        } elseif ($stats->disk->percentage > 80) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'High Disk Usage',
                'message' => 'Consider cleaning up files to prevent issues',
                'actions' => [
                    'Review largest courses',
                    'Optimize backup retention',
                    'Clean temporary files'
                ]
            ];
        }
        
        // Growth-based recommendations
        if ($stats->projections->days_to_disk_threshold < 30) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Disk Space Planning',
                'message' => "Disk threshold will be reached in {$stats->projections->days_to_disk_threshold} days",
                'actions' => [
                    'Plan for storage expansion',
                    'Implement automated cleanup',
                    'Review file retention policies'
                ]
            ];
        }
        
        return $recommendations;
    }

    private static function generate_user_recommendations($stats) {
        $recommendations = [];
        
        if ($stats->users->percentage > 90) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'High User Load',
                'message' => 'Consider increasing user limits or optimizing performance',
                'actions' => [
                    'Review user activity patterns',
                    'Optimize peak hour distribution',
                    'Consider load balancing'
                ]
            ];
        }
        
        return $recommendations;
    }

    private static function generate_performance_recommendations($stats) {
        $recommendations = [];
        
        // Based on system info
        $memory_limit = ini_get('memory_limit');
        if (self::parse_size($memory_limit) < 256 * 1024 * 1024) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Memory Optimization',
                'message' => 'Consider increasing PHP memory limit',
                'actions' => ['Increase memory_limit to at least 256M']
            ];
        }
        
        return $recommendations;
    }

    private static function generate_security_recommendations($stats) {
        $recommendations = [];
        
        // Check for old PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'PHP Version',
                'message' => 'Consider upgrading to PHP 8.0+ for better security and performance',
                'actions' => ['Plan PHP upgrade']
            ];
        }
        
        return $recommendations;
    }

    /**
     * Helper functions for enhanced features
     */
    private static function calculate_course_efficiency($course) {
        if ($course->enrolled_users == 0) return 0;
        
        $size_score = min(100, (1000000000 - $course->totalsize) / 10000000); // Penalty for large size
        $user_score = min(100, $course->enrolled_users * 2); // Bonus for more users
        
        return max(0, round(($size_score + $user_score) / 2));
    }

    private static function get_course_last_activity($course_id) {
        global $DB;
        
        $sql = "SELECT MAX(timecreated) as last_activity
                FROM {logstore_standard_log}
                WHERE courseid = ?";
        
        $result = $DB->get_field_sql($sql, [$course_id]);
        return $result ? (int)$result : 0;
    }

    private static function enhance_disk_history($history) {
        $enhanced = [];
        $previous = null;
        
        foreach ($history as $record) {
            $enhanced_record = (array)$record;
            
            if ($previous) {
                $enhanced_record['change'] = $record->value - $previous->value;
                $enhanced_record['change_percent'] = $previous->value > 0 
                    ? round((($record->value - $previous->value) / $previous->value) * 100, 2)
                    : 0;
            } else {
                $enhanced_record['change'] = 0;
                $enhanced_record['change_percent'] = 0;
            }
            
            $enhanced[] = $enhanced_record;
            $previous = $record;
        }
        
        return $enhanced;
    }

    private static function enhance_user_history($history) {
        $enhanced = [];
        
        foreach ($history as $record) {
            $enhanced_record = (array)$record;
            $enhanced_record['day_of_week'] = date('w', $record->avg_time);
            $enhanced_record['formatted_date'] = date('Y-m-d', $record->avg_time);
            $enhanced[] = $enhanced_record;
        }
        
        return $enhanced;
    }

    private static function analyze_usage_patterns($disk_history, $user_history) {
        return [
            'peak_disk_day' => self::find_peak_day($disk_history, 'value'),
            'peak_user_day' => self::find_peak_day($user_history, 'users'),
            'growth_correlation' => self::calculate_correlation($disk_history, $user_history)
        ];
    }

    private static function find_peak_day($history, $field) {
        if (empty($history)) return null;
        
        $max_value = 0;
        $peak_day = null;
        
        foreach ($history as $record) {
            if (isset($record->$field) && $record->$field > $max_value) {
                $max_value = $record->$field;
                $peak_day = $record;
            }
        }
        
        return $peak_day;
    }

    private static function calculate_correlation($disk_history, $user_history) {
        // Simplified correlation calculation
        if (count($disk_history) < 2 || count($user_history) < 2) {
            return 0;
        }
        
        // This would need more sophisticated implementation for real correlation
        return 0.5; // Placeholder
    }

    private static function calculate_growth_health($stats) {
        $disk_growth = $stats->disk->growth_rate;
        $user_growth = $stats->users->growth_rate;
        
        // Healthy growth is moderate (2-5% monthly)
        $disk_score = $disk_growth < 2 ? 100 : ($disk_growth > 10 ? 50 : 100 - ($disk_growth - 2) * 5);
        $user_score = $user_growth < 2 ? 100 : ($user_growth > 8 ? 60 : 100 - ($user_growth - 2) * 4);
        
        return round(($disk_score + $user_score) / 2);
    }

    private static function calculate_system_health($stats) {
        $score = 100;
        
        // Deduct points for various issues
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $score -= 20;
        }
        
        if (self::parse_size(ini_get('memory_limit')) < 256 * 1024 * 1024) {
            $score -= 15;
        }
        
        return max(0, $score);
    }

    private static function get_health_status($score) {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'fair';
        if ($score >= 60) return 'poor';
        return 'critical';
    }

    private static function get_health_trend($stats) {
        // Simplified trend calculation based on growth rates
        $avg_growth = ($stats->disk->growth_rate + $stats->users->growth_rate) / 2;
        
        if ($avg_growth < 2) return 'stable';
        if ($avg_growth < 5) return 'growing';
        return 'rapid_growth';
    }

    private static function parse_size($size_str) {
        $size_str = trim($size_str);
        $last = strtolower($size_str[strlen($size_str) - 1]);
        $size = (int)$size_str;
        
        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        
        return $size;
    }

    private static function should_send_notification($type, $data) {
        $last_sent = get_config('report_usage_monitor', "last_notification_{$type}_time") ?: 0;
        $interval = self::get_notification_interval($type, $data);
        
        return (time() - $last_sent) >= $interval;
    }

    private static function get_notification_interval($type, $data) {
        // Dynamic intervals based on severity
        if ($type === 'disk') {
            $percentage = $data['percentage'] ?? 0;
            if ($percentage > 95) return 6 * 3600;  // 6 hours
            if ($percentage > 90) return 12 * 3600; // 12 hours
            return 24 * 3600; // 24 hours
        }
        
        return 24 * 3600; // Default 24 hours
    }

    private static function prepare_notification_data($type, $data) {
        global $CFG, $SITE;
        
        $template_data = new stdClass();
        $template_data->sitename = format_string($SITE->fullname);
        $template_data->siteurl = $CFG->wwwroot;
        $template_data->timestamp = time();
        $template_data->type = $type;
        
        // Merge with provided data
        foreach ($data as $key => $value) {
            $template_data->$key = $value;
        }
        
        return $template_data;
    }

    private static function send_email_notification($email, $type, $data) {
        global $CFG;
        
        $subject = self::get_notification_subject($type, $data);
        $message = self::get_notification_message($type, $data);
        
        $user = self::create_email_user($email);
        $from = self::create_email_user($CFG->noreplyaddress, $CFG->supportname ?? 'System');
        
        return email_to_user($user, $from, $subject, strip_tags($message), $message);
    }

    private static function get_notification_subject($type, $data) {
        switch ($type) {
            case 'disk':
                return "Disk Usage Alert - {$data->sitename}";
            case 'users':
                return "User Limit Alert - {$data->sitename}";
            default:
                return "System Alert - {$data->sitename}";
        }
    }

    private static function get_notification_message($type, $data) {
        // Enhanced HTML email templates
        $template = self::get_email_template($type);
        return self::render_email_template($template, $data);
    }

    private static function get_email_template($type) {
        // Return enhanced HTML email templates
        $templates = [
            'disk' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: #f44336; color: white; padding: 20px; text-align: center;">
                        <h1>Disk Usage Alert</h1>
                    </div>
                    <div style="padding: 20px; background: #f9f9f9;">
                        <h2>Alert Details</h2>
                        <p><strong>Site:</strong> {{sitename}}</p>
                        <p><strong>Current Usage:</strong> {{current_usage}}</p>
                        <p><strong>Percentage:</strong> {{percentage}}%</p>
                        <p><strong>Available Space:</strong> {{available_space}}</p>
                        
                        <h3>Recommended Actions</h3>
                        <ul>
                            <li>Clean up old backup files</li>
                            <li>Remove unused course content</li>
                            <li>Clear system cache</li>
                            <li>Archive old courses</li>
                        </ul>
                        
                        <p><a href="{{siteurl}}/report/usage_monitor/" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Dashboard</a></p>
                    </div>
                </div>
            ',
            'users' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: #ff9800; color: white; padding: 20px; text-align: center;">
                        <h1>User Limit Alert</h1>
                    </div>
                    <div style="padding: 20px; background: #f9f9f9;">
                        <h2>Alert Details</h2>
                        <p><strong>Site:</strong> {{sitename}}</p>
                        <p><strong>Current Users:</strong> {{current_users}}</p>
                        <p><strong>Threshold:</strong> {{threshold}}</p>
                        <p><strong>Percentage:</strong> {{percentage}}%</p>
                        
                        <p><a href="{{siteurl}}/report/usage_monitor/" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Dashboard</a></p>
                    </div>
                </div>
            '
        ];
        
        return $templates[$type] ?? $templates['disk'];
    }

    private static function render_email_template($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    private static function create_email_user($email, $name = '') {
        $user = new stdClass();
        $user->email = $email;
        $user->firstname = $name ?: 'System';
        $user->lastname = '';
        $user->maildisplay = true;
        $user->mailformat = 1;
        $user->id = -1;
        return $user;
    }

    private static function log_notification($type, $data) {
        global $DB;
        
        if (!$DB->get_manager()->table_exists('report_usage_monitor_history')) {
            return;
        }
        
        $record = new stdClass();
        $record->type = $type . '_notification';
        $record->percentage = $data['percentage'] ?? 0;
        $record->value = $data['value'] ?? 0;
        $record->threshold = $data['threshold'] ?? 0;
        $record->timecreated = time();
        
        try {
            $DB->insert_record('report_usage_monitor_history', $record);
            set_config("last_notification_{$type}_time", time(), 'report_usage_monitor');
        } catch (Exception $e) {
            debugging('Error logging notification: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    private static function validate_threshold($key, $value) {
        $valid_keys = [
            'max_daily_users_threshold',
            'disk_quota',
            'disk_warning_level',
            'users_warning_level'
        ];
        
        if (!in_array($key, $valid_keys)) {
            return false;
        }
        
        if (!is_numeric($value) || $value <= 0) {
            return false;
        }
        
        // Additional validation for percentage values
        if (in_array($key, ['disk_warning_level', 'users_warning_level'])) {
            return $value > 0 && $value <= 100;
        }
        
        return true;
    }

    /**
     * Additional helper methods for trends and analysis
     */
    private static function calculate_disk_trend() {
        // Simplified trend calculation - could be enhanced
        return 'stable'; // 'increasing', 'decreasing', 'stable'
    }

    private static function calculate_user_trend() {
        // Simplified trend calculation - could be enhanced
        return 'stable'; // 'increasing', 'decreasing', 'stable'
    }

    private static function get_directory_trend($directory) {
        // Placeholder for directory-specific trend analysis
        return 'stable';
    }

    private static function calculate_projection_confidence($type) {
        // Calculate confidence level based on data quality and consistency
        // This would analyze historical data variance
        return 75; // Percentage confidence
    }
}

/**
 * Legacy function wrappers for backward compatibility
 */

function display_size_in_gb($bytes, $precision = 2) {
    return usage_monitor_manager::format_bytes($bytes, $precision);
}

function calculate_threshold_percentage($current, $threshold) {
    if (!is_numeric($current) || !is_numeric($threshold) || $threshold <= 0) {
        return 0;
    }
    return ($current / $threshold) * 100;
}

/**
 * Simplified access functions for common operations
 */

function get_usage_statistics($force_refresh = false) {
    return usage_monitor_manager::get_usage_statistics($force_refresh);
}

function get_disk_usage($force_refresh = false) {
    return usage_monitor_manager::get_disk_usage($force_refresh);
}

function get_user_usage($force_refresh = false) {
    return usage_monitor_manager::get_user_usage($force_refresh);
}

function send_usage_notification($type, $data) {
    return usage_monitor_manager::send_notification($type, $data);
}

function update_usage_thresholds($thresholds) {
    return usage_monitor_manager::update_thresholds($thresholds);
}

function clear_usage_cache() {
    usage_monitor_manager::clear_cache();
}