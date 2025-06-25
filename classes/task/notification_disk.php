<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option)any later version.
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
 * Scheduled task for disk usage notifications.
 * 
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_disk extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('processdisknotificationtask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $CFG;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting disk notification task...");
        }

        $result = $this->notify_disk_usage();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Disk notification task completed.");
        }
        
        return $result;
    }

    /**
     * Calculate notification interval based on disk usage percentage
     *
     * @param float $disk_percent Disk usage percentage
     * @return int Interval in seconds
     */
    private function calculate_notification_interval($disk_percent)
    {
        if (!is_numeric($disk_percent)) {
            debugging('Non-numeric disk percentage: ' . var_export($disk_percent, true), DEBUG_DEVELOPER);
            return PHP_INT_MAX;
        }

        $config = usage_monitor_data_manager::get_config();
        $warning_level = (float)($config->disk_warning_level ?? 90);
        
        $critical_threshold = min(99.9, $warning_level + 8);
        $high_threshold = min(98.5, $warning_level + 4);
        $base_threshold = $warning_level;
        
        $thresholds = [
            $critical_threshold => 12 * 60 * 60,    // 12 hours
            $high_threshold => 24 * 60 * 60,        // 1 day
            $base_threshold => 5 * 24 * 60 * 60,    // 5 days
        ];

        foreach ($thresholds as $threshold => $interval) {
            if ($disk_percent >= $threshold) {
                return $interval;
            }
        }

        return PHP_INT_MAX;
    }

    /**
     * Process disk usage notification
     *
     * @return bool Success status
     */
    private function notify_disk_usage()
    {
        global $DB;
        
        $disk_usage = usage_monitor_data_manager::get_disk_usage();
        $warning_level = $disk_usage->warning_level;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Disk quota: {$disk_usage->quota_bytes} bytes, Usage: {$disk_usage->current_bytes} bytes, Percentage: {$disk_usage->percentage}%");
            mtrace("Warning level: {$warning_level}%");
        }

        if ($disk_usage->percentage < $warning_level) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Disk usage ({$disk_usage->percentage}%) is below warning level ({$warning_level}%). No notification needed.");
            }
            return true;
        }

        $notification_interval = $this->calculate_notification_interval($disk_usage->percentage);
        $last_notification_time = get_config('report_usage_monitor', 'last_notificationdisk_time') ?: 0;
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
            mtrace("Sending disk usage notification...");
        }
        
        $user_access_count = $this->get_total_user_access_count();
        
        $result = usage_monitor_notifications::send_disk_usage_notification(
            $disk_usage->quota_bytes, 
            $disk_usage->current_bytes, 
            $disk_usage->percentage, 
            $user_access_count
        );
        
        if ($result) {
            set_config('last_notificationdisk_time', $current_time, 'report_usage_monitor');
            $this->log_notification($disk_usage->percentage, $disk_usage->current_bytes, $disk_usage->quota_bytes);
            
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
     * Get total user access count
     *
     * @return int User count
     */
    private function get_total_user_access_count()
    {
        global $DB;
        
        $sql = "SELECT COUNT(DISTINCT userid) 
                FROM {logstore_standard_log} 
                WHERE action = 'loggedin' 
                  AND timecreated > :start";
                  
        $params = ['start' => strtotime('-1 day')];
        
        return (int) $DB->get_field_sql($sql, $params);
    }
    
    /**
     * Log notification in history
     *
     * @param float $disk_percent Disk percentage
     * @param int $disk_usage Disk usage in bytes
     * @param int $quotadisk Disk quota in bytes
     * @return bool Success status
     */
    private function log_notification($disk_percent, $disk_usage, $quotadisk)
    {
        global $DB;
        
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'disk';
            $record->percentage = $disk_percent;
            $record->value = $disk_usage;
            $record->threshold = $quotadisk;
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