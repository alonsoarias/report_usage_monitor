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
 * Plugin strings in Spanish.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings
$string['pluginname'] = 'Monitor de Uso';
$string['reportinfotext'] = 'Monitor de Uso v5.0 - Desarrollado por <strong>Alonso Arias</strong> para <strong>IngeWeb</strong>. Una solución integral de monitoreo para plataformas de hosting Moodle.';
$string['exclusivedisclaimer'] = 'Este plugin es parte del servicio de hosting Moodle proporcionado por <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';
$string['privacy:metadata'] = 'El plugin Monitor de Uso no almacena datos personales.';

// Dashboard strings
$string['dashboard'] = 'Panel de Control';
$string['dashboard_title'] = 'Panel de Control del Monitor de Uso';
$string['diskusage'] = 'Uso del Disco';
$string['users_today_card'] = 'Usuarios Activos Diarios';
$string['max_userdaily_for_90_days'] = 'Pico de Usuarios (90 días)';
$string['notcalculatedyet'] = 'Aún no calculado';
$string['lastexecutioncalculate'] = 'Último cálculo de disco: {$a}';
$string['lastexecution'] = 'Último cálculo de usuarios: {$a}';
$string['date'] = 'Fecha';
$string['last_calculation'] = 'Último cálculo';
$string['usersquantity'] = 'Usuarios Diarios';
$string['disk_usage_distribution'] = 'Distribución del Uso de Disco';
$string['disk_usage_history'] = 'Historial de Uso de Disco (30 Días)';
$string['percentage_used'] = 'Porcentaje Utilizado';

// Dashboard sections
$string['disk_usage_by_directory'] = 'Desglose de Almacenamiento';
$string['largest_courses'] = 'Cursos más Grandes';
$string['database'] = 'Base de Datos';
$string['files_dir'] = 'Archivos';
$string['cache'] = 'Caché';
$string['others'] = 'Otros';
$string['directory'] = 'Directorio';
$string['size'] = 'Tamaño';
$string['percentage'] = 'Porcentaje';
$string['course'] = 'Curso';
$string['backup_count'] = 'Copias de Seguridad';
$string['topuser'] = 'Días de Mayor Uso';
$string['lastusers'] = 'Actividad Reciente (10 días)';
$string['usertable'] = 'Vista de Tabla';
$string['userchart'] = 'Vista de Gráfico';
$string['system_info'] = 'Información del Sistema';
$string['moodle_version'] = 'Versión de Moodle';
$string['total_courses'] = 'Total de Cursos';
$string['backup_per_course'] = 'Copias por Curso';
$string['registered_users'] = 'Usuarios Registrados';
$string['active_users'] = 'activos';
$string['suspended_users'] = 'suspendidos';
$string['recommendations'] = 'Recomendaciones';
$string['projections'] = 'Proyecciones';

// Warning levels and indicators
$string['warning70'] = 'Precaución (70%)';
$string['critical90'] = 'Advertencia (90%)';
$string['limit100'] = 'Crítico (100%)';
$string['percent_of_threshold'] = '% del límite';

// Recommendation tips
$string['space_saving_tips'] = 'Consejos para optimizar almacenamiento:';
$string['tip_backups'] = 'Considere reducir las copias automáticas por curso (actual: {$a})';
$string['tip_files'] = 'Limpie archivos antiguos sin uso mediante herramientas de gestión';
$string['tip_courses'] = 'Archive o elimine cursos no utilizados';
$string['tip_cache'] = 'Limpie la caché del sistema para liberar espacio temporal';
$string['disk_usage_ok'] = 'El uso del disco está en niveles óptimos.';
$string['user_count_ok'] = 'La actividad de usuarios está dentro de parámetros normales.';
$string['user_limit_tips'] = 'Recomendaciones de gestión de usuarios:';
$string['tip_user_inactive'] = 'Revise y limpie cuentas de usuario inactivas';
$string['tip_user_limit'] = 'Considere aumentar los límites si se acerca consistentemente al umbral';

// Task strings
$string['calculatediskusagetask'] = 'Calcular estadísticas de uso de disco';
$string['calculateuserstask'] = 'Calcular estadísticas de actividad de usuarios';
$string['getlastusers'] = 'Calcular estadísticas de usuarios diarios';
$string['getlastusers90days'] = 'Calcular pico de uso de 90 días';
$string['processdisknotificationtask'] = 'Procesar notificaciones de uso de disco';
$string['processuserlimitnotificationtask'] = 'Procesar notificaciones de límite de usuarios';
$string['cleanuphistorytask'] = 'Limpiar datos históricos';

// Settings strings
$string['mainsettings'] = 'Configuración Principal';
$string['email'] = 'Email de Notificaciones';
$string['configemail'] = 'Dirección de correo para notificaciones y alertas del sistema.';
$string['max_daily_users_threshold'] = 'Límite de Usuarios Diarios';
$string['configmax_daily_users_threshold'] = 'Número máximo de usuarios activos diarios permitidos.';
$string['disk_quota'] = 'Cuota de Disco (GB)';
$string['configdisk_quota'] = 'Asignación total de espacio en disco en gigabytes.';
$string['notificationsettings'] = 'Configuración de Notificaciones';
$string['notificationsettingsinfo'] = 'Configure umbrales de alerta y comportamiento de notificaciones.';
$string['disk_warning_level'] = 'Umbral de Advertencia de Disco';
$string['configdisk_warning_level'] = 'Porcentaje de uso de disco que activa advertencias.';
$string['users_warning_level'] = 'Umbral de Advertencia de Usuarios';
$string['configusers_warning_level'] = 'Porcentaje del límite de usuarios que activa advertencias.';
$string['enable_api'] = 'Habilitar API REST';
$string['configenable_api'] = 'Permitir que sistemas externos accedan a datos de uso vía API REST.';
$string['data_retention_days'] = 'Retención de Datos (días)';
$string['configdata_retention_days'] = 'Número de días para mantener datos históricos (predeterminado: 90).';

// System paths
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Ruta del sistema al comando de uso de disco (du) para cálculos precisos.';
$string['pathtodurecommendation'] = 'Para cálculos óptimos de uso de disco, configure la ruta al comando "du" en Rutas del Sistema.';
$string['pathtodunote'] = 'La ruta del comando du se detectará automáticamente en sistemas Linux cuando esté disponible.';
$string['activateshellexec'] = 'La función shell_exec está deshabilitada. Habilítela para cálculos mejorados de uso de disco.';

// Email notification strings
$string['subjectemail1'] = 'Alerta de Límite de Usuarios - ';
$string['subjectemail2'] = 'Alerta de Uso de Disco - ';

// API strings
$string['api_documentation'] = 'Documentación de API';
$string['user_threshold_updated'] = 'Umbral de usuarios actualizado exitosamente.';
$string['disk_threshold_updated'] = 'Umbral de disco actualizado exitosamente.';
$string['error_user_threshold_negative'] = 'El umbral de usuarios debe ser mayor que 0.';
$string['error_disk_threshold_negative'] = 'El umbral de disco debe ser mayor que 0.';
$string['error_no_thresholds_provided'] = 'No se proporcionaron umbrales válidos para actualizar.';

// Capabilities
$string['usage_monitor:view'] = 'Ver reportes del monitor de uso';
$string['usage_monitor:manage'] = 'Gestionar configuraciones del monitor de uso';
$string['usage_monitor:apiuse'] = 'Acceder a la API del monitor de uso';