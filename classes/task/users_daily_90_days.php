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

// Prevenir el acceso directo a este archivo.
defined('MOODLE_INTERNAL') || die();

/**
 * Tarea para calcular los usuarios principales en los últimos 90 días.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */
class users_daily_90_days extends \core\task\scheduled_task
{

    /**
     * Obtener el nombre de la tarea tal como se muestra en las pantallas de administración.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string('getlastusers90days', 'report_usage_monitor');
    }

    /**
     * Ejecutar la tarea para calcular los usuarios principales en los últimos 90 días.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');
    
        // Asumiendo que la función max_userdaily_for_90_days devuelve una consulta SQL correcta.
        $sql = max_userdaily_for_90_days(get_string('dateformatsql', 'report_usage_monitor'));
        $users_90_days_records = $DB->get_records_sql($sql);
        foreach ($users_90_days_records as $record) {
            // Asegúrate de que el nombre de la columna en tu consulta SQL sea 'usuarios'.
            // Si no, reemplaza 'usuarios' con el nombre de columna correcto.
            if (isset($record->usuarios)) {
                set_config('max_userdaily_for_90_days_date', $record->fecha, 'report_usage_monitor');
                set_config('max_userdaily_for_90_days_users', $record->usuarios, 'report_usage_monitor');
            }
        }
    }
    
}
