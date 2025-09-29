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

use report_usage_monitor\local\dashboard_data;

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
        global $CFG, $DB, $SITE;

        self::validate_parameters(self::get_monitor_stats_parameters(), []);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        $disk = dashboard_data::get_disk_summary();
        $users = dashboard_data::get_user_summary();
        $courses = dashboard_data::get_largest_courses();
        $siteoverview = dashboard_data::get_site_overview();

        $diskusage = $disk['total_bytes'];
        $diskquota = $disk['quota_bytes'];
        $diskdetails = self::format_disk_details_for_api($disk['details']);

        $diskgrowth = calculate_growth_rate('disk');
        $usergrowth = calculate_growth_rate('users');

        $diskthresholdtarget = ($diskquota > 0) ? (int)round($diskquota * 0.9) : 0;
        $userthresholdtarget = ($users['threshold'] > 0) ? (int)round($users['threshold'] * 0.9) : 0;

        $diskprojection = ($diskthresholdtarget > 0 && $diskusage > 0 && $diskgrowth > 0)
            ? project_limit_date($diskusage, $diskthresholdtarget, $diskgrowth)
            : PHP_INT_MAX;
        $userprojection = ($userthresholdtarget > 0 && $users['current'] > 0 && $usergrowth > 0)
            ? project_limit_date($users['current'], $userthresholdtarget, $usergrowth)
            : PHP_INT_MAX;

        $largestcourses = array_map(static function($course) {
            return array(
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'size_bytes' => $course->filesize,
                'size_readable' => display_size($course->filesize),
                'backup_size_bytes' => $course->backupsize,
                'backup_size_readable' => display_size($course->backupsize),
                'percentage' => $course->percentage,
                'backup_count' => $course->backupcount,
            );
        }, $courses);

        return array(
            'site_info' => array(
                'name' => format_string($SITE->fullname),
                'shortname' => format_string($SITE->shortname),
                'moodle_version' => (int)$CFG->version,
                'moodle_release' => $CFG->release,
                'course_count' => $siteoverview['total_courses'],
                'user_count' => $siteoverview['registered_users'],
                'backup_auto_max_kept' => $siteoverview['backup_auto_max_kept'],
            ),
            'disk_usage' => array(
                'total_bytes' => $diskusage,
                'total_readable' => display_size($diskusage),
                'quota_bytes' => $diskquota,
                'quota_readable' => display_size($diskquota),
                'percentage' => round($disk['percentage'], 2),
                'details' => $diskdetails,
            ),
            'user_usage' => array(
                'daily_users' => $users['current'],
                'threshold' => $users['threshold'],
                'percentage' => round($users['percentage'], 2),
                'max_90_days' => $users['max_90_users'],
                'max_90_days_date' => !empty($users['max_90_date'])
                    ? userdate($users['max_90_date'], '%Y-%m-%d')
                    : null,
            ),
            'largest_courses' => $largestcourses,
            'timestamps' => array(
                'disk_calculation' => self::safe_timestamp($disk['last_calculated'] ?? null),
                'users_calculation' => self::safe_timestamp($users['last_calculated'] ?? null),
            ),
            'growth_rates' => array(
                'disk' => array(
                    'monthly_percent' => $diskgrowth,
                    'projected_days_to_threshold' => $diskprojection,
                ),
                'users' => array(
                    'monthly_percent' => $usergrowth,
                    'projected_days_to_threshold' => $userprojection,
                ),
            ),
        );
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
        self::validate_parameters(self::get_usage_data_parameters(), []);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        $disk = dashboard_data::get_disk_summary();
        $users = dashboard_data::get_user_summary();

        $diskgrowth = calculate_growth_rate('disk');
        $usergrowth = calculate_growth_rate('users');

        $diskthresholdtarget = ($disk['quota_bytes'] > 0) ? (int)round($disk['quota_bytes'] * 0.9) : 0;
        $userthresholdtarget = ($users['threshold'] > 0) ? (int)round($users['threshold'] * 0.9) : 0;

        $diskprojection = ($diskthresholdtarget > 0 && $disk['total_bytes'] > 0 && $diskgrowth > 0)
            ? project_limit_date($disk['total_bytes'], $diskthresholdtarget, $diskgrowth)
            : PHP_INT_MAX;
        $userprojection = ($userthresholdtarget > 0 && $users['current'] > 0 && $usergrowth > 0)
            ? project_limit_date($users['current'], $userthresholdtarget, $usergrowth)
            : PHP_INT_MAX;

        return array(
            'disk_usage' => array(
                'current' => $disk['total_bytes'],
                'current_readable' => display_size($disk['total_bytes']),
                'threshold' => $disk['quota_bytes'],
                'threshold_readable' => display_size($disk['quota_bytes']),
                'percentage' => round($disk['percentage'], 2),
                'last_calculated' => self::safe_timestamp($disk['last_calculated'] ?? null)
            ),
            'user_usage' => array(
                'current' => $users['current'],
                'threshold' => $users['threshold'],
                'percentage' => round($users['percentage'], 2),
                'last_calculated' => self::safe_timestamp($users['last_calculated'] ?? null),
                'max_90_days' => $users['max_90_users'],
                'max_90_days_date' => self::safe_timestamp($users['max_90_date'] ?? null)
            ),
            'projections' => array(
                'disk_growth_rate' => $diskgrowth,
                'users_growth_rate' => $usergrowth,
                'days_to_disk_threshold' => $diskprojection,
                'days_to_users_threshold' => $userprojection,
            )
        );
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
                    $usersummary = dashboard_data::get_user_summary();
                    $users_today = $usersummary['current'];
                    $users_percent = ($params['user_threshold'] > 0)
                        ? min(100, ($users_today / $params['user_threshold']) * 100)
                        : 0;
                    $users_warning_class = ($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger');

                    set_config('users_percent', round($users_percent, 2), 'report_usage_monitor');
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
                    $disksummary = dashboard_data::get_disk_summary();
                    $disk_usage = $disksummary['total_bytes'];
                    $quotadisk_bytes = ((int) $params['disk_threshold'] * 1024) * 1024 * 1024;
                    $disk_percent = ($quotadisk_bytes > 0)
                        ? min(100, ($disk_usage / $quotadisk_bytes) * 100)
                        : 0;
                    $disk_warning_class = ($disk_percent < 70) ? 'bg-success' : (($disk_percent < 90) ? 'bg-warning' : 'bg-danger');

                    set_config('disk_percent', round($disk_percent, 2), 'report_usage_monitor');
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

    /**
     * Formats disk usage details into the structure expected by the external API.
     *
     * @param array $details
     * @return array
     */
    private static function format_disk_details_for_api(array $details): array {
        $database = $details['database'] ?? ['bytes' => 0, 'percentage' => 0];
        $filedir = $details['filedir'] ?? ['bytes' => 0, 'percentage' => 0];
        $cache = $details['cache'] ?? ['bytes' => 0, 'percentage' => 0];
        $others = $details['others'] ?? ['bytes' => 0, 'percentage' => 0];

        return array(
            'database' => array(
                'bytes' => (int)($database['bytes'] ?? 0),
                'readable' => display_size($database['bytes'] ?? 0),
                'percentage' => round((float)($database['percentage'] ?? 0), 2),
            ),
            'filedir' => array(
                'bytes' => (int)($filedir['bytes'] ?? 0),
                'readable' => display_size($filedir['bytes'] ?? 0),
                'percentage' => round((float)($filedir['percentage'] ?? 0), 2),
            ),
            'cache' => array(
                'bytes' => (int)($cache['bytes'] ?? 0),
                'readable' => display_size($cache['bytes'] ?? 0),
                'percentage' => round((float)($cache['percentage'] ?? 0), 2),
            ),
            'backup' => array(
                'bytes' => 0,
                'readable' => display_size(0),
                'percentage' => 0.0,
            ),
            'others' => array(
                'bytes' => (int)($others['bytes'] ?? 0),
                'readable' => display_size($others['bytes'] ?? 0),
                'percentage' => round((float)($others['percentage'] ?? 0), 2),
            ),
        );
    }

    /**
     * Normalises timestamps so the API always returns an integer value.
     *
     * @param mixed $value
     * @return int
     */
    private static function safe_timestamp($value): int {
        return (is_numeric($value) && (int)$value > 0) ? (int)$value : 0;
    }
}