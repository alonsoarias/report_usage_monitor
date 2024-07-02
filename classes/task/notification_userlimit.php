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

defined('MOODLE_INTERNAL') || die();

class notification_userlimit extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('processuserlimitnotificationtask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        $this->notify_user_limit();
    }

    private function notify_user_limit()
    {
        global $DB;
        $reportconfig = get_config('report_usage_monitor');
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $lastday_users_records = $DB->get_records_sql($lastday_users);

        foreach ($lastday_users_records as $item) {
            $user_threshold = $reportconfig->max_daily_users_threshold;
            $users_percent = calculate_user_threshold_percentage($item->conteo_accesos_unicos, $user_threshold);
            $notification_interval = $this->calculate_notification_interval($users_percent);

            $last_notificationusers_time = get_config('report_usage_monitor', 'last_notificationusers_time');
            $current_time = time();

            if ($current_time - $last_notificationusers_time >= $notification_interval) {
                email_notify_user_limit($item->conteo_accesos_unicos, $item->fecha, $users_percent);
                set_config('last_notificationusers_time', $current_time, 'report_usage_monitor');
            }
        }
    }

    private function calculate_notification_interval($users_percent)
    {
        $thresholds = [
            99.9 => 12 * 60 * 60,   // 12 horas
            98.5 => 24 * 60 * 60,   // 1 día
            90 => 5 * 24 * 60 * 60, // 5 días
        ];
        foreach ($thresholds as $threshold => $interval) {
            if ($users_percent >= $threshold) {
                return $interval;
            }
        }
        return 0; // No notification if under 90%
    }
}
