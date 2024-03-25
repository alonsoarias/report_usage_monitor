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
 
 class users_daily extends \core\task\scheduled_task {
     public function get_name() {
         return get_string('getlastusers', 'report_usage_monitor');
     }
 
     public function execute() {
         global $DB, $CFG;
         require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');
 
         // Inicialización de $array_daily_top como array vacío para evitar el error de variable no definida.
         $array_daily_top = [];
 
         // Obtener el top de usuarios diarios.
         $userdailytop = report_user_daily_top_task();
         $userdaily_recordstop = $DB->get_records_sql($userdailytop);
         foreach ($userdaily_recordstop as $log) {
             $array_daily_top[] = [
                 "usuarios" => $log->cantidad_usuarios,
                 "fecha" => $log->fecha,
             ];
         }
 
         // Verificar si $array_daily_top no está vacío antes de usar min().
         if (!empty($array_daily_top)) {
             $menor = min(array_column($array_daily_top, 'usuarios'));
         } else {
             $menor = null;
         }
 
         // Inicialización de $users como null para evitar el error de variable no definida.
         $users = null;
 
         // Obtener el límite diario de usuarios.
         $users_daily = user_limit_daily_task();
         $users_daily_record = $DB->get_records_sql($users_daily);
         foreach ($users_daily_record as $log) {
             $users = [
                 "usuarios" => $log->conteo_accesos_unicos,
                 "fecha" => $log->fecha,
             ];
             // Suponiendo que solo necesitamos el último registro, rompemos el bucle.
             break;
         }
 
         // Verifica que $users no sea null antes de proceder.
         if ($users !== null && !empty($array_daily_top)) {
             // Insertar el registro si el top de usuarios diarios no tiene 10 registros.
             if (count($array_daily_top) < 10) {
                 insert_top_sql($users["fecha"], $users["usuarios"]);
             } else {
                 // Identifica el registro menor para su actualización.
                 foreach ($array_daily_top as $item) {
                     if ($item['usuarios'] == $menor) {
                         $menor_fecha = $item['fecha'];
                         break;
                     }
                 }
                 // Actualizar el top de usuarios diarios si el número de usuarios actuales es mayor o igual al menor registro en el top.
                 if ($users["usuarios"] >= $menor) {
                     update_min_top_sql($users["fecha"], $users["usuarios"], $menor_fecha);
                 }
             }
         }
 
         set_config('lastexecution', time(), 'report_usage_monitor');
     }
 }
 