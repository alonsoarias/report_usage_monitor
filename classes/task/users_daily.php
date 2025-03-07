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
 * Tarea programada para el uso del disco, para ejecutar los informes programados.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

class users_daily extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('getlastusers', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea para calcular el top de accesos únicos diarios...");
        }

        $array_daily_top = [];

        // Obtener el top de usuarios diarios.
        $userdailytop = report_user_daily_top_task();
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Ejecutando consulta: $userdailytop");
        }
        
        // Ajustar la consulta para no incluir la columna `id`.
        $userdaily_records = $DB->get_records_sql("SELECT fecha, cantidad_usuarios FROM ($userdailytop) AS userdailytop ORDER BY cantidad_usuarios DESC");
        
        foreach ($userdaily_records as $record) {
            $array_daily_top[] = [
                "usuarios" => $record->cantidad_usuarios,
                "fecha" => $record->fecha,
            ];
        }

        // Corrige el uso de min() verificando si $array_daily_top está vacío.
        if (!empty($array_daily_top)) {
            $menor = min(array_column($array_daily_top, 'usuarios'));
        } else {
            $menor = null;
        }

        // Verificar si hay que insertar un nuevo registro de usuarios.
        $users_daily = user_limit_daily_task();
        $users_daily_record = $DB->get_records_sql($users_daily);
        $users = [];
        foreach ($users_daily_record as $log) {
            $users = [
                "usuarios" => $log->conteo_accesos_unicos,
                "fecha" => $log->fecha,
            ];
            // Solo se espera un registro, así que se puede salir del bucle.
            break;
        }

        if (empty($array_daily_top) || count($array_daily_top) < 10) {
            // Se inserta si la tabla está vacía o tiene menos de 10 registros.
            insert_top_sql($users['fecha'], $users['usuarios']);
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Insertando nuevo registro.");
            }
        } else {
            // Se actualiza si hay 10 o más registros y el nuevo registro tiene más usuarios.
            if (!is_null($menor) && $users['usuarios'] >= $menor) {
                // La función update_min_top_sql debe identificar correctamente cuál registro actualizar.
                update_min_top_sql($users['fecha'], $users['usuarios'], $menor);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Actualizando registro existente.");
                }
            }
        }
        
        // Calcular el porcentaje de uso respecto al umbral
        $reportconfig = get_config('report_usage_monitor');
        $users_today = !empty($reportconfig->totalusersdaily) ? ($reportconfig->totalusersdaily) : 0;
        $max_users_threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $users_percent = calculate_threshold_percentage($users_today, $max_users_threshold);
        $users_warning_class = ($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger');
        
        // Guardar valores precomputados
        set_config('users_percent', $users_percent, 'report_usage_monitor');
        set_config('users_warning_class', $users_warning_class, 'report_usage_monitor');
        
        // Limpiar datos antiguos
        // Clean up records older than 6 months
        $sixMonthsAgo = time() - (180 * 24 * 60 * 60); // 180 days
        
        // Para report_usage_monitor_history tabla
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $oldHistoryRecords = $DB->get_records_select('report_usage_monitor_history', 'timecreated < ?', [$sixMonthsAgo]);
            if (!empty($oldHistoryRecords)) {
                $DB->delete_records_select('report_usage_monitor_history', 'timecreated < ?', [$sixMonthsAgo]);
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Removed " . count($oldHistoryRecords) . " records older than 6 months from report_usage_monitor_history.");
                }
            }
        }
        $execution_time = time();
        set_config('lastexecution', $execution_time, 'report_usage_monitor');
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Tarea completada.");
        }
    }
}