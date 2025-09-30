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

namespace report_usage_monitor\task;

use report_usage_monitor\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to calculate maximum users in last 90 days.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_daily_90_days extends \core\task\scheduled_task {
    
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('getlastusers90days', 'report_usage_monitor');
    }
    
    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        global $DB;
        
        mtrace("Starting calculation of maximum users in last 90 days...");
        
        $ninetydaysago = time() - (90 * 24 * 60 * 60);
        $now = time();
        
        // Get max users in last 90 days grouped by day.
        $sql = "SELECT MAX(usercount) as maxusers, 
                       MIN(daytime) as maxdate
                FROM (
                    SELECT COUNT(DISTINCT userid) as usercount,
                           DATE(FROM_UNIXTIME(timecreated)) as daydate,
                           MIN(timecreated) as daytime
                    FROM {logstore_standard_log}
                    WHERE action = :action
                      AND timecreated BETWEEN :from AND :to
                    GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ) subquery
                WHERE usercount = (
                    SELECT MAX(usercount)
                    FROM (
                        SELECT COUNT(DISTINCT userid) as usercount
                        FROM {logstore_standard_log}
                        WHERE action = :action2
                          AND timecreated BETWEEN :from2 AND :to2
                        GROUP BY DATE(FROM_UNIXTIME(timecreated))
                    ) maxquery
                )";
        
        $params = [
            'action' => 'loggedin',
            'from' => $ninetydaysago,
            'to' => $now,
            'action2' => 'loggedin',
            'from2' => $ninetydaysago,
            'to2' => $now
        ];
        
        // For database compatibility, use simpler query.
        $sql = "SELECT COUNT(DISTINCT userid) as usercount,
                       DATE(FROM_UNIXTIME(timecreated)) as daydate
                FROM {logstore_standard_log}
                WHERE action = :action
                  AND timecreated BETWEEN :from AND :to
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY usercount DESC";
        
        $params = [
            'action' => 'loggedin',
            'from' => $ninetydaysago,
            'to' => $now
        ];
        
        $records = $DB->get_records_sql($sql, $params, 0, 1);
        
        if ($records) {
            $record = reset($records);
            $maxusers = $record->usercount;
            
            // Store the maximum.
            set_config('max_userdaily_for_90_days_users', $maxusers, 'report_usage_monitor');
            set_config('max_userdaily_for_90_days_date', time(), 'report_usage_monitor');
            
            // Log to history if needed.
            if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
                $reportconfig = get_config('report_usage_monitor');
                $threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
                
                $historyrecord = new \stdClass();
                $historyrecord->type = 'users90d';
                $historyrecord->percentage = helper::calculate_percentage($maxusers, $threshold);
                $historyrecord->value = $maxusers;
                $historyrecord->threshold = $threshold;
                $historyrecord->timecreated = time();
                
                try {
                    $DB->insert_record('report_usage_monitor_history', $historyrecord);
                } catch (\Exception $e) {
                    debugging('Failed to log to history: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
            
            mtrace("Maximum users in last 90 days: $maxusers");
        } else {
            mtrace("No user data found for the last 90 days.");
        }
        
        set_config('lastexecutioncalculateusers90days', time(), 'report_usage_monitor');
        
        mtrace("Task completed.");
        
        return true;
    }
}