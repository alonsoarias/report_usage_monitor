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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Local functions for Usage Monitor plugin.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Core data manager class for usage monitoring
 */
class usage_monitor_data_manager {
    
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
     * Clear all caches
     */
    public static function clear_cache() {
        self::$config_cache = [];
        self::$calculation_cache = [];
    }
    
    /**
     * Get comprehensive usage statistics
     *
     * @return stdClass Complete usage data
     */
    public static function get_usage_statistics() {
        $cache_key = 'complete_stats';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            return self::$calculation_cache[$cache_key];
        }
        
        $stats = new stdClass();
        $stats->disk = self::get_disk_usage();
        $stats->users = self::get_user_usage();
        $stats->projections = self::get_projections();
        $stats->system = self::get_system_info();
        $stats->directories = self::get_directory_analysis();
        $stats->courses = self::get_largest_courses();
        $stats->history = self::get_usage_history();
        
        self::$calculation_cache[$cache_key] = $stats;
        return $stats;
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
        $usage->last_calculated = self::validate_timestamp($config->lastexecutioncalculate ?? time());
        $usage->warning_level = (float)($config->disk_warning_level ?? 90);
        $usage->warning_class = self::get_warning_class($usage->percentage, $usage->warning_level);
        
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
        $usage->last_calculated = self::validate_timestamp($config->lastexecution ?? time());
        $usage->max_90_days = (int)($config->max_userdaily_for_90_days_users ?? 0);
        $usage->max_90_days_date = self::validate_timestamp($config->max_userdaily_for_90_days_date ?? time());
        $usage->warning_level = (float)($config->users_warning_level ?? 90);
        $usage->warning_class = self::get_warning_class($usage->percentage, $usage->warning_level);
        
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
        $projections->disk_growth_rate = usage_monitor_analytics::calculate_growth_rate('disk');
        $projections->users_growth_rate = usage_monitor_analytics::calculate_growth_rate('users');
        $projections->days_to_disk_threshold = usage_monitor_analytics::project_limit_date(
            $disk_usage->current_bytes,
            $disk_usage->quota_bytes * 0.9,
            $projections->disk_growth_rate
        );
        $projections->days_to_users_threshold = usage_monitor_analytics::project_limit_date(
            $user_usage->current,
            $user_usage->threshold * 0.9,
            $projections->users_growth_rate
        );
        
        self::$calculation_cache[$cache_key] = $projections;
        return $projections;
    }
    
    /**
     * Get system information
     *
     * @return stdClass System info data
     */
    public static function get_system_info() {
        global $CFG, $DB, $SITE;
        
        $cache_key = 'system_info';
        
        if (isset(self::$calculation_cache[$cache_key])) {
            return self::$calculation_cache[$cache_key];
        }
        
        $info = new stdClass();
        $info->site_name = format_string($SITE->fullname);
        $info->site_shortname = format_string($SITE->shortname);
        $info->moodle_version = $CFG->version;
        $info->moodle_release = $CFG->release;
        $info->course_count = $DB->count_records('course');
        $info->user_count = $DB->count_records('user', ['deleted' => 0]) - 1;
        $info->active_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
        $info->suspended_users = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
        $info->backup_auto_max_kept = get_config('backup', 'backup_auto_max_kept') ?? 0;
        
        self::$calculation_cache[$cache_key] = $info;
        return $info;
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
            $dir_analysis = usage_monitor_disk_analyzer::analyze_disk_usage_by_directory($CFG->dataroot);
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
            $largest_courses = usage_monitor_disk_analyzer::get_largest_courses($limit);
        }
        
        return $largest_courses;
    }
    
    /**
     * Get usage history for charts
     *
     * @param int $days Number of days to retrieve
     * @return array History data
     */
    public static function get_usage_history($days = 30) {
        global $DB;
        
        $cache_key = "history_{$days}";
        
        if (isset(self::$calculation_cache[$cache_key])) {
            return self::$calculation_cache[$cache_key];
        }
        
        $time_threshold = time() - ($days * 86400);
        
        // Get disk history
        $disk_sql = "SELECT timecreated, value, percentage 
                     FROM {report_usage_monitor_history} 
                     WHERE type = 'disk' AND timecreated > ? 
                     ORDER BY timecreated ASC";
        $disk_history = $DB->get_records_sql($disk_sql, [$time_threshold]);
        
        // Get user history
        $user_sql = "SELECT (timecreated - (timecreated % 86400)) as date_key, 
                            COUNT(DISTINCT userid) as users
                     FROM {logstore_standard_log}
                     WHERE action = 'loggedin' AND timecreated > ?
                     GROUP BY date_key
                     ORDER BY date_key ASC";
        $user_history = $DB->get_records_sql($user_sql, [$time_threshold]);
        
        $history = [
            'disk' => $disk_history,
            'users' => $user_history
        ];
        
        self::$calculation_cache[$cache_key] = $history;
        return $history;
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
                    self::clear_cache();
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
                    self::clear_cache();
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
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result['success'] = false;
            $result['messages'][] = 'Error: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Validate timestamp and return valid value
     *
     * @param mixed $timestamp Timestamp to validate
     * @return int Valid timestamp
     */
    private static function validate_timestamp($timestamp) {
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            debugging('Invalid timestamp: ' . var_export($timestamp, true), DEBUG_DEVELOPER);
            return time();
        }
        return (int)$timestamp;
    }
    
    /**
     * Get warning class based on percentage and threshold
     *
     * @param float $percentage Current percentage
     * @param float $warning_level Warning threshold
     * @return string CSS class
     */
    private static function get_warning_class($percentage, $warning_level) {
        $caution_level = max(70, $warning_level - 20);
        
        if ($percentage < $caution_level) {
            return 'bg-success';
        } elseif ($percentage < $warning_level) {
            return 'bg-warning';
        } else {
            return 'bg-danger';
        }
    }
}

