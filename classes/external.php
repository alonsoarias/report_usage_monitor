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
 * External API for report_usage_monitor.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use report_usage_monitor\helper;

/**
 * External API class for report_usage_monitor plugin.
 */
class report_usage_monitor_external extends external_api {

    /**
     * Returns description of get_monitor_stats parameters.
     *
     * @return external_function_parameters
     */
    public static function get_monitor_stats_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get current usage statistics for external integration.
     *
     * @return array Statistics
     */
    public static function get_monitor_stats() {
        global $DB, $CFG, $SITE;
        
        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);
        
        // Check if API is enabled.
        $reportconfig = get_config('report_usage_monitor');
        if (empty($reportconfig->enable_api)) {
            throw new moodle_exception('apidisabled', 'report_usage_monitor');
        }
        
        // Calculate disk usage.
        $diskusage = ((int)($reportconfig->totalusagereadable ?? 0) + 
                      (int)($reportconfig->totalusagereadabledb ?? 0));
        $quotadisk = ((int)($reportconfig->disk_quota ?? 10) * 1024 * 1024 * 1024);
        $diskpercent = helper::calculate_percentage($diskusage, $quotadisk);
        
        // Calculate user usage.
        $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
        $userthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $userspercent = helper::calculate_percentage($userstoday, $userthreshold);
        
        // Get directory analysis.
        $diranalysis = json_decode($reportconfig->dir_analysis ?? '{}', true);
        if (!is_array($diranalysis)) {
            $diranalysis = [
                'database' => 0,
                'filedir' => 0,
                'cache' => 0,
                'others' => 0
            ];
        }
        
        // Get largest courses.
        $largestcourses = json_decode($reportconfig->largest_courses ?? '[]', true);
        if (!is_array($largestcourses)) {
            $largestcourses = [];
        }
        
        // Validate timestamps.
        $lastdiskcalc = helper::validate_timestamp($reportconfig->lastexecutioncalculate ?? 0);
        $lastuserscalc = helper::validate_timestamp($reportconfig->lastexecution ?? 0);
        $max90daysdate = helper::validate_timestamp($reportconfig->max_userdaily_for_90_days_date ?? 0, null);
        
        // Build response.
        $response = [
            'site_info' => [
                'name' => format_string($SITE->fullname),
                'shortname' => format_string($SITE->shortname),
                'moodle_version' => $CFG->version,
                'moodle_release' => $CFG->release,
                'course_count' => $DB->count_records('course'),
                'user_count' => $DB->count_records('user', ['deleted' => 0]) - 1,
                'backup_auto_max_kept' => (int)get_config('backup', 'backup_auto_max_kept'),
            ],
            'disk_usage' => [
                'total_bytes' => $diskusage,
                'total_readable' => display_size($diskusage),
                'quota_bytes' => $quotadisk,
                'quota_readable' => display_size($quotadisk),
                'percentage' => round($diskpercent, 2),
                'details' => [
                    'database' => [
                        'bytes' => (int)($diranalysis['database'] ?? 0),
                        'readable' => display_size($diranalysis['database'] ?? 0),
                        'percentage' => helper::calculate_percentage($diranalysis['database'] ?? 0, $diskusage)
                    ],
                    'filedir' => [
                        'bytes' => (int)($diranalysis['filedir'] ?? 0),
                        'readable' => display_size($diranalysis['filedir'] ?? 0),
                        'percentage' => helper::calculate_percentage($diranalysis['filedir'] ?? 0, $diskusage)
                    ],
                    'cache' => [
                        'bytes' => (int)($diranalysis['cache'] ?? 0),
                        'readable' => display_size($diranalysis['cache'] ?? 0),
                        'percentage' => helper::calculate_percentage($diranalysis['cache'] ?? 0, $diskusage)
                    ],
                    'backup' => [
                        'bytes' => 0,
                        'readable' => display_size(0),
                        'percentage' => 0
                    ],
                    'others' => [
                        'bytes' => (int)($diranalysis['others'] ?? 0),
                        'readable' => display_size($diranalysis['others'] ?? 0),
                        'percentage' => helper::calculate_percentage($diranalysis['others'] ?? 0, $diskusage)
                    ]
                ]
            ],
            'user_usage' => [
                'daily_users' => $userstoday,
                'threshold' => $userthreshold,
                'percentage' => round($userspercent, 2),
                'max_90_days' => (int)($reportconfig->max_userdaily_for_90_days_users ?? 0),
                'max_90_days_date' => $max90daysdate ? date('Y-m-d', $max90daysdate) : null
            ],
            'largest_courses' => [],
            'timestamps' => [
                'disk_calculation' => $lastdiskcalc,
                'users_calculation' => $lastuserscalc
            ],
            'growth_rates' => [
                'disk' => [
                    'monthly_percent' => 5.0, // Placeholder
                    'projected_days_to_threshold' => 100 // Placeholder
                ],
                'users' => [
                    'monthly_percent' => 3.0, // Placeholder
                    'projected_days_to_threshold' => 150 // Placeholder
                ]
            ]
        ];
        
