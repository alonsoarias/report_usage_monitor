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

defined('MOODLE_INTERNAL') || die();

class disk_usage extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('calculatediskusagetask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de cálculo de uso del disco...");
        }

        // Calcular el tamaño de la base de datos.
        $size = size_database();
        $size_database = $DB->get_records_sql($size);
        foreach ($size_database as $item) {
            $totalusagereadabledb = $item->size;
            set_config('totalusagereadabledb', $totalusagereadabledb, 'report_usage_monitor');
        }

        // Calcular el tamaño del directorio dataroot.
        $totalusagedataroot = directory_size($CFG->dataroot);

        // Calcular el tamaño del directorio dirroot.
        $totalusagedirroot = directory_size($CFG->dirroot);

        // Calcular el total del uso del disco legible.
        $totalusagereadable = $totalusagedataroot + $totalusagedirroot;
        set_config('totalusagereadable', $totalusagereadable, 'report_usage_monitor');
        set_config('lastexecutioncalculate', time(), 'report_usage_monitor');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Uso del disco calculado. Total base de datos: $totalusagereadabledb bytes, Total dataroot: $totalusagedataroot bytes, Total dirroot: $totalusagedirroot bytes, Total uso legible: $totalusagereadable bytes.");
            mtrace("Tarea de cálculo de uso del disco completada.");
        }

        return true;
    }
}
