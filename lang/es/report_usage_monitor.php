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
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings
$string['pluginname'] = 'Usage Report';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';
$string['exclusivedisclaimer'] = 'Este plugin hace parte y es de uso exclusivo del servicio de hosting para Moodle proporcionado por <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// Dashboard strings
$string['dashboard'] = 'Panel de Control';
$string['dashboard_title'] = 'Panel de Control de Uso';
$string['diskusage'] = 'Uso del disco';
$string['users_today_card'] = 'Usuarios Diarios Hoy';
$string['max_userdaily_for_90_days'] = 'Máximo de usuarios diarios en los últimos 90 días';
$string['notcalculatedyet'] = 'Aún no calculado';
$string['lastexecution'] = 'Última ejecución de cálculo de usuarios diarios: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['users_today'] = 'Cantidad de usuarios diarios el día de hoy: {$a}';
$string['date'] = 'Fecha';
$string['last_calculation'] = 'Último cálculo';
$string['usersquantity'] = 'Cantidad de usuarios diarios';
$string['disk_usage_distribution'] = 'Distribución de Uso de Disco';
$string['disk_usage_history'] = 'Historial de Uso de Disco (Últimos 30 Días)';
$string['percentage_used'] = 'Porcentaje Utilizado';

// Dashboard sections
$string['disk_usage_by_directory'] = 'Uso de Disco por Directorio';
$string['largest_courses'] = 'Cursos más Grandes';
$string['database'] = 'Base de datos';
$string['files_dir'] = 'Archivos (filedir)';
$string['cache'] = 'Caché';
$string['others'] = 'Otros';
$string['directory'] = 'Directorio';
$string['size'] = 'Tamaño';
$string['percentage'] = 'Porcentaje';
$string['course'] = 'Curso';
$string['backup_count'] = 'Número de Copias';
$string['topuser'] = 'Top 10 usuarios diarios';
$string['lastusers'] = 'Usuarios diarios de los últimos 10 días';
$string['usertable'] = 'Tabla de top usuarios';
$string['userchart'] = 'Graficar top usuarios';
$string['system_info'] = 'Información del Sistema';
$string['moodle_version'] = 'Versión de Moodle';
$string['total_courses'] = 'Total de Cursos';
$string['backup_per_course'] = 'Copias de Seguridad por Curso';
$string['registered_users'] = 'Usuarios Registrados';
$string['active_users'] = 'usuarios activos';
$string['suspended_users'] = 'usuarios suspendidos';
$string['recommendations'] = 'Recomendaciones';

// Warning levels and indicator labels
$string['warning70'] = 'Advertencia (70%)';
$string['critical90'] = 'Crítico (90%)';
$string['limit100'] = 'Límite (100%)';
$string['percent_of_threshold'] = '% del umbral';

// Recommendation tips
$string['space_saving_tips'] = 'Consejos para ahorrar espacio en disco:';
$string['tip_backups'] = 'Reducir el número de copias de seguridad automáticas por curso (actualmente: {$a})';
$string['tip_files'] = 'Limpiar archivos antiguos sin uso mediante la herramienta de limpieza de archivos';
$string['tip_courses'] = 'Archivar o eliminar cursos antiguos que ya no se utilizan';
$string['tip_cache'] = 'Purgar la caché del sistema para liberar espacio temporal';
$string['disk_usage_ok'] = 'El uso del disco está en un nivel aceptable. No se requiere acción inmediata.';
$string['user_count_ok'] = 'El recuento de usuarios está en un nivel aceptable. No se requiere acción inmediata.';
$string['user_limit_tips'] = 'Consejos para gestionar el límite de usuarios:';
$string['tip_user_inactive'] = 'Considere limpiar las cuentas de usuario inactivas que no han iniciado sesión durante mucho tiempo.';
$string['tip_user_limit'] = 'Si el número de usuarios se acerca constantemente al límite, considere aumentar su cuota.';

// Task strings
$string['calculatediskusagetask'] = 'Tarea para calcular el uso del disco';
$string['getlastusers'] = 'Tarea para calcular el top de accesos unicos';
$string['getlastusers90days'] = 'Tarea para obtener el top de usuarios en los últimos 90 días';
$string['getlastusersconnected'] = 'Tarea para calcular la cantidad de usuarios diarios de hoy';
$string['processdisknotificationtask'] = 'Tarea de notificación del uso del disco';
$string['processuserlimitnotificationtask'] = 'Tarea de notificación del límite de usuarios diarios';

