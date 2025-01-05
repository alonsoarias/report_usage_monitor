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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Definición de las tareas programadas para el plugin report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Lista de tareas programadas del plugin
$tasks = [
    // 1) Tarea para calcular el uso del disco (disk_usage)
    //    Por defecto, se programa a las 12:00 cada día.
    //    La tarea ad-hoc (check_php_functions) podrá actualizarla
    //    a "*/6" si detecta shell_exec + pathtodu.
    [
        'classname' => 'report_usage_monitor\task\disk_usage',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '12',  // Valor por defecto
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ],

    // 2) Tarea para calcular los usuarios conectados más recientes (last_users)
    [
        'classname' => 'report_usage_monitor\task\last_users',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '*/2',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ],

    // 3) Tarea para calcular usuarios en los últimos 90 días (users_daily_90_days)
    [
        'classname' => 'report_usage_monitor\task\users_daily_90_days',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '0',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ],

    // 4) Tarea para calcular usuarios diarios (users_daily)
    [
        'classname' => 'report_usage_monitor\task\users_daily',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '0',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ],

    // 5) Tarea unificada de notificación de uso (usuarios + disco)
    [
        'classname' => 'report_usage_monitor\task\notification_usage',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '8', // p. ej. 1 vez al día a las 8 AM
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ],

    // 6) Tarea programada para encolar la ad-hoc check_php_functions cada 3 horas
    [
        'classname' => 'report_usage_monitor\task\check_env_scheduler',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '*/3',  // cada 3 horas
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*'
    ],
];
