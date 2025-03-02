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
        return new external_function_parameters(
            array()
        );
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
                        'name' => new external_value(PARAM_TEXT, 'Nombre del sitio'),
                        'shortname' => new external_value(PARAM_TEXT, 'Nombre corto del sitio'),
                        'moodle_version' => new external_value(PARAM_INT, 'Versión de Moodle'),
                        'moodle_release' => new external_value(PARAM_TEXT, 'Versión legible de Moodle'),
                        'course_count' => new external_value(PARAM_INT, 'Número total de cursos'),
                        'user_count' => new external_value(PARAM_INT, 'Número total de usuarios'),
                        'backup_auto_max_kept' => new external_value(PARAM_INT, 'Número de copias automáticas conservadas')
                    )
                ),
                'disk_usage' => new external_single_structure(
                    array(
                        'total_bytes' => new external_value(PARAM_INT, 'Uso total de disco en bytes'),
                        'total_readable' => new external_value(PARAM_TEXT, 'Uso total de disco legible'),
                        'quota_bytes' => new external_value(PARAM_INT, 'Cuota de disco en bytes'),
                        'quota_readable' => new external_value(PARAM_TEXT, 'Cuota de disco legible'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de uso de disco'),
                        'details' => new external_single_structure(
                            array(
                                'database' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Tamaño de la base de datos en bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Tamaño legible de la base de datos'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de la base de datos')
                                    )
                                ),
                                'filedir' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Tamaño del directorio de archivos en bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Tamaño legible del directorio de archivos'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje del directorio de archivos')
                                    )
                                ),
                                'backup' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Tamaño de copias de seguridad en bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Tamaño legible de copias de seguridad'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de copias de seguridad')
                                    )
                                ),
                                'cache' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Tamaño de caché en bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Tamaño legible de caché'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de caché')
                                    )
                                ),
                                'others' => new external_single_structure(
                                    array(
                                        'bytes' => new external_value(PARAM_INT, 'Tamaño de otros directorios en bytes'),
                                        'readable' => new external_value(PARAM_TEXT, 'Tamaño legible de otros directorios'),
                                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de otros directorios')
                                    )
                                )
                            )
                        )
                    )
                ),
                'user_usage' => new external_single_structure(
                    array(
                        'daily_users' => new external_value(PARAM_INT, 'Usuarios diarios actuales'),
                        'threshold' => new external_value(PARAM_INT, 'Umbral de usuarios'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de uso de usuarios'),
                        'max_90_days' => new external_value(PARAM_INT, 'Máximo de usuarios en 90 días'),
                        'max_90_days_date' => new external_value(PARAM_TEXT, 'Fecha del máximo de usuarios en 90 días', VALUE_OPTIONAL)
                    )
                ),
                'largest_courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'ID del curso'),
                            'fullname' => new external_value(PARAM_TEXT, 'Nombre completo del curso'),
                            'shortname' => new external_value(PARAM_TEXT, 'Nombre corto del curso'),
                            'size_bytes' => new external_value(PARAM_INT, 'Tamaño del curso en bytes'),
                            'size_readable' => new external_value(PARAM_TEXT, 'Tamaño legible del curso'),
                            'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje del espacio total'),
                            'backup_count' => new external_value(PARAM_INT, 'Número de copias de seguridad')
                        )
                    )
                ),
                'timestamps' => new external_single_structure(
                    array(
                        'disk_calculation' => new external_value(PARAM_INT, 'Timestamp del último cálculo de disco'),
                        'users_calculation' => new external_value(PARAM_INT, 'Timestamp del último cálculo de usuarios')
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
                'type' => new external_value(PARAM_ALPHA, 'Tipo de notificación (disk o users)', VALUE_DEFAULT, 'all'),
                'limit' => new external_value(PARAM_INT, 'Número máximo de registros a devolver', VALUE_DEFAULT, 30),
                'offset' => new external_value(PARAM_INT, 'Desplazamiento para paginación', VALUE_DEFAULT, 0)
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
                'total' => new external_value(PARAM_INT, 'Número total de registros disponibles'),
                'limit' => new external_value(PARAM_INT, 'Número máximo de registros solicitados'),
                'offset' => new external_value(PARAM_INT, 'Desplazamiento solicitado'),
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'ID del registro'),
                            'type' => new external_value(PARAM_ALPHA, 'Tipo de notificación (disk o users)'),
                            'percentage' => new external_value(PARAM_FLOAT, 'Porcentaje de uso'),
                            'value' => new external_value(PARAM_TEXT, 'Valor legible'),
                            'value_raw' => new external_value(PARAM_INT, 'Valor en bytes o cantidad de usuarios'),
                            'threshold' => new external_value(PARAM_TEXT, 'Umbral legible'),
                            'threshold_raw' => new external_value(PARAM_INT, 'Umbral en bytes o cantidad de usuarios'),
                            'timecreated' => new external_value(PARAM_INT, 'Timestamp de creación'),
                            'timereadable' => new external_value(PARAM_TEXT, 'Fecha y hora legible')
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
                'url' => new external_value(PARAM_URL, 'URL del webhook para enviar notificaciones'),
                'events' => new external_multiple_structure(
                    new external_value(PARAM_ALPHA, 'Tipo de evento (disk_warning, user_warning)'),
                    'Lista de eventos a los que suscribirse', 
                    VALUE_DEFAULT, 
                    array('disk_warning', 'user_warning')
                ),
                'secret' => new external_value(PARAM_TEXT, 'Clave secreta para firmar las notificaciones', VALUE_DEFAULT, '')
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
            $message = 'Webhook updated successfully';
        } else {
            // Crear nuevo webhook
            $webhook = new stdClass();
            $webhook->url = $params['url'];
            $webhook->events = json_encode($params['events']);
            $webhook->secret = $params['secret'];
            $webhook->timecreated = time();
            $webhook->timemodified = time();
            
            $webhookid = $DB->insert_record('report_usage_monitor_webhook', $webhook);
            $message = 'Webhook registered successfully';
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
                'success' => new external_value(PARAM_BOOL, 'Indica si la operación fue exitosa'),
                'message' => new external_value(PARAM_TEXT, 'Mensaje descriptivo del resultado'),
                'webhook_id' => new external_value(PARAM_INT, 'ID del webhook registrado o actualizado')
            )
        );
    }
}