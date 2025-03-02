<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Servicios externos del plugin report_usage_monitor.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'report_usage_monitor_get_monitor_stats' => array(
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'get_monitor_stats',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Obtiene las estadísticas actuales de uso del sistema.',
        'type'          => 'read',
        'capabilities'  => 'report/usage_monitor:view',
        'ajax'          => true,
    ),
    'report_usage_monitor_get_notification_history' => array(
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'get_notification_history',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Obtiene el historial de notificaciones enviadas.',
        'type'          => 'read',
        'capabilities'  => 'report/usage_monitor:view',
        'ajax'          => true,
    ),
    'report_usage_monitor_register_webhook' => array(
        'classname'     => 'report_usage_monitor_external',
        'methodname'    => 'register_webhook',
        'classpath'     => 'report/usage_monitor/classes/external.php',
        'description'   => 'Registra un webhook para recibir notificaciones.',
        'type'          => 'write',
        'capabilities'  => 'moodle/site:config',
        'ajax'          => true,
    ),
);

// Definimos los servicios
$services = array(
    'Usage Monitor API' => array(
        'functions' => array(
            'report_usage_monitor_get_monitor_stats',
            'report_usage_monitor_get_notification_history',
            'report_usage_monitor_register_webhook',
        ),
        'restrictedusers' => 0, // No es restringido por usuario
        'enabled' => 1, // Habilitado por defecto
        'shortname' => 'report_usage_monitor',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ),
);