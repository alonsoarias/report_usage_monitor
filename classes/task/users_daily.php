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
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

namespace report_usage_monitor\task;

// Esta línea protege el archivo para que no pueda ser accedido directamente por una URL.
defined('MOODLE_INTERNAL') || die();



/**
 * Tarea para calcular los usuarios diarios.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */
class users_daily extends \core\task\scheduled_task
{

    /**
     * Obtener el nombre de la tarea tal como se muestra en las pantallas de administración.
     *
     * @return string
     *
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string('getlastusers', 'report_usage_monitor');
    }

    /**
     * Ejecutar la tarea.
     */
    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Obtener el top de usuarios diarios.
        $userdailytop = report_user_daily_top_task();
        $userdaily_recordstop = $DB->get_records_sql($userdailytop);
        foreach ($userdaily_recordstop as $log) {
            $array_daily_top[] = array(
                "usuarios"  =>  $log->cantidad_usuarios,
                "fecha"  => $log->fecha,
            );
        }
        $menor = min($array_daily_top);

        // Obtener el límite diario de usuarios.
        $users_daily = user_limit_daily_task();
        $users_daily_record = $DB->get_records_sql($users_daily);
        foreach ($users_daily_record as $log) {
            $users = array(
                "usuarios"  =>  $log->conteo_accesos_unicos,
                "fecha"  => $log->fecha,
            );
        }

        // Insertar el registro si el top de usuarios diarios no tiene 10 registros.
        if (count($array_daily_top) < 10) {
            insert_top_sql($users["fecha"], $users["usuarios"]);
        } else {
            // Actualizar el top de usuarios diarios si el número de usuarios actuales es mayor o igual al menor registro en el top.
            if ($users["usuarios"] >= $menor["usuarios"]) {
                update_min_top_sql($users["fecha"], $users["usuarios"], $menor["fecha"]);
            }
        }

        set_config('lastexecution', time(), 'report_usage_monitor');
    }
}
