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
$string['subjectemail1'] = 'Límite de usuarios diarios superado plataforma:';
$string['subjectemail2'] = 'Alerta de espacio en disco plataforma:';
$string['userstopnum'] = 'Usuarios diarios';
$string['processdisknotificationtask'] = 'Tarea de notificación del uso del disco';
$string['processuserlimitnotificationtask'] = 'Tarea de notificación del límite de usuarios diarios';
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

// Nuevas cadenas para notificaciones mejoradas
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

// Nuevas cadenas para el dashboard
$string['dashboard'] = 'Panel de Control';
$string['dashboard_title'] = 'Panel de Control de Uso';
$string['users_today_card'] = 'Usuarios Diarios Hoy';
$string['max_90days'] = 'Máximo en 90 Días';
$string['of'] = 'de';
$string['of_limit'] = '% del límite ({$a})';
$string['disk_summary'] = 'Resumen de Uso de Disco';
$string['users_summary'] = 'Resumen de Actividad de Usuarios';
$string['disk_usage_distribution'] = 'Distribución de Uso de Disco';
$string['directory'] = 'Directorio';
$string['size'] = 'Tamaño';
$string['percentage'] = 'Porcentaje';
$string['database'] = 'Base de datos';
$string['files_dir'] = 'Archivos (filedir)';
$string['backups'] = 'Copias de seguridad';
$string['cache'] = 'Caché';
$string['others'] = 'Otros';
$string['daily_users'] = 'Usuarios Diarios';
$string['threshold'] = 'Umbral';
$string['system_info'] = 'Información del Sistema';
$string['moodle_version'] = 'Versión de Moodle';
$string['total_courses'] = 'Total de Cursos';
$string['backup_per_course'] = 'Copias de Seguridad por Curso';
$string['registered_users'] = 'Usuarios Registrados';
$string['disk_usage_overview'] = 'Resumen de Uso de Disco';
$string['disk_usage_by_directory'] = 'Uso de Disco por Directorio';
$string['largest_courses'] = 'Cursos más Grandes';
$string['course'] = 'Curso';
$string['backup_count'] = 'Número de Copias';
$string['recommendations'] = 'Recomendaciones';
$string['space_saving_tips'] = 'Consejos para ahorrar espacio en disco:';
$string['tip_backups'] = 'Reducir el número de copias de seguridad automáticas por curso (actualmente: {$a})';
$string['tip_files'] = 'Limpiar archivos antiguos sin uso mediante la herramienta de limpieza de archivos';
$string['tip_courses'] = 'Archivar o eliminar cursos antiguos que ya no se utilizan';
$string['tip_cache'] = 'Purgar la caché del sistema para liberar espacio temporal';
$string['size_in_gb'] = 'Tamaño (GB)';
$string['totaldiskusage'] = 'Uso total del disco';
$string['diskquota'] = 'Cuota de disco';

// Cadenas para settings.php
$string['mainsettings'] = 'Configuraciones principales';
$string['notificationsettings'] = 'Configuración de notificaciones';
$string['notificationsettingsinfo'] = 'Configure cuándo y cómo se envían las notificaciones.';
$string['disk_warning_level'] = 'Nivel de advertencia de disco';
$string['configdisk_warning_level'] = 'Porcentaje de uso de disco que activa las advertencias.';
$string['users_warning_level'] = 'Nivel de advertencia de usuarios';
$string['configusers_warning_level'] = 'Porcentaje del límite de usuarios que activa las advertencias.';
$string['integrationsettings'] = 'Configuración de integración';
$string['integrationsettingsinfo'] = 'Configure la integración con sistemas externos a través de API y webhooks.';
$string['enable_api'] = 'Habilitar API';
$string['configenable_api'] = 'Habilitar acceso API para que sistemas externos obtengan información de uso.';
$string['enable_webhooks'] = 'Habilitar webhooks';
$string['configenable_webhooks'] = 'Habilitar webhooks para enviar notificaciones a sistemas externos.';

// Cadenas para la API
$string['api_documentation'] = 'Documentación de API';
$string['api_key'] = 'Clave de API';
$string['api_url'] = 'URL de API';
$string['webhook_url'] = 'URL del Webhook';
$string['webhook_secret'] = 'Secreto del Webhook';
$string['webhook_events'] = 'Eventos del Webhook';
$string['webhook_test'] = 'Probar Webhook';
$string['webhook_test_success'] = 'Prueba de webhook exitosa.';
$string['webhook_test_failure'] = 'Prueba de webhook fallida: {$a}';
$string['webhook_added'] = 'Webhook agregado exitosamente.';
$string['webhook_updated'] = 'Webhook actualizado exitosamente.';
$string['webhook_deleted'] = 'Webhook eliminado exitosamente.';
$string['webhook_event_disk_warning'] = 'Advertencia de espacio en disco';
$string['webhook_event_user_warning'] = 'Advertencia de límite de usuarios';

// Cadenas para mensajes de error y éxito
$string['error_invalid_url'] = 'Formato de URL inválido.';
$string['error_connection_failed'] = 'Conexión fallida: {$a}';
$string['error_response_invalid'] = 'Respuesta recibida inválida.';
$string['error_permission_denied'] = 'Permiso denegado.';
$string['error_data_not_found'] = 'Datos no encontrados.';
$string['success_data_saved'] = 'Datos guardados exitosamente.';
$string['success_operation_completed'] = 'Operación completada exitosamente.';

// Cadenas para la visualización de datos
$string['disk_usage_over_time'] = 'Uso de disco a lo largo del tiempo';
$string['user_count_over_time'] = 'Recuento de usuarios a lo largo del tiempo';
$string['notification_history'] = 'Historial de notificaciones';
$string['last_7_days'] = 'Últimos 7 días';
$string['last_30_days'] = 'Últimos 30 días';
$string['last_90_days'] = 'Últimos 90 días';
$string['custom_range'] = 'Rango personalizado';
$string['date_from'] = 'Fecha desde';
$string['date_to'] = 'Fecha hasta';
$string['apply_filter'] = 'Aplicar filtro';
$string['reset_filter'] = 'Restablecer filtro';
$string['export_data'] = 'Exportar datos';
$string['print_report'] = 'Imprimir informe';
$string['refresh_data'] = 'Actualizar datos';
$string['auto_refresh'] = 'Actualización automática';

$string['messagehtml1'] = '<p>La plataforma <a href="{$a->siteurl}" target="_blank" ><strong>\'{$a->sitename}\'</strong></a> ha superado el umbral de usuarios en un {$a->percentaje}%</p>
<p>Fecha (DD/MM/AAAA): {$a->lastday} </p>
<p>Usuarios*: <strong>{$a->numberofusers}</strong></p>
<p>Umbral establecido de usuarios máximos diarios: {$a->threshold} usuarios</p>
<p>Umbral establecido de espacio en disco: <strong>{$a->quotadisk}</strong></p>
<p>Espacio en disco usado*: <strong>{$a->diskusage}</strong></p>
<strong>Url monitor: </strong> {$a->referer}
<br>
<br>
{$a->table}
<br>
<hr>
<i><p>Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" target="_blank" ><strong>ingeweb.co</strong></a></p>
*Se contabilizan usuarios distintos que se autenticaron en la fecha indicada. Usuarios que se autentican más de una vez solo cuenta una vez.<i>';
$string['messagehtml2'] = '
<p>La plataforma <a href="{$a->siteurl}" target="_blank"><strong>\'{$a->sitename}\'</strong></a> ha superado el 90% del umbral del espacio en disco asignado</p>
<p>Fecha (DD/MM/AAAA): {$a->lastday}</p>
<p>Espacio en disco usado*: <strong>{$a->diskusage}</strong></p>
<p>Umbral establecido de espacio en disco: <strong>{$a->quotadisk}</strong></p>
<p>Umbral establecido de usuarios máximos diarios: <strong>{$a->threshold} usuarios</strong></p>
<p>Usuarios*: <strong>{$a->numberofusers}</strong></p>
<strong>Url monitor: </strong> {$a->referer}
<br>
<br>
<table border="1" style="border-collapse: collapse; width: 50%;">
    <tr>
        <th style="padding: 8px; background-color: #f2f2f2;">Descripción</th>
        <th style="padding: 8px; background-color: #f2f2f2;">Detalle</th>
    </tr>
    <tr>
        <td style="padding: 8px;">Porcentaje de uso del disco</td>
        <td style="padding: 8px;"><strong>{$a->percentage}%</strong></td>
    </tr>
    <tr>
        <td style="padding: 8px;">Tamaño de la base de datos</td>
        <td style="padding: 8px;"><strong>{$a->databasesize}</strong></td>
    </tr>
    <tr>
        <td style="padding: 8px;">Cantidad de cursos</td>
        <td style="padding: 8px;"><strong>{$a->coursescount}</strong></td>
    </tr>
    <tr>
        <td style="padding: 8px;">Cantidad de copias de seguridad por curso</td>
        <td style="padding: 8px;"><strong>{$a->backupcount}</strong></td>
    </tr>
</table>
<hr>
<p>Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" target="_blank"><strong>ingeweb.co</strong></a>. Por favor, no responda a este mensaje.</p>';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';
$string['view_details'] = 'Ver detalles';
// Cadenas adicionales para el dashboard
$string['disk_usage_ok'] = 'El uso del disco está en un nivel aceptable. No se requiere acción inmediata.';
$string['user_count_ok'] = 'El recuento de usuarios está en un nivel aceptable. No se requiere acción inmediata.';
$string['user_limit_tips'] = 'Consejos para gestionar el límite de usuarios:';
$string['tip_user_inactive'] = 'Considere limpiar las cuentas de usuario inactivas que no han iniciado sesión durante mucho tiempo.';
$string['tip_user_limit'] = 'Si el número de usuarios se acerca constantemente al límite, considere aumentar su cuota.';
$string['files_dir'] = 'Files (filedir)';
$string['exclusivedisclaimer'] = 'This plugin is part of, and is to be exclusively used with the Moodle hosting service provided by <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
