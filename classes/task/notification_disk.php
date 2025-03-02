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

    /**
     * Calcula el intervalo de notificación basado en el porcentaje de uso del disco.
     * 
     * @param float $disk_percent Porcentaje de uso del disco.
     * @return int Intervalo en segundos entre notificaciones.
     */
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

    /**
     * Gestiona el proceso de notificación del uso de disco.
     * Versión mejorada que incluye análisis detallado por directorios.
     */
    private function notify_disk_usage()
    {
        global $DB, $CFG;
        
        // Obtener configuraciones y calcular uso
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk);

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Cuota de disco: $quotadisk bytes, Uso de disco: $disk_usage bytes, Porcentaje de disco: $disk_percent%");
        }

        // Determinar si es necesario enviar notificación
        $notification_interval = $this->calculate_notification_interval($disk_percent);
        $last_notificationdisk_time = get_config('report_usage_monitor', 'last_notificationdisk_time') ?: 0;
        $current_time = time();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Intervalo de notificación: $notification_interval segundos, Última notificación: $last_notificationdisk_time");
        }

        if ($current_time - $last_notificationdisk_time >= $notification_interval) {
            // Recopilar información adicional para la notificación mejorada
            $userAccessCount = $this->get_total_user_access_count();
            
            // Análisis por directorios
            $dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);
            
            // Cursos más grandes
            $largest_courses = get_largest_courses(5);
            
            // Calcular tasas de crecimiento
            $growth_rate = calculate_growth_rate('disk');
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Enviando notificación de uso de disco mejorada...");
                mtrace("Análisis por directorios completado: " . count($dir_analysis) . " directorios analizados");
                mtrace("Cursos más grandes identificados: " . count($largest_courses) . " cursos");
            }
            
            // Enviar notificación con toda la información recopilada
            email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount);
            
            // Actualizar tiempo de última notificación
            set_config('last_notificationdisk_time', $current_time, 'report_usage_monitor');
            
            // Guardar historial de notificaciones (si se implementa esta funcionalidad)
            $this->log_notification($disk_percent, $disk_usage, $quotadisk);
            
        } else {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("No ha pasado el intervalo de notificación.");
                $time_remaining = ($last_notificationdisk_time + $notification_interval) - $current_time;
                mtrace("Próxima notificación posible en: " . format_time($time_remaining));
            }
        }
    }

    /**
     * Obtiene el conteo total de accesos de usuarios.
     * 
     * @return int Conteo de usuarios únicos del último día.
     */
    private function get_total_user_access_count()
    {
        global $DB;
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        return (int) $DB->get_field_sql($lastday_users);
    }
    
    /**
     * Registra información sobre la notificación enviada.
     * Esta función permite llevar un historial de las notificaciones.
     * 
     * @param float $disk_percent Porcentaje de uso del disco.
     * @param int $disk_usage Uso del disco en bytes.
     * @param int $quotadisk Cuota de disco en bytes.
     */
    private function log_notification($disk_percent, $disk_usage, $quotadisk)
    {
        global $DB;
        
        // Verificar si existe una tabla para almacenar el historial de notificaciones
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'disk';
            $record->percentage = $disk_percent;
            $record->value = $disk_usage;
            $record->threshold = $quotadisk;
            $record->timecreated = time();
            
            try {
                $DB->insert_record('report_usage_monitor_history', $record);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Notificación registrada en el historial.");
                }
            } catch (\Exception $e) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Error al registrar la notificación: " . $e->getMessage());
                }
            }
        }
    }
}