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

class notification_disk extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('processdisknotificationtask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        self::notify_disk_usage();
    }

    private static function calculate_notification_interval($disk_percent)
    {
        $thresholds = [
            99.9 => 12 * 60 * 60,   // 12 horas
            98.5 => 24 * 60 * 60,   // 1 día
            90 => 5 * 24 * 60 * 60, // 5 días
        ];

        foreach ($thresholds as $threshold => $interval) {
            if ($disk_percent >= $threshold) {
                return $interval;
            }
        }

        return 0; // No notification if under 90%
    }

    private static function notify_disk_usage()
    {
        global $DB;
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $disk_percent = ($quotadisk > 0) ? ($disk_usage / $quotadisk) * 100 : 0;

        $notification_interval = self::calculate_notification_interval($disk_percent);
        $last_notificationdisk_time = get_config('report_usage_monitor', 'last_notificationdisk_time');
        $current_time = time();

        if ($current_time - $last_notificationdisk_time >= $notification_interval) {
            $userAccessCount = self::get_total_user_access_count();
            email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount);
            set_config('last_notificationdisk_time', $current_time, 'report_usage_monitor');
        }
    }

    private static function get_total_user_access_count()
    {
        global $DB;
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        return (int) $DB->get_field_sql($lastday_users);
    }
}
