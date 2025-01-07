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
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Cadenas generales del plugin
$string['pluginname'] = 'Usage Report';
$string['exclusivedisclaimer'] = 'Este plugin hace parte y es de uso exclusivo del servicio de hosting para Moodle proporcionado por <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';

// Cadenas de configuración
$string['email'] = 'Email para notificaciones';
$string['configemail'] = 'Dirección de correo donde desea enviar las notificaciones.';
$string['max_daily_users_threshold'] = 'Límite de usuarios';
$string['configmax_daily_users_threshold'] = 'Establezca el límite de usuarios.';
$string['disk_quota'] = 'Cuota de disco';
$string['configdisk_quota'] = 'Cuota de disco en gigabytes';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Configura la ruta al comando du (uso de disco). Esto es necesario para calcular el uso de disco. <strong>Este ajuste se refleja en las rutas del sistema de Moodle</strong>)';

// Descripciones de tareas
$string['processunifiednotificationtask'] = 'Procesar notificaciones unificadas de monitoreo del sistema';
$string['calculatediskusagetask'] = 'Tarea para calcular el uso del disco';
$string['getlastusers'] = 'Tarea para calcular el top de accesos únicos';
$string['getlastusers90days'] = 'Tarea para obtener el top de usuarios en los últimos 90 días';
$string['getlastusersconnected'] = 'Tarea para calcular la cantidad de usuarios diarios de hoy';

// Niveles de alerta y umbrales
$string['critical_threshold'] = 'CRÍTICO';
$string['high_threshold'] = 'ALTO';
$string['medium_threshold'] = 'MEDIO';
$string['normal_threshold'] = 'NORMAL';
$string['threshold_info'] = 'Umbrales de alerta: CRÍTICO (95%), ALTO (90%), MEDIO (80%)';

// Mensajes de estado
$string['disk_usage_status'] = 'El uso del disco ha alcanzado el nivel {$a->level}';
$string['user_count_status'] = 'La cantidad de usuarios ha alcanzado el nivel {$a->level}';
$string['notcalculatedyet'] = 'Aún no calculado';

// Secciones del informe
$string['topuser'] = 'Top 10 usuarios diarios';
$string['lastusers'] = 'Usuarios diarios de los últimos 10 días';
$string['max_userdaily_for_90_days'] = 'Máximo de usuarios diarios en los últimos 90 días';
$string['userstopnum'] = 'Usuarios diarios';
$string['user_count_title'] = 'Total de Usuarios';
$string['additional_info'] = 'Información Adicional';

// Elementos de interfaz
$string['usertable'] = 'Tabla de top usuarios';
$string['userchart'] = 'Gráfico de top usuarios';
$string['date'] = 'Fecha';
$string['usersquantity'] = 'Cantidad de usuarios diarios';
$string['dateformatsql'] = '%d/%m/%Y';
$string['dateformat'] = 'd/m/Y';

// Relacionado con uso del disco
$string['diskusage'] = 'Uso del disco';
$string['sizeusage'] = 'Total de uso de disco';
$string['sizedatabase'] = 'Tamaño base de datos';
$string['avalilabledisk'] = '% de espacio en disco disponible';
$string['disk_metrics_details'] = 'Métricas detalladas del disco';

// Información de última ejecución
$string['lastexecution'] = 'Última ejecución de cálculo de usuarios diarios: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['last_execution_title'] = 'Última Ejecución del Reporte';
$string['today_users_title'] = 'Usuarios Diarios Actuales';
$string['users_today'] = 'Cantidad de usuarios diarios el día de hoy: {$a}';

// Requisitos y recomendaciones del sistema
$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a \'du\' en las Rutas del sistema de Moodle. Puede encontrar esta configuración en Administración del sitio > Servidor > Rutas del sistema. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Haga clic aquí para ir a Rutas del sistema</a>.';
$string['pathtodunote'] = 'Nota: El path a \'du\' se detectará automáticamente solo si este plugin se encuentra en un sistema Linux y si se logra detectar la ubicación de \'du\'.';

// Cadenas de notificación unificada
$string['unifiednotification_subject'] = 'Alerta de Monitoreo del Sistema - {$a}';
$string['system_metrics'] = 'Resumen de Métricas del Sistema';
$string['disk_metrics'] = 'Métricas de Uso de Disco';
$string['user_metrics'] = 'Métricas de Actividad de Usuarios';
$string['additional_metrics'] = 'Información Adicional del Sistema';
$string['system_status'] = 'Estado Actual del Sistema';
$string['disk_usage_title'] = 'Uso Actual del Disco';
$string['disk_quota_title'] = 'Cuota Total de Disco';
$string['database_size_title'] = 'Tamaño de la Base de Datos';
$string['available_space'] = 'Espacio Disponible';
$string['active_users'] = 'Usuarios Activos';
$string['user_limit'] = 'Límite de Usuarios';
$string['monitoring_date'] = 'Fecha de Monitoreo';
$string['total_courses'] = 'Total de Cursos';
$string['backup_retention'] = 'Retención de Copias de Seguridad';
$string['days'] = 'días';
$string['notification_footer'] = 'Esta es una notificación automatizada de monitoreo. Las métricas del sistema son recolectadas y analizadas periódicamente para asegurar un rendimiento óptimo de la plataforma.';
$string['historical_data'] = 'Datos Históricos de Acceso de Usuarios';

// Plantilla de correo
$string['unifiednotification_html'] = '
<p>Plataforma: <a href="{$a->siteurl}" target="_blank"><strong>{$a->sitename}</strong></a></p>
<p><strong>Resumen de Alertas:</strong></p>
<ul>
    <li>Uso de Disco: {$a->diskusage} de {$a->quotadisk} ({$a->disk_percent}%) - Nivel: {$a->disk_alert}</li>
    <li>Usuarios Activos: {$a->numberofusers} de {$a->threshold} ({$a->user_percent}%) - Nivel: {$a->user_alert}</li>
    <li>Fecha de Monitoreo: {$a->lastday}</li>
</ul>

<p><strong>URL del Monitor:</strong> <a href="{$a->referer}" target="_blank">Panel de Monitoreo del Sistema</a></p>

{$a->table}

<hr>
<p style="font-size: 0.9em; color: #666;">
Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" target="_blank"><strong>ingeweb.co</strong></a>
</p>';