// Settings strings
$string['mainsettings'] = 'Configuraciones principales';
$string['email'] = 'Email para notificaciones';
$string['configemail'] = 'Dirección de correo donde desea enviar las notificaciones.';
$string['max_daily_users_threshold'] = 'Límite de usuarios';
$string['configmax_daily_users_threshold'] = 'Establezca el límite de usuarios.';
$string['disk_quota'] = 'Cuota de disco';
$string['configdisk_quota'] = 'Cuota de disco en gigabytes';
$string['notificationsettings'] = 'Configuración de notificaciones';
$string['notificationsettingsinfo'] = 'Configure cuándo y cómo se envían las notificaciones.';
$string['disk_warning_level'] = 'Nivel de advertencia de disco';
$string['configdisk_warning_level'] = 'Porcentaje de uso de disco que activa las advertencias.';
$string['users_warning_level'] = 'Nivel de advertencia de usuarios';
$string['configusers_warning_level'] = 'Porcentaje del límite de usuarios que activa las advertencias.';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Configura la ruta al comando du (uso de disco). Esto es necesario para calcular el uso de disco. <strong>Este ajuste se refleja en las rutas del sistema de Moodle</strong>)';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a \'du\' en las Rutas del sistema de Moodle. Puede encontrar esta configuración en Administración del sitio > Servidor > Rutas del sistema. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Haga clic aquí para ir a Rutas del sistema</a>.';
$string['pathtodunote'] = 'Nota: El path a \'du\' se detectará automáticamente solo si este plugin se encuentra en un sistema Linux y si se logra detectar la ubicación de \'du\'.';
$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';
$string['enable_api'] = 'Habilitar API';
$string['configenable_api'] = 'Habilitar acceso API para que sistemas externos obtengan información de uso.';

// Email notification strings
$string['subjectemail1'] = 'Límite de usuarios diarios superado plataforma:';
$string['subjectemail2'] = 'Alerta de espacio en disco plataforma:';

// API documentation strings
$string['api_documentation'] = 'Documentación de API';
$string['get_usage_data'] = 'Obtener datos de uso';
$string['get_usage_data_desc'] = 'Recupera datos precalculados de uso de disco y usuarios con mínima sobrecarga.';
$string['set_usage_thresholds'] = 'Configurar umbrales de uso';
$string['set_usage_thresholds_desc'] = 'Actualiza los umbrales configurados para usuarios y espacio en disco.';
$string['user_threshold_updated'] = 'Umbral de usuarios actualizado correctamente.';
$string['disk_threshold_updated'] = 'Umbral de disco actualizado correctamente.';
$string['error_user_threshold_negative'] = 'El umbral de usuarios debe ser mayor que 0.';
$string['error_disk_threshold_negative'] = 'El umbral de disco debe ser mayor que 0.';
$string['error_no_thresholds_provided'] = 'No se proporcionaron umbrales para actualizar.';

// API response field descriptions
$string['site_name'] = 'Nombre del sitio';
$string['site_shortname'] = 'Nombre corto del sitio';
$string['moodle_release'] = 'Versión de Moodle legible';
$string['course_count'] = 'Número de cursos';
$string['user_count'] = 'Número de usuarios';
$string['backup_auto_max_kept'] = 'Número de copias automáticas conservadas';
$string['total_bytes'] = 'Uso total de disco en bytes';
$string['total_readable'] = 'Uso de disco legible';
$string['quota_bytes'] = 'Cuota de disco en bytes';
$string['quota_readable'] = 'Cuota de disco legible';
$string['disk_percentage'] = 'Porcentaje de uso de disco';
$string['database_bytes'] = 'Tamaño de la base de datos en bytes';
$string['database_readable'] = 'Tamaño legible de la base de datos';
$string['database_percentage'] = 'Porcentaje de tamaño de la base de datos';
$string['filedir_bytes'] = 'Tamaño del directorio de archivos en bytes';
$string['filedir_readable'] = 'Tamaño legible del directorio de archivos';
$string['filedir_percentage'] = 'Porcentaje de tamaño del directorio de archivos';
$string['cache_bytes'] = 'Tamaño de caché en bytes';
$string['cache_readable'] = 'Tamaño legible de caché';
$string['cache_percentage'] = 'Porcentaje de caché';
$string['backup_bytes'] = 'Tamaño de copia de seguridad en bytes';
$string['backup_readable'] = 'Tamaño legible de copia de seguridad';
$string['backup_percentage'] = 'Porcentaje de copia de seguridad';
$string['others_bytes'] = 'Tamaño de otros directorios en bytes';
$string['others_readable'] = 'Tamaño legible de otros directorios';
$string['others_percentage'] = 'Porcentaje de otros directorios';
$string['user_threshold'] = 'Umbral de usuarios';
$string['user_percentage'] = 'Porcentaje de uso de usuarios';
$string['course_id'] = 'ID del curso';
$string['course_fullname'] = 'Nombre completo del curso';
$string['course_shortname'] = 'Nombre corto del curso';
$string['course_size_bytes'] = 'Tamaño del curso en bytes';
$string['course_size_readable'] = 'Tamaño legible del curso';
$string['course_backup_size_bytes'] = 'Tamaño de la copia de seguridad del curso en bytes';
$string['course_backup_size_readable'] = 'Tamaño legible de la copia de seguridad del curso';
$string['course_percentage'] = 'Porcentaje de tamaño del curso';
$string['course_backup_count'] = 'Número de copias de seguridad del curso';
$string['disk_calculation_timestamp'] = 'Timestamp del cálculo de disco';
$string['users_calculation_timestamp'] = 'Timestamp del cálculo de usuarios';

