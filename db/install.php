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
 * Páginas de administración del plugin se definen aquí.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Configura el script de instalación.
 * Esta función se ejecuta durante la instalación del complemento y muestra notificaciones al usuario según las capacidades del servidor.
 * @return void
 */
function xmldb_report_usage_monitor_install()
{
    global $CFG;

    $messages = [];

    if (function_exists('shell_exec')) {
        $messages[] = get_string('pathtodurecommendation', 'report_usage_monitor');
        $messages[] = get_string('pathtodunote', 'report_usage_monitor');
    } else {
        $messages[] = get_string('activateshellexec', 'report_usage_monitor');
    }

    if (CLI_SCRIPT) {
        require_once($CFG->libdir . '/clilib.php');
        foreach ($messages as $message) {
            cli_writeln($message);
        }
    } else {
        foreach ($messages as $message) {
            \core\notification::info($message);
        }
    }

    return true;
}
