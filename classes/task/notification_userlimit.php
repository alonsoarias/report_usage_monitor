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

    /**
     * Gestiona el proceso de notificación del límite de usuarios.
     * Versión mejorada con más información contextual y análisis de tendencias.
     */
    private function notify_user_limit()
    {
        global $DB, $CFG;
        
        // Obtener configuraciones
        $reportconfig = get_config('report_usage_monitor');
        $user_threshold = $reportconfig->max_daily_users_threshold;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Umbral de usuarios: $user_threshold");
        }

        // Obtener usuarios activos del último día
        $lastday_users = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $lastday_users_records = $DB->get_records_sql($lastday_users);

        // Verificar si hay notificaciones previas
        $last_notificationusers_time = get_config('report_usage_monitor', 'last_notificationusers_time') ?: 0;
        $current_time = time();

        foreach ($lastday_users_records as $item) {
            // Calcular porcentaje de uso
            $users_percent = calculate_threshold_percentage($item->conteo_accesos_unicos, $user_threshold);
            $notification_interval = $this->calculate_notification_interval($users_percent);

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Usuarios únicos: {$item->conteo_accesos_unicos}, Porcentaje de usuarios: $users_percent%, Intervalo de notificación: $notification_interval segundos");
            }

            // Decidir si enviar notificación
            if ($current_time - $last_notificationusers_time >= $notification_interval) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Enviando notificación de límite de usuarios mejorada...");
                }
                
                // Recopilar información adicional para la notificación enriquecida
                $historical_data = $this->generate_historical_user_data($user_threshold);
                $growth_rate = calculate_growth_rate('users');
                $days_to_critical = project_limit_date($item->conteo_accesos_unicos, $user_threshold * 1.2, $growth_rate);
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Tasa de crecimiento: $growth_rate%, Días hasta nivel crítico: $days_to_critical");
                }
                
                // Enviar notificación mejorada
                email_notify_user_limit($item->conteo_accesos_unicos, $item->fecha, $users_percent);
                
                // Actualizar tiempo de última notificación
                set_config('last_notificationusers_time', $current_time, 'report_usage_monitor');
                
                // Guardar historial de notificaciones (si se implementa esta funcionalidad)
                $this->log_notification($users_percent, $item->conteo_accesos_unicos, $user_threshold);
                
            } else {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("No ha pasado el intervalo de notificación.");
                    $time_remaining = ($last_notificationusers_time + $notification_interval) - $current_time;
                    mtrace("Próxima notificación posible en: " . format_time($time_remaining));
                }
            }
        }
    }

    /**
     * Calcula el intervalo de notificación basado en el porcentaje de uso de usuarios.
     * 
     * @param float $users_percent Porcentaje de uso de usuarios.
     * @return int Intervalo en segundos entre notificaciones.
     */
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
    
    /**
     * Genera datos históricos de usuarios para incluir en las notificaciones.
     * 
     * @param int $user_threshold Umbral de usuarios configurado.
     * @return string HTML generado con datos históricos.
     */
    private function generate_historical_user_data($user_threshold)
    {
        return generate_historical_data_html(7, $user_threshold);
    }
    
    /**
     * Registra información sobre la notificación enviada.
     * Esta función permite llevar un historial de las notificaciones.
     * 
     * @param float $users_percent Porcentaje de uso de usuarios.
     * @param int $users_count Conteo de usuarios.
     * @param int $user_threshold Umbral de usuarios.
     */
    private function log_notification($users_percent, $users_count, $user_threshold)
    {
        global $DB;
        
        // Verificar si existe una tabla para almacenar el historial de notificaciones
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'users';
            $record->percentage = $users_percent;
            $record->value = $users_count;
            $record->threshold = $user_threshold;
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