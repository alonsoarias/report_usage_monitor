<?php
// This file is part of Moodle - https://www.gnu.org/
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
 * Archivo de idioma (ES) para report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    string
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ------------------------------------------------------
// CADENAS GENERALES DEL PLUGIN
// ------------------------------------------------------
$string['pluginname'] = 'Usage Monitor';
$string['exclusivedisclaimer'] = 'Este plugin es de uso exclusivo del equipo de soporte Moodle de IngeWeb.';

// ------------------------------------------------------
// ENCABEZADOS / SECCIONES
// ------------------------------------------------------
$string['userstopnum'] = 'Usuarios diarios';
$string['diskusage']   = 'Uso del disco';

// ------------------------------------------------------
// SECCIÓN DE USUARIOS
// ------------------------------------------------------
$string['lastusers']     = 'Usuarios diarios de los últimos 10 días';
$string['topuser']       = 'Top 10 usuarios diarios';
$string['date']          = 'Fecha';
$string['usersquantity'] = 'Cantidad de usuarios diarios';
$string['usertable']     = 'Tabla de usuarios';
$string['userchart']     = 'Gráfica de usuarios';

// ------------------------------------------------------
// SECCIÓN DE USO DE DISCO
// ------------------------------------------------------
$string['notcalculatedyet']       = 'Aún no calculado';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['sizeusage']              = 'Total de uso de disco';
$string['avalilabledisk']         = '% de espacio disponible';
$string['sizedatabase']           = 'Tamaño de la base de datos';

// ------------------------------------------------------
// INFORMACIÓN / CRÉDITOS DEL REPORTE
// ------------------------------------------------------
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de IngeWeb. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// ------------------------------------------------------
// CONFIGURACIONES / AJUSTES
// ------------------------------------------------------
$string['email']       = 'Email para notificaciones';
$string['configemail'] = 'Dirección de correo para recibir alertas.';

$string['max_daily_users_threshold']       = 'Límite de usuarios';
$string['configmax_daily_users_threshold'] = 'Establezca el límite de usuarios diarios.';
$string['disk_quota']       = 'Cuota de disco';
$string['configdisk_quota'] = 'Configure la cuota de disco (en gigabytes) para las notificaciones.';


$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';
$string['pathtodu']          = 'Ruta al comando du';
$string['configpathtodu']    = 'Configura la ruta al comando du (uso de disco). Esto es necesario para calcular el uso de disco.';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a "du" en las Rutas del sistema de Moodle.';
$string['pathtodunote']      = 'Nota: La ruta a "du" se detectará automáticamente solo si este plugin está en un sistema Linux y se logra ubicar con éxito.';

// ------------------------------------------------------
// FORMATOS DE FECHA
// ------------------------------------------------------
$string['dateformatsql'] = '%d/%m/%Y';
$string['dateformat']    = 'd/m/Y';

// ------------------------------------------------------
// TAREAS Y NOTIFICACIONES
// ------------------------------------------------------
$string['check_php_functions_taskname']  = 'Verificar funciones PHP (tarea ad-hoc)';
$string['check_env_scheduler_taskname']  = 'Programar verificación de funciones PHP cada 3 horas';
$string['notification_usage_taskname']   = 'Notificación unificada de uso (disco + usuarios)';

// Se añaden las cadenas necesarias para las clases de tareas:
$string['calculatediskusagetask']     = 'Tarea para calcular el uso del disco';
$string['getlastusersconnected']      = 'Tarea para calcular los últimos usuarios conectados';
$string['getlastusers']              = 'Tarea para calcular el top de usuarios únicos diarios';
$string['getlastusers90days']        = 'Tarea para obtener el top de usuarios en los últimos 90 días';

// ------------------------------------------------------
// NOTIFICACIÓN UNIFICADA (DISCO + USUARIOS)
// ------------------------------------------------------
$string['subjectemail_unified'] = 'Alerta de uso en la plataforma {$a->sitename}';
$string['messagehtml_unified'] = '
<p>La plataforma <a href="{$a->siteurl}"><strong>{$a->sitename}</strong></a> ha sido verificada:</p>
<ul>
    <li><strong>¿Límite diario de usuarios excedido?</strong> {$a->exceededUsersLabel}</li>
    <li>Usuarios (ayer): <strong>{$a->users}</strong> / {$a->userthreshold} ({$a->userpercent}%)</li>
    <li><strong>¿Cuota de disco excedida?</strong> {$a->exceededDiskLabel}</li>
    <li>Uso de disco: <strong>{$a->diskusage}</strong> / {$a->diskquota} ({$a->diskpercent}%)</li>
    <li>Tamaño de la base de datos: <strong>{$a->databasesize}</strong></li>
    <li>Cursos: <strong>{$a->coursescount}</strong></li>
    <li>Copias de seguridad por curso: <strong>{$a->backupcount}</strong></li>
</ul>
<hr>
{$a->table} <!-- Se imprime la tabla HTML de últimos 10 días o lo que quieras -->
<hr>
<p>Este mensaje fue generado automáticamente por el complemento "Usage Monitor" de IngeWeb.</p>';
