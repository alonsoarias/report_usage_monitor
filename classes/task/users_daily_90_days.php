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

/**
 * Tarea para calcular los usuarios principales en los últimos 90 días.
 */
class users_daily_90_days extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('getlastusers90days', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de cálculo de usuarios diarios en los últimos 90 días...");
        }

        // Asumiendo que la función max_userdaily_for_90_days devuelve una consulta SQL correcta.
        $sql = max_userdaily_for_90_days(get_string('dateformatsql', 'report_usage_monitor'));
        $users_90_days_records = $DB->get_records_sql($sql);

        $max_users = 0;
        $max_date = 0;

        foreach ($users_90_days_records as $record) {
            // Asegúrate de que el nombre de la columna en tu consulta SQL sea 'usuarios'.
            if (isset($record->usuarios)) {
                $max_users = $record->usuarios;
                $max_date = $record->fecha;

                set_config('max_userdaily_for_90_days_date', $record->fecha, 'report_usage_monitor');
                set_config('max_userdaily_for_90_days_users', $record->usuarios, 'report_usage_monitor');
            }
        }

        // Registrar el valor máximo en el historial para trazabilidad
        if ($max_users > 0 && $DB->get_manager()->table_exists('report_usage_monitor_history')) {
            // Verificar si ya existe un registro para esta fecha
            $existing = $DB->get_record_sql(
                "SELECT id FROM {report_usage_monitor_history}
                  WHERE type = 'users_90_days' AND timecreated = ?",
                [$max_date]
            );

            if (!$existing) {
                // Crear el registro histórico
                $reportconfig = get_config('report_usage_monitor');
                $threshold = $reportconfig->max_daily_users_threshold ?? 100;

                $record = new \stdClass();
                $record->type = 'users_90_days';
                $record->percentage = calculate_threshold_percentage($max_users, $threshold);
                $record->value = $max_users;
                $record->threshold = $threshold;
                $record->timecreated = $max_date;

                try {
                    $DB->insert_record('report_usage_monitor_history', $record);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Máximo de usuarios 90 días registrado en el historial.");
                    }
                } catch (\Exception $e) {
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Error al registrar el máximo de usuarios 90 días: " . $e->getMessage());
                    }
                }
            }
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Usuarios principales en los últimos 90 días calculados: " . $max_users . " en fecha " . userdate($max_date));
            mtrace("Tarea de cálculo de usuarios principales en los últimos 90 días completada.");
        }
    }
}
