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

class notification_disk extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('processdisknotificationtask', 'report_usage_monitor');
    }

    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        
        // Check if the property exists before trying to use it
        $disk_usage = (int) $reportconfig->totalusagereadable + (isset($reportconfig->totalusagereadadb) ? $reportconfig->totalusagereadadb : 0);
        
        $disk_percent = ($disk_usage / $quotadisk) * 100;

        $last_notified = (int) $reportconfig->last_notification_time;
        $current_time = time();
        $notification_interval = $this->calculate_notification_interval($disk_percent);
        $time_difference = $current_time - $last_notified;

        if ($time_difference >= $notification_interval) {
            // Ensure the function email_notify_disk is available
            if (function_exists('email_notify_disk_limit')) {
                email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent);
                set_config('last_notification_time', $current_time, 'report_usage_monitor');
            } else {
                // Handle the error if the function does not exist
                debugging('Function email_notify_disk_limit does not exist', DEBUG_DEVELOPER);
            }
        }
    }

    private function calculate_notification_interval($disk_percent) {
        if ($disk_percent >= 99.9) {
            return 12 * 60 * 60; // 12 hours
        } elseif ($disk_percent > 98.5) {
            return 24 * 60 * 60; // 1 day
        } elseif ($disk_percent >= 90) {
            return 5 * 24 * 60 * 60; // 5 days
        }
        return PHP_INT_MAX; // No notification needed if under 90%
    }
}
