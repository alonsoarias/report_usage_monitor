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
 * Spanish language strings for the Usage Monitor Report plugin.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings
$string['pluginname'] = 'Usage Monitor';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';
$string['exclusivedisclaimer'] = 'Este plugin es de uso exclusivo del equipo de soporte Moodle de IngeWeb.';

// Settings
$string['email'] = 'Email para notificaciones';
$string['configemail'] = 'Dirección de correo donde desea enviar las notificaciones.';
$string['max_daily_users_threshold'] = 'Límite de usuarios';
$string['configmax_daily_users_threshold'] = 'Establezca el límite de usuarios diarios.';
$string['disk_quota'] = 'Cuota de disco';
$string['configdisk_quota'] = 'Configure la cuota de disco (en gigabytes) para las notificaciones.';

// Environment checks
$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Configura la ruta al comando du (uso de disco). Esto es necesario para calcular el uso de disco.';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a "du" en las Rutas del sistema de Moodle.';
$string['pathtodunote'] = 'Nota: La ruta a "du" se detectará automáticamente solo si este plugin está en un sistema Linux y si se logra ubicar con éxito.';

// Task names
$string['check_php_functions_taskname'] = 'Verificar funciones PHP';
$string['check_env_scheduler_taskname'] = 'Programar verificaciones de entorno';
$string['calculatediskusagetask'] = 'Calcular uso del disco';
$string['getlastusersconnected'] = 'Obtener usuarios conectados recientemente';
$string['getlastusers'] = 'Calcular usuarios únicos diarios';
$string['getlastusers90days'] = 'Obtener usuarios de últimos 90 días';
$string['notification_usage_taskname'] = 'Notificación de monitoreo de uso';

// Interface elements
$string['userstopnum'] = 'Usuarios diarios';
$string['topuser'] = 'Top 10 usuarios diarios';
$string['diskusage'] = 'Uso del disco';
$string['lastusers'] = 'Usuarios diarios de los últimos 10 días';
$string['usertable'] = 'Tabla de usuarios';
$string['userchart'] = 'Gráfica de usuarios';
$string['date'] = 'Fecha';
$string['usersquantity'] = 'Cantidad de usuarios';
$string['sizeusage'] = 'Uso total de disco';
$string['avalilabledisk'] = '% de espacio disponible';
$string['sizedatabase'] = 'Tamaño de base de datos';

// Status and calculations
$string['notcalculatedyet'] = 'Aún no calculado';
$string['lastexecution'] = 'Último cálculo de usuarios diarios: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['max_userdaily_for_90_days'] = 'Máximo de usuarios diarios en los últimos 90 días';
$string['users_today'] = 'Cantidad de usuarios diarios hoy: {$a}';

// Date formats
$string['dateformatsql'] = '%d/%m/%Y';
$string['dateformat'] = 'd/m/Y';

// Notification email strings
$string['subjectemail_unified'] = 'Alerta de Uso - {$a->sitename}';
$string['messagehtml_unified'] = '
<h2>Reporte de Uso del Sistema</h2>
<p>Plataforma: <a href="{$a->siteurl}" target="_blank">{$a->sitename}</a></p>

<h3>Actividad de Usuarios</h3>
<ul>
    <li><strong>¿Límite de usuarios excedido?</strong> {$a->exceededUsersLabel}</li>
    <li>Usuarios activos: <strong>{$a->users}</strong> / {$a->userthreshold}</li>
    <li>Porcentaje de uso: <strong>{$a->userpercent}%</strong></li>
</ul>

<h3>Uso de Disco</h3>
<ul>
    <li><strong>¿Cuota de disco excedida?</strong> {$a->exceededDiskLabel}</li>
    <li>Uso actual: <strong>{$a->diskusage}</strong> / {$a->diskquota}</li>
    <li>Porcentaje de uso: <strong>{$a->diskpercent}%</strong></li>
</ul>

<h3>Información Adicional</h3>
<ul>
    <li>Tamaño de base de datos: <strong>{$a->databasesize}</strong></li>
    <li>Total de cursos: <strong>{$a->coursescount}</strong></li>
    <li>Copias de seguridad por curso: <strong>{$a->backupcount}</strong></li>
</ul>

{$a->table}

<hr>
<p><small>Este mensaje fue generado automáticamente por el plugin Usage Monitor de <a href="https://ingeweb.co/" target="_blank">IngeWeb</a>.</small></p>';