/**
 * Analytics and projection calculations
 */
class usage_monitor_analytics {
    
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
        } catch (Exception $e) {
            debugging('Error in projection calculation: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return PHP_INT_MAX;
        }
    }
}

/**
 * Disk analysis utilities
 */
class usage_monitor_disk_analyzer {
    
    /**
     * Analyze disk usage by directories
     *
     * @param string $rootdir Root directory to analyze
     * @return array Directory usage breakdown
     */
    public static function analyze_disk_usage_by_directory($rootdir) {
        global $CFG;
        
        $directories = [
            'filedir' => $rootdir . '/filedir',
            'cache' => $rootdir . '/cache',
        ];
        
        $usage = [];
        $total_analyzed = 0;
        
        foreach ($directories as $key => $dir) {
            if (is_dir($dir)) {
                $size = self::directory_size($dir);
                $usage[$key] = $size;
                $total_analyzed += $size;
            } else {
                $usage[$key] = 0;
            }
        }
        
        $total_size = self::directory_size($rootdir);
        $usage['others'] = max(0, $total_size - $total_analyzed);
        
        // Add database size
        $usage['database'] = self::get_database_size();
        
        return $usage;
    }
    
    /**
     * Get largest courses
     *
     * @param int $limit Number of courses to return
     * @return array Course data
     */
    public static function get_largest_courses($limit = 5) {
        global $DB;
        
        $filesql = self::get_course_filesize_sql();
        $sql = "SELECT c.id, c.fullname, c.shortname, c.category, rc.filesize
                FROM {course} c
                JOIN ($filesql) rc on rc.course = c.id
                WHERE c.id != :siteid
                ORDER BY rc.filesize DESC";
        
        $params = ['siteid' => SITEID];
        $courses = $DB->get_records_sql($sql, $params, 0, $limit);
        
        $backupsql = self::get_course_backupsize_sql();
        $backupsizes = $DB->get_records_sql($backupsql);
        
        $totalfilessize = $DB->get_field_sql("SELECT SUM(filesize) FROM {files} WHERE filesize > 0");
        
        foreach ($courses as $course) {
            $course->backupsize = isset($backupsizes[$course->id]) ? $backupsizes[$course->id]->filesize : 0;
            
            $course->backupcount = $DB->count_records_sql("
                SELECT COUNT(f.id)
                FROM {files} f
                JOIN {context} ctx ON f.contextid = ctx.id
                WHERE ctx.instanceid = :courseid
                  AND ctx.contextlevel = " . CONTEXT_COURSE . "
                  AND f.component = 'backup'
                  AND f.filearea = 'automated'
            ", ['courseid' => $course->id]);
            
            $course->percentage = $totalfilessize > 0
                ? round(($course->filesize / $totalfilessize) * 100, 2)
                : 0;
                
            $course->totalsize = $course->filesize + $course->backupsize;
        }
        
        return $courses;
    }
    
    /**
     * Calculate directory size
     *
     * @param string $rootdir Directory to analyze
     * @param string $excludefile File to exclude
     * @return int Size in bytes
     */
    public static function directory_size($rootdir, $excludefile = '') {
        global $CFG;
        
        if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
            $escapedRootdir = escapeshellarg($rootdir);
            $command = trim($CFG->pathtodu) . ' -Lsk ' . $escapedRootdir;
            
            if (PHP_OS === 'Linux') {
                $command = 'nice -n 19 ionice -c3 ' . $command;
            }
            
            if (!empty($excludefile)) {
                $escapedExcludefile = escapeshellarg($excludefile);
                $command .= ' --exclude=' . $escapedExcludefile;
            }
            
            $output = null;
            $return = null;
            exec($command, $output, $return);
            if (is_array($output) && isset($output[0])) {
                return intval($output[0]) * 1024;
            }
        }
        
        if (!is_dir($rootdir)) {
            return 0;
        }
        
        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootdir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && ($excludefile === '' || $file->getFilename() !== $excludefile)) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Get database size
     *
     * @return int Database size in bytes
     */
    private static function get_database_size() {
        global $DB, $CFG;
        
        $sql = "SELECT TABLE_SCHEMA AS `database_name`, 
                       ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)) AS size
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = '$CFG->dbname'";
        
        $result = $DB->get_records_sql($sql);
        foreach ($result as $item) {
            return $item->size;
        }
        
        return 0;
    }
    
    /**
     * Get SQL for calculating course file sizes
     *
     * @return string SQL query
     */
    private static function get_course_filesize_sql() {
        $sqlunion = "UNION ALL
                    SELECT c.id, f.filesize
                    FROM {block_instances} bi
                    JOIN {context} cx1 ON cx1.contextlevel = " . CONTEXT_BLOCK . " AND cx1.instanceid = bi.id
                    JOIN {context} cx2 ON cx2.contextlevel = " . CONTEXT_COURSE . " AND cx2.id = bi.parentcontextid
                    JOIN {course} c ON c.id = cx2.instanceid
                    JOIN {files} f ON f.contextid = cx1.id
                UNION ALL
                    SELECT c.id, f.filesize
                    FROM {course_modules} cm
                    JOIN {context} cx ON cx.contextlevel = " . CONTEXT_MODULE . " AND cx.instanceid = cm.id
                    JOIN {course} c ON c.id = cm.course
                    JOIN {files} f ON f.contextid = cx.id";
        
        return "SELECT id AS course, SUM(filesize) AS filesize
                FROM (SELECT c.id, f.filesize
                      FROM {course} c
                      JOIN {context} cx ON cx.contextlevel = " . CONTEXT_COURSE . " AND cx.instanceid = c.id
                      JOIN {files} f ON f.contextid = cx.id {$sqlunion}) x
                GROUP BY id";
    }
    
    /**
     * Get SQL for calculating course backup sizes
     *
     * @return string SQL query
     */
    private static function get_course_backupsize_sql() {
        return "SELECT id AS course, SUM(filesize) AS filesize
                FROM (SELECT c.id, f.filesize
                      FROM {course} c
                      JOIN {context} cx ON cx.contextlevel = " . CONTEXT_COURSE . " AND cx.instanceid = c.id
                      JOIN {files} f ON f.contextid = cx.id AND f.component = 'backup') x
                GROUP BY id";
    }
}

