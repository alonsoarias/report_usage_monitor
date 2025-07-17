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
 * Enhanced External API for Usage Monitor plugin - Simplified and centralized
 *
 * @package    report_usage_monitor
 * @copyright  2025 Alonso Arias <alonso@aloarias.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

/**
 * Enhanced External API class - All functions use centralized manager
 */
class report_usage_monitor_external extends external_api {

    /**
     * Get comprehensive usage statistics
     */
    public static function get_usage_statistics_parameters() {
        return new external_function_parameters([
            'include_history' => new external_value(PARAM_BOOL, 'Include historical data', VALUE_DEFAULT, false),
            'history_days' => new external_value(PARAM_INT, 'Number of days for history', VALUE_DEFAULT, 30),
            'force_refresh' => new external_value(PARAM_BOOL, 'Force cache refresh', VALUE_DEFAULT, false)
        ]);
    }

    public static function get_usage_statistics($include_history = false, $history_days = 30, $force_refresh = false) {
        $params = self::validate_parameters(self::get_usage_statistics_parameters(), [
            'include_history' => $include_history,
            'history_days' => $history_days,
            'force_refresh' => $force_refresh
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Use centralized manager
        $stats = usage_monitor_manager::get_usage_statistics($params['force_refresh']);

        // Format response with enhanced data
        $response = [
            'site_info' => [
                'name' => $stats->system->site_name,
                'shortname' => $stats->system->site_shortname,
                'moodle_version' => $stats->system->moodle_version,
                'moodle_release' => $stats->system->moodle_release,
                'php_version' => $stats->system->php_version,
                'course_count' => $stats->system->course_count,
                'user_count' => $stats->system->user_count,
                'active_users' => $stats->system->active_users,
                'suspended_users' => $stats->system->suspended_users,
                'backup_auto_max_kept' => $stats->system->backup_auto_max_kept
            ],
            'disk_usage' => [
                'current_bytes' => $stats->disk->current_bytes,
                'current_readable' => $stats->disk->current_readable,
                'quota_bytes' => $stats->disk->quota_bytes,
                'quota_readable' => $stats->disk->quota_readable,
                'available_bytes' => $stats->disk->available_bytes,
                'available_readable' => $stats->disk->available_readable,
                'percentage' => round($stats->disk->percentage, 2),
                'warning_level' => $stats->disk->warning_level,
                'warning_class' => $stats->disk->warning_class,
                'last_calculated' => $stats->disk->last_calculated,
                'trend' => $stats->disk->trend,
                'growth_rate' => $stats->disk->growth_rate,
                'directories' => self::format_directories($stats->directories)
            ],
            'user_usage' => [
                'current' => $stats->users->current,
                'threshold' => $stats->users->threshold,
                'percentage' => round($stats->users->percentage, 2),
                'warning_level' => $stats->users->warning_level,
                'warning_class' => $stats->users->warning_class,
                'last_calculated' => $stats->users->last_calculated,
                'max_90_days' => $stats->users->max_90_days,
                'max_90_days_date' => $stats->users->max_90_days_date,
                'trend' => $stats->users->trend,
                'growth_rate' => $stats->users->growth_rate,
                'peak_hours' => array_values($stats->users->peak_hours ?? [])
            ],
            'projections' => [
                'disk_growth_rate' => $stats->projections->disk_growth_rate,
                'users_growth_rate' => $stats->projections->users_growth_rate,
                'days_to_disk_threshold' => $stats->projections->days_to_disk_threshold,
                'days_to_users_threshold' => $stats->projections->days_to_users_threshold,
                'days_to_disk_critical' => $stats->projections->days_to_disk_critical,
                'days_to_users_critical' => $stats->projections->days_to_users_critical,
                'disk_confidence' => $stats->projections->disk_confidence,
                'users_confidence' => $stats->projections->users_confidence
            ],
            'health_score' => $stats->health_score,
            'recommendations' => $stats->recommendations,
            'largest_courses' => self::format_courses($stats->courses)
        ];

        // Include history if requested
        if ($params['include_history']) {
            $history = usage_monitor_manager::get_usage_history($params['history_days']);
            $response['history'] = [
                'disk' => array_values($history['disk']),
                'users' => array_values($history['users']),
                'patterns' => $history['patterns']
            ];
        }

        return $response;
    }

    public static function get_usage_statistics_returns() {
        return new external_single_structure([
            'site_info' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Site name'),
                'shortname' => new external_value(PARAM_TEXT, 'Site short name'),
                'moodle_version' => new external_value(PARAM_INT, 'Moodle version'),
                'moodle_release' => new external_value(PARAM_TEXT, 'Moodle release'),
                'php_version' => new external_value(PARAM_TEXT, 'PHP version'),
                'course_count' => new external_value(PARAM_INT, 'Number of courses'),
                'user_count' => new external_value(PARAM_INT, 'Number of users'),
                'active_users' => new external_value(PARAM_INT, 'Number of active users'),
                'suspended_users' => new external_value(PARAM_INT, 'Number of suspended users'),
                'backup_auto_max_kept' => new external_value(PARAM_INT, 'Number of automatic backups kept')
            ]),
            'disk_usage' => new external_single_structure([
                'current_bytes' => new external_value(PARAM_INT, 'Current disk usage in bytes'),
                'current_readable' => new external_value(PARAM_TEXT, 'Human-readable current usage'),
                'quota_bytes' => new external_value(PARAM_INT, 'Disk quota in bytes'),
                'quota_readable' => new external_value(PARAM_TEXT, 'Human-readable quota'),
                'available_bytes' => new external_value(PARAM_INT, 'Available space in bytes'),
                'available_readable' => new external_value(PARAM_TEXT, 'Human-readable available space'),
                'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                'warning_level' => new external_value(PARAM_FLOAT, 'Warning threshold level'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'trend' => new external_value(PARAM_TEXT, 'Usage trend'),
                'growth_rate' => new external_value(PARAM_FLOAT, 'Monthly growth rate'),
                'directories' => new external_multiple_structure(
                    new external_single_structure([
                        'name' => new external_value(PARAM_TEXT, 'Directory name'),
                        'bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Human-readable size'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage of total'),
                        'trend' => new external_value(PARAM_TEXT, 'Directory trend')
                    ])
                )
            ]),
            'user_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current daily users'),
                'threshold' => new external_value(PARAM_INT, 'User threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'User usage percentage'),
                'warning_level' => new external_value(PARAM_FLOAT, 'Warning threshold level'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'max_90_days' => new external_value(PARAM_INT, 'Maximum users in last 90 days'),
                'max_90_days_date' => new external_value(PARAM_INT, 'Date of maximum users'),
                'trend' => new external_value(PARAM_TEXT, 'User trend'),
                'growth_rate' => new external_value(PARAM_FLOAT, 'Monthly growth rate'),
                'peak_hours' => new external_multiple_structure(
                    new external_single_structure([
                        'hour' => new external_value(PARAM_INT, 'Hour of day'),
                        'user_count' => new external_value(PARAM_INT, 'User count')
                    ])
                )
            ]),
            'projections' => new external_single_structure([
                'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly disk growth rate percentage'),
                'users_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly users growth rate percentage'),
                'days_to_disk_threshold' => new external_value(PARAM_INT, 'Days to reach disk threshold'),
                'days_to_users_threshold' => new external_value(PARAM_INT, 'Days to reach users threshold'),
                'days_to_disk_critical' => new external_value(PARAM_INT, 'Days to reach disk critical level'),
                'days_to_users_critical' => new external_value(PARAM_INT, 'Days to reach users critical level'),
                'disk_confidence' => new external_value(PARAM_INT, 'Disk projection confidence percentage'),
                'users_confidence' => new external_value(PARAM_INT, 'Users projection confidence percentage')
            ]),
            'health_score' => new external_single_structure([
                'overall' => new external_value(PARAM_INT, 'Overall health score'),
                'status' => new external_value(PARAM_TEXT, 'Health status'),
                'trend' => new external_value(PARAM_TEXT, 'Health trend'),
                'components' => new external_multiple_structure(
                    new external_single_structure([
                        'name' => new external_value(PARAM_TEXT, 'Component name'),
                        'score' => new external_value(PARAM_INT, 'Component score'),
                        'weight' => new external_value(PARAM_FLOAT, 'Component weight')
                    ])
                )
            ]),
            'recommendations' => new external_multiple_structure(
                new external_single_structure([
                    'category' => new external_value(PARAM_TEXT, 'Recommendation category'),
                    'type' => new external_value(PARAM_TEXT, 'Recommendation type'),
                    'title' => new external_value(PARAM_TEXT, 'Recommendation title'),
                    'message' => new external_value(PARAM_TEXT, 'Recommendation message'),
                    'actions' => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'Recommended action')
                    )
                ])
            ),
            'largest_courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                    'size_bytes' => new external_value(PARAM_INT, 'Course size in bytes'),
                    'size_readable' => new external_value(PARAM_TEXT, 'Human-readable course size'),
                    'backup_size_bytes' => new external_value(PARAM_INT, 'Backup size in bytes'),
                    'backup_size_readable' => new external_value(PARAM_TEXT, 'Human-readable backup size'),
                    'total_size_bytes' => new external_value(PARAM_INT, 'Total size in bytes'),
                    'total_size_readable' => new external_value(PARAM_TEXT, 'Human-readable total size'),
                    'percentage' => new external_value(PARAM_FLOAT, 'Percentage of total site files'),
                    'backup_count' => new external_value(PARAM_INT, 'Number of backups'),
                    'enrolled_users' => new external_value(PARAM_INT, 'Number of enrolled users'),
                    'efficiency_score' => new external_value(PARAM_INT, 'Course efficiency score'),
                    'last_activity' => new external_value(PARAM_INT, 'Last activity timestamp')
                ])
            ),
            'history' => new external_single_structure([
                'disk' => new external_multiple_structure(
                    new external_single_structure([
                        'timecreated' => new external_value(PARAM_INT, 'Timestamp'),
                        'value' => new external_value(PARAM_INT, 'Disk usage value'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                        'change' => new external_value(PARAM_INT, 'Change from previous'),
                        'change_percent' => new external_value(PARAM_FLOAT, 'Percentage change')
                    ])
                ),
                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'date_key' => new external_value(PARAM_TEXT, 'Date key'),
                        'users' => new external_value(PARAM_INT, 'Number of users'),
                        'day_of_week' => new external_value(PARAM_INT, 'Day of week'),
                        'formatted_date' => new external_value(PARAM_TEXT, 'Formatted date')
                    ])
                ),
                'patterns' => new external_single_structure([
                    'peak_disk_day' => new external_value(PARAM_TEXT, 'Peak disk usage day', VALUE_OPTIONAL),
                    'peak_user_day' => new external_value(PARAM_TEXT, 'Peak user activity day', VALUE_OPTIONAL),
                    'growth_correlation' => new external_value(PARAM_FLOAT, 'Growth correlation coefficient')
                ])
            ], 'Historical data', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Update thresholds using centralized manager
     */
    public static function update_thresholds_parameters() {
        return new external_function_parameters([
            'user_threshold' => new external_value(PARAM_INT, 'New user threshold', VALUE_OPTIONAL),
            'disk_threshold' => new external_value(PARAM_INT, 'New disk threshold in GB', VALUE_OPTIONAL),
            'disk_warning_level' => new external_value(PARAM_FLOAT, 'Disk warning level percentage', VALUE_OPTIONAL),
            'users_warning_level' => new external_value(PARAM_FLOAT, 'Users warning level percentage', VALUE_OPTIONAL)
        ]);
    }

    public static function update_thresholds($user_threshold = null, $disk_threshold = null, 
                                           $disk_warning_level = null, $users_warning_level = null) {
        $params = self::validate_parameters(self::update_thresholds_parameters(), [
            'user_threshold' => $user_threshold,
            'disk_threshold' => $disk_threshold,
            'disk_warning_level' => $disk_warning_level,
            'users_warning_level' => $users_warning_level
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:manage', $context);

        // Prepare thresholds for centralized manager
        $thresholds = [];
        if ($params['user_threshold'] !== null) {
            $thresholds['max_daily_users_threshold'] = $params['user_threshold'];
        }
        if ($params['disk_threshold'] !== null) {
            $thresholds['disk_quota'] = $params['disk_threshold'];
        }
        if ($params['disk_warning_level'] !== null) {
            $thresholds['disk_warning_level'] = $params['disk_warning_level'];
        }
        if ($params['users_warning_level'] !== null) {
            $thresholds['users_warning_level'] = $params['users_warning_level'];
        }

        // Use centralized manager
        $result = usage_monitor_manager::update_thresholds($thresholds);

        return [
            'success' => $result['success'],
            'updated' => $result['updated'],
            'errors' => $result['errors']
        ];
    }

    public static function update_thresholds_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Overall operation success'),
            'updated' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Updated threshold name')
            ),
            'errors' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Error message')
            )
        ]);
    }

    /**
     * Get optimized dashboard data
     */
    public static function get_dashboard_data_parameters() {
        return new external_function_parameters([
            'force_refresh' => new external_value(PARAM_BOOL, 'Force cache refresh', VALUE_DEFAULT, false)
        ]);
    }

    public static function get_dashboard_data($force_refresh = false) {
        $params = self::validate_parameters(self::get_dashboard_data_parameters(), [
            'force_refresh' => $force_refresh
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Get optimized data using centralized manager
        $disk_usage = usage_monitor_manager::get_disk_usage($params['force_refresh']);
        $user_usage = usage_monitor_manager::get_user_usage($params['force_refresh']);
        $stats = usage_monitor_manager::get_usage_statistics($params['force_refresh']);

        return [
            'disk_usage' => [
                'current' => $disk_usage->current_bytes,
                'current_readable' => $disk_usage->current_readable,
                'threshold' => $disk_usage->quota_bytes,
                'threshold_readable' => $disk_usage->quota_readable,
                'percentage' => round($disk_usage->percentage, 2),
                'warning_class' => $disk_usage->warning_class,
                'last_calculated' => $disk_usage->last_calculated,
                'trend' => $disk_usage->trend
            ],
            'user_usage' => [
                'current' => $user_usage->current,
                'threshold' => $user_usage->threshold,
                'percentage' => round($user_usage->percentage, 2),
                'warning_class' => $user_usage->warning_class,
                'last_calculated' => $user_usage->last_calculated,
                'max_90_days' => $user_usage->max_90_days,
                'max_90_days_date' => $user_usage->max_90_days_date,
                'trend' => $user_usage->trend
            ],
            'projections' => [
                'disk_growth_rate' => $stats->projections->disk_growth_rate,
                'users_growth_rate' => $stats->projections->users_growth_rate,
                'days_to_disk_threshold' => $stats->projections->days_to_disk_threshold,
                'days_to_users_threshold' => $stats->projections->days_to_users_threshold
            ],
            'health_score' => $stats->health_score
        ];
    }

    public static function get_dashboard_data_returns() {
        return new external_single_structure([
            'disk_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current usage in bytes'),
                'current_readable' => new external_value(PARAM_TEXT, 'Human-readable current usage'),
                'threshold' => new external_value(PARAM_INT, 'Threshold in bytes'),
                'threshold_readable' => new external_value(PARAM_TEXT, 'Human-readable threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'trend' => new external_value(PARAM_TEXT, 'Usage trend')
            ]),
            'user_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current users'),
                'threshold' => new external_value(PARAM_INT, 'User threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'max_90_days' => new external_value(PARAM_INT, 'Maximum users in 90 days'),
                'max_90_days_date' => new external_value(PARAM_INT, 'Date of maximum users'),
                'trend' => new external_value(PARAM_TEXT, 'User trend')
            ]),
            'projections' => new external_single_structure([
                'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly disk growth rate'),
                'users_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly users growth rate'),
                'days_to_disk_threshold' => new external_value(PARAM_INT, 'Days to disk threshold'),
                'days_to_users_threshold' => new external_value(PARAM_INT, 'Days to users threshold')
            ]),
            'health_score' => new external_single_structure([
                'overall' => new external_value(PARAM_INT, 'Overall health score'),
                'status' => new external_value(PARAM_TEXT, 'Health status'),
                'trend' => new external_value(PARAM_TEXT, 'Health trend')
            ])
        ]);
    }

    /**
     * Get notification history
     */
    public static function get_notification_history_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_ALPHA, 'Notification type (disk, users, or all)', VALUE_DEFAULT, 'all'),
            'limit' => new external_value(PARAM_INT, 'Maximum number of records', VALUE_DEFAULT, 30),
            'offset' => new external_value(PARAM_INT, 'Offset for pagination', VALUE_DEFAULT, 0)
        ]);
    }

    public static function get_notification_history($type = 'all', $limit = 30, $offset = 0) {
        global $DB;

        $params = self::validate_parameters(self::get_notification_history_parameters(), [
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Build query with enhanced filtering
        $where_conditions = [];
        $sql_params = [];

        if ($params['type'] !== 'all') {
            $where_conditions[] = 'type LIKE :type';
            $sql_params['type'] = $params['type'] . '%';
        }

        $where_clause = empty($where_conditions) ? '' : ' WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "SELECT * FROM {report_usage_monitor_history}" . $where_clause . 
               " ORDER BY timecreated DESC";

        $records = $DB->get_records_sql($sql, $sql_params, $params['offset'], $params['limit']);
        $total = $DB->count_records_sql("SELECT COUNT(*) FROM {report_usage_monitor_history}" . $where_clause, $sql_params);

        // Format results with enhanced data
        $results = [];
        foreach ($records as $record) {
            $timestamp = usage_monitor_manager::validate_timestamp($record->timecreated);
            
            $results[] = [
                'id' => $record->id,
                'type' => $record->type,
                'percentage' => $record->percentage,
                'value' => $record->type === 'disk' ? usage_monitor_manager::format_bytes($record->value) : $record->value,
                'value_raw' => $record->value,
                'threshold' => $record->type === 'disk' ? usage_monitor_manager::format_bytes($record->threshold) : $record->threshold,
                'threshold_raw' => $record->threshold,
                'timecreated' => $timestamp,
                'timereadable' => date('M d, Y H:i', $timestamp),
                'severity' => self::calculate_severity($record->percentage)
            ];
        }

        return [
            'total' => $total,
            'limit' => $params['limit'],
            'offset' => $params['offset'],
            'items' => $results
        ];
    }

    public static function get_notification_history_returns() {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total number of records'),
            'limit' => new external_value(PARAM_INT, 'Requested limit'),
            'offset' => new external_value(PARAM_INT, 'Requested offset'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Notification ID'),
                    'type' => new external_value(PARAM_ALPHA, 'Notification type'),
                    'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                    'value' => new external_value(PARAM_TEXT, 'Human-readable value'),
                    'value_raw' => new external_value(PARAM_INT, 'Raw value'),
                    'threshold' => new external_value(PARAM_TEXT, 'Human-readable threshold'),
                    'threshold_raw' => new external_value(PARAM_INT, 'Raw threshold'),
                    'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                    'timereadable' => new external_value(PARAM_TEXT, 'Human-readable time'),
                    'severity' => new external_value(PARAM_TEXT, 'Severity level')
                ])
            )
        ]);
    }

    /**
     * Helper methods for data formatting
     */
    private static function format_directories($directories) {
        $formatted = [];
        foreach ($directories as $name => $data) {
            $formatted[] = [
                'name' => $name,
                'bytes' => $data['bytes'],
                'readable' => $data['readable'],
                'percentage' => $data['percentage'],
                'trend' => $data['trend']
            ];
        }
        return $formatted;
    }

    private static function format_courses($courses) {
        $formatted = [];
        foreach ($courses as $course) {
            $formatted[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'size_bytes' => $course->filesize,
                'size_readable' => usage_monitor_manager::format_bytes($course->filesize),
                'backup_size_bytes' => $course->backupsize ?? 0,
                'backup_size_readable' => usage_monitor_manager::format_bytes($course->backupsize ?? 0),
                'total_size_bytes' => $course->totalsize,
                'total_size_readable' => usage_monitor_manager::format_bytes($course->totalsize),
                'percentage' => $course->percentage,
                'backup_count' => $course->backupcount,
                'enrolled_users' => $course->enrolled_users ?? 0,
                'efficiency_score' => $course->efficiency_score ?? 0,
                'last_activity' => $course->last_activity ?? 0
            ];
        }
        return $formatted;
    }

    private static function calculate_severity($percentage) {
        if ($percentage >= 95) return 'critical';
        if ($percentage >= 90) return 'high';
        if ($percentage >= 80) return 'medium';
        return 'low';
    }
}