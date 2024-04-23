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

    public function execute()
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Ejecutar notificaciones sin comprobar intervalos
        $this->notify_disk_usage();
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

    private function notify_disk_usage()
    {
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;

        $disk_usage_readable = isset($reportconfig->totalusagereadable) ? (int) $reportconfig->totalusagereadable : 0;
        $disk_usage_readadb = isset($reportconfig->totalusagereadadb) ? (int) $reportconfig->totalusagereadadb : 0;
        $disk_usage = $disk_usage_readable + $disk_usage_readadb;
        $disk_percent = ($quotadisk > 0) ? ($disk_usage / $quotadisk) * 100 : 0;

        // Get user access count using the provided snippet
        $userAccessCount = $this->get_total_user_access_count();

        // Call the disk limit notification function with user access count
        email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount);
    }

    private function get_total_user_access_count()
    {
        global $DB;
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $lastday_users_records = $DB->get_records_sql($lastday_users);
        foreach ($lastday_users_records as $item) {
            $totalAccessCount = $item->conteo_accesos_unicos;
        }
        return $totalAccessCount;
    }
}
