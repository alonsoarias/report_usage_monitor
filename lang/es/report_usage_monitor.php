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
 * Las cadenas de complementos se definen aquí.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Usage Report';
$string['topuser'] = 'Top 10 usuarios diarios';
$string['lastusers'] = 'Usuarios diarios de los últimos 10 días';
$string['email'] = 'Email para notificaciones';
$string['configemail'] = 'Dirección de correo donde desea enviar las notificaciones.';
$string['max_daily_users_threshold'] = 'Límite de usuarios';
$string['configmax_daily_users_threshold'] = 'Establezca el límite de usuarios.';
$string['processnotificationstask'] = 'Notificar sí se superó la cantidad de usuarios diarios conectados';
$string['diskusage'] = 'Uso del disco';
$string['notcalculatedyet'] = 'Aún no calculado';
$string['calculatediskusagetask'] = 'Tarea para calcular el uso del disco';
$string['getlastusers'] = 'Tarea para calcular el top de accesos unicos';
$string['getlastusers90days'] = 'Tarea para obtener el top de usuarios en los últimos 90 días';
$string['getlastusersconnected'] = 'Tarea para calcular la cantidad de usuarios diarios de hoy';
$string['date'] = 'Fecha';
$string['usersquantity'] = 'Cantidad de usuarios diarios';
$string['lastexecution'] = 'Última ejecución de cálculo de usuarios diarios: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['max_userdaily_for_90_days'] = 'Máximo de usuarios diarios en los últimos 90 días';
$string['users_today'] = 'Cantidad de usuarios diarios el día de hoy: {$a}';
$string['sizeusage'] = 'Total de uso de disco';
$string['sizedatabase'] = 'Tamaño base de datos';
$string['subjectemail1'] = 'Límite de usuarios diarios superado';
$string['subjectemail2'] = 'Alerta de espacio en disco';
$string['userstopnum'] = 'Usuarios diarios';
$string['usertable'] = 'Tabla de top usuarios';
$string['userchart'] = 'Graficar top usuarios';
$string['dateformatsql'] = '%d/%m/%Y';
$string['dateformat'] = 'd/m/Y';
$string['disk_quota'] = 'Cuota de disco';
$string['configdisk_quota'] = 'Cuota de disco en gigabytes'; 
$string['avalilabledisk'] = '% de espacio en disco disponible';
$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Configura la ruta al comando du (uso de disco). Esto es necesario para calcular el uso de disco. <strong>Este ajuste se refleja en las rutas del sistema de Moodle</strong>)';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a \'du\' en las Rutas del sistema de Moodle. Puede encontrar esta configuración en Administración del sitio > Servidor > Rutas del sistema. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Haga clic aquí para ir a Rutas del sistema</a>.';
$string['pathtodunote'] = 'Nota: El path a \'du\' se detectará automáticamente solo si este plugin se encuentra en un sistema Linux y si se logra detectar la ubicación de \'du\'.';
$string['messagehtml1'] = '<p>La plataforma <a href="{$a->siteurl}" target="_blank" ><strong>\'{$a->sitename}\'</strong></a> ha superado el umbral de usuarios en un {$a->percentaje}%</p>
<p>Fecha (DD/MM/AAAA): {$a->lastday} </p>
<p>Usuarios*: <strong>{$a->numberofusers}</strong></p>
<p>Umbral establecido de usuarios máximos diarios: {$a->threshold} usuarios</p>
<strong>Url monitor: </strong> {$a->referer}
<br>
<br>
{$a->table}
<br>
<hr>
<i><p>Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" target="_blank" ><strong>ingeweb.co</strong></a></p>
*Se contabilizan usuarios distintos que se autenticaron en la fecha indicada. Usuarios que se autentican más de una vez solo cuenta una vez.<i>';
$string['messagehtml2'] = '<p>La plataforma <a href="{$a->siteurl}" target="_blank" ><strong>\'{$a->sitename}\'</strong></a> ha superado el 90% del espacio en disco asignado</p>
<p>Espacio en disco asignado: {$a->quotadisk} </p>
<p>Espacio en disco usado: <strong>{$a->diskusage}</strong></p>
<p>Porcentaje de uso del disco: <strong>{$a->percentage}%</strong></p>
<p>Tamaño de la base de datos: <strong>{$a->databasesize}</strong></p>
<p>Tamaño de Moodledata: <strong>{$a->moodledata}</strong></p>
<p>Cantidad de copias de seguridad por curso: <strong>{$a->backupcount}</strong></p>
<p>Usuarios*: <strong>{$a->numberofusers}</strong></p>
<p>Umbral establecido de usuarios máximos diarios: <strong>{$a->threshold}</strong> usuarios</p>
<strong>Url del monitor: </strong> {$a->referer}
<br>
<hr>
<i><p>Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" target="_blank" ><strong>ingeweb.co</strong></a></p><i>';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';