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
 * Local library functions for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Note: Most functions have been moved to the helper class.
// This file is kept for backward compatibility.

/**
 * Get users logged in during last 10 days.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function report_user_daily_sql() {
    debugging('report_user_daily_sql is deprecated. Use report_usage_monitor\helper methods.', DEBUG_DEVELOPER);
    
    $tendaysago = time() - (10 * 24 * 60 * 60);
    $yesterday = time() - (24 * 60 * 60);
    
    return "SELECT DATE(FROM_UNIXTIME(timecreated)) as day,
                   COUNT(DISTINCT userid) as usercount
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
              AND timecreated BETWEEN $tendaysago AND $yesterday
            GROUP BY DATE(FROM_UNIXTIME(timecreated))
            ORDER BY day DESC";
}

/**
 * Get top daily users data.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function report_user_daily_top_sql() {
    debugging('report_user_daily_top_sql is deprecated. Use report_usage_monitor\helper methods.', DEBUG_DEVELOPER);
    
    return "SELECT timecreated, usercount
            FROM {report_usage_monitor}
            ORDER BY usercount DESC, timecreated DESC";
}

/**
 * Get top daily users data for task.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function report_user_daily_top_task() {
    return report_user_daily_top_sql();
}

/**
 * Update minimum top record.
 * @deprecated Use proper DB methods instead
 * @param int $timestamp Timestamp
 * @param int $users User count
 * @param int $min Minimum value
 */
function update_min_top_sql($timestamp, $users, $min) {
    global $DB;
    
    debugging('update_min_top_sql is deprecated.', DEBUG_DEVELOPER);
    
    $record = $DB->get_record('report_usage_monitor', ['usercount' => $min], '*', IGNORE_MULTIPLE);
    if ($record) {
        $record->timecreated = $timestamp;
        $record->usercount = $users;
        $DB->update_record('report_usage_monitor', $record);
    }
}

/**
 * Insert top record.
 * @deprecated Use proper DB methods instead
 * @param int $timestamp Timestamp
 * @param int $users User count
 */
function insert_top_sql($timestamp, $users) {
    global $DB;
    
    debugging('insert_top_sql is deprecated.', DEBUG_DEVELOPER);
    
    $record = new stdClass();
    $record->timecreated = $timestamp;
    $record->usercount = $users;
    $DB->insert_record('report_usage_monitor', $record);
}

/**
 * Get yesterday's user count.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function user_limit_daily_sql() {
    debugging('user_limit_daily_sql is deprecated. Use report_usage_monitor\helper methods.', DEBUG_DEVELOPER);
    
    $yesterdaystart = strtotime('yesterday midnight');
    $todaystart = strtotime('today midnight');
    
    return "SELECT COUNT(DISTINCT userid) as usercount,
                   DATE(FROM_UNIXTIME(timecreated)) as day
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
              AND timecreated BETWEEN $yesterdaystart AND $todaystart
            GROUP BY DATE(FROM_UNIXTIME(timecreated))";
}

/**
 * Get user limit for daily task.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function user_limit_daily_task() {
    return user_limit_daily_sql();
}

/**
 * Get users logged in today.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function users_today() {
    debugging('users_today is deprecated. Use report_usage_monitor\helper methods.', DEBUG_DEVELOPER);
    
    $onedayago = time() - (24 * 60 * 60);
    
    return "SELECT COUNT(DISTINCT id) as usercount
            FROM {user}
            WHERE lastaccess >= $onedayago";
}

/**
 * Get maximum users in last 90 days.
 * @deprecated Use report_usage_monitor\helper methods instead
 * @return string SQL query
 */
function max_userdaily_for_90_days() {
    debugging('max_userdaily_for_90_days is deprecated. Use report_usage_monitor\helper methods.', DEBUG_DEVELOPER);
    
    $ninetydaysago = time() - (90 * 24 * 60 * 60);
    
    return "SELECT DATE(FROM_UNIXTIME(timecreated)) as day,
                   COUNT(DISTINCT userid) as usercount
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
              AND timecreated >= $ninetydaysago
            GROUP BY DATE(FROM_UNIXTIME(timecreated))
            ORDER BY usercount DESC
            LIMIT 1";
}

/**
 * Get database size.
 * @deprecated Use report_usage_monitor\helper::get_database_size() instead
 * @return string SQL query
 */
function size_database() {
    global $CFG;
    
    debugging('size_database is deprecated. Use report_usage_monitor\helper::get_database_size().', DEBUG_DEVELOPER);
    
    return "SELECT TABLE_SCHEMA AS `database_name`,
                   ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)) AS size
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '$CFG->dbname'";
}

/**
 * Calculate directory size.
 * @deprecated Use report_usage_monitor\helper::get_directory_size() instead
 * @param string $rootdir Directory path
 * @param string $excludefile File to exclude
 * @return int Size in bytes
 */
