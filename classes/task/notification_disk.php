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

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de notificación de uso de disco...");
        }

        $this->notify_disk_usage();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Tarea de notificación de uso de disco completada.");
        }
    }

    private function calculate_notification_interval($disk_percent)
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

        return PHP_INT_MAX; // No notification if under 90%
    }

    private function notify_disk_usage()
    {
        global $DB;
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk);

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Cuota de disco: $quotadisk bytes, Uso de disco: $disk_usage bytes, Porcentaje de disco: $disk_percent%");
        }

        $notification_interval = $this->calculate_notification_interval($disk_percent);
        $last_notificationdisk_time = get_config('report_usage_monitor', 'last_notificationdisk_time');
        $current_time = time();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Intervalo de notificación: $notification_interval segundos, Última notificación: $last_notificationdisk_time");
        }

        if ($current_time - $last_notificationdisk_time >= $notification_interval) {
            $userAccessCount = $this->get_total_user_access_count();
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Enviando notificación de uso de disco...");
            }
            email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount);
            set_config('last_notificationdisk_time', $current_time, 'report_usage_monitor');
        } else {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("No ha pasado el intervalo de notificación.");
            }
        }
    }

    private function get_total_user_access_count()
    {
        global $DB;
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        return (int) $DB->get_field_sql($lastday_users);
    }
}
