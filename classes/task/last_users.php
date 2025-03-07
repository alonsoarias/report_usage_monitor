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
 * Tarea para calcular los usuarios conectados recientemente.
 */
class last_users extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('getlastusersconnected', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de cálculo de usuarios conectados recientemente...");
        }

        // Recuperar los usuarios conectados recientemente para hoy.
        $users = $DB->get_records_sql(users_today());
        foreach ($users as $log) {
            $users_today = $log->conteo_accesos_unicos;
            set_config('totalusersdaily', $users_today, 'report_usage_monitor');
        }

        // Precomputar porcentajes para el dashboard
        $reportconfig = get_config('report_usage_monitor');
        $max_users_threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $users_percent = calculate_threshold_percentage($users_today, $max_users_threshold);
        $users_warning_class = ($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger');

        // Guardar valores precomputados
        set_config('users_percent', $users_percent, 'report_usage_monitor');
        set_config('users_warning_class', $users_warning_class, 'report_usage_monitor');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Usuarios conectados recientemente: $users_today.");
            // mtrace("Tarea de cálculo de usuarios conectados recientemente completada.");
        }
        $execution_time = time();
        set_config('lastexecution', $execution_time, 'report_usage_monitor');
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Tarea completada.");
        }

        return true;
    }
}