function directory_size($rootdir, $excludefile = '') {
    debugging('directory_size is deprecated. Use report_usage_monitor\helper::get_directory_size().', DEBUG_DEVELOPER);
    return report_usage_monitor\helper::get_directory_size($rootdir);
}

/**
 * Analyze disk usage by directory.
 * @deprecated Moved to task class
 * @param string $rootdir Root directory
 * @return array Directory sizes
 */
function analyze_disk_usage_by_directory($rootdir) {
    debugging('analyze_disk_usage_by_directory is deprecated.', DEBUG_DEVELOPER);
    
    $analysis = [];
    $directories = [
        'filedir' => $rootdir . '/filedir',
        'cache' => $rootdir . '/cache',
    ];
    
    foreach ($directories as $key => $dir) {
        if (is_dir($dir)) {
            $analysis[$key] = report_usage_monitor\helper::get_directory_size($dir);
        } else {
            $analysis[$key] = 0;
        }
    }
    
    $totalsize = report_usage_monitor\helper::get_directory_size($rootdir);
    $analysis['others'] = max(0, $totalsize - array_sum($analysis));
    $analysis['database'] = report_usage_monitor\helper::get_database_size();
    
    return $analysis;
}

/**
 * Get largest courses.
 * @deprecated Use report_usage_monitor\helper::get_largest_courses() instead
 * @param int $limit Number of courses
 * @return array Courses
 */
function get_largest_courses($limit = 5) {
    debugging('get_largest_courses is deprecated. Use report_usage_monitor\helper::get_largest_courses().', DEBUG_DEVELOPER);
    return report_usage_monitor\helper::get_largest_courses($limit);
}

/**
 * Display size in GB.
 * @deprecated Use report_usage_monitor\helper::format_bytes() instead
 * @param int $sizeinbytes Size in bytes
 * @param int $precision Decimal places
 * @return string Formatted size
 */
function display_size_in_gb($sizeinbytes, $precision = 2) {
    debugging('display_size_in_gb is deprecated. Use report_usage_monitor\helper::format_bytes().', DEBUG_DEVELOPER);
    
    if (!is_numeric($sizeinbytes) || $sizeinbytes <= 0) {
        return '0';
    }
    
    return round($sizeinbytes / (1024 * 1024 * 1024), $precision);
}

/**
 * Calculate threshold percentage.
 * @deprecated Use report_usage_monitor\helper::calculate_percentage() instead
 * @param int $currentvalue Current value
 * @param int $threshold Threshold value
 * @return float Percentage
 */
function calculate_threshold_percentage($currentvalue, $threshold) {
    debugging('calculate_threshold_percentage is deprecated. Use report_usage_monitor\helper::calculate_percentage().', DEBUG_DEVELOPER);
    return report_usage_monitor\helper::calculate_percentage($currentvalue, $threshold);
}

/**
 * Calculate growth rate.
 * @deprecated Placeholder function
 * @param string $type Type (users or disk)
 * @param int $days Number of days
 * @return float Growth rate
 */
function calculate_growth_rate($type = 'users', $days = 30) {
    debugging('calculate_growth_rate is not implemented yet.', DEBUG_DEVELOPER);
    return 5.0; // Placeholder value
}

/**
 * Project limit date.
 * @deprecated Placeholder function
 * @param int $currentvalue Current value
 * @param int $thresholdvalue Threshold value
 * @param float $growthrate Growth rate
 * @return int Days to threshold
 */
function project_limit_date($currentvalue, $thresholdvalue, $growthrate) {
    debugging('project_limit_date is not implemented yet.', DEBUG_DEVELOPER);
    return 100; // Placeholder value
}

/**
 * Send email notification for user limit.
 * @deprecated Use report_usage_monitor\notification class instead
 * @param int $numberofusers Number of users
 * @param int $timestamp Timestamp
 * @param float $percentage Percentage
 * @return bool
 */
function email_notify_user_limit($numberofusers, $timestamp, $percentage) {
    debugging('email_notify_user_limit is deprecated. Use report_usage_monitor\notification class.', DEBUG_DEVELOPER);
    
    $notification = new report_usage_monitor\notification();
    $reportconfig = get_config('report_usage_monitor');
    $threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
    
    return $notification->send_user_notification($numberofusers, $threshold, $percentage, $timestamp);
}

/**
 * Send email notification for disk limit.
 * @deprecated Use report_usage_monitor\notification class instead
 * @param int $quotadisk Disk quota
 * @param int $diskusage Disk usage
 * @param float $diskpercent Disk percentage
 * @param int $useraccesscount User access count
 * @return bool
 */
function email_notify_disk_limit($quotadisk, $diskusage, $diskpercent, $useraccesscount) {
    debugging('email_notify_disk_limit is deprecated. Use report_usage_monitor\notification class.', DEBUG_DEVELOPER);
    
    $notification = new report_usage_monitor\notification();
    return $notification->send_disk_notification($diskusage, $quotadisk, $diskpercent);
}