/**
 * User activity queries
 */
class usage_monitor_user_queries {
    
    /**
     * Get daily user statistics for last N days
     *
     * @param int $days Number of days
     * @return array User statistics
     */
    public static function get_daily_users($days = 10) {
        global $DB;
        
        $today_start = strtotime('today midnight');
        $days_ago = strtotime("-{$days} days midnight");
        $yesterday_end = $today_start - 1;
        
        $sql = "SELECT (timecreated - (timecreated % 86400)) as timestamp_fecha, 
                       COUNT(DISTINCT userid) as conteo_accesos_unicos
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated BETWEEN :start AND :end
                GROUP BY timestamp_fecha 
                ORDER BY timestamp_fecha DESC";
        
        return $DB->get_records_sql($sql, ['start' => $days_ago, 'end' => $yesterday_end]);
    }
    
    /**
     * Get top user days
     *
     * @return array Top user statistics
     */
    public static function get_top_user_days() {
        global $DB;
        
        $sql = "SELECT fecha as timestamp_fecha, cantidad_usuarios 
                FROM {report_usage_monitor}  
                ORDER BY cantidad_usuarios DESC, fecha DESC";
        
        return $DB->get_records_sql($sql);
    }
    
    /**
     * Get users for yesterday
     *
     * @return array Yesterday's user count
     */
    public static function get_yesterday_users() {
        global $DB;
        
        $yesterday_start = strtotime('yesterday midnight');
        $today_start = strtotime('today midnight');
        
        $sql = "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                       COUNT(DISTINCT userid) as conteo_accesos_unicos 
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated BETWEEN :start AND :end
                GROUP BY fecha";
        
        return $DB->get_records_sql($sql, ['start' => $yesterday_start, 'end' => $today_start]);
    }
    
