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
 * Tarea programada para el monitoreo diario de usuarios.
 * Mantiene un registro histórico de los últimos 10 días con mayor cantidad de usuarios,
 * eliminando automáticamente registros mayores a 1 año.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
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

        try {
            // Eliminar registros mayores a 1 año
            $oneYearAgo = time() - (365 * 24 * 60 * 60);
            $oldRecordsCount = $DB->count_records_select('report_usage_monitor', 'fecha < ?', array($oneYearAgo));
            if ($oldRecordsCount > 0) {
                $DB->delete_records_select('report_usage_monitor', 'fecha < ?', array($oneYearAgo));
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Se eliminaron $oldRecordsCount registros mayores a 1 año.");
                }
            }

            // Obtener datos de usuarios del día anterior
            $users_daily = user_limit_daily_task();
            $users_daily_record = $DB->get_records_sql($users_daily);

            if (empty($users_daily_record)) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("No se encontraron registros de usuarios para el día anterior.");
                }
                set_config('lastexecution', time(), 'report_usage_monitor');
                return;
            }

            // Obtener el registro del día anterior
            $current_record = reset($users_daily_record);
            if (!$current_record || empty($current_record->conteo_accesos_unicos)) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("El registro del día anterior no contiene datos válidos.");
                }
                set_config('lastexecution', time(), 'report_usage_monitor');
                return;
            }

            // Preparar datos del día anterior
            $current_data = [
                'usuarios' => (int)$current_record->conteo_accesos_unicos,
                'fecha' => (int)$current_record->fecha // Ya viene como timestamp
            ];

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Procesando registro: fecha=" . userdate($current_data['fecha']) .
                    ", usuarios=" . $current_data['usuarios']);
            }

            // Verificar si el registro ya existe para esta fecha
            $existingRecord = $DB->get_record(
                'report_usage_monitor',
                array('fecha' => $current_data['fecha'])
            );

            if ($existingRecord) {
                // Actualizar solo si el nuevo valor es mayor
                if ($current_data['usuarios'] > $existingRecord->cantidad_usuarios) {
                    $DB->update_record('report_usage_monitor', (object)[
                        'id' => $existingRecord->id,
                        'fecha' => $current_data['fecha'],
                        'cantidad_usuarios' => $current_data['usuarios']
                    ]);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Registro actualizado para fecha existente.");
                    }
                }
            } else {
                // Obtener cantidad actual de registros y el valor mínimo
                $current_records = $DB->get_records('report_usage_monitor', null, 'cantidad_usuarios DESC');
                $total_records = count($current_records);

                if ($total_records < 10) {
                    // Insertar nuevo registro si hay menos de 10
                    $DB->insert_record('report_usage_monitor', (object)[
                        'fecha' => $current_data['fecha'],
                        'cantidad_usuarios' => $current_data['usuarios']
                    ]);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Nuevo registro insertado (total registros: " . ($total_records + 1) . ")");
                    }
                } else {
                    // Encontrar el registro con menor cantidad de usuarios
                    $min_record = end($current_records);
                    if ($current_data['usuarios'] > $min_record->cantidad_usuarios) {
                        $DB->update_record('report_usage_monitor', (object)[
                            'id' => $min_record->id,
                            'fecha' => $current_data['fecha'],
                            'cantidad_usuarios' => $current_data['usuarios']
                        ]);
                        if (debugging('', DEBUG_DEVELOPER)) {
                            mtrace("Se reemplazó el registro con menor cantidad de usuarios.");
                        }
                    }
                }
            }

            set_config('lastexecution', time(), 'report_usage_monitor');
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Tarea completada exitosamente.");
            }
        } catch (\Exception $e) {
            mtrace('Error en la ejecución de la tarea: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Indicates whether this task can be run from CLI.
     *
     * @return bool
     */
    public static function can_run_from_cli()
    {
        return true;
    }
}
