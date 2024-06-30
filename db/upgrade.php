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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Definición de pasos de actualización del complemento.
 *
 * @package     report_usage_monitor
 * @category    upgrade
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Actualiza el complemento report_usage_monitor.
 *
 * @param int $oldversion La versión antigua del complemento
 * @return bool
 */
function xmldb_report_usage_monitor_upgrade($oldversion)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');
    $dbman = $DB->get_manager();

    // Función de actualización.
    if ($oldversion < 2022090200) {
        // Define la tabla report_usage_monitor que se creará.
        $table = new xmldb_table('report_usage_monitor');

        // Agrega campos a la tabla report_usage_monitor.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fecha', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cantidad_usuarios', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Agrega claves a la tabla report_usage_monitor.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Crea la tabla report_usage_monitor de forma condicional.
        try {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        } catch (Exception $e) {
            echo "Error generado: $e";
        }

        // Punto de guardado de la versión 2022090200.
        upgrade_plugin_savepoint(true, 2022090200, 'report', 'usage_monitor');
    }

    if ($oldversion < 2022091600) {
        // Punto de guardado de la versión 2022091600.
        upgrade_plugin_savepoint(true, 2022091600, 'report', 'usage_monitor');
    }
    if ($oldversion < 2022103100) {
        // Se actualiza el campo fecha en el informe report_usage_monitor de tipo fecha a timestamp.
        $sql = "UPDATE {report_usage_monitor} set fecha=(UNIX_TIMESTAMP(STR_TO_DATE(fecha, '%d/%m/%Y')))";
        $DB->execute($sql);
        // Punto de guardado de la versión 2022103100.
        upgrade_plugin_savepoint(true, 2022103100, 'report', 'usage_monitor');
    }
    if ($oldversion < 2022110101) {
        // Punto de guardado de la versión 2022110101.
        upgrade_plugin_savepoint(true, 2022110101, 'report', 'usage_monitor');
    }
    if ($oldversion < 2022121100) {
        // Punto de guardado de la versión 2022121100.
        upgrade_plugin_savepoint(true, 2022121100, 'report', 'usage_monitor');
    }
    if ($oldversion < 2022121600) {
        // Punto de guardado de la versión 2022121600.
        upgrade_plugin_savepoint(true, 2022121600, 'report', 'usage_monitor');
    }
    if ($oldversion < 2022121601) {
        // Punto de guardado de la versión 2022121601.
        upgrade_plugin_savepoint(true, 2022121601, 'report', 'usage_monitor');
    }
    if ($oldversion < 2022121604) {
        // Punto de guardado de la versión 2022121604.
        unset_config('max_userdaily_for_90_days_date', 'report_usage_monitor');
        unset_config('max_userdaily_for_90_days_users', 'report_usage_monitor');
        unset_config('totalusagereadable', 'report_usage_monitor');
        unset_config('totalusagereadabledb', 'report_usage_monitor');
        unset_config('lastexecutioncalculate', 'report_usage_monitor');
        unset_config('lastexecution', 'report_usage_monitor');
        unset_config('totalusersdaily', 'report_usage_monitor');
        upgrade_plugin_savepoint(true, 2022121604, 'report', 'usage_monitor');
    }

    // A partir de aquí, realizamos las actualizaciones para la versión 2023080100.
    if ($oldversion < 2023080100) {
        // Aquí se pueden agregar los pasos de actualización específicos para la nueva versión.
        // No hay pasos de actualización necesarios en esta versión, por lo que este bloque queda vacío.
        // Punto de guardado de la versión 2023080100.
        upgrade_plugin_savepoint(true, 2023080100, 'report', 'usage_monitor');
    }
    // A partir de aquí, realizamos las actualizaciones para la versión 2023080107.
    if ($oldversion < 2023080107) {
        // Aquí se pueden agregar los pasos de actualización específicos para la nueva versión (2023080107).
        // No hay pasos de actualización necesarios en esta versión, por lo que este bloque queda vacío.

        // Mostramos las notificaciones de recomendación o advertencia según la disponibilidad de shell_exec.
        if (function_exists('shell_exec')) {
            upgrade_show_recommended_notification();
        } else {
            upgrade_show_warning_notification();
        }
        // Punto de guardado de la versión 2023080107.
        upgrade_plugin_savepoint(true, 2023080107, 'report', 'usage_monitor');
    }
    if ($oldversion < 2024040101) {
        // Aquí no se necesitan cambios en la base de datos,
        // solo actualizamos el punto de guardado de la versión del plugin.

        // Actualiza la versión guardada del plugin en la base de datos.
        upgrade_plugin_savepoint(true, 2024040101, 'report', 'usage_monitor');
    }
    if ($oldversion < 2024042201) {
        // Aquí no se necesitan cambios en la base de datos,
        // solo actualizamos el punto de guardado de la versión del plugin.

        // Actualiza la versión guardada del plugin en la base de datos.
        upgrade_plugin_savepoint(true, 2024042201, 'report', 'usage_monitor');
    }
    
    // A partir de aquí, realizamos las actualizaciones para la versión 2024070101.
    if ($oldversion < 2024070101) {
        // Añadimos los índices para los campos fecha y cantidad_usuarios.
        $table = new xmldb_table('report_usage_monitor');

        // Índice para el campo fecha.
        $index = new xmldb_index('idx_fecha', XMLDB_INDEX_NOTUNIQUE, ['fecha']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Índice para el campo cantidad_usuarios.
        $index = new xmldb_index('idx_cantidad_usuarios', XMLDB_INDEX_NOTUNIQUE, ['cantidad_usuarios']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Punto de guardado de la versión 2024070101.
        upgrade_plugin_savepoint(true, 2024070101, 'report', 'usage_monitor');
    }

    return true;
}

// Función para mostrar la notificación de recomendación si shell_exec está disponible.
function upgrade_show_recommended_notification()
{
    global $OUTPUT;
    echo $OUTPUT->notification(
        get_string('pathtodurecommendation', 'report_usage_monitor'), // Mostramos un mensaje de recomendación. Este texto será traducido según el idioma del usuario.
        'info' // Se muestra una notificación informativa.
    );
    echo $OUTPUT->notification(
        get_string('pathtodunote', 'report_usage_monitor'), // Mostramos una nota adicional. Este texto será traducido según el idioma del usuario.
        'info' // Se muestra una notificación informativa.
    );
}

// Función para mostrar la notificación de advertencia si shell_exec no está disponible.
function upgrade_show_warning_notification()
{
    global $OUTPUT;
    echo $OUTPUT->notification(
        get_string('activateshellexec', 'report_usage_monitor'), // Mostramos un mensaje de advertencia. Este texto será traducido según el idioma del usuario.
        'warning' // Se muestra una notificación de advertencia.
    );
}
