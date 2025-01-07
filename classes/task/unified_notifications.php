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
 * Unified notifications task for disk usage and user limit monitoring.
 *
 * @package     report_usage_monitor
 * @category    task
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Unified notification task class.
 */
class unified_notifications extends \core\task\scheduled_task {
    /** @var int Critical threshold percentage */
    const CRITICAL_THRESHOLD = 95;
    
    /** @var int High threshold percentage */
    const HIGH_THRESHOLD = 90;
    
    /** @var int Medium threshold percentage */
    const MEDIUM_THRESHOLD = 80;
    
    /** @var int Critical notification interval - 12 hours in seconds */
    const CRITICAL_INTERVAL = 43200;
    
    /** @var int High notification interval - 24 hours in seconds */
    const HIGH_INTERVAL = 86400;
    
    /** @var int Medium notification interval - 3 days in seconds */
    const MEDIUM_INTERVAL = 259200;
    
    /** @var int Low notification interval - 7 days in seconds */
    const LOW_INTERVAL = 604800;

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('processunifiednotificationtask', 'report_usage_monitor');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting unified notifications task...");
        }

        try {
            $this->process_notifications();
        } catch (\Exception $e) {
            mtrace('Error processing notifications: ' . $e->getMessage());
            return false;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Unified notifications task completed.");
        }
    }

    /**
     * Process all system notifications based on current metrics.
     */
    private function process_notifications() {
        global $DB;
        $reportconfig = get_config('report_usage_monitor');

        // Collect all system metrics
        $metrics = $this->collect_system_metrics();

        // Determine alert levels
        $disk_alert_level = $this->get_alert_level($metrics['disk_percent']);
        $user_alert_level = $this->get_alert_level($metrics['user_percent']);

        // Add alert levels to metrics
        $metrics['disk_alert_level'] = $disk_alert_level;
        $metrics['user_alert_level'] = $user_alert_level;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Disk usage: {$metrics['disk_percent']}% - Level: $disk_alert_level");
            mtrace("User count: {$metrics['user_percent']}% - Level: $user_alert_level");
        }

        // Get the highest percentage for interval calculation
        $highest_percent = max($metrics['disk_percent'], $metrics['user_percent']);
        $notification_interval = $this->calculate_unified_notification_interval($highest_percent);

        $last_notification_time = get_config('report_usage_monitor', 'last_unified_notification_time');
        $current_time = time();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Time since last notification: " . ($current_time - $last_notification_time) . " seconds");
            mtrace("Required interval: $notification_interval seconds");
        }

        // Check if it's time to send notifications
        if ($current_time - $last_notification_time >= $notification_interval) {
            if ($highest_percent >= self::MEDIUM_THRESHOLD) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Sending unified system notification...");
                }

                // Add thresholds to metrics for reference
                $metrics['thresholds'] = [
                    'critical' => self::CRITICAL_THRESHOLD,
                    'high' => self::HIGH_THRESHOLD,
                    'medium' => self::MEDIUM_THRESHOLD
                ];

                $success = email_notify_unified($metrics);

                if ($success) {
                    set_config('last_unified_notification_time', $current_time, 'report_usage_monitor');
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Notification sent successfully.");
                    }
                } else {
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Failed to send notification.");
                    }
                }
            }
        }
    }

    /**
     * Collect all system metrics.
     *
     * @return array Array containing all system metrics
     */
    private function collect_system_metrics() {
        global $DB;
        $reportconfig = get_config('report_usage_monitor');

        // Get disk metrics
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk);

        // Get database size
        $size = size_database();
        $size_database = $DB->get_records_sql($size);
        $database_size = 0;
        foreach ($size_database as $item) {
            $database_size = $item->size;
        }

        // Get user metrics
        $user_threshold = $reportconfig->max_daily_users_threshold;
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $lastday_users_records = $DB->get_records_sql($lastday_users);
        $user_data = reset($lastday_users_records);

        $user_count = 0;
        $fecha = date('d/m/Y');
        if ($user_data) {
            $user_count = $user_data->conteo_accesos_unicos;
            $fecha = $user_data->fecha;
        }

        $user_percent = calculate_threshold_percentage($user_count, $user_threshold);

        return array(
            'disk_quota' => $quotadisk,
            'disk_usage' => $disk_usage,
            'disk_percent' => $disk_percent,
            'database_size' => $database_size,
            'user_count' => $user_count,
            'user_threshold' => $user_threshold,
            'user_percent' => $user_percent,
            'fecha' => $fecha,
            'coursescount' => $DB->count_records('course'),
            'backupcount' => get_config('backup', 'backup_auto_max_kept')
        );
    }

    /**
     * Calculate notification interval based on percentage.
     *
     * @param float $percent The percentage to evaluate
     * @return int The notification interval in seconds
     */
    private function calculate_unified_notification_interval($percent) {
        if ($percent >= self::CRITICAL_THRESHOLD) {
            return self::CRITICAL_INTERVAL;
        } else if ($percent >= self::HIGH_THRESHOLD) {
            return self::HIGH_INTERVAL;
        } else if ($percent >= self::MEDIUM_THRESHOLD) {
            return self::MEDIUM_INTERVAL;
        }
        return self::LOW_INTERVAL;
    }

    /**
     * Get alert level based on percentage.
     *
     * @param float $percent Percentage to evaluate
     * @return string Alert level (critical, high, medium, normal)
     */
    private function get_alert_level($percent) {
        if ($percent >= self::CRITICAL_THRESHOLD) {
            return 'critical';
        } else if ($percent >= self::HIGH_THRESHOLD) {
            return 'high';
        } else if ($percent >= self::MEDIUM_THRESHOLD) {
            return 'medium';
        }
        return 'normal';
    }
}