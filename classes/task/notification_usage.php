<?php
// This file is part of Moodle - https://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tarea programada para unificar la notificación de límite de usuarios
 * y uso de disco en un solo correo.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase programada que verifica el límite de usuarios y el uso de disco,
 * y envía un correo unificado con toda la información.
 */
class notification_usage extends \core\task\scheduled_task
{
    /**
     * Nombre de la tarea, se muestra en la lista de tareas programadas.
     *
     * @return string
     */
    public function get_name()
    {
        // Este identificador debe existir en tu archivo de idioma:
        // $string['notification_usage_taskname'] = 'Notificación unificada de uso (disco + usuarios)';
        return get_string('notification_usage_taskname', 'report_usage_monitor');
    }

    /**
     * Lógica principal de la tarea programada.
     *
     * @return bool
     */
    public function execute()
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Mostrar mensaje en logs de depuración (cron).
        mtrace("Iniciando tarea unificada de notificación (usuarios + disco)...");

        $reportconfig = get_config('report_usage_monitor');

        // ------------------------------------------------
        // 1) Verificar número de usuarios del día anterior
        // ------------------------------------------------
        $userthreshold = (int)$reportconfig->max_daily_users_threshold;  // umbral
        // Consulta para obtener usuarios día anterior.
        // Nota: user_limit_daily_sql(...) debe estar definida en locallib.php
        $userSql = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $record  = $DB->get_record_sql($userSql);

        // Si no hubo registros, 0 usuarios.
        $usersYesterday = $record ? (int)$record->conteo_accesos_unicos : 0;

        // Porcentaje respecto al umbral.
        $userPercent   = 0;
        if ($userthreshold > 0) {
            $userPercent = ($usersYesterday / $userthreshold) * 100;
        }
        // ¿Se excedió el umbral de usuarios?
        $exceededUsers = ($usersYesterday > $userthreshold);

        // ------------------------------------------------
        // 2) Verificar uso de disco
        // ------------------------------------------------
        // Cuota en bytes
        $diskQuotaBytes = ((int)$reportconfig->disk_quota * 1024 * 1024 * 1024);
        // Uso total (combinar dataroot+dirroot + DB).
        $totalFS = (int)($reportconfig->totalusagereadable   ?? 0);
        $totalDB = (int)($reportconfig->totalusagereadabledb ?? 0);
        $totalDiskUsage = $totalFS + $totalDB;

        $diskPercent = 0;
        if ($diskQuotaBytes > 0) {
            $diskPercent = ($totalDiskUsage / $diskQuotaBytes) * 100;
        }
        // Ejemplo: considerar excedido si >= 90%
        $exceededDisk = ($diskPercent >= 90);

        // ------------------------------------------------
        // 3) Controlar intervalo mínimo de notificación
        // ------------------------------------------------
        $lastNotificationUsage = (int)get_config('report_usage_monitor', 'last_notification_usage_time');
        $currentTime           = time();
        $notifyEvery           = 24 * 60 * 60; // 1 día

        // Si no se excedió nada, o si no pasó el intervalo, omitir
        if (!$exceededUsers && !$exceededDisk) {
            mtrace("No se excedió ningún umbral, no se enviará correo unificado.");
            return true;
        }
        if (($currentTime - $lastNotificationUsage) < $notifyEvery) {
            mtrace("Se notificó hace menos de 24 horas. Omitiendo envío.");
            return true;
        }

        // ------------------------------------------------
        // 4) Preparar el objeto $info con todos los datos
        // ------------------------------------------------
        $info             = new \stdClass();
        $info->sitename   = format_string(get_site()->fullname);
        $info->siteurl    = $CFG->wwwroot;

        // Datos de usuarios
        $info->userthreshold = $userthreshold;
        $info->users         = $usersYesterday;
        $info->userpercent   = $userPercent;

        // Datos de disco
        $info->diskusage   = display_size($totalDiskUsage);
        $info->diskquota   = display_size($diskQuotaBytes);
        $info->diskpercent = $diskPercent;

        // Datos extra (opcional, si antes se mostraban en las notificaciones separadas):
        // - Tamaño base de datos
        // - Número de cursos
        // - Valor de backup_auto_max_kept, etc.
        $info->databasesize  = display_size($reportconfig->totalusagereadabledb ?? 0);
        $info->coursescount  = $DB->count_records('course');
        $info->backupcount   = get_config('backup', 'backup_auto_max_kept'); // ejemplificando

        // Ej. si deseas incluir una tabla HTML con usuarios de últimos 10 días:
        // $info->table = notification_table(...);

        // ------------------------------------------------
        // 5) Llamar a la función unificada
        // ------------------------------------------------
        $sent = email_notify_usage_monitor($exceededUsers, $exceededDisk, $info);
        if ($sent) {
            // Actualizar última fecha de notificación
            set_config('last_notification_usage_time', $currentTime, 'report_usage_monitor');
            mtrace("Notificación unificada enviada satisfactoriamente.");
        } else {
            mtrace("No se pudo enviar la notificación unificada (o no se envió).");
        }

        mtrace("Tarea unificada completada.");

        return true;
    }
}
