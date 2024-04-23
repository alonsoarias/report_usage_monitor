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

/**
 * Tarea programada para enviar notificaciones sobre el uso del disco y los límites diarios de usuarios.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

namespace report_usage_monitor\task;

// Prevenir el acceso directo a este archivo.
defined('MOODLE_INTERNAL') || die();

/**
 * Tarea programada para notificar el espacio en disco y los límites diarios de usuarios.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */
class notification extends \core\task\scheduled_task
{

    /**
     * Obtener el nombre de la tarea tal como se muestra en las pantallas de administración.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string('processdisknotificationtask', 'report_usage_monitor');
    }

    /**
     * Ejecutar la tarea para procesar las notificaciones sobre el espacio en disco y los límites diarios de usuarios.
     */
    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Obtener la configuración de report_usage_monitor.
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024; // Cuota de espacio en disco asignada (en bytes).
        $disk_usage = (int) $reportconfig->totalusagereadable + $reportconfig->totalusagereadadb; // Uso de espacio en disco (en bytes).
        $disk_percent = ($disk_usage / $quotadisk) * 100; // Porcentaje de uso del disco.

        // Verificar si el espacio en disco supera el umbral de advertencia (90%).
        if ($disk_percent >= 90) {
            // Verificar si ha pasado el tiempo de notificación requerido (5 días).
            $last_notified = (int) $reportconfig->last_notification_time; // Última vez que se envió una notificación (marca de tiempo UNIX).
            $notification_interval = 5 * 24 * 60 * 60; // 5 días en segundos.
            $time_difference = time() - $last_notified;
            if ($time_difference >= $notification_interval) {
                // Notificar cuando el espacio en disco está en el rango del 90% al 95% y ha pasado el tiempo de notificación requerido.
                email_notify_disk($quotadisk, $disk_usage);
                set_config('last_notification_time', time(), 'report_usage_monitor');
            }
        }

        // Verificar si el espacio en disco supera el límite (95%).
        if ($disk_percent >= 95) {
            // Notificar diariamente si el espacio en disco supera el 95%.
            email_notify_disk($quotadisk, $disk_usage);
            set_config('last_notification_time', time(), 'report_usage_monitor');
        }

        // Restablecer la última notificación si el espacio en disco está por debajo del umbral de advertencia.
        if ($disk_percent < 90) {
            set_config('last_notification_time', 0, 'report_usage_monitor');
        }

        // Notificar límite diario de usuarios.
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $lastday_users_records = $DB->get_records_sql($lastday_users);
        foreach ($lastday_users_records as $item) {
            if ($item->conteo_accesos_unicos >= get_config('report_usage_monitor', 'max_daily_users_threshold')) {
                email_notify_user_limit($item->conteo_accesos_unicos, $item->fecha);
            }
        }
    }
}
