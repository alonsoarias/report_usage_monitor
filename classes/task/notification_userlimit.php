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

namespace report_usage_monitor\task;

use report_usage_monitor\helper;
use report_usage_monitor\notification;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for user limit notifications.
 *
 * @package     report_usage_monitor
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_userlimit extends \core\task\scheduled_task {
    
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('processuserlimitnotificationtask', 'report_usage_monitor');
    }
    
    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        mtrace("Starting user limit notification task...");
        
        $result = $this->notify_user_limit();
        
        mtrace("User limit notification task completed.");
        
        return $result;
    }
    
    /**
     * Process user limit notification.
     *
     * @return bool
     */
    private function notify_user_limit() {
        global $DB;
        
        // Get plugin configuration.
        $reportconfig = get_config('report_usage_monitor');
        
        // Get user threshold.
        $userthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        
        // Get warning level (default: 90%).
        $warninglevel = (int)($reportconfig->users_warning_level ?? 90);
        
        mtrace("User threshold: $userthreshold");
        mtrace("Warning level: $warninglevel%");
        
        // Get users from last 24 hours.
        $onedayago = time() - (24 * 60 * 60);
        $now = time();
        
        $userscount = helper::get_users_logged_in($onedayago, $now);
        
        if ($userscount === 0) {
            mtrace("No users found for the last day.");
            return true;
        }
        
        // Calculate percentage.
        $userspercent = helper::calculate_percentage($userscount, $userthreshold);
        
        mtrace("Users: $userscount, Percentage: $userspercent%");
        
        // Check if percentage exceeds warning level.
        if ($userspercent < $warninglevel) {
            mtrace("User count ($userspercent%) is below warning level ($warninglevel%). No notification needed.");
            return true;
        }
        
        // Determine notification interval.
        $notificationinterval = $this->calculate_notification_interval($userspercent);
        $lastnotificationtime = (int)get_config('report_usage_monitor', 'last_notificationusers_time');
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
        mtrace("Sending user limit notification...");
        
        $notification = new notification();
        $result = $notification->send_user_notification($userscount, $userthreshold, $userspercent, time());
        
        if ($result) {
            set_config('last_notificationusers_time', $currenttime, 'report_usage_monitor');
            $this->log_notification($userspercent, $userscount, $userthreshold);
            mtrace("Notification sent successfully.");
        } else {
            mtrace("Failed to send notification.");
        }
        
        return $result;
    }
    
    /**
     * Calculate notification interval based on usage percentage.
     *
     * @param float $userspercent User usage percentage
     * @return int Interval in seconds
     */
    private function calculate_notification_interval($userspercent) {
        if (!is_numeric($userspercent)) {
            return PHP_INT_MAX;
        }
        
        $thresholds = [
            100 => 24 * 60 * 60,     // 1 day when exceeds 100%
            95 => 2 * 24 * 60 * 60,  // 2 days when exceeds 95%
            90 => 3 * 24 * 60 * 60,  // 3 days when exceeds 90%
            80 => 7 * 24 * 60 * 60   // 1 week when exceeds 80%
        ];
        
        foreach ($thresholds as $threshold => $interval) {
            if ($userspercent >= $threshold) {
                return $interval;
            }
        }
        
        return PHP_INT_MAX;
    }
    
    /**
     * Log notification to history.
     *
     * @param float $percentage Usage percentage
     * @param int $value Current user count
     * @param int $threshold User limit
     * @return bool
     */
    private function log_notification($percentage, $value, $threshold) {
        global $DB;
        
        if (!$DB->get_manager()->table_exists('report_usage_monitor_history')) {
            return false;
        }
        
        $record = new \stdClass();
        $record->type = 'users';
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