// Notification history API strings
$string['notification_type'] = 'Tipo de notificación (disk, users, o all)';
$string['notification_limit'] = 'Número máximo de registros a devolver';
$string['notification_offset'] = 'Desplazamiento para paginación';
$string['notification_total'] = 'Número total de registros disponibles';
$string['notification_limit_value'] = 'Número máximo de registros solicitados';
$string['notification_offset_value'] = 'Desplazamiento solicitado';
$string['notification_id'] = 'ID de la notificación';
$string['notification_type_value'] = 'Tipo de notificación (disk o users)';
$string['notification_percentage'] = 'Porcentaje de uso';
$string['notification_value'] = 'Valor legible';
$string['notification_value_raw'] = 'Valor en bytes o número de usuarios';
$string['notification_threshold'] = 'Umbral legible';
$string['notification_threshold_raw'] = 'Umbral en bytes o número de usuarios';
$string['notification_timecreated'] = 'Timestamp de creación';
$string['notification_timereadable'] = 'Fecha y hora legibles';

// Projections and growth rates
$string['api_projections_title'] = 'Proyecciones de crecimiento';
$string['api_projections_desc'] = 'Datos de proyección de crecimiento y días estimados para alcanzar umbrales';
$string['api_monthly_growth_rate'] = 'Tasa de crecimiento mensual';
$string['api_projection_days'] = 'Días para alcanzar umbral';
$string['growth_rate_disk'] = 'Tasa de crecimiento de disco';
$string['growth_rate_disk_desc'] = 'Tasa de crecimiento mensual del uso de disco en porcentaje';
$string['growth_rate_users'] = 'Tasa de crecimiento de usuarios';
$string['growth_rate_users_desc'] = 'Tasa de crecimiento mensual del número de usuarios en porcentaje';
$string['days_to_threshold_disk'] = 'Días hasta umbral de disco';
$string['days_to_threshold_disk_desc'] = 'Días proyectados hasta alcanzar el umbral de advertencia de disco';
$string['days_to_threshold_users'] = 'Días hasta umbral de usuarios';
$string['days_to_threshold_users_desc'] = 'Días proyectados hasta alcanzar el umbral de advertencia de usuarios';

