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
 * Tarea programada para encolar la ad-hoc check_php_functions.
 * Por defecto, se ejecuta cada 3 horas (ver db/tasks.php).
 *
 * @package     report_usage_monitor
 * @category    task
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

class check_env_scheduler extends \core\task\scheduled_task {

    /**
     * Nombre para identificar la tarea programada (logs, pantalla de config, etc.).
     */
    public function get_name() {
        return get_string('check_env_scheduler_taskname', 'report_usage_monitor');
    }

    /**
     * Se ejecuta (p.ej.) cada 3 horas y encola la ad-hoc check_php_functions.
     */
    public function execute() {
        mtrace("Iniciando 'check_env_scheduler' para encolar ad-hoc check_php_functions...");

        // Crear instancia de la ad-hoc
        $adhoc = new check_php_functions();
        // (Opcional) set_custom_data() si se desea pasar info adicional

        // Encolar
        \core\task\manager::queue_adhoc_task($adhoc);

        mtrace("Tarea ad-hoc 'check_php_functions' encolada. Finalizando check_env_scheduler.");
        return true;
    }
}
