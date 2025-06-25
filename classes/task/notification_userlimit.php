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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

/**
 * Scheduled task for user limit notifications.
 * 
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_userlimit extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('processuserlimitnotificationtask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $CFG;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting user limit notification task...");
        }

        $result = $this->notify_user_limit();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("User limit notification task completed.");
        }
        
        return $result;
    }

    /**
     * Process user limit notification
     *
     * @return bool Success status
     */
    private function notify_user_limit()
    {
        global $DB;
        
        $user_usage = usage_monitor_data_manager::get_user_usage();
        $warning_level = $user_usage->warning_level;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("User threshold: {$user_usage->threshold}");
            mtrace("Warning level: {$warning_level}%");
        }

        // Get yesterday's user data
        $sql = "SELECT COUNT(DISTINCT userid) AS conteo_accesos_unicos, 
                       UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(timecreated))) AS timestamp_fecha
                FROM {logstore_standard_log}
                WHERE action = 'loggedin'
                  AND timecreated > :start_time
                GROUP BY timestamp_fecha
                ORDER BY timestamp_fecha DESC
                LIMIT 1";
                
        $params = ['start_time' => strtotime('-1 day')];
        $lastday_users_record = $DB->get_record_sql($sql, $params);
        
        if (!$lastday_users_record) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("No user data found for the last day.");
            }
            return true;
        }
        
        $users_count = (int)$lastday_users_record->conteo_accesos_unicos;
        $users_percent = calculate_threshold_percentage($users_count, $user_usage->threshold);
        $fecha_timestamp = $lastday_users_record->timestamp_fecha;
        
        if (!is_numeric($fecha_timestamp) || $fecha_timestamp <= 0) {
            debugging('Invalid timestamp obtained: ' . var_export($fecha_timestamp, true), DEBUG_DEVELOPER);
            $fecha_timestamp = time();
        }
        
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Unique users: $users_count, Percentage: $users_percent%, Date: " . date('d/m/Y', $fecha_timestamp));
        }
        
        if ($users_percent < $warning_level) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("User usage ({$users_percent}%) is below warning level ({$warning_level}%). No notification needed.");
            }
            return true;
        }
        
        $notification_interval = $this->calculate_notification_interval($users_percent);
        $last_notification_time = get_config('report_usage_monitor', 'last_notificationusers_time') ?: 0;
        $current_time = time();
        
        if ($current_time - $last_notification_time < $notification_interval) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Notification interval not reached.");
                $time_remaining = ($last_notification_time + $notification_interval) - $current_time;
                mtrace("Next notification possible in: " . format_time($time_remaining));
            }
            return true;
        }
        
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Sending user limit notification...");
        }
        
        $result = usage_monitor_notifications::send_user_limit_notification(
            $users_count, 
            $fecha_timestamp, 
            $users_percent
        );
        
        if ($result) {
            set_config('last_notificationusers_time', $current_time, 'report_usage_monitor');
            $this->log_notification($users_percent, $users_count, $user_usage->threshold);
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Notification sent successfully.");
            }
        } else {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Error sending notification email.");
            }
        }
        
        return $result;
    }

    /**
     * Calculate notification interval based on user percentage
     *
     * @param float $users_percent User usage percentage
     * @return int Interval in seconds
     */
    private function calculate_notification_interval($users_percent)
    {
        if (!is_numeric($users_percent)) {
            debugging('Non-numeric user percentage: ' . var_export($users_percent, true), DEBUG_DEVELOPER);
            return PHP_INT_MAX;
        }

        $config = usage_monitor_data_manager::get_config();
        $warning_level = (float)($config->users_warning_level ?? 90);
        
        $critical_threshold = min(100, $warning_level + 10);
        $high_threshold = $warning_level;
        $low_threshold = max(70, $warning_level - 10);
        
        $thresholds = [
            $critical_threshold => 24 * 60 * 60,     // 1 day
            $high_threshold => 3 * 24 * 60 * 60,     // 3 days
            $low_threshold => 7 * 24 * 60 * 60       // 1 week
        ];

        foreach ($thresholds as $threshold => $interval) {
            if ($users_percent >= $threshold) {
                return $interval;
            }
        }

        return PHP_INT_MAX;
    }
    
    /**
     * Log notification in history
     *
     * @param float $users_percent User percentage
     * @param int $users_count User count
     * @param int $user_threshold User threshold
     * @return bool Success status
     */
    private function log_notification($users_percent, $users_count, $user_threshold)
    {
        global $DB;
        
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'users';
            $record->percentage = $users_percent;
            $record->value = $users_count;
            $record->threshold = $user_threshold;
            $record->timecreated = time();
            
            $transaction = $DB->start_delegated_transaction();
            
            try {
                $DB->insert_record('report_usage_monitor_history', $record);
                $transaction->allow_commit();
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Notification logged in history.");
                }
                return true;
            } catch (\Exception $e) {
                $transaction->rollback($e);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Error logging notification: " . $e->getMessage());
                }
                return false;
            }
        }
        
        return false;
    }
}