// Email templates
$string['messagehtml_userlimit'] = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Límite de Usuarios - {$a->sitename}</title>
    <style>
        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #e74c3c;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .alert-badge {
            display: inline-block;
            background-color: white;
            color: #e74c3c;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        .content {
            padding: 20px 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: 500;
            width: 40%;
            color: #555;
        }
        .info-table td:last-child {
            font-weight: 600;
        }
        .progress-container {
            background-color: #f5f5f5;
            border-radius: 20px;
            height: 25px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(to right, #3498db, #e74c3c);
            text-align: center;
            line-height: 25px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: width 0.5s ease;
        }
        .warning-level-high {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }
        .warning-level-medium {
            background: linear-gradient(to right, #f39c12, #e67e22);
        }
        .warning-level-low {
            background: linear-gradient(to right, #2ecc71, #27ae60);
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .historical-data {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .historical-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .historical-table th {
            background-color: #e8e8e8;
            font-weight: 600;
            text-align: left;
            padding: 10px;
        }
        .historical-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .historical-table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        .platform-info {
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            background-color: #f8f9fa;
            margin: 15px 0;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100%;
                border-radius: 0;
            }
            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Límite de Usuarios Diarios Excedido</h1>
            <div class="alert-badge">{$a->percentaje}% del límite</div>
        </div>
        
        <div class="content">
            <p>La plataforma <a href="{$a->siteurl}" style="color: #3498db; font-weight: bold;">{$a->sitename}</a> ha superado el umbral de usuarios diarios establecido.</p>
            
            <div class="section">
                <h2 class="section-title">Resumen de la Alerta</h2>
                
                <div class="progress-container">
                    <div class="progress-bar warning-level-high" style="width: {$a->percentaje}%;">
                        {$a->percentaje}%
                    </div>
                </div>
                
                <table class="info-table">
                    <tr>
                        <td>Fecha:</td>
                        <td>{$a->lastday}</td>
                    </tr>
                    <tr>
                        <td>Usuarios activos:</td>
                        <td>{$a->numberofusers}</td>
                    </tr>
                    <tr>
                        <td>Límite configurado:</td>
                        <td>{$a->threshold} usuarios</td>
                    </tr>
                    <tr>
                        <td>Exceso:</td>
                        <td>{$a->excess_users} usuarios ({$a->percentaje}%)</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">Información de la Plataforma</h2>
                <table class="info-table">
                    <tr>
                        <td>Versión de Moodle:</td>
                        <td>{$a->moodle_release} ({$a->moodle_version})</td>
                    </tr>
                    <tr>
                        <td>Cursos totales:</td>
                        <td>{$a->courses_count}</td>
                    </tr>
                    <tr>
                        <td>Copias de seguridad por curso:</td>
                        <td>{$a->backup_auto_max_kept}</td>
                    </tr>
                    <tr>
                        <td>Espacio en disco:</td>
                        <td>{$a->diskusage} / {$a->quotadisk} ({$a->disk_percent}%)</td>
                    </tr>
                </table>
                
                <div class="platform-info">
                    <p><strong>Proyección:</strong> De mantenerse la tendencia actual, se estima que en {$a->days_to_critical} días se alcanzará el {$a->critical_threshold}% del límite.</p>
                </div>
            </div>
            
            <div class="section historical-data">
                <h2 class="section-title">Historial Reciente de Usuarios</h2>
                <table class="historical-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuarios Activos</th>
                            <th>% del Límite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos del historial -->
                        {$a->historical_data_rows}
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center;">
                <a href="{$a->referer}" class="cta-button">Ver Panel de Control</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" style="color: #3498db;">ingeweb.co</a></p>
            <p><em>*Se contabilizan usuarios distintos que se autenticaron en la fecha indicada. Usuarios que se autentican más de una vez solo cuentan una vez.</em></p>
        </div>
    </div>
</body>
</html>';

$string['messagehtml_diskusage'] = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Espacio en Disco - {$a->sitename}</title>
    <style>
        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #e67e22;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .alert-badge {
            display: inline-block;
            background-color: white;
            color: #e67e22;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
        }
        .content {
            padding: 20px 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: 500;
            width: 40%;
            color: #555;
        }
        .info-table td:last-child {
            font-weight: 600;
        }
        .progress-container {
            background-color: #f5f5f5;
            border-radius: 20px;
            height: 25px;
            width: 100%;
            margin: 15px 0;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(to right, #3498db, #e67e22);
            text-align: center;
            line-height: 25px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: width 0.5s ease;
        }
        .warning-level-high {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }
        .warning-level-medium {
            background: linear-gradient(to right, #f39c12, #e67e22);
        }
        .warning-level-low {
            background: linear-gradient(to right, #2ecc71, #27ae60);
        }
        .cta-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #2980b9;
        }
        .directory-chart {
            margin: 20px 0;
            width: 100%;
        }
        .directory-chart-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .directory-bar {
            height: 35px;
            display: flex;
            margin-bottom: 8px;
            border-radius: 5px;
            overflow: hidden;
        }
        .directory-label {
            width: 150px;
            background-color: #34495e;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 10px;
            font-size: 14px;
        }
        .directory-value {
            flex-grow: 1;
            background-color: #3498db;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: white;
            font-weight: 600;
            position: relative;
        }
        .directory-value-text {
            position: relative;
            z-index: 2;
        }
        .directory-value-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
        }
        .top-items {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .top-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .top-items-table th {
            background-color: #e8e8e8;
            font-weight: 600;
            text-align: left;
            padding: 10px;
        }
        .top-items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .top-items-table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #ecf0f1;
            padding: 15px;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        .recommendation {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            margin: 15px 0;
        }
        .recommendation h3 {
            margin-top: 0;
            color: #2980b9;
            font-size: 16px;
            font-weight: 600;
        }
        .recommendation ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .recommendation li {
            margin-bottom: 5px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100%;
                border-radius: 0;
            }
            .content {
                padding: 15px;
            }
            .directory-label {
                width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Alerta de Espacio en Disco</h1>
            <div class="alert-badge">{$a->percentage}% utilizado</div>
        </div>
        
        <div class="content">
            <p>La plataforma <a href="{$a->siteurl}" style="color: #3498db; font-weight: bold;">{$a->sitename}</a> ha superado el {$a->percentage}% del espacio en disco asignado.</p>
            
            <div class="section">
                <h2 class="section-title">Resumen de Uso de Disco</h2>
                
                <div class="progress-container">
                    <div class="progress-bar {$a->warning_level_class}" style="width: {$a->percentage}%;">
                        {$a->percentage}%
                    </div>
                </div>
                
                <table class="info-table">
                    <tr>
                        <td>Fecha:</td>
                        <td>{$a->lastday}</td>
                    </tr>
                    <tr>
                        <td>Espacio utilizado:</td>
                        <td>{$a->diskusage}</td>
                    </tr>
                    <tr>
                        <td>Cuota asignada:</td>
                        <td>{$a->quotadisk}</td>
                    </tr>
                    <tr>
                        <td>Espacio disponible:</td>
                        <td>{$a->available_space} ({$a->available_percent}%)</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">Distribución del Espacio</h2>
                
                <div class="directory-chart">
                    <div class="directory-chart-title">Uso por directorio</div>
                    <!-- Barra visual para cada directorio -->
                    <div class="directory-bar">
                        <div class="directory-label">Base de datos</div>
                        <div class="directory-value" style="background-color: #9b59b6;">
                            <div class="directory-value-bar" style="width: {$a->db_percent}%;"></div>
                            <span class="directory-value-text">{$a->databasesize} ({$a->db_percent}%)</span>
                        </div>
                    </div>
                    <div class="directory-bar">
                        <div class="directory-label">Archivos (filedir)</div>
                        <div class="directory-value" style="background-color: #2ecc71;">
                            <div class="directory-value-bar" style="width: {$a->filedir_percent}%;"></div>
                            <span class="directory-value-text">{$a->filedir_size} ({$a->filedir_percent}%)</span>
                        </div>
                    </div>
                    <div class="directory-bar">
                        <div class="directory-label">Caché</div>
                        <div class="directory-value" style="background-color: #e67e22;">
                            <div class="directory-value-bar" style="width: {$a->cache_percent}%;"></div>
                            <span class="directory-value-text">{$a->cache_size} ({$a->cache_percent}%)</span>
                        </div>
                    </div>
                    <div class="directory-bar">
                        <div class="directory-label">Otros</div>
                        <div class="directory-value" style="background-color: #95a5a6;">
                            <div class="directory-value-bar" style="width: {$a->other_percent}%;"></div>
                            <span class="directory-value-text">{$a->other_size} ({$a->other_percent}%)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">Información de la Plataforma</h2>
                <table class="info-table">
                    <tr>
                        <td>Versión de Moodle:</td>
                        <td>{$a->moodle_release} ({$a->moodle_version})</td>
                    </tr>
                    <tr>
                        <td>Cursos totales:</td>
                        <td>{$a->coursescount}</td>
                    </tr>
                    <tr>
                        <td>Copias de seguridad por curso:</td>
                        <td>{$a->backupcount}</td>
                    </tr>
                    <tr>
                        <td>Usuarios activos:</td>
                        <td>{$a->numberofusers} / {$a->threshold} ({$a->user_percent}%)</td>
                    </tr>
                </table>
            </div>
            
            <div class="section top-items">
                <h2 class="section-title">Cursos que más espacio ocupan</h2>
                <table class="top-items-table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Tamaño</th>
                            <th>% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos dinámicos de los cursos -->
                        {$a->top_courses_rows}
                    </tbody>
                </table>
            </div>
            
            <div class="recommendation">
                <h3>Recomendaciones para liberar espacio</h3>
                <ul>
                    <li>Reducir el número de copias de seguridad automáticas por curso (actualmente: {$a->backupcount})</li>
                    <li>Eliminar archivos antiguos sin uso mediante la herramienta de limpieza de archivos</li>
                    <li>Revisar y limpiar los cursos más grandes identificados en la tabla anterior</li>
                    <li>Purgar la caché del sistema para liberar espacio temporal</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{$a->referer}" class="cta-button">Ver Panel de Control</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" style="color: #3498db;">ingeweb.co</a></p>
            <p>Si necesita asistencia técnica, por favor no responda a este correo y contacte a su administrador de hosting.</p>
        </div>
    </div>
</body>
</html>';