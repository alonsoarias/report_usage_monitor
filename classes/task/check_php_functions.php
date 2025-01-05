<?php
// This file is part of Moodle - http://moodle.org/
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
 * Tarea ad-hoc para verificar si shell_exec está activo y si pathtodu es ejecutable;
 * luego ajusta la frecuencia de la tarea disk_usage en la tabla de tareas programadas.
 *
 * @package     report_usage_monitor
 * @category    task
 * @copyright   
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase ad-hoc que extiende \core\task\adhoc_task.
 * Se encola para verificar dinámicamente la disponibilidad de shell_exec/pathtodu.
 */
class check_php_functions extends \core\task\adhoc_task {

    /**
     * Nombre descriptivo de la tarea (aparece en los logs de Moodle).
     */
    public function get_name() {
        return get_string('check_php_functions_taskname', 'report_usage_monitor');
    }

    /**
     * Lógica principal de la tarea ad-hoc.
     */
    public function execute() {
        global $CFG, $DB;

        mtrace("[Ad-hoc] check_php_functions iniciada...");

        // 1) Verificar si shell_exec está habilitado o no en este servidor.
        $disabledfunctions = explode(',', (string) ini_get('disable_functions'));
        $disabledfunctions = array_map('trim', $disabledfunctions);
        $shellExecOk = function_exists('shell_exec') && !in_array('shell_exec', $disabledfunctions, true);

        // 2) Verificar si pathtodu está configurado y es ejecutable
        $duOk = false;
        if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu)) && $shellExecOk) {
            $duOk = true;
        }

        mtrace(" - shell_exec activo?: " . ($shellExecOk ? 'SÍ' : 'NO'));
        mtrace(" - pathtodu ejecutable?: " . ($duOk ? 'SÍ' : 'NO'));

        // 3) Actualizar la tarea disk_usage en la tabla task_scheduled
        //    Cambiamos el campo 'hour' a '*/6' si duOk es true, o '12' si no.
        $classname = 'report_usage_monitor\task\disk_usage';
        $record = $DB->get_record('task_scheduled', ['classname' => $classname], '*', IGNORE_MISSING);

        if ($record) {
            if ($duOk) {
                // du disponible => cada 6 horas
                $record->hour = '*/6';
                mtrace(" -> Configurando 'disk_usage' para ejecutarse cada 6 horas (du disponible).");
            } else {
                // si no, a las 12:00
                $record->hour = '12';
                mtrace(" -> Configurando 'disk_usage' para ejecutarse a las 12:00 (sin du).");
            }
            $DB->update_record('task_scheduled', $record);
        } else {
            mtrace("No se encontró la tarea 'disk_usage' en la tabla task_scheduled.");
        }

        mtrace("[Ad-hoc] check_php_functions completada.");
    }
}
