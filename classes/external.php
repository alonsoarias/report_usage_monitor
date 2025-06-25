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
 * External API for Usage Monitor plugin.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

/**
 * External API class for Usage Monitor plugin
 */
class report_usage_monitor_external extends external_api {

    /**
     * Returns description of method parameters for get_usage_statistics
     *
     * @return external_function_parameters
     */
    public static function get_usage_statistics_parameters() {
        return new external_function_parameters([
            'include_history' => new external_value(PARAM_BOOL, 'Include historical data', VALUE_DEFAULT, false),
            'history_days' => new external_value(PARAM_INT, 'Number of days for history', VALUE_DEFAULT, 30)
        ]);
    }

    /**
     * Get comprehensive usage statistics
     *
     * @param bool $include_history Include historical data
     * @param int $history_days Number of days for history
     * @return array Usage statistics
     */
    public static function get_usage_statistics($include_history = false, $history_days = 30) {
        // Validate parameters
        $params = self::validate_parameters(self::get_usage_statistics_parameters(), [
            'include_history' => $include_history,
            'history_days' => $history_days
        ]);

        // Check permissions
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Get statistics
        $stats = usage_monitor_data_manager::get_usage_statistics();

        // Format response
        $response = [
            'site_info' => [
                'name' => $stats->system->site_name,
                'shortname' => $stats->system->site_shortname,
                'moodle_version' => $stats->system->moodle_version,
                'moodle_release' => $stats->system->moodle_release,
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
                'percentage' => round($stats->disk->percentage, 2),
                'warning_level' => $stats->disk->warning_level,
                'warning_class' => $stats->disk->warning_class,
                'last_calculated' => $stats->disk->last_calculated,
                'directories' => [
                    'database' => [
                        'bytes' => $stats->directories['database'],
                        'readable' => display_size($stats->directories['database']),
                        'percentage' => round(($stats->directories['database'] / $stats->disk->current_bytes) * 100, 2)
                    ],
                    'filedir' => [
                        'bytes' => $stats->directories['filedir'],
                        'readable' => display_size($stats->directories['filedir']),
                        'percentage' => round(($stats->directories['filedir'] / $stats->disk->current_bytes) * 100, 2)
                    ],
                    'cache' => [
                        'bytes' => $stats->directories['cache'],
                        'readable' => display_size($stats->directories['cache']),
                        'percentage' => round(($stats->directories['cache'] / $stats->disk->current_bytes) * 100, 2)
                    ],
                    'others' => [
                        'bytes' => $stats->directories['others'],
                        'readable' => display_size($stats->directories['others']),
                        'percentage' => round(($stats->directories['others'] / $stats->disk->current_bytes) * 100, 2)
                    ]
                ]
            ],
            'user_usage' => [
                'current' => $stats->users->current,
                'threshold' => $stats->users->threshold,
                'percentage' => round($stats->users->percentage, 2),
                'warning_level' => $stats->users->warning_level,
                'warning_class' => $stats->users->warning_class,
                'last_calculated' => $stats->users->last_calculated,
                'max_90_days' => $stats->users->max_90_days,
                'max_90_days_date' => $stats->users->max_90_days_date
            ],
            'projections' => [
                'disk_growth_rate' => $stats->projections->disk_growth_rate,
                'users_growth_rate' => $stats->projections->users_growth_rate,
                'days_to_disk_threshold' => $stats->projections->days_to_disk_threshold,
                'days_to_users_threshold' => $stats->projections->days_to_users_threshold
            ],
            'largest_courses' => []
        ];

        // Format largest courses
        foreach ($stats->courses as $course) {
            $response['largest_courses'][] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'size_bytes' => $course->filesize,
                'size_readable' => display_size($course->filesize),
                'backup_size_bytes' => $course->backupsize ?? 0,
                'backup_size_readable' => display_size($course->backupsize ?? 0),
                'total_size_bytes' => $course->totalsize,
                'total_size_readable' => display_size($course->totalsize),
                'percentage' => $course->percentage,
                'backup_count' => $course->backupcount
            ];
        }