    /**
     * Get users active today
     *
     * @return array Today's user count
     */
    public static function get_today_users() {
        global $DB;
        
        $one_day_ago = time() - 86400;
        
        $sql = "SELECT (lastaccess - (lastaccess % 86400)) as timestamp_fecha, 
                       COUNT(DISTINCT id) as conteo_accesos_unicos 
                FROM {user}
                WHERE lastaccess >= :threshold
                GROUP BY timestamp_fecha";
        
        return $DB->get_records_sql($sql, ['threshold' => $one_day_ago]);
    }
    
    /**
     * Get maximum users in last 90 days
     *
     * @return array Maximum user statistics
     */
    public static function get_max_users_90_days() {
        global $DB;
        
        $ninety_days_ago = time() - (90 * 86400);
        
        $sql = "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                       COUNT(DISTINCT userid) as usuarios 
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated >= :threshold
                GROUP BY fecha
                ORDER BY usuarios DESC 
                LIMIT 1";
        
        return $DB->get_records_sql($sql, ['threshold' => $ninety_days_ago]);
    }
    
    /**
     * Update top users record
     *
     * @param int $fecha Timestamp
     * @param int $usuarios User count
     * @param int $min Minimum value to replace
     */
    public static function update_min_top_record($fecha, $usuarios, $min) {
        global $DB;
        
        if (!is_numeric($fecha) || $fecha <= 0) {
            debugging('Invalid timestamp provided: ' . var_export($fecha, true), DEBUG_DEVELOPER);
            return;
        }
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            $sql = "SELECT fecha FROM {report_usage_monitor} 
                    WHERE cantidad_usuarios = ? 
                    ORDER BY fecha ASC LIMIT 1";
            $oldest_min_record = $DB->get_field_sql($sql, [$min]);
            
            if ($oldest_min_record) {
                $DB->execute(
                    "UPDATE {report_usage_monitor} 
                     SET fecha = ?, cantidad_usuarios = ? 
                     WHERE fecha = ?",
                    [$fecha, $usuarios, $oldest_min_record]
                );
            }
            
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('Error updating record: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
    
    /**
     * Insert new top users record
     *
     * @param int $fecha Timestamp
     * @param int $cantidad_usuarios User count
     */
    public static function insert_top_record($fecha, $cantidad_usuarios) {
        global $DB;
        
        if (!is_numeric($fecha) || $fecha <= 0) {
            debugging('Invalid timestamp provided: ' . var_export($fecha, true), DEBUG_DEVELOPER);
            return;
        }
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            $DB->execute(
                "INSERT INTO {report_usage_monitor} (fecha, cantidad_usuarios) 
                 VALUES (?, ?)",
                [$fecha, $cantidad_usuarios]
            );
            
            $count = $DB->count_records('report_usage_monitor');
            if ($count > 10) {
                $sql = "SELECT id FROM {report_usage_monitor} ORDER BY fecha ASC LIMIT " . ($count - 10);
                $records = $DB->get_records_sql($sql);
                
                if (!empty($records)) {
                    $ids = array_keys($records);
                    $DB->delete_records_list('report_usage_monitor', 'id', $ids);
                }
            }
            
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('Error inserting record: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

/**
 * Notification utilities
 */
class usage_monitor_notifications {
    
    /**
     * Send user limit notification
     *
     * @param int $numberofusers Number of users
     * @param int $fecha Date timestamp
     * @param float $percentage Percentage of threshold
     * @return bool Success status
     */
    public static function send_user_limit_notification($numberofusers, $fecha, $percentage) {
        global $CFG, $DB;
        
        if (!is_numeric($fecha)) {
            debugging('Invalid timestamp provided: ' . var_export($fecha, true), DEBUG_DEVELOPER);
            $fecha = time();
        }
        
        $site = get_site();
        $config = usage_monitor_data_manager::get_config();
        $system_info = usage_monitor_data_manager::get_system_info();
        $disk_usage = usage_monitor_data_manager::get_disk_usage();
        
        $data = new stdClass();
        $data->sitename = format_string($site->fullname);
        $data->threshold = $config->max_daily_users_threshold;
        $data->numberofusers = $numberofusers;
        $data->lastday = is_numeric($fecha) && $fecha > 0 ? date('d/m/Y', (int)$fecha) : date('d/m/Y');
        $data->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
        $data->siteurl = $CFG->wwwroot;
        $data->percentaje = round($percentage, 2);
        $data->excess_users = max(0, $numberofusers - $data->threshold);
        
        // System information
        $data->moodle_version = $system_info->moodle_version;
        $data->moodle_release = $system_info->moodle_release;
        $data->courses_count = $system_info->course_count;
        $data->backup_auto_max_kept = $system_info->backup_auto_max_kept;
        
        // Disk information
        $data->diskusage = $disk_usage->current_readable;
        $data->quotadisk = $disk_usage->quota_readable;
        $data->disk_percent = round($disk_usage->percentage, 2);
        
        // Projections
        $projections = usage_monitor_data_manager::get_projections();
        $data->days_to_critical = $projections->days_to_users_threshold;
        $data->critical_threshold = 120;
        
        // Historical data
        $data->historical_data_rows = self::generate_historical_data_html(7, $data->threshold);
        
        return self::send_email($data, 'user_limit');
    }
    
    /**
     * Send disk usage notification
     *
     * @param int $quotadisk Disk quota in bytes
     * @param int $disk_usage Current disk usage in bytes
     * @param float $disk_percent Disk usage percentage
     * @param int $userAccessCount Active user count
     * @return bool Success status
     */
    public static function send_disk_usage_notification($quotadisk, $disk_usage, $disk_percent, $userAccessCount) {
        global $CFG;
        
        $site = get_site();
        $config = usage_monitor_data_manager::get_config();
        $system_info = usage_monitor_data_manager::get_system_info();
        $dir_analysis = usage_monitor_data_manager::get_directory_analysis();
        $largest_courses = usage_monitor_data_manager::get_largest_courses(5);
        
        $data = new stdClass();
        $data->sitename = format_string($site->fullname);
        $data->quotadisk = display_size($quotadisk);
        $data->diskusage = display_size($disk_usage);
        $data->percentage = round($disk_percent, 2);
        $data->databasesize = display_size($dir_analysis['database']);
        $data->available_space = display_size($quotadisk - $disk_usage);
        $data->available_percent = round(100 - $disk_percent, 2);
        
        $data->warning_level_class = $disk_percent < 70 ? 'warning-level-low' : 
                                   ($disk_percent < 90 ? 'warning-level-medium' : 'warning-level-high');
        
        // System information
        $data->backupcount = $system_info->backup_auto_max_kept;
        $data->threshold = $config->max_daily_users_threshold;
        $data->numberofusers = $userAccessCount;
        $data->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
        $data->siteurl = $CFG->wwwroot;
        $data->lastday = date('d/m/Y', time());
        $data->coursescount = $system_info->course_count;
        $data->user_percent = round(($userAccessCount / $data->threshold) * 100, 2);
        $data->moodle_version = $system_info->moodle_version;
        $data->moodle_release = $system_info->moodle_release;
        
        // Directory analysis
        $data->db_percent = round(($dir_analysis['database'] / $disk_usage) * 100, 2);
        $data->filedir_size = display_size($dir_analysis['filedir']);
        $data->filedir_percent = round(($dir_analysis['filedir'] / $disk_usage) * 100, 2);
        $data->cache_size = display_size($dir_analysis['cache']);
        $data->cache_percent = round(($dir_analysis['cache'] / $disk_usage) * 100, 2);
        $data->other_size = display_size($dir_analysis['others']);
        $data->other_percent = round(($dir_analysis['others'] / $disk_usage) * 100, 2);
        
        // Top courses
        $data->top_courses_rows = self::generate_top_courses_html($largest_courses);
        
        return self::send_email($data, 'disk_usage');
    }
    
    /**
     * Generate historical data HTML
     *
     * @param int $limit Number of records
     * @param int $max_threshold Maximum threshold
     * @return string HTML content
     */
    private static function generate_historical_data_html($limit = 10, $max_threshold = 100) {
        global $DB;
        
        $html = '';
        $limit_days_ago = time() - ($limit * 86400);
        
        $sql = "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                       COUNT(DISTINCT userid) as usuarios
                FROM {logstore_standard_log}
                WHERE action = 'loggedin'
                  AND timecreated > :limit_days_ago
                GROUP BY (timecreated - (timecreated % 86400))
                ORDER BY fecha DESC
                LIMIT :limit";
        
        $records = $DB->get_records_sql($sql, ['limit_days_ago' => $limit_days_ago, 'limit' => $limit]);
        
        foreach ($records as $record) {
            if (!is_numeric($record->fecha) || $record->fecha <= 0) {
                continue;
            }
            
            $percent = round(($record->usuarios / $max_threshold) * 100, 1);
            $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
            $formatted_date = date('d/m/Y', (int)$record->fecha);
            
            $html .= '<tr>';
            $html .= '<td>' . $formatted_date . '</td>';
            $html .= '<td>' . $record->usuarios . '</td>';
            $html .= '<td class="' . $class . '">' . $percent . '%</td>';
            $html .= '</tr>';
        }
        
        return $html;
    }
    
    /**
     * Generate top courses HTML
     *
     * @param array $courses Course data
     * @return string HTML content
     */
    private static function generate_top_courses_html($courses) {
        $html = '';
        
        foreach ($courses as $course) {
            $html .= '<tr>';
            $html .= '<td>' . format_string($course->fullname) . ' (' . $course->shortname . ')</td>';
            $html .= '<td>' . display_size($course->totalsize) . '</td>';
            $html .= '<td>' . $course->percentage . '%</td>';
            $html .= '</tr>';
        }
        
        return $html;
    }
    
    /**
     * Send email notification
     *
     * @param stdClass $data Email data
     * @param string $type Notification type
     * @return bool Success status
     */
    private static function send_email($data, $type) {
        global $CFG;
        
        $toemail = self::generate_email_user(get_config('report_usage_monitor', 'email'), '');
        $fromemail = self::generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));
        
        if ($type === 'user_limit') {
            $subject = get_string('subjectemail1', 'report_usage_monitor') . " {$data->sitename}";
            $messagehtml = get_string('messagehtml_userlimit', 'report_usage_monitor', $data);
        } else {
            $subject = get_string('subjectemail2', 'report_usage_monitor') . " {$data->sitename}";
            $messagehtml = get_string('messagehtml_diskusage', 'report_usage_monitor', $data);
        }
        
        $messagetext = html_to_text($messagehtml);
        
        $previous_noemailever = $CFG->noemailever ?? false;
        $CFG->noemailever = false;
        $result = email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
        $CFG->noemailever = $previous_noemailever;
        
        return $result;
    }
    
    /**
     * Generate email user object
     *
     * @param string $email Email address
     * @param string $name User name
     * @param int $id User ID
     * @return stdClass User object
     */
    private static function generate_email_user($email, $name = '', $id = -99) {
        $emailuser = new stdClass();
        $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailuser->email = '';
        }
        $name = format_text($name, FORMAT_HTML, array('trusted' => false, 'noclean' => false));
        $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
        $emailuser->lastname = '';
        $emailuser->maildisplay = true;
        $emailuser->mailformat = 1;
        $emailuser->id = $id;
        $emailuser->firstnamephonetic = '';
        $emailuser->lastnamephonetic = '';
        $emailuser->middlename = '';
        $emailuser->alternatename = '';
        return $emailuser;
    }
}

/**
 * Utility functions
 */

/**
 * Convert bytes to GB
 *
 * @param mixed $sizeInBytes Size in bytes
 * @param int $precision Decimal precision
 * @return string Size in GB
 */
function display_size_in_gb($sizeInBytes, $precision = 2) {
    if (!is_numeric($sizeInBytes) || $sizeInBytes === null) {
        debugging("Expected numeric value, received: " . var_export($sizeInBytes, true), DEBUG_DEVELOPER);
        return '0';
    }
    
    $sizeInGb = $sizeInBytes / (1024 * 1024 * 1024);
    return round($sizeInGb, $precision);
}

/**
 * Calculate threshold percentage
 *
 * @param int $current_value Current value
 * @param int $threshold Threshold value
 * @return float Percentage
 */
function calculate_threshold_percentage($current_value, $threshold) {
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