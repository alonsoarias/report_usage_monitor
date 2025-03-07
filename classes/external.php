<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * API externa para obtener estadísticas del plugin.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

/**
 * Clase de API externa para el plugin report_usage_monitor
 */
class report_usage_monitor_external extends external_api {

    /**
     * Devuelve la definición de parámetros para get_monitor_stats.
     *
     * @return external_function_parameters
     */
    public static function get_monitor_stats_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Devuelve las estadísticas actuales de uso para integración con sistemas externos.
     *
     * @return array Conjunto de estadísticas
     */
    public static function get_monitor_stats() {
        global $DB, $CFG;
        
        // Verificar permisos
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);
        
        // Obtener configuraciones
        $reportconfig = get_config('report_usage_monitor');
        
        // Calcular uso de disco
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk);
        
        // Calcular uso de usuarios
        $users_today = !empty($reportconfig->totalusersdaily) ? ($reportconfig->totalusersdaily) : 0;
        $user_threshold = $reportconfig->max_daily_users_threshold;
        $users_percent = calculate_threshold_percentage($users_today, $user_threshold);
        
        // Analizar directorios - usar datos precalculados si están disponibles
        $dir_analysis_json = $reportconfig->dir_analysis ?? '{}';
        $dir_analysis = json_decode($dir_analysis_json, true);
        if (empty($dir_analysis) || !is_array($dir_analysis)) {
            $dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);
        }
        
        // Obtener cursos más grandes - usar datos precalculados si están disponibles
        $largest_courses_json = $reportconfig->largest_courses ?? '[]';
        $largest_courses = json_decode($largest_courses_json);
        if (empty($largest_courses)) {
            $largest_courses = get_largest_courses(5);
        }

        // Validar timestamps para info de últimos cálculos
        $last_disk_calc = !empty($reportconfig->lastexecutioncalculate) ? $reportconfig->lastexecutioncalculate : 0;
        $last_users_calc = !empty($reportconfig->lastexecution) ? $reportconfig->lastexecution : 0;
        
        // Validar que los timestamps sean valores numéricos válidos
        if (!is_numeric($last_disk_calc) || $last_disk_calc <= 0) {
            $last_disk_calc = time();
            debugging('get_monitor_stats: Timestamp inválido para lastexecutioncalculate: ' . var_export($reportconfig->lastexecutioncalculate, true), DEBUG_DEVELOPER);
        }
        
        if (!is_numeric($last_users_calc) || $last_users_calc <= 0) {
            $last_users_calc = time();
            debugging('get_monitor_stats: Timestamp inválido para lastexecution: ' . var_export($reportconfig->lastexecution, true), DEBUG_DEVELOPER);
        }
        
        // Validar timestamp para max_userdaily_for_90_days_date
        $max_90_days_date = !empty($reportconfig->max_userdaily_for_90_days_date) ? 
                          $reportconfig->max_userdaily_for_90_days_date : null;
        if (!is_numeric($max_90_days_date) || $max_90_days_date <= 0) {
            $max_90_days_date = null;
            debugging('get_monitor_stats: Timestamp inválido para max_userdaily_for_90_days_date: ' . var_export($reportconfig->max_userdaily_for_90_days_date, true), DEBUG_DEVELOPER);
        }
        
        // Crear estructura de respuesta
        $response = array(
            'site_info' => array(
                'name' => format_string($SITE->fullname),
                'shortname' => format_string($SITE->shortname),
                'moodle_version' => $CFG->version,
                'moodle_release' => $CFG->release,
                'course_count' => $DB->count_records('course'),
                'user_count' => $DB->count_records('user', array('deleted' => 0)) - 1,
                'backup_auto_max_kept' => get_config('backup', 'backup_auto_max_kept'),
            ),
            'disk_usage' => array(
                'total_bytes' => $disk_usage,
                'total_readable' => display_size($disk_usage),
                'quota_bytes' => $quotadisk,
                'quota_readable' => display_size($quotadisk),
                'percentage' => round($disk_percent, 2),
                'details' => array(
                    'database' => array(
                        'bytes' => $dir_analysis['database'],
                        'readable' => display_size($dir_analysis['database']),
                        'percentage' => round(($dir_analysis['database'] / $disk_usage) * 100, 2)
                    ),
                    'filedir' => array(
                        'bytes' => $dir_analysis['filedir'],
                        'readable' => display_size($dir_analysis['filedir']),
                        'percentage' => round(($dir_analysis['filedir'] / $disk_usage) * 100, 2)
                    ),
                    'cache' => array(
                        'bytes' => $dir_analysis['cache'],
                        'readable' => display_size($dir_analysis['cache']),
                        'percentage' => round(($dir_analysis['cache'] / $disk_usage) * 100, 2)
                    ),
                    'backup' => array(
                        'bytes' => $dir_analysis['backup'] ?? 0,
                        'readable' => display_size($dir_analysis['backup'] ?? 0),
                        'percentage' => round((($dir_analysis['backup'] ?? 0) / $disk_usage) * 100, 2)
                    ),
                    'others' => array(
                        'bytes' => $dir_analysis['others'],
                        'readable' => display_size($dir_analysis['others']),
                        'percentage' => round(($dir_analysis['others'] / $disk_usage) * 100, 2)
                    )
                )
            ),
            'user_usage' => array(
                'daily_users' => $users_today,
                'threshold' => $user_threshold,
                'percentage' => round($users_percent, 2),
                'max_90_days' => !empty($reportconfig->max_userdaily_for_90_days_users) ? 
                                $reportconfig->max_userdaily_for_90_days_users : 0,
                'max_90_days_date' => $max_90_days_date ? 
                                date('Y-m-d', $max_90_days_date) : null
            ),
            'largest_courses' => array(),
            'timestamps' => array(
                'disk_calculation' => $last_disk_calc,
                'users_calculation' => $last_users_calc
            ),
            // Tasas de crecimiento y proyecciones
            'growth_rates' => array(
                'disk' => array(
                    'monthly_percent' => calculate_growth_rate('disk'),
                    'projected_days_to_threshold' => project_limit_date(
                        $disk_usage, 
                        $quotadisk * 0.9, // Proyección para alcanzar el 90% del umbral
                        calculate_growth_rate('disk')
                    )
                ),
                'users' => array(
                    'monthly_percent' => calculate_growth_rate('users'),
                    'projected_days_to_threshold' => project_limit_date(
                        $users_today,
                        $user_threshold * 0.9, // Proyección para alcanzar el 90% del umbral
                        calculate_growth_rate('users')
                    )
                )
            )
        );
        
        // Formatear datos de cursos más grandes
        foreach ($largest_courses as $course) {
            // Asegurarse de que los datos del curso son válidos
            if (!isset($course->id) || !isset($course->fullname) || !isset($course->shortname)) {
                debugging('get_monitor_stats: Datos de curso incompletos: ' . var_export($course, true), DEBUG_DEVELOPER);
                continue;
            }
            
            $response['largest_courses'][] = array(
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'size_bytes' => $course->filesize,
                'size_readable' => display_size($course->filesize),
                'backup_size_bytes' => $course->backupsize ?? 0,
                'backup_size_readable' => display_size($course->backupsize ?? 0),
                'percentage' => $course->percentage,
                'backup_count' => $course->backupcount
            );
        }
        
        return $response;
    }

    /**
     * Devuelve la definición de resultado para get_monitor_stats.
     *
     * @return external_description
     */
    public static function get_monitor_stats_returns() {
        return new external_single_structure(
            array(
                'site_info' => new external_single_structure(
                    array(
                        'name' => new external_value(PARAM_TEXT, get_string('site_name', 'report_usage_monitor')),
                        'shortname' => new external_value(PARAM_TEXT, get_string('site_shortname', 'report_usage_monitor')),
                        'moodle_version' => new external_value(PARAM_INT, get_string('moodle_version', 'report_usage_monitor')),
                        'moodle_release' => new external_value(PARAM_TEXT, get_string('moodle_release', 'report_usage_monitor')),
                        'course_count' => new external_value(PARAM_INT, get_string('course_count', 'report_usage_monitor')),
                        'user_count' => new external_value(PARAM_INT, get_string('user_count', 'report_usage_monitor')),
                        'backup_auto_max_kept' => new external_value(PARAM_INT, get_string('backup_auto_max_kept', 'report_usage_monitor'))
                    )
                ),
                'disk_usage' => new external_single_structure(
                    array(
                        'total_bytes' => new external_value(PARAM_INT, get_string('total_bytes', 'report_usage_monitor')),
                        'total_readable' => new external_value(PARAM_TEXT, get_string('total_readable', 'report_usage_monitor')),
                        'quota_bytes' => new external_value(PARAM_INT, get_string('quota_bytes', 'report_usage_monitor')),
                        'quota_readable' => new external_value(PARAM_TEXT, get_string('quota_readable', 'report_usage_monitor')),
                        'percentage' => new external_value(PARAM_FLOAT, get_string('disk_percentage', 'report_usage_monitor')),
                        'details' => new external_single_structure(
                            array(
                                'database' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, get_string('database_bytes', 'report_usage_monitor')),
                                        'readable' => new external_value(PARAM_TEXT, get_string('database_readable', 'report_usage_monitor')),
                                        'percentage' => new external_value(PARAM_FLOAT, get_string('database_percentage', 'report_usage_monitor'))
                                    )
                                ),
                                'filedir' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, get_string('filedir_bytes', 'report_usage_monitor')),
                                        'readable' => new external_value(PARAM_TEXT, get_string('filedir_readable', 'report_usage_monitor')),
                                        'percentage' => new external_value(PARAM_FLOAT, get_string('filedir_percentage', 'report_usage_monitor'))
                                    )
                                ),
                                'cache' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, get_string('cache_bytes', 'report_usage_monitor')),
                                        'readable' => new external_value(PARAM_TEXT, get_string('cache_readable', 'report_usage_monitor')),
                                        'percentage' => new external_value(PARAM_FLOAT, get_string('cache_percentage', 'report_usage_monitor'))
                                    )
                                ),
                                'backup' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, get_string('backup_bytes', 'report_usage_monitor')),
                                        'readable' => new external_value(PARAM_TEXT, get_string('backup_readable', 'report_usage_monitor')),
                                        'percentage' => new external_value(PARAM_FLOAT, get_string('backup_percentage', 'report_usage_monitor'))
                                    )
                                ),
                                'others' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, get_string('others_bytes', 'report_usage_monitor')),
                                        'readable' => new external_value(PARAM_TEXT, get_string('others_readable', 'report_usage_monitor')),
                                        'percentage' => new external_value(PARAM_FLOAT, get_string('others_percentage', 'report_usage_monitor'))
                                    )
                                )
                            )
                        )
                    )
                ),
                'user_usage' => new external_single_structure(
                    array(
                        'daily_users' => new external_value(PARAM_INT, get_string('daily_users', 'report_usage_monitor')),
                        'threshold' => new external_value(PARAM_INT, get_string('user_threshold', 'report_usage_monitor')),
                        'percentage' => new external_value(PARAM_FLOAT, get_string('user_percentage', 'report_usage_monitor')),
                        'max_90_days' => new external_value(PARAM_INT, get_string('max_90_days', 'report_usage_monitor')),
                        'max_90_days_date' => new external_value(PARAM_TEXT, get_string('max_90_days_date', 'report_usage_monitor'), VALUE_OPTIONAL)
                    )
                ),
                'largest_courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, get_string('course_id', 'report_usage_monitor')),
                            'fullname' => new external_value(PARAM_TEXT, get_string('course_fullname', 'report_usage_monitor')),
                            'shortname' => new external_value(PARAM_TEXT, get_string('course_shortname', 'report_usage_monitor')),
                            'size_bytes' => new external_value(PARAM_INT, get_string('course_size_bytes', 'report_usage_monitor')),
                            'size_readable' => new external_value(PARAM_TEXT, get_string('course_size_readable', 'report_usage_monitor')),
                            'backup_size_bytes' => new external_value(PARAM_INT, get_string('course_backup_size_bytes', 'report_usage_monitor')),
                            'backup_size_readable' => new external_value(PARAM_TEXT, get_string('course_backup_size_readable', 'report_usage_monitor')),
                            'percentage' => new external_value(PARAM_FLOAT, get_string('course_percentage', 'report_usage_monitor')),
                            'backup_count' => new external_value(PARAM_INT, get_string('course_backup_count', 'report_usage_monitor'))
                        )
                    )
                ),
                'timestamps' => new external_single_structure(
                    array(
                        'disk_calculation' => new external_value(PARAM_INT, get_string('disk_calculation_timestamp', 'report_usage_monitor')),
                        'users_calculation' => new external_value(PARAM_INT, get_string('users_calculation_timestamp', 'report_usage_monitor'))
                    )
                ),
                // Nueva estructura para datos de crecimiento y proyecciones
                'growth_rates' => new external_single_structure(
                    array(
                        'disk' => new external_single_structure(
                            array(
                                'monthly_percent' => new external_value(PARAM_FLOAT, 'Tasa de crecimiento mensual de disco en porcentaje'),
                                'projected_days_to_threshold' => new external_value(PARAM_INT, 'Días proyectados para alcanzar el umbral de advertencia')
                            )
                        ),
                        'users' => new external_single_structure(
                            array(
                                'monthly_percent' => new external_value(PARAM_FLOAT, 'Tasa de crecimiento mensual de usuarios en porcentaje'),
                                'projected_days_to_threshold' => new external_value(PARAM_INT, 'Días proyectados para alcanzar el umbral de advertencia')
                            )
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Devuelve la definición de parámetros para get_notification_history.
     *
     * @return external_function_parameters
     */
    public static function get_notification_history_parameters() {
        return new external_function_parameters(
            array(
                'type' => new external_value(PARAM_ALPHA, get_string('notification_type', 'report_usage_monitor'), VALUE_DEFAULT, 'all'),
                'limit' => new external_value(PARAM_INT, get_string('notification_limit', 'report_usage_monitor'), VALUE_DEFAULT, 30),
                'offset' => new external_value(PARAM_INT, get_string('notification_offset', 'report_usage_monitor'), VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Devuelve el historial de notificaciones enviadas.
     *
     * @param string $type Tipo de notificación (disk, users, o all)
     * @param int $limit Número máximo de registros
     * @param int $offset Desplazamiento para paginación
     * @return array Historial de notificaciones
     */
    public static function get_notification_history($type = 'all', $limit = 30, $offset = 0) {
        global $DB;
        
        // Verificar permisos
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);
        
        // Validar parámetros
        $params = self::validate_parameters(self::get_notification_history_parameters(), 
                                           array('type' => $type, 'limit' => $limit, 'offset' => $offset));
        
        // Consultar historial de notificaciones
        $where = '';
        $sqlparams = array();
        
        if ($params['type'] !== 'all') {
            $where = ' WHERE type = :type';
            $sqlparams['type'] = $params['type'];
        }
        
        $sql = "SELECT * FROM {report_usage_monitor_history}" . $where . 
               " ORDER BY timecreated DESC";
        
        $records = $DB->get_records_sql($sql, $sqlparams, $params['offset'], $params['limit']);
        
        // Formatear resultados
        $results = array();
        foreach ($records as $record) {
            // Validar que timecreated sea un timestamp válido
            if (!is_numeric($record->timecreated) || $record->timecreated <= 0) {
                debugging('get_notification_history: Timestamp inválido: ' . var_export($record->timecreated, true), DEBUG_DEVELOPER);
                $record->timecreated = time(); // Usar tiempo actual como fallback
            }
            
            $results[] = array(
                'id' => $record->id,
                'type' => $record->type,
                'percentage' => $record->percentage,
                'value' => $record->type === 'disk' ? display_size($record->value) : $record->value,
                'value_raw' => $record->value,
                'threshold' => $record->type === 'disk' ? display_size($record->threshold) : $record->threshold,
                'threshold_raw' => $record->threshold,
                'timecreated' => $record->timecreated,
                'timereadable' => is_numeric($record->timecreated) && $record->timecreated > 0 ? date('M d, Y H:i', (int)$record->timecreated) : date('M d, Y H:i'));
        }
        
        return array(
            'total' => $DB->count_records('report_usage_monitor_history', $sqlparams),
            'limit' => $params['limit'],
            'offset' => $params['offset'],
            'items' => $results
        );
    }

    /**
     * Devuelve la definición de resultado para get_notification_history.
     *
     * @return external_description
     */
    public static function get_notification_history_returns() {
        return new external_single_structure(
            array(
                'total' => new external_value(PARAM_INT, get_string('notification_total', 'report_usage_monitor')),
                'limit' => new external_value(PARAM_INT, get_string('notification_limit_value', 'report_usage_monitor')),
                'offset' => new external_value(PARAM_INT, get_string('notification_offset_value', 'report_usage_monitor')),
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, get_string('notification_id', 'report_usage_monitor')),
                            'type' => new external_value(PARAM_ALPHA, get_string('notification_type_value', 'report_usage_monitor')),
                            'percentage' => new external_value(PARAM_FLOAT, get_string('notification_percentage', 'report_usage_monitor')),
                            'value' => new external_value(PARAM_TEXT, get_string('notification_value', 'report_usage_monitor')),
                            'value_raw' => new external_value(PARAM_INT, get_string('notification_value_raw', 'report_usage_monitor')),
                            'threshold' => new external_value(PARAM_TEXT, get_string('notification_threshold', 'report_usage_monitor')),
                            'threshold_raw' => new external_value(PARAM_INT, get_string('notification_threshold_raw', 'report_usage_monitor')),
                            'timecreated' => new external_value(PARAM_INT, get_string('notification_timecreated', 'report_usage_monitor')),
                            'timereadable' => new external_value(PARAM_TEXT, get_string('notification_timereadable', 'report_usage_monitor'))
                        )
                    )
                )
            )
        );
    }

    /**
     * Devuelve la definición de parámetros para get_usage_data.
     * Método GET optimizado para obtener datos precalculados.
     *
     * @return external_function_parameters
     */
    public static function get_usage_data_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Obtiene los datos precalculados de usuarios y uso de disco.
     * Método GET simplificado para consumo ligero por API.
     *
     * @return array Datos de uso
     */
    public static function get_usage_data() {
        global $DB, $CFG;
        
        // Verificar permisos
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);
        
        // Obtener configuraciones
        $reportconfig = get_config('report_usage_monitor');
        
        // Datos de uso de disco
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk);
        
        // Datos de usuarios
        $users_today = !empty($reportconfig->totalusersdaily) ? ($reportconfig->totalusersdaily) : 0;
        $user_threshold = $reportconfig->max_daily_users_threshold;
        $users_percent = calculate_threshold_percentage($users_today, $user_threshold);
        
        // Validación de timestamps para datos de usuarios
        $last_disk_calc = !empty($reportconfig->lastexecutioncalculate) ? $reportconfig->lastexecutioncalculate : 0;
        $last_users_calc = !empty($reportconfig->lastexecution) ? $reportconfig->lastexecution : 0;
        $max_90_days_date = !empty($reportconfig->max_userdaily_for_90_days_date) ? $reportconfig->max_userdaily_for_90_days_date : 0;
        
        // Validar cada timestamp
        if (!is_numeric($last_disk_calc) || $last_disk_calc <= 0) {
            debugging('get_usage_data: Timestamp inválido para lastexecutioncalculate: ' . var_export($reportconfig->lastexecutioncalculate, true), DEBUG_DEVELOPER);
            $last_disk_calc = time();
        }
        
        if (!is_numeric($last_users_calc) || $last_users_calc <= 0) {
            debugging('get_usage_data: Timestamp inválido para lastexecution: ' . var_export($reportconfig->lastexecution, true), DEBUG_DEVELOPER);
            $last_users_calc = time();
        }
        
        if (!is_numeric($max_90_days_date) || $max_90_days_date <= 0) {
            debugging('get_usage_data: Timestamp inválido para max_userdaily_for_90_days_date: ' . var_export($reportconfig->max_userdaily_for_90_days_date, true), DEBUG_DEVELOPER);
            $max_90_days_date = time();
        }
        
        // Preparar respuesta
        $response = array(
            'disk_usage' => array(
                'current' => $disk_usage,
                'current_readable' => display_size($disk_usage),
                'threshold' => $quotadisk,
                'threshold_readable' => display_size($quotadisk),
                'percentage' => round($disk_percent, 2),
                'last_calculated' => $last_disk_calc
            ),
            'user_usage' => array(
                'current' => $users_today,
                'threshold' => $user_threshold,
                'percentage' => round($users_percent, 2),
                'last_calculated' => $last_users_calc,
                'max_90_days' => !empty($reportconfig->max_userdaily_for_90_days_users) ? 
                                $reportconfig->max_userdaily_for_90_days_users : 0,
                'max_90_days_date' => $max_90_days_date
            ),
            // NUEVOS CAMPOS para proyecciones
            'projections' => array(
                'disk_growth_rate' => calculate_growth_rate('disk'),
                'users_growth_rate' => calculate_growth_rate('users'),
                'days_to_disk_threshold' => project_limit_date(
                    $disk_usage, 
                    $quotadisk * 0.9,
                    calculate_growth_rate('disk')
                ),
                'days_to_users_threshold' => project_limit_date(
                    $users_today,
                    $user_threshold * 0.9,
                    calculate_growth_rate('users')
                )
            )
        );
        
        return $response;
    }

    /**
     * Devuelve la definición de resultado para get_usage_data.
     *
     * @return external_description
     */
    public static function get_usage_data_returns() {
        return new external_single_structure(
            array(
                'disk_usage' => new external_single_structure(
                    array(
                        'current' => new external_value(PARAM_INT, 'Uso actual de disco en bytes'),
                        'current_readable' => new external_value(PARAM_TEXT, 'Uso actual de disco en formato legible'),
                        'threshold' => new external_value(PARAM_INT, 'Umbral de disco en bytes'),
                        'threshold_readable' => new external_value(PARAM_TEXT, 'Umbral de disco en formato legible'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de uso de disco'),
                        'last_calculated' => new external_value(PARAM_INT, 'Timestamp del último cálculo de disco')
                    )
                ),
                'user_usage' => new external_single_structure(
                    array(
                        'current' => new external_value(PARAM_INT, 'Usuarios actuales'),
                        'threshold' => new external_value(PARAM_INT, 'Umbral de usuarios'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de uso de usuarios'),
                        'last_calculated' => new external_value(PARAM_INT, 'Timestamp del último cálculo de usuarios'),
                        'max_90_days' => new external_value(PARAM_INT, 'Máximo de usuarios en los últimos 90 días'),
                        'max_90_days_date' => new external_value(PARAM_INT, 'Timestamp de la fecha con máximo de usuarios')
                    )
                ),
                // Nueva estructura para proyecciones
                'projections' => new external_single_structure(
                    array(
                        'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Tasa de crecimiento mensual de disco en porcentaje'),
                        'users_growth_rate' => new external_value(PARAM_FLOAT, 'Tasa de crecimiento mensual de usuarios en porcentaje'),
                        'days_to_disk_threshold' => new external_value(PARAM_INT, 'Días proyectados para alcanzar el umbral de advertencia de disco'),
                        'days_to_users_threshold' => new external_value(PARAM_INT, 'Días proyectados para alcanzar el umbral de advertencia de usuarios')
                    )
                )
            )
        );
    }

    /**
     * Devuelve la definición de parámetros para set_usage_thresholds.
     * Método SET para configurar umbrales.
     *
     * @return external_function_parameters
     */
    public static function set_usage_thresholds_parameters() {
        return new external_function_parameters(
            array(
                'user_threshold' => new external_value(PARAM_INT, 
                    'Nuevo umbral para usuarios diarios', 
                    VALUE_DEFAULT, null),
                'disk_threshold' => new external_value(PARAM_INT, 
                    'Nuevo umbral para espacio en disco en GB', 
                    VALUE_DEFAULT, null)
            )
        );
    }

    /**
     * Configura los umbrales de usuarios y disco.
     * Método SET para actualizar la configuración.
     *
     * @param int|null $user_threshold Nuevo umbral de usuarios diarios
     * @param int|null $disk_threshold Nuevo umbral de disco en GB
     * @return array Resultado de la operación
     */
    public static function set_usage_thresholds($user_threshold = null, $disk_threshold = null) {
        global $DB;
        
        // Verificar permisos
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:manage', $context);
        
        // Validar parámetros
        $params = self::validate_parameters(self::set_usage_thresholds_parameters(), 
                                         array('user_threshold' => $user_threshold,
                                               'disk_threshold' => $disk_threshold));
        
        $result = array(
            'success' => true,
            'user_threshold_updated' => false,
            'disk_threshold_updated' => false,
            'messages' => array()
        );
        
        // Iniciar transacción para garantizar consistencia
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // Actualizar umbral de usuarios si se proporciona
            if ($params['user_threshold'] !== null) {
                if ($params['user_threshold'] > 0) {
                    set_config('max_daily_users_threshold', $params['user_threshold'], 'report_usage_monitor');
                    $result['user_threshold_updated'] = true;
                    $result['messages'][] = get_string('user_threshold_updated', 'report_usage_monitor');
                    
                    // Actualizar valores precalculados para que reflejen el nuevo umbral
                    $reportconfig = get_config('report_usage_monitor');
                    $users_today = !empty($reportconfig->totalusersdaily) ? ($reportconfig->totalusersdaily) : 0;
                    $users_percent = calculate_threshold_percentage($users_today, $params['user_threshold']);
                    $users_warning_class = ($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger');
                    
                    set_config('users_percent', $users_percent, 'report_usage_monitor');
                    set_config('users_warning_class', $users_warning_class, 'report_usage_monitor');
                } else {
                    $result['success'] = false;
                    $result['messages'][] = get_string('error_user_threshold_negative', 'report_usage_monitor');
                }
            }
            
            // Actualizar umbral de disco si se proporciona
            if ($params['disk_threshold'] !== null) {
                if ($params['disk_threshold'] > 0) {
                    set_config('disk_quota', $params['disk_threshold'], 'report_usage_monitor');
                    $result['disk_threshold_updated'] = true;
                    $result['messages'][] = get_string('disk_threshold_updated', 'report_usage_monitor');
                    
                    // Actualizar valores precalculados para que reflejen el nuevo umbral
                    $reportconfig = get_config('report_usage_monitor');
                    $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
                    $quotadisk_bytes = ((int) $params['disk_threshold'] * 1024) * 1024 * 1024;
                    $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk_bytes);
                    $disk_warning_class = ($disk_percent < 70) ? 'bg-success' : (($disk_percent < 90) ? 'bg-warning' : 'bg-danger');
                    
                    set_config('disk_percent', $disk_percent, 'report_usage_monitor');
                    set_config('disk_warning_class', $disk_warning_class, 'report_usage_monitor');
                    set_config('quotadisk_gb', display_size_in_gb($quotadisk_bytes, 2), 'report_usage_monitor');
                } else {
                    $result['success'] = false;
                    $result['messages'][] = get_string('error_disk_threshold_negative', 'report_usage_monitor');
                }
            }
            
            // Si no se proporcionó ningún parámetro
            if ($params['user_threshold'] === null && $params['disk_threshold'] === null) {
                $result['success'] = false;
                $result['messages'][] = get_string('error_no_thresholds_provided', 'report_usage_monitor');
            }
            
            // Permitir commit de la transacción si todo ha ido bien
            if ($result['success']) {
                $transaction->allow_commit();
            } else {
                $transaction->rollback(new moodle_exception('thresholds_update_failed', 'report_usage_monitor'));
            }
        } catch (Exception $e) {
            $transaction->rollback($e);
            $result['success'] = false;
            $result['messages'][] = 'Error en actualización: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Devuelve la definición de resultado para set_usage_thresholds.
     *
     * @return external_description
     */
    public static function set_usage_thresholds_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Indica si la operación fue exitosa en general'),
                'user_threshold_updated' => new external_value(PARAM_BOOL, 'Indica si se actualizó el umbral de usuarios'),
                'disk_threshold_updated' => new external_value(PARAM_BOOL, 'Indica si se actualizó el umbral de disco'),
                'messages' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Mensaje informativo o de error')
                )
            )
        );
    }
}