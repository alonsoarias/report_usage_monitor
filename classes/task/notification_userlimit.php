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

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de notificación de límite de usuarios...");
        }

        $this->notify_user_limit();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Tarea de notificación de límite de usuarios completada.");
        }
    }

    private function notify_user_limit()
    {
        global $DB;
        $reportconfig = get_config('report_usage_monitor');
        $user_threshold = $reportconfig->max_daily_users_threshold;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Umbral de usuarios: $user_threshold");
        }

        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $lastday_users_records = $DB->get_records_sql($lastday_users);

        $last_notificationusers_time = get_config('report_usage_monitor', 'last_notificationusers_time');
        $current_time = time();

        foreach ($lastday_users_records as $item) {
            $users_percent = calculate_threshold_percentage($item->conteo_accesos_unicos, $user_threshold);
            $notification_interval = $this->calculate_notification_interval($users_percent);

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Usuarios únicos: {$item->conteo_accesos_unicos}, Porcentaje de usuarios: $users_percent%, Intervalo de notificación: $notification_interval segundos");
            }

            if ($current_time - $last_notificationusers_time >= $notification_interval) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Enviando notificación de límite de usuarios...");
                }
                email_notify_user_limit($item->conteo_accesos_unicos, $item->fecha, $users_percent);
                set_config('last_notificationusers_time', $current_time, 'report_usage_monitor');
            } else {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("No ha pasado el intervalo de notificación.");
                }
            }
        }
    }

    private function calculate_notification_interval($users_percent)
    {
        $thresholds = [
            100 => 24 * 60 * 60,     // 1 día
            90 => 3 * 24 * 60 * 60,  // 3 días
            80 => 7 * 24 * 60 * 60   // 1 semana
        ];

        foreach ($thresholds as $threshold => $interval) {
            if ($users_percent >= $threshold) {
                return $interval;
            }
        }

        return PHP_INT_MAX; // No notification if under 80%
    }
}
