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
use report_usage_monitor\notification;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for disk usage notifications.
 *
 * @package     report_usage_monitor
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_disk extends \core\task\scheduled_task {
    
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('processdisknotificationtask', 'report_usage_monitor');
    }
    
    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        mtrace("Starting disk usage notification task...");
        
        $result = $this->notify_disk_usage();
        
        mtrace("Disk usage notification task completed.");
        
        return $result;
    }
    
    /**
     * Process disk usage notification.
     *
     * @return bool
     */
    private function notify_disk_usage() {
        global $DB;
        
        // Get plugin configuration.
        $reportconfig = get_config('report_usage_monitor');
        
        // Calculate disk usage and percentage.
        $quotadisk = ((int)($reportconfig->disk_quota ?? 10) * 1024 * 1024 * 1024);
        $diskusage = ((int)($reportconfig->totalusagereadable ?? 0) + 
                      (int)($reportconfig->totalusagereadabledb ?? 0));
        
        if ($quotadisk <= 0) {
            mtrace("Invalid disk quota configuration.");
            return false;
        }
        
        $diskpercent = helper::calculate_percentage($diskusage, $quotadisk);
        
        // Get warning level (default: 90%).
        $warninglevel = (int)($reportconfig->disk_warning_level ?? 90);
        
        mtrace("Disk quota: " . helper::format_bytes($quotadisk) . 
               ", Usage: " . helper::format_bytes($diskusage) . 
               ", Percentage: $diskpercent%");
        mtrace("Warning level: $warninglevel%");
        
        // Check if percentage exceeds warning level.
        if ($diskpercent < $warninglevel) {
            mtrace("Disk usage ($diskpercent%) is below warning level ($warninglevel%). No notification needed.");
            return true;
        }
        
        // Determine notification interval.
        $notificationinterval = $this->calculate_notification_interval($diskpercent);
        $lastnotificationtime = (int)get_config('report_usage_monitor', 'last_notificationdisk_time');
        $currenttime = time();
        
        mtrace("Notification interval: $notificationinterval seconds, Last notification: " . 
               ($lastnotificationtime ? userdate($lastnotificationtime) : 'Never'));
        
        // Check if enough time has passed.
        if ($currenttime - $lastnotificationtime < $notificationinterval) {
            $timeremaining = ($lastnotificationtime + $notificationinterval) - $currenttime;
            mtrace("Notification interval not reached. Next possible in: " . format_time($timeremaining));
            return true;
        }
        
        // Send notification.
        mtrace("Sending disk usage notification...");
        
        $notification = new notification();
        $result = $notification->send_disk_notification($diskusage, $quotadisk, $diskpercent);
        
        if ($result) {
            set_config('last_notificationdisk_time', $currenttime, 'report_usage_monitor');
            $this->log_notification($diskpercent, $diskusage, $quotadisk);
            mtrace("Notification sent successfully.");
        } else {
            mtrace("Failed to send notification.");
        }
        
        return $result;
    }
    
    /**
     * Calculate notification interval based on usage percentage.
     *
     * @param float $diskpercent Disk usage percentage
     * @return int Interval in seconds
     */
    private function calculate_notification_interval($diskpercent) {
        if (!is_numeric($diskpercent)) {
            return PHP_INT_MAX;
        }
        
        // Define thresholds and intervals.
        $thresholds = [
            99.9 => 12 * 60 * 60,    // 12 hours for critical usage (>99.9%)
            98.5 => 24 * 60 * 60,    // 1 day for very high usage (>98.5%)
            95.0 => 3 * 24 * 60 * 60, // 3 days for high usage (>95%)
            90.0 => 5 * 24 * 60 * 60, // 5 days for warning level (>90%)
        ];
        
        foreach ($thresholds as $threshold => $interval) {
            if ($diskpercent >= $threshold) {
                return $interval;
            }
        }
        
        // Default to weekly notifications.
        return 7 * 24 * 60 * 60;
    }
    
    /**
     * Log notification to history.
     *
     * @param float $percentage Usage percentage
     * @param int $value Current usage
     * @param int $threshold Quota
     * @return bool
     */
    private function log_notification($percentage, $value, $threshold) {
        global $DB;
        
        if (!$DB->get_manager()->table_exists('report_usage_monitor_history')) {
            return false;
        }
        
        $record = new \stdClass();
        $record->type = 'disk';
        $record->percentage = $percentage;
        $record->value = $value;
        $record->threshold = $threshold;
        $record->timecreated = time();
        
        try {
            $DB->insert_record('report_usage_monitor_history', $record);
            mtrace("Notification logged to history.");
            return true;
        } catch (\Exception $e) {
            debugging('Failed to log notification: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}