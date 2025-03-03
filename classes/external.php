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

defined('MOODLE_INTERNAL') || die;

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
        
        // Analizar directorios
        $dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);
        
        // Obtener cursos más grandes
        $largest_courses = get_largest_courses(5);
        
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
                'max_90_days_date' => !empty($reportconfig->max_userdaily_for_90_days_date) ? 
                                    date('Y-m-d', $reportconfig->max_userdaily_for_90_days_date) : null
            ),
            'largest_courses' => array(),
            'timestamps' => array(
                'disk_calculation' => !empty($reportconfig->lastexecutioncalculate) ? 
                                   $reportconfig->lastexecutioncalculate : 0,
                'users_calculation' => !empty($reportconfig->lastexecution) ? 
                                    $reportconfig->lastexecution : 0
            )
        );
        
        // Formatear datos de cursos más grandes
        foreach ($largest_courses as $course) {
            $response['largest_courses'][] = array(
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'size_bytes' => $course->totalsize,
                'size_readable' => display_size($course->totalsize),
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
            $results[] = array(
                'id' => $record->id,
                'type' => $record->type,
                'percentage' => $record->percentage,
                'value' => $record->type === 'disk' ? display_size($record->value) : $record->value,
                'value_raw' => $record->value,
                'threshold' => $record->type === 'disk' ? display_size($record->threshold) : $record->threshold,
                'threshold_raw' => $record->threshold,
                'timecreated' => $record->timecreated,
                'timereadable' => userdate($record->timecreated)
            );
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
     * Devuelve la definición de parámetros para register_webhook.
     *
     * @return external_function_parameters
     */
    public static function register_webhook_parameters() {
        return new external_function_parameters(
            array(
                'url' => new external_value(PARAM_URL, get_string('webhook_url', 'report_usage_monitor')),
                'events' => new external_multiple_structure(
                    new external_value(PARAM_ALPHA, get_string('webhook_event_type', 'report_usage_monitor')),
                    get_string('webhook_events_list', 'report_usage_monitor'), 
                    VALUE_DEFAULT, 
                    array('disk_warning', 'user_warning')
                ),
                'secret' => new external_value(PARAM_TEXT, get_string('webhook_secret', 'report_usage_monitor'), VALUE_DEFAULT, '')
            )
        );
    }

    /**
     * Registra un webhook para recibir notificaciones de cambios.
     *
     * @param string $url URL del webhook
     * @param array $events Lista de eventos a los que suscribirse
     * @param string $secret Clave secreta para firmar las notificaciones
     * @return array Resultado del registro
     */
    public static function register_webhook($url, $events = array('disk_warning', 'user_warning'), $secret = '') {
        global $DB;
        
        // Verificar permisos
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);
        
        // Validar parámetros
        $params = self::validate_parameters(self::register_webhook_parameters(), 
                                           array('url' => $url, 'events' => $events, 'secret' => $secret));
        
        // Verificar si ya existe un webhook con esta URL
        $existingwebhook = $DB->get_record('report_usage_monitor_webhook', array('url' => $params['url']));
        
        if ($existingwebhook) {
            // Actualizar webhook existente
            $webhook = new stdClass();
            $webhook->id = $existingwebhook->id;
            $webhook->events = json_encode($params['events']);
            $webhook->secret = $params['secret'];
            $webhook->timemodified = time();
            
            $DB->update_record('report_usage_monitor_webhook', $webhook);
            $webhookid = $existingwebhook->id;
            $message = get_string('webhook_updated', 'report_usage_monitor');
        } else {
            // Crear nuevo webhook
            $webhook = new stdClass();
            $webhook->url = $params['url'];
            $webhook->events = json_encode($params['events']);
            $webhook->secret = $params['secret'];
            $webhook->timecreated = time();
            $webhook->timemodified = time();
            
            $webhookid = $DB->insert_record('report_usage_monitor_webhook', $webhook);
            $message = get_string('webhook_added', 'report_usage_monitor');
        }
        
        return array(
            'success' => true,
            'message' => $message,
            'webhook_id' => $webhookid
        );
    }

    /**
     * Devuelve la definición de resultado para register_webhook.
     *
     * @return external_description
     */
    public static function register_webhook_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, get_string('webhook_success', 'report_usage_monitor')),
                'message' => new external_value(PARAM_TEXT, get_string('webhook_message', 'report_usage_monitor')),
                'webhook_id' => new external_value(PARAM_INT, get_string('webhook_id', 'report_usage_monitor'))
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
        
        // Preparar respuesta
        $response = array(
            'disk_usage' => array(
                'current' => $disk_usage,
                'current_readable' => display_size($disk_usage),
                'threshold' => $quotadisk,
                'threshold_readable' => display_size($quotadisk),
                'percentage' => round($disk_percent, 2),
                'last_calculated' => !empty($reportconfig->lastexecutioncalculate) ? 
                                   $reportconfig->lastexecutioncalculate : 0
            ),
            'user_usage' => array(
                'current' => $users_today,
                'threshold' => $user_threshold,
                'percentage' => round($users_percent, 2),
                'last_calculated' => !empty($reportconfig->lastexecution) ? 
                                   $reportconfig->lastexecution : 0,
                'max_90_days' => !empty($reportconfig->max_userdaily_for_90_days_users) ? 
                                $reportconfig->max_userdaily_for_90_days_users : 0,
                'max_90_days_date' => !empty($reportconfig->max_userdaily_for_90_days_date) ? 
                                    $reportconfig->max_userdaily_for_90_days_date : 0
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
                        'current' => new external_value(PARAM_INT, get_string('usage_disk_current', 'report_usage_monitor')),
                        'current_readable' => new external_value(PARAM_TEXT, get_string('usage_disk_current_readable', 'report_usage_monitor')),
                        'threshold' => new external_value(PARAM_INT, get_string('usage_disk_threshold', 'report_usage_monitor')),
                        'threshold_readable' => new external_value(PARAM_TEXT, get_string('usage_disk_threshold_readable', 'report_usage_monitor')),
                        'percentage' => new external_value(PARAM_FLOAT, get_string('usage_disk_percentage', 'report_usage_monitor')),
                        'last_calculated' => new external_value(PARAM_INT, get_string('usage_disk_last_calculated', 'report_usage_monitor'))
                    )
                ),
                'user_usage' => new external_single_structure(
                    array(
                        'current' => new external_value(PARAM_INT, get_string('usage_user_current', 'report_usage_monitor')),
                        'threshold' => new external_value(PARAM_INT, get_string('usage_user_threshold', 'report_usage_monitor')),
                        'percentage' => new external_value(PARAM_FLOAT, get_string('usage_user_percentage', 'report_usage_monitor')),
                        'last_calculated' => new external_value(PARAM_INT, get_string('usage_user_last_calculated', 'report_usage_monitor')),
                        'max_90_days' => new external_value(PARAM_INT, get_string('usage_user_max_90_days', 'report_usage_monitor')),
                        'max_90_days_date' => new external_value(PARAM_INT, get_string('usage_user_max_90_days_date', 'report_usage_monitor'))
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
                    get_string('param_user_threshold', 'report_usage_monitor'), 
                    VALUE_DEFAULT, null),
                'disk_threshold' => new external_value(PARAM_INT, 
                    get_string('param_disk_threshold', 'report_usage_monitor'), 
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
        
        // Actualizar umbral de usuarios si se proporciona
        if ($params['user_threshold'] !== null) {
            if ($params['user_threshold'] > 0) {
                set_config('max_daily_users_threshold', $params['user_threshold'], 'report_usage_monitor');
                $result['user_threshold_updated'] = true;
                $result['messages'][] = get_string('user_threshold_updated', 'report_usage_monitor');
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
                'success' => new external_value(PARAM_BOOL, get_string('threshold_success', 'report_usage_monitor')),
                'user_threshold_updated' => new external_value(PARAM_BOOL, get_string('user_threshold_updated_status', 'report_usage_monitor')),
                'disk_threshold_updated' => new external_value(PARAM_BOOL, get_string('disk_threshold_updated_status', 'report_usage_monitor')),
                'messages' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, get_string('threshold_message', 'report_usage_monitor'))
                )
            )
        );
    }
}