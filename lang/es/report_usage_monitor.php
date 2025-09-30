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
 * Spanish language strings for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings.
$string['pluginname'] = 'Monitor de Uso';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';
$string['exclusivedisclaimer'] = 'Este plugin hace parte y es de uso exclusivo del servicio de hosting para Moodle proporcionado por <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// Dashboard strings.
$string['dashboard'] = 'Panel de Control';
$string['dashboard_title'] = 'Panel de Control de Uso';
$string['diskusage'] = 'Uso del Disco';
$string['users_today_card'] = 'Usuarios Diarios Hoy';
$string['max_userdaily_for_90_days'] = 'Máximo de Usuarios Diarios (Últimos 90 Días)';
$string['notcalculatedyet'] = 'Aún no calculado';
$string['lastexecution'] = 'Último cálculo: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de disco: {$a}';
$string['users_today'] = 'Usuarios diarios hoy: {$a}';
$string['date'] = 'Fecha';
$string['last_calculation'] = 'Último cálculo';
$string['usersquantity'] = 'Cantidad de Usuarios';
$string['disk_usage_distribution'] = 'Distribución de Uso de Disco';
$string['disk_usage_history'] = 'Historial de Uso de Disco (Últimos 30 Días)';
$string['percentage_used'] = 'Porcentaje Utilizado';

// Dashboard sections.
$string['disk_usage_by_directory'] = 'Uso de Disco por Directorio';
$string['largest_courses'] = 'Cursos Más Grandes';
$string['database'] = 'Base de Datos';
$string['files_dir'] = 'Archivos';
$string['cache'] = 'Caché';
$string['others'] = 'Otros';
$string['directory'] = 'Directorio';
$string['size'] = 'Tamaño';
$string['percentage'] = 'Porcentaje';
$string['course'] = 'Curso';
$string['backup_count'] = 'Copias';
$string['topuser'] = 'Top 10 Usuarios Diarios';
$string['lastusers'] = 'Últimos 10 Días de Usuarios';
$string['usertable'] = 'Vista de Tabla';
$string['userchart'] = 'Vista de Gráfica';
$string['system_info'] = 'Información del Sistema';
$string['moodle_version'] = 'Versión de Moodle';
$string['total_courses'] = 'Total de Cursos';
$string['backup_per_course'] = 'Copias por Curso';
$string['registered_users'] = 'Usuarios Registrados';
$string['active_users'] = 'activos';
$string['suspended_users'] = 'suspendidos';
$string['recommendations'] = 'Recomendaciones';

// Warning levels.
$string['warning70'] = 'Advertencia (70%)';
$string['critical90'] = 'Crítico (90%)';
$string['limit100'] = 'Límite (100%)';
$string['percent_of_threshold'] = '% del umbral';

// Recommendations.
$string['space_saving_tips'] = 'Consejos para ahorrar espacio en disco:';
$string['tip_backups'] = 'Reducir el número de copias automáticas por curso (actualmente: {$a})';
$string['tip_files'] = 'Limpiar archivos antiguos sin uso';
$string['tip_courses'] = 'Archivar o eliminar cursos antiguos';
$string['tip_cache'] = 'Purgar la caché del sistema';
$string['disk_usage_ok'] = 'El uso del disco está en un nivel aceptable.';
$string['user_count_ok'] = 'El recuento de usuarios está en un nivel aceptable.';
$string['user_limit_tips'] = 'Consejos para gestionar el límite de usuarios:';
$string['tip_user_inactive'] = 'Limpiar cuentas de usuario inactivas';
$string['tip_user_limit'] = 'Considere aumentar su cuota de usuarios';

// Task strings.
$string['calculatediskusagetask'] = 'Calcular uso del disco';
$string['getlastusers'] = 'Calcular top de usuarios diarios';
$string['getlastusers90days'] = 'Calcular máximo de usuarios en 90 días';
$string['getlastusersconnected'] = 'Calcular usuarios de hoy';
$string['processdisknotificationtask'] = 'Procesar notificaciones de disco';
$string['processuserlimitnotificationtask'] = 'Procesar notificaciones de límite de usuarios';

// Settings strings.
$string['mainsettings'] = 'Configuración Principal';
$string['email'] = 'Email de Notificación';
$string['configemail'] = 'Dirección de correo para notificaciones';
$string['max_daily_users_threshold'] = 'Límite de Usuarios';
$string['configmax_daily_users_threshold'] = 'Número máximo de usuarios diarios';
$string['disk_quota'] = 'Cuota de Disco';
$string['configdisk_quota'] = 'Cuota de disco en gigabytes';
$string['notificationsettings'] = 'Configuración de Notificaciones';
$string['notificationsettingsinfo'] = 'Configure los umbrales de notificación';
$string['disk_warning_level'] = 'Nivel de Advertencia de Disco';
$string['configdisk_warning_level'] = 'Porcentaje que activa advertencias de disco';
$string['users_warning_level'] = 'Nivel de Advertencia de Usuarios';
$string['configusers_warning_level'] = 'Porcentaje que activa advertencias de usuarios';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Ruta al comando de uso de disco (du)';
$string['pathtodu_autodetected'] = 'Ruta a du auto-detectada: {$a}';
$string['pathtodurecommendation'] = 'Configure la ruta a \'du\' en Rutas del Sistema';
$string['pathtodunote'] = 'La auto-detección solo funciona en sistemas Linux';
$string['activateshellexec'] = 'shell_exec no está disponible';
$string['enable_api'] = 'Habilitar API';
$string['configenable_api'] = 'Habilitar acceso API externo';

// Email notification strings.
$string['subjectemail1'] = 'Alerta de Límite de Usuarios:';
$string['subjectemail2'] = 'Alerta de Espacio en Disco:';

// API strings.
$string['apidisabled'] = 'La API está deshabilitada';
$string['user_threshold_updated'] = 'Umbral de usuarios actualizado correctamente';
$string['disk_threshold_updated'] = 'Umbral de disco actualizado correctamente';
$string['error_user_threshold_negative'] = 'El umbral de usuarios debe ser positivo';
$string['error_disk_threshold_negative'] = 'El umbral de disco debe ser positivo';
$string['error_no_thresholds_provided'] = 'No se proporcionaron umbrales';

// Email templates (simplified).
$string['messagehtml_userlimit'] = 'Límite de usuarios excedido en {$a->sitename}. Actual: {$a->numberofusers}, Límite: {$a->threshold}';
$string['messagehtml_diskusage'] = 'Alerta de uso de disco en {$a->sitename}. Usado: {$a->diskusage}, Cuota: {$a->quotadisk}';