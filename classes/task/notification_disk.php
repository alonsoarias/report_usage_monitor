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
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_usage = (int) $reportconfig->totalusagereadable + $reportconfig->totalusagereadadb;
        $disk_percent = ($disk_usage / $quotadisk) * 100;

        if ($disk_percent >= 98) {
            email_notify_disk_limit($quotadisk, $disk_usage,$disk_percent);
            set_config('last_notification_time', time(), 'report_usage_monitor');
        } elseif ($disk_percent >= 90) {
            $last_notified = (int) $reportconfig->last_notification_time;
            $notification_interval = 5 * 24 * 60 * 60;
            if (time() - $last_notified >= $notification_interval) {
                email_notify_disk_limit($quotadisk, $disk_usage,$disk_percent);
                set_config('last_notification_time', time(), 'report_usage_monitor');
            }
        }

        if ($disk_percent < 90) {
            set_config('last_notification_time', 0, 'report_usage_monitor');
        }
    }
}