        // Include history if requested
        if ($params['include_history']) {
            $history = usage_monitor_data_manager::get_usage_history($params['history_days']);
            $response['history'] = [
                'disk' => array_values($history['disk']),
                'users' => array_values($history['users'])
            ];
        }

        return $response;
    }

    /**
     * Returns description of method result value for get_usage_statistics
     *
     * @return external_description
     */
    public static function get_usage_statistics_returns() {
        return new external_single_structure([
            'site_info' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Site name'),
                'shortname' => new external_value(PARAM_TEXT, 'Site short name'),
                'moodle_version' => new external_value(PARAM_INT, 'Moodle version'),
                'moodle_release' => new external_value(PARAM_TEXT, 'Moodle release'),
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
                'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                'warning_level' => new external_value(PARAM_FLOAT, 'Warning threshold level'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'directories' => new external_single_structure([
                    'database' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Database size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Human-readable database size'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Database percentage of total')
                    ]),
                    'filedir' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'File directory size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Human-readable file directory size'),
                        'percentage' => new external_value(PARAM_FLOAT, 'File directory percentage of total')
                    ]),
                    'cache' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Cache size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Human-readable cache size'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Cache percentage of total')
                    ]),
                    'others' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Other directories size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Human-readable other directories size'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Other directories percentage of total')
                    ])
                ])
            ]),
            'user_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current daily users'),
                'threshold' => new external_value(PARAM_INT, 'User threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'User usage percentage'),
                'warning_level' => new external_value(PARAM_FLOAT, 'Warning threshold level'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'max_90_days' => new external_value(PARAM_INT, 'Maximum users in last 90 days'),
                'max_90_days_date' => new external_value(PARAM_INT, 'Date of maximum users')
            ]),
            'projections' => new external_single_structure([
                'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly disk growth rate percentage'),
                'users_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly users growth rate percentage'),
                'days_to_disk_threshold' => new external_value(PARAM_INT, 'Days to reach disk threshold'),
                'days_to_users_threshold' => new external_value(PARAM_INT, 'Days to reach users threshold')
            ]),
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
                    'backup_count' => new external_value(PARAM_INT, 'Number of backups')
                ])
            ),
            'history' => new external_single_structure([
                'disk' => new external_multiple_structure(
                    new external_single_structure([
                        'timecreated' => new external_value(PARAM_INT, 'Timestamp'),
                        'value' => new external_value(PARAM_INT, 'Disk usage value'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage')
                    ])
                ),
                'users' => new external_multiple_structure(
                    new external_single_structure([
                        'date_key' => new external_value(PARAM_INT, 'Date key'),
                        'users' => new external_value(PARAM_INT, 'Number of users')
                    ])
                )
            ], 'Historical data', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Returns description of method parameters for update_thresholds
     *
     * @return external_function_parameters
     */
    public static function update_thresholds_parameters() {
        return new external_function_parameters([
            'user_threshold' => new external_value(PARAM_INT, 'New user threshold', VALUE_OPTIONAL),
            'disk_threshold' => new external_value(PARAM_INT, 'New disk threshold in GB', VALUE_OPTIONAL),
            'disk_warning_level' => new external_value(PARAM_FLOAT, 'Disk warning level percentage', VALUE_OPTIONAL),
            'users_warning_level' => new external_value(PARAM_FLOAT, 'Users warning level percentage', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Update configuration thresholds
     *
     * @param int|null $user_threshold New user threshold
     * @param int|null $disk_threshold New disk threshold in GB
     * @param float|null $disk_warning_level Disk warning level percentage
     * @param float|null $users_warning_level Users warning level percentage
     * @return array Result of update operation
     */
    public static function update_thresholds($user_threshold = null, $disk_threshold = null, 
                                           $disk_warning_level = null, $users_warning_level = null) {
        // Validate parameters
        $params = self::validate_parameters(self::update_thresholds_parameters(), [
            'user_threshold' => $user_threshold,
            'disk_threshold' => $disk_threshold,
            'disk_warning_level' => $disk_warning_level,
            'users_warning_level' => $users_warning_level
        ]);

        // Check permissions
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:manage', $context);

        // Prepare thresholds array
        $thresholds = [];
        if ($params['user_threshold'] !== null) {
            $thresholds['user_threshold'] = $params['user_threshold'];
        }
        if ($params['disk_threshold'] !== null) {
            $thresholds['disk_threshold'] = $params['disk_threshold'];
        }

        // Update basic thresholds
        $result = usage_monitor_data_manager::update_thresholds($thresholds);

        // Update warning levels if provided
        if ($params['disk_warning_level'] !== null) {
            if ($params['disk_warning_level'] > 0 && $params['disk_warning_level'] <= 100) {
                set_config('disk_warning_level', $params['disk_warning_level'], 'report_usage_monitor');
                $result['disk_warning_level_updated'] = true;
                $result['messages'][] = 'Disk warning level updated successfully.';
            } else {
                $result['success'] = false;
                $result['messages'][] = 'Disk warning level must be between 0 and 100.';
            }
        }

        if ($params['users_warning_level'] !== null) {
            if ($params['users_warning_level'] > 0 && $params['users_warning_level'] <= 100) {
                set_config('users_warning_level', $params['users_warning_level'], 'report_usage_monitor');
                $result['users_warning_level_updated'] = true;
                $result['messages'][] = 'Users warning level updated successfully.';
            } else {
                $result['success'] = false;
                $result['messages'][] = 'Users warning level must be between 0 and 100.';
            }
        }

        return $result;
    }

    /**
     * Returns description of method result value for update_thresholds
     *
     * @return external_description
     */
    public static function update_thresholds_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Overall operation success'),
            'user_threshold_updated' => new external_value(PARAM_BOOL, 'User threshold updated', VALUE_OPTIONAL),
            'disk_threshold_updated' => new external_value(PARAM_BOOL, 'Disk threshold updated', VALUE_OPTIONAL),
            'disk_warning_level_updated' => new external_value(PARAM_BOOL, 'Disk warning level updated', VALUE_OPTIONAL),
            'users_warning_level_updated' => new external_value(PARAM_BOOL, 'Users warning level updated', VALUE_OPTIONAL),
            'messages' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Status message')
            )
        ]);
    }

    /**
     * Returns description of method parameters for get_notification_history
     *
     * @return external_function_parameters
     */
    public static function get_notification_history_parameters() {
        return new external_function_parameters([
            'type' => new external_value(PARAM_ALPHA, 'Notification type (disk, users, or all)', VALUE_DEFAULT, 'all'),
            'limit' => new external_value(PARAM_INT, 'Maximum number of records', VALUE_DEFAULT, 30),
            'offset' => new external_value(PARAM_INT, 'Offset for pagination', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Get notification history
     *
     * @param string $type Notification type
     * @param int $limit Maximum number of records
     * @param int $offset Offset for pagination
     * @return array Notification history
     */
    public static function get_notification_history($type = 'all', $limit = 30, $offset = 0) {
        global $DB;

        // Validate parameters
        $params = self::validate_parameters(self::get_notification_history_parameters(), [
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset
        ]);

        // Check permissions
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Build query
        $where = '';
        $sqlparams = [];

        if ($params['type'] !== 'all') {
            $where = ' WHERE type = :type';
            $sqlparams['type'] = $params['type'];
        }

        $sql = "SELECT * FROM {report_usage_monitor_history}" . $where . 
               " ORDER BY timecreated DESC";

        $records = $DB->get_records_sql($sql, $sqlparams, $params['offset'], $params['limit']);

        // Format results
        $results = [];
        foreach ($records as $record) {
            if (!is_numeric($record->timecreated) || $record->timecreated <= 0) {
                debugging('Invalid timestamp in notification history: ' . var_export($record->timecreated, true), DEBUG_DEVELOPER);
                $record->timecreated = time();
            }

            $results[] = [
                'id' => $record->id,
                'type' => $record->type,
                'percentage' => $record->percentage,
                'value' => $record->type === 'disk' ? display_size($record->value) : $record->value,
                'value_raw' => $record->value,
                'threshold' => $record->type === 'disk' ? display_size($record->threshold) : $record->threshold,
                'threshold_raw' => $record->threshold,
                'timecreated' => $record->timecreated,
                'timereadable' => date('M d, Y H:i', (int)$record->timecreated)
            ];
        }

        return [
            'total' => $DB->count_records('report_usage_monitor_history', $sqlparams),
            'limit' => $params['limit'],
            'offset' => $params['offset'],
            'items' => $results
        ];
    }

    /**
     * Returns description of method result value for get_notification_history
     *
     * @return external_description
     */
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
                    'timereadable' => new external_value(PARAM_TEXT, 'Human-readable time')
                ])
            )
        ]);
    }

    /**
     * Returns description of method parameters for get_dashboard_data
     *
     * @return external_function_parameters
     */
    public static function get_dashboard_data_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get optimized dashboard data
     *
     * @return array Dashboard data
     */
    public static function get_dashboard_data() {
        // Check permissions
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Get basic statistics
        $disk_usage = usage_monitor_data_manager::get_disk_usage();
        $user_usage = usage_monitor_data_manager::get_user_usage();
        $projections = usage_monitor_data_manager::get_projections();

        return [
            'disk_usage' => [
                'current' => $disk_usage->current_bytes,
                'current_readable' => $disk_usage->current_readable,
                'threshold' => $disk_usage->quota_bytes,
                'threshold_readable' => $disk_usage->quota_readable,
                'percentage' => round($disk_usage->percentage, 2),
                'warning_class' => $disk_usage->warning_class,
                'last_calculated' => $disk_usage->last_calculated
            ],
            'user_usage' => [
                'current' => $user_usage->current,
                'threshold' => $user_usage->threshold,
                'percentage' => round($user_usage->percentage, 2),
                'warning_class' => $user_usage->warning_class,
                'last_calculated' => $user_usage->last_calculated,
                'max_90_days' => $user_usage->max_90_days,
                'max_90_days_date' => $user_usage->max_90_days_date
            ],
            'projections' => [
                'disk_growth_rate' => $projections->disk_growth_rate,
                'users_growth_rate' => $projections->users_growth_rate,
                'days_to_disk_threshold' => $projections->days_to_disk_threshold,
                'days_to_users_threshold' => $projections->days_to_users_threshold
            ]
        ];
    }

    /**
     * Returns description of method result value for get_dashboard_data
     *
     * @return external_description
     */
    public static function get_dashboard_data_returns() {
        return new external_single_structure([
            'disk_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current usage in bytes'),
                'current_readable' => new external_value(PARAM_TEXT, 'Human-readable current usage'),
                'threshold' => new external_value(PARAM_INT, 'Threshold in bytes'),
                'threshold_readable' => new external_value(PARAM_TEXT, 'Human-readable threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp')
            ]),
            'user_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current users'),
                'threshold' => new external_value(PARAM_INT, 'User threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                'warning_class' => new external_value(PARAM_TEXT, 'CSS warning class'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'max_90_days' => new external_value(PARAM_INT, 'Maximum users in 90 days'),
                'max_90_days_date' => new external_value(PARAM_INT, 'Date of maximum users')
            ]),
            'projections' => new external_single_structure([
                'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly disk growth rate'),
                'users_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly users growth rate'),
                'days_to_disk_threshold' => new external_value(PARAM_INT, 'Days to disk threshold'),
                'days_to_users_threshold' => new external_value(PARAM_INT, 'Days to users threshold')
            ])
        ]);
    }
}