        // Format courses data.
        foreach ($largestcourses as $course) {
            if (is_array($course)) {
                $response['largest_courses'][] = [
                    'id' => (int)($course['id'] ?? 0),
                    'fullname' => format_string($course['fullname'] ?? ''),
                    'shortname' => format_string($course['shortname'] ?? ''),
                    'size_bytes' => (int)($course['filesize'] ?? 0),
                    'size_readable' => display_size($course['filesize'] ?? 0),
                    'backup_size_bytes' => (int)($course['backupsize'] ?? 0),
                    'backup_size_readable' => display_size($course['backupsize'] ?? 0),
                    'percentage' => (float)($course['percentage'] ?? 0),
                    'backup_count' => (int)($course['backupcount'] ?? 0)
                ];
            }
        }
        
        return $response;
    }

    /**
     * Returns description of get_monitor_stats return value.
     *
     * @return external_single_structure
     */
    public static function get_monitor_stats_returns() {
        return new external_single_structure(
            array(
                'site_info' => new external_single_structure(
                    array(
                        'name' => new external_value(PARAM_TEXT, 'Site name'),
                        'shortname' => new external_value(PARAM_TEXT, 'Site short name'),
                        'moodle_version' => new external_value(PARAM_INT, 'Moodle version'),
                        'moodle_release' => new external_value(PARAM_TEXT, 'Moodle release'),
                        'course_count' => new external_value(PARAM_INT, 'Number of courses'),
                        'user_count' => new external_value(PARAM_INT, 'Number of users'),
                        'backup_auto_max_kept' => new external_value(PARAM_INT, 'Max auto backups kept')
                    )
                ),
                'disk_usage' => new external_single_structure(
                    array(
                        'total_bytes' => new external_value(PARAM_INT, 'Total disk usage in bytes'),
                        'total_readable' => new external_value(PARAM_TEXT, 'Human readable disk usage'),
                        'quota_bytes' => new external_value(PARAM_INT, 'Disk quota in bytes'),
                        'quota_readable' => new external_value(PARAM_TEXT, 'Human readable disk quota'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Disk usage percentage'),
                        'details' => new external_single_structure(
                            array(
                                'database' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Human readable size'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage')
                                    )
                                ),
                                'filedir' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Human readable size'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage')
                                    )
                                ),
                                'cache' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Human readable size'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage')
                                    )
                                ),
                                'backup' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Human readable size'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage')
                                    )
                                ),
                                'others' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Human readable size'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Percentage')
                                    )
                                )
                            )
                        )
                    )
                ),
                'user_usage' => new external_single_structure(
                    array(
                        'daily_users' => new external_value(PARAM_INT, 'Daily users'),
                        'threshold' => new external_value(PARAM_INT, 'User threshold'),
                        'percentage' => new external_value(PARAM_FLOAT, 'User percentage'),
                        'max_90_days' => new external_value(PARAM_INT, 'Max users in 90 days'),
                        'max_90_days_date' => new external_value(PARAM_TEXT, 'Date of max users', VALUE_OPTIONAL)
                    )
                ),
                'largest_courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Course ID'),
                            'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                            'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                            'size_bytes' => new external_value(PARAM_INT, 'Size in bytes'),
                            'size_readable' => new external_value(PARAM_TEXT, 'Human readable size'),
                            'backup_size_bytes' => new external_value(PARAM_INT, 'Backup size in bytes'),
                            'backup_size_readable' => new external_value(PARAM_TEXT, 'Human readable backup size'),
                            'percentage' => new external_value(PARAM_FLOAT, 'Percentage of total'),
                            'backup_count' => new external_value(PARAM_INT, 'Number of backups')
                        )
                    )
                ),
                'timestamps' => new external_single_structure(
                    array(
                        'disk_calculation' => new external_value(PARAM_INT, 'Disk calculation timestamp'),
                        'users_calculation' => new external_value(PARAM_INT, 'Users calculation timestamp')
                    )
                ),
                'growth_rates' => new external_single_structure(
                    array(
                        'disk' => new external_single_structure(
                            array(
                                'monthly_percent' => new external_value(PARAM_FLOAT, 'Monthly growth rate'),
                                'projected_days_to_threshold' => new external_value(PARAM_INT, 'Days to threshold')
                            )
                        ),
                        'users' => new external_single_structure(
                            array(
                                'monthly_percent' => new external_value(PARAM_FLOAT, 'Monthly growth rate'),
                                'projected_days_to_threshold' => new external_value(PARAM_INT, 'Days to threshold')
                            )
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Returns description of get_notification_history parameters.
     *
     * @return external_function_parameters
     */
    public static function get_notification_history_parameters() {
        return new external_function_parameters(
            array(
                'type' => new external_value(PARAM_ALPHA, 'Notification type', VALUE_DEFAULT, 'all'),
                'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 30),
                'offset' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Get notification history.
     *
     * @param string $type Type of notification
     * @param int $limit Maximum records
     * @param int $offset Offset for pagination
     * @return array History records
     */
    public static function get_notification_history($type = 'all', $limit = 30, $offset = 0) {
        global $DB;
        
        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);
        
        // Validate parameters.
        $params = self::validate_parameters(self::get_notification_history_parameters(), 
                                           array('type' => $type, 'limit' => $limit, 'offset' => $offset));
        
        // Build query.
        $where = '';
        $sqlparams = array();
        
        if ($params['type'] !== 'all') {
            $where = ' WHERE type = :type';
            $sqlparams['type'] = $params['type'];
        }
        
        $sql = "SELECT * FROM {report_usage_monitor_history}" . $where . 
               " ORDER BY timecreated DESC";
        
        $records = $DB->get_records_sql($sql, $sqlparams, $params['offset'], $params['limit']);
        
        // Format results.
        $results = array();
        foreach ($records as $record) {
            $results[] = array(
                'id' => $record->id,
                'type' => $record->type,
                'percentage' => (float)$record->percentage,
                'value' => $record->type === 'disk' ? display_size($record->value) : (string)$record->value,
                'value_raw' => (int)$record->value,
                'threshold' => $record->type === 'disk' ? display_size($record->threshold) : (string)$record->threshold,
                'threshold_raw' => (int)$record->threshold,
                'timecreated' => (int)$record->timecreated,
                'timereadable' => userdate($record->timecreated)
            );
        }
        
        return array(
            'total' => $DB->count_records('report_usage_monitor_history', $sqlparams),
            'limit' => $params['limit'],
            'offset' => $params['offset'],
            'items' => $results
        );
    }

    /**
     * Returns description of get_notification_history return value.
     *
     * @return external_single_structure
     */
    public static function get_notification_history_returns() {
        return new external_single_structure(
            array(
                'total' => new external_value(PARAM_INT, 'Total records'),
                'limit' => new external_value(PARAM_INT, 'Limit'),
                'offset' => new external_value(PARAM_INT, 'Offset'),
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Record ID'),
                            'type' => new external_value(PARAM_ALPHA, 'Notification type'),
                            'percentage' => new external_value(PARAM_FLOAT, 'Percentage'),
                            'value' => new external_value(PARAM_TEXT, 'Human readable value'),
                            'value_raw' => new external_value(PARAM_INT, 'Raw value'),
                            'threshold' => new external_value(PARAM_TEXT, 'Human readable threshold'),
                            'threshold_raw' => new external_value(PARAM_INT, 'Raw threshold'),
                            'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                            'timereadable' => new external_value(PARAM_TEXT, 'Human readable date')
                        )
                    )
                )
            )
        );
    }

    /**
     * Returns description of get_usage_data parameters.
     *
     * @return external_function_parameters
     */
    public static function get_usage_data_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get precalculated usage data.
     *
     * @return array Usage data
     */
    public static function get_usage_data() {
        global $DB;
        
        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);
        
        // Get configuration.
        $reportconfig = get_config('report_usage_monitor');
        
        // Check if API is enabled.
        if (empty($reportconfig->enable_api)) {
            throw new moodle_exception('apidisabled', 'report_usage_monitor');
        }
        
        // Disk usage data.
        $diskusage = ((int)($reportconfig->totalusagereadable ?? 0) + 
                      (int)($reportconfig->totalusagereadabledb ?? 0));
        $quotadisk = ((int)($reportconfig->disk_quota ?? 10) * 1024 * 1024 * 1024);
        $diskpercent = helper::calculate_percentage($diskusage, $quotadisk);
        
        // User data.
        $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
        $userthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $userspercent = helper::calculate_percentage($userstoday, $userthreshold);
        
        // Validate timestamps.
        $lastdiskcalc = helper::validate_timestamp($reportconfig->lastexecutioncalculate ?? 0);
        $lastuserscalc = helper::validate_timestamp($reportconfig->lastexecution ?? 0);
        $max90daysdate = helper::validate_timestamp($reportconfig->max_userdaily_for_90_days_date ?? 0);
        
        // Build response.
        $response = array(
            'disk_usage' => array(
                'current' => $diskusage,
                'current_readable' => display_size($diskusage),
                'threshold' => $quotadisk,
                'threshold_readable' => display_size($quotadisk),
                'percentage' => round($diskpercent, 2),
                'last_calculated' => $lastdiskcalc
            ),
            'user_usage' => array(
                'current' => $userstoday,
                'threshold' => $userthreshold,
                'percentage' => round($userspercent, 2),
                'last_calculated' => $lastuserscalc,
                'max_90_days' => (int)($reportconfig->max_userdaily_for_90_days_users ?? 0),
                'max_90_days_date' => $max90daysdate
            ),
            'projections' => array(
                'disk_growth_rate' => 5.0, // Placeholder
                'users_growth_rate' => 3.0, // Placeholder
                'days_to_disk_threshold' => 100, // Placeholder
                'days_to_users_threshold' => 150 // Placeholder
            )
        );
        
        return $response;
    }

    /**
     * Returns description of get_usage_data return value.
     *
     * @return external_single_structure
     */
    public static function get_usage_data_returns() {
        return new external_single_structure(
            array(
                'disk_usage' => new external_single_structure(
                    array(
                        'current' => new external_value(PARAM_INT, 'Current disk usage in bytes'),
                        'current_readable' => new external_value(PARAM_TEXT, 'Human readable disk usage'),
                        'threshold' => new external_value(PARAM_INT, 'Disk threshold in bytes'),
                        'threshold_readable' => new external_value(PARAM_TEXT, 'Human readable threshold'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Disk usage percentage'),
                        'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp')
                    )
                ),
                'user_usage' => new external_single_structure(
                    array(
                        'current' => new external_value(PARAM_INT, 'Current users'),
                        'threshold' => new external_value(PARAM_INT, 'User threshold'),
                        'percentage' => new external_value(PARAM_FLOAT, 'User usage percentage'),
                        'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                        'max_90_days' => new external_value(PARAM_INT, 'Max users in 90 days'),
                        'max_90_days_date' => new external_value(PARAM_INT, 'Max users date timestamp')
                    )
                ),
                'projections' => new external_single_structure(
                    array(
                        'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Disk growth rate'),
                        'users_growth_rate' => new external_value(PARAM_FLOAT, 'Users growth rate'),
                        'days_to_disk_threshold' => new external_value(PARAM_INT, 'Days to disk threshold'),
                        'days_to_users_threshold' => new external_value(PARAM_INT, 'Days to users threshold')
                    )
                )
            )
        );
    }

    /**
     * Returns description of set_usage_thresholds parameters.
     *
     * @return external_function_parameters
     */
    public static function set_usage_thresholds_parameters() {
        return new external_function_parameters(
            array(
                'user_threshold' => new external_value(PARAM_INT, 'User threshold', VALUE_DEFAULT, null),
                'disk_threshold' => new external_value(PARAM_INT, 'Disk threshold in GB', VALUE_DEFAULT, null)
            )
        );
    }

    /**
     * Set usage thresholds.
     *
     * @param int|null $user_threshold New user threshold
     * @param int|null $disk_threshold New disk threshold
     * @return array Result
     */
    public static function set_usage_thresholds($user_threshold = null, $disk_threshold = null) {
        global $DB;
        
        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:manage', $context);
        
        // Validate parameters.
        $params = self::validate_parameters(self::set_usage_thresholds_parameters(), 
                                           array('user_threshold' => $user_threshold,
                                                 'disk_threshold' => $disk_threshold));
        
        $result = array(
            'success' => true,
            'user_threshold_updated' => false,
            'disk_threshold_updated' => false,
            'messages' => array()
        );
        
        // Update user threshold if provided.
        if ($params['user_threshold'] !== null) {
            if ($params['user_threshold'] > 0) {
                set_config('max_daily_users_threshold', $params['user_threshold'], 'report_usage_monitor');
                $result['user_threshold_updated'] = true;
                $result['messages'][] = get_string('user_threshold_updated', 'report_usage_monitor');
                
                // Update calculated values.
                $reportconfig = get_config('report_usage_monitor');
                $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
                $userspercent = helper::calculate_percentage($userstoday, $params['user_threshold']);
                
                set_config('users_percent', $userspercent, 'report_usage_monitor');
            } else {
                $result['success'] = false;
                $result['messages'][] = get_string('error_user_threshold_negative', 'report_usage_monitor');
            }
        }
        
        // Update disk threshold if provided.
        if ($params['disk_threshold'] !== null) {
            if ($params['disk_threshold'] > 0) {
                set_config('disk_quota', $params['disk_threshold'], 'report_usage_monitor');
                $result['disk_threshold_updated'] = true;
                $result['messages'][] = get_string('disk_threshold_updated', 'report_usage_monitor');
                
                // Update calculated values.
                $reportconfig = get_config('report_usage_monitor');
                $diskusage = ((int)($reportconfig->totalusagereadable ?? 0) + 
                              (int)($reportconfig->totalusagereadabledb ?? 0));
                $quotadiskbytes = $params['disk_threshold'] * 1024 * 1024 * 1024;
                $diskpercent = helper::calculate_percentage($diskusage, $quotadiskbytes);
                
                set_config('disk_percent', $diskpercent, 'report_usage_monitor');
            } else {
                $result['success'] = false;
                $result['messages'][] = get_string('error_disk_threshold_negative', 'report_usage_monitor');
            }
        }
        
        // Check if no parameters provided.
        if ($params['user_threshold'] === null && $params['disk_threshold'] === null) {
            $result['success'] = false;
            $result['messages'][] = get_string('error_no_thresholds_provided', 'report_usage_monitor');
        }
        
        return $result;
    }

    /**
     * Returns description of set_usage_thresholds return value.
     *
     * @return external_single_structure
     */
    public static function set_usage_thresholds_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success status'),
                'user_threshold_updated' => new external_value(PARAM_BOOL, 'User threshold updated'),
                'disk_threshold_updated' => new external_value(PARAM_BOOL, 'Disk threshold updated'),
                'messages' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Message')
                )
            )
        );
    }
}