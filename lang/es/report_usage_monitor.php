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
 * Cadenas de texto en español.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Cadenas generales del plugin
$string['pluginname'] = 'Monitor de Uso';
$string['exclusivedisclaimer'] = 'Este plugin hace parte y es de uso exclusivo del servicio de hosting para Moodle proporcionado por <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
$string['reportinfotext'] = 'Este plugin ha sido creado por <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';

// Cadenas de configuración
$string['email'] = 'Correo para notificaciones';
$string['configemail'] = 'Dirección de correo donde se enviarán las notificaciones del sistema';
$string['max_daily_users_threshold'] = 'Límite de usuarios diarios';
$string['configmax_daily_users_threshold'] = 'Número máximo de usuarios diarios permitidos antes de generar alertas';
$string['disk_quota'] = 'Cuota de disco';
$string['configdisk_quota'] = 'Cuota de disco en gigabytes';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Configura la ruta al comando du para los cálculos de uso de disco';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a \'du\' en las Rutas del sistema de Moodle. Puede encontrar esta configuración en Administración del sitio > Servidor > Rutas del sistema. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Haga clic aquí para ir a Rutas del sistema</a>.';
$string['pathtodunote'] = 'Nota: El path a \'du\' se detectará automáticamente solo si este plugin se encuentra en un sistema Linux y si se logra detectar la ubicación de \'du\'.';

// Descripciones de tareas
$string['processunifiednotificationtask'] = 'Procesar notificaciones unificadas de monitoreo del sistema';
$string['calculatediskusagetask'] = 'Calcular uso de disco';
$string['getlastusers'] = 'Calcular estadísticas de usuarios diarios';
$string['getlastusers90days'] = 'Obtener pico de usuarios diarios en últimos 90 días';
$string['getlastusersconnected'] = 'Calcular usuarios diarios actuales';

// Niveles de alerta
$string['critical_threshold'] = 'CRÍTICO';
$string['high_threshold'] = 'ALTO';
$string['medium_threshold'] = 'MEDIO';
$string['normal_threshold'] = 'NORMAL';
$string['threshold_info'] = 'Umbrales de alerta: CRÍTICO (95%), ALTO (90%), MEDIO (80%)';

// Información de usuarios pico
$string['peak_users_title'] = 'Pico de Usuarios Diarios (90 Días)';
$string['peak_date_label'] = 'Fecha Pico';
$string['peak_users_label'] = 'Máximo de Usuarios Diarios';
$string['peak_percent_label'] = 'Uso Pico';

// Mensajes de estado
$string['notcalculatedyet'] = 'Aún no calculado';
$string['disk_usage_status'] = 'Estado de uso de disco: {$a->level}';
$string['user_count_status'] = 'Estado de usuarios diarios: {$a->level}';
$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';

// Secciones del informe
$string['topuser'] = 'Top 10 Usuarios Diarios';
$string['lastusers'] = 'Usuarios Diarios Últimos 10 Días';
$string['max_userdaily_for_90_days'] = 'Pico de Usuarios Diarios (90 Días)';
$string['userstopnum'] = 'Usuarios Diarios';
$string['user_count_title'] = 'Contador de Usuarios Diarios';
$string['additional_info'] = 'Información Adicional';
$string['system_status'] = 'Estado del Sistema';
$string['historical_data'] = 'Datos Históricos';

// Elementos de interfaz
$string['usertable'] = 'Tabla de usuarios';
$string['userchart'] = 'Gráfico de usuarios';
$string['date'] = 'Fecha';
$string['usersquantity'] = 'Cantidad de usuarios diarios';
$string['dateformatsql'] = '%d/%m/%Y';
$string['dateformat'] = 'd/m/Y';

// Métricas de uso de disco
$string['diskusage'] = 'Uso de Disco';
$string['sizeusage'] = 'Uso total de disco';
$string['sizedatabase'] = 'Tamaño de base de datos';
$string['avalilabledisk'] = 'Espacio en disco disponible';
$string['disk_metrics_details'] = 'Detalles de Métricas de Disco';
$string['disk_usage_title'] = 'Uso Actual del Disco';
$string['database_size_title'] = 'Tamaño de Base de Datos';

// Información de ejecución
$string['lastexecution'] = 'Última ejecución de cálculo de usuarios diarios: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['last_execution_title'] = 'Última Ejecución del Reporte';
$string['today_users_title'] = 'Usuarios Diarios Actuales';
$string['users_today'] = 'Cantidad de usuarios diarios el día de hoy: {$a}';

// Métricas adicionales
$string['total_courses'] = 'Total de Cursos';
$string['backup_retention'] = 'Retención de Copias';
$string['per_course'] = 'por curso';

// Cadenas de notificación
$string['unifiednotification_subject'] = 'Alerta de Monitoreo del Sistema - {$a}';
$string['unifiednotification_html'] = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        .metric-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            min-height: 150px;
        }
        .metric-box {
            flex: 1;
            min-width: 200px;
            max-width: calc(33.33% - 14px);
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .metric-title {
            font-size: 14px;
            color: #495057;
            margin-bottom: 10px;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #212529;
            margin: 10px 0;
        }
        .metric-subtitle {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .metric-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .alert-critical { background: #ffebee; border-left: 4px solid #c62828; }
        .alert-high { background: #fff3e0; border-left: 4px solid #ef6c00; }
        .alert-medium { background: #fff8e1; border-left: 4px solid #f9a825; }
        .alert-normal { background: #e8f5e9; border-left: 4px solid #2e7d32; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #1a73e8; margin-bottom: 20px;">Reporte de Estado del Sistema - {$a->sitename}</h1>
        
        <!-- Tarjeta de Uso de Disco -->
        <div class="metric-card alert-{$a->disk_alert_class}">
            <h2>Estado de Uso de Disco</h2>
            <div class="metric-container">
                <div class="metric-box">
                    <div class="metric-title">Uso Total</div>
                    <div class="metric-value">{$a->diskusage}</div>
                    <div class="metric-subtitle">de {$a->quotadisk}</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Nivel de Uso</div>
                    <div class="metric-value">{$a->disk_percent}%</div>
                    <div class="metric-subtitle">{$a->disk_alert}</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Tamaño de Base de Datos</div>
                    <div class="metric-value">{$a->databasesize}</div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Usuarios Diarios -->
        <div class="metric-card alert-{$a->user_alert_class}">
            <h2>Estado de Usuarios Diarios</h2>
            <div class="metric-container">
                <div class="metric-box">
                    <div class="metric-title">Usuarios Diarios Actuales</div>
                    <div class="metric-value">{$a->numberofusers}</div>
                    <div class="metric-subtitle">de {$a->threshold} límite</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Nivel de Uso</div>
                    <div class="metric-value">{$a->user_percent}%</div>
                    <div class="metric-subtitle">{$a->user_alert}</div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Pico de Usuarios Diarios -->
        <div class="metric-card">
            <h2>Pico de Usuarios Diarios (90 Días)</h2>
            <div class="metric-container">
                <div class="metric-box">
                    <div class="metric-title">Fecha Pico</div>
                    <div class="metric-value">{$a->max_90_days_date}</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Máximo de Usuarios Diarios</div>
                    <div class="metric-value">{$a->max_90_days_users}</div>
                    <div class="metric-subtitle">de {$a->threshold} límite</div>
                </div>
                <div class="metric-box">
                    <div class="metric-title">Uso Pico</div>
                    <div class="metric-value">{$a->max_90_days_percent}%</div>
                    <div class="metric-subtitle">de capacidad total</div>
                </div>
            </div>
        </div>

        <!-- Métricas Adicionales -->
        {$a->table}

        <div class="footer">
            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
            <i>
                <p style="font-size: 0.9em; color: #666;">Este mensaje ha sido generado automáticamente por "Usage Report" de <a href="https://ingeweb.co/" target="_blank"><strong>ingeweb.co</strong></a></p>
                <p style="font-size: 0.9em; color: #666;">*Se contabilizan usuarios distintos que iniciaron sesión en la fecha indicada. Múltiples inicios de sesión del mismo usuario en el mismo día cuentan como un usuario diario.</p>
            </i>
            <p style="font-size: 0.9em; color: #666;">Esta es una notificación automatizada de monitoreo. Las métricas del sistema son recolectadas y analizadas periódicamente para asegurar un rendimiento óptimo de la plataforma.</p>
        </div>
    </div>
</body>
</html>';