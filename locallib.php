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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Local functions.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Obtener la lista de usuarios de los últimos 10 días.
 * Refactorizado para usar aritmética de timestamps para agrupación por día.
 *
 * @return string Consulta SQL para obtener la lista de usuarios.
 */
function report_user_daily_sql()
{
    // Calcular timestamps constantes para mejorar rendimiento
    $today_start = strtotime('today midnight');
    $ten_days_ago = strtotime('-10 days midnight');
    $yesterday_end = $today_start - 1;
    
    return "SELECT (timecreated - (timecreated % 86400)) as timestamp_fecha, 
                   COUNT(DISTINCT userid) as conteo_accesos_unicos
            FROM {logstore_standard_log}
            WHERE action = 'loggedin' 
              AND timecreated BETWEEN $ten_days_ago AND $yesterday_end
            GROUP BY timestamp_fecha 
            ORDER BY timestamp_fecha DESC";
}

/**
 * Obtener datos del top de usuarios máximos diarios.
 * Refactorizado para trabajar directamente con timestamps.
 *
 * @return string Consulta SQL para obtener los datos del top de usuarios.
 */
function report_user_daily_top_sql()
{
    return "SELECT fecha as timestamp_fecha, cantidad_usuarios 
            FROM {report_usage_monitor}  
            ORDER BY cantidad_usuarios DESC, fecha DESC";
}

/**
 * Obtener datos del top de usuarios máximos diarios para una tarea específica.
 * Refactorizado para trabajar con timestamps.
 *
 * @return string Consulta SQL para obtener los datos del top de usuarios.
 */
function report_user_daily_top_task()
{
    return "SELECT fecha, cantidad_usuarios 
            FROM {report_usage_monitor}  
            ORDER BY cantidad_usuarios DESC, fecha DESC";
}

/**
 * Actualizar el top de usuarios diarios si el número de usuarios actuales es mayor o igual al menor registro en el top.
 *
 * @param int $fecha Timestamp de la fecha a actualizar.
 * @param int $usuarios Cantidad de usuarios a actualizar en el top.
 * @param int $min Valor mínimo a comparar en el top.
 * @return void
 */
function update_min_top_sql($fecha, $usuarios, $min)
{
    global $DB;
    
    // Validar que el timestamp sea válido
    if (!is_numeric($fecha) || $fecha <= 0) {
        debugging('update_min_top_sql: Timestamp inválido proporcionado: ' . var_export($fecha, true), DEBUG_DEVELOPER);
        return;
    }
    
    // Iniciar transacción para evitar race conditions
    $transaction = $DB->start_delegated_transaction();
    
    try {
        // Obtener el registro con la menor cantidad de usuarios
        $sql = "SELECT fecha FROM {report_usage_monitor} 
                WHERE cantidad_usuarios = ? 
                ORDER BY fecha ASC LIMIT 1";
        $oldest_min_record = $DB->get_field_sql($sql, [$min]);
        
        if ($oldest_min_record) {
            $DB->execute(
                "UPDATE {report_usage_monitor} 
                 SET fecha = ?, cantidad_usuarios = ? 
                 WHERE fecha = ?",
                [$fecha, $usuarios, $oldest_min_record]
            );
        }
        
        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
        debugging('update_min_top_sql: Error al actualizar registro: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Insertar un registro si el top de usuarios diarios no tiene 10 registros.
 * Mantiene solo los 10 registros más recientes.
 *
 * @param int $fecha Timestamp de la fecha a insertar.
 * @param int $cantidad_usuarios Cantidad de usuarios a insertar en el top.
 * @return void
 */
function insert_top_sql($fecha, $cantidad_usuarios)
{
    global $DB;
    
    // Validar que el timestamp sea válido
    if (!is_numeric($fecha) || $fecha <= 0) {
        debugging('insert_top_sql: Timestamp inválido proporcionado: ' . var_export($fecha, true), DEBUG_DEVELOPER);
        return;
    }
    
    // Iniciar transacción para asegurar consistencia
    $transaction = $DB->start_delegated_transaction();
    
    try {
        // Insert the new record
        $DB->execute(
            "INSERT INTO {report_usage_monitor} (fecha, cantidad_usuarios) 
             VALUES (?, ?)",
            [$fecha, $cantidad_usuarios]
        );
        
        // Count records and remove excess
        $count = $DB->count_records('report_usage_monitor');
        if ($count > 10) {
            // Get the IDs of the oldest records based on fecha column
            $sql = "SELECT id FROM {report_usage_monitor} ORDER BY fecha ASC LIMIT " . ($count - 10);
            $records = $DB->get_records_sql($sql);
            
            if (!empty($records)) {
                $ids = array_keys($records);
                // Delete the oldest records
                $DB->delete_records_list('report_usage_monitor', 'id', $ids);
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Removed " . count($ids) . " old records to maintain 10 record limit.");
                }
            }
        }
        
        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
        debugging('insert_top_sql: Error al insertar registro: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Obtener la cantidad de usuarios conectados el día de ayer.
 * Optimizado para usar índices en logstore_standard_log (action, timecreated)
 * Refactorizado para usar aritmética de timestamps para agrupación por día.
 *
 * @return string Consulta SQL para obtener la cantidad de usuarios conectados.
 */
function user_limit_daily_sql()
{
    // Calcular timestamps
    $yesterday_start = strtotime('yesterday midnight');
    $today_start = strtotime('today midnight');
    
    return "SELECT COUNT(DISTINCT userid) as conteo_accesos_unicos, 
                   (timecreated - (timecreated % 86400)) as timestamp_fecha
            FROM {logstore_standard_log}
            WHERE action = 'loggedin' 
              AND timecreated BETWEEN $yesterday_start AND $today_start
            GROUP BY timestamp_fecha";
}

/**
 * Obtener el límite diario de usuarios para una tarea específica.
 * Optimizado para rendimiento usando aritmética de timestamps para agrupación por día.
 *
 * @return string Consulta SQL para obtener el límite diario de usuarios.
 */
function user_limit_daily_task()
{
    // Calcular timestamps
    $yesterday_start = strtotime('yesterday midnight');
    $today_start = strtotime('today midnight');
    
    return "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                   COUNT(DISTINCT userid) as conteo_accesos_unicos 
            FROM {logstore_standard_log}
            WHERE action = 'loggedin' 
              AND timecreated BETWEEN $yesterday_start AND $today_start
            GROUP BY fecha";
}

/**
 * Recuperar los usuarios conectados hoy.
 * Optimizado para usar índice en user.lastaccess y aritmética de timestamps para agrupación por día.
 *
 * @return string Consulta SQL para obtener los usuarios conectados hoy.
 */
function users_today()
{
    // Un día en segundos
    $one_day_ago = time() - 86400;
    
    return "SELECT (lastaccess - (lastaccess % 86400)) as timestamp_fecha, 
                   COUNT(DISTINCT id) as conteo_accesos_unicos 
            FROM {user}
            WHERE lastaccess >= $one_day_ago
            GROUP BY timestamp_fecha";
}

/**
 * Obtener el número máximo de accesos en los últimos 90 días.
 * Optimizado para usar índices y mejorar rendimiento con aritmética de timestamps para agrupación por día.
 *
 * @return string Consulta SQL para obtener el número máximo de accesos en los últimos 90 días.
 */
function max_userdaily_for_90_days()
{
    // 90 días en segundos: 90 * 86400
    $ninety_days_ago = time() - (90 * 86400);
    
    return "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                   COUNT(DISTINCT userid) as usuarios 
            FROM {logstore_standard_log}
            WHERE action = 'loggedin' 
              AND timecreated >= $ninety_days_ago
            GROUP BY fecha
            ORDER BY usuarios DESC 
            LIMIT 1";
}

/**
 * Calcular el tamaño de la base de datos.
 *
 * @return string Consulta SQL para obtener el tamaño de la base de datos.
 */
function size_database()
{
    global $CFG;
    return "SELECT TABLE_SCHEMA AS `database_name`, 
                   ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)) AS size
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = '$CFG->dbname'";
}

/**
 * Generate a user info object based on provided parameters.
 *
 * This function creates a standardized user object that can be used for email operations within Moodle.
 * It sanitizes and sets default values for user details.
 *
 * @param string $email Plain text email address.
 * @param string $name Optional plain text real name.
 * @param int $id Optional user ID, default is -99 which typically signifies a non-persistent user.
 *
 * @return object Returns a user object with email, name, and other related properties.
 */
function generate_email_user($email, $name = '', $id = -99)
{
    $emailuser = new stdClass();
    $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailuser->email = '';
    }
    $name = format_text($name, FORMAT_HTML, array('trusted' => false, 'noclean' => false));
    $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
    $emailuser->lastname = '';
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML emails.
    $emailuser->id = $id;
    $emailuser->firstnamephonetic = '';
    $emailuser->lastnamephonetic = '';
    $emailuser->middlename = '';
    $emailuser->alternatename = '';
    return $emailuser;
}

/**
 * Adds up all the files in a directory and works out the size.
 * Optimizado para usar 'du' cuando esté disponible.
 *
 * @param string $rootdir  The directory to start from
 * @param string $excludefile A file to exclude when summing directory size
 * @return int The summed size of all files and subfiles within the root directory
 */
function directory_size($rootdir, $excludefile = '')
{
    global $CFG;

    // Verificamos si el sistema operativo es Linux y si el comando 'du' está disponible.
    if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
        $escapedRootdir = escapeshellarg($rootdir);
        $command = trim($CFG->pathtodu) . ' -Lsk ' . $escapedRootdir;

        if (PHP_OS === 'Linux') {
            // Usamos 'nice' y 'ionice' en sistemas Linux para reducir la prioridad del comando.
            $command = 'nice -n 19 ionice -c3 ' . $command;
        }

        if (!empty($excludefile)) {
            // Añadimos la opción de excluir un archivo específico.
            $escapedExcludefile = escapeshellarg($excludefile);
            $command .= ' --exclude=' . $escapedExcludefile;
        }

        // Ejecutamos el comando y procesamos la salida.
        $output = null;
        $return = null;
        exec($command, $output, $return);
        if (is_array($output) && isset($output[0])) {
            // Convertimos el tamaño devuelto por 'du' de kilobytes a bytes.
            return intval($output[0]) * 1024;
        }
    }

    // Si no podemos usar 'du', calculamos el tamaño recursivamente.
    if (!is_dir($rootdir)) {
        // Si no es un directorio, retornamos 0.
        return 0;
    }

    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootdir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && ($excludefile === '' || $file->getFilename() !== $excludefile)) {
            // Sumamos el tamaño del archivo si no está excluido.
            $size += $file->getSize();
        }
    }

    return $size;
}

/**
 * Get SQL for calculating course file sizes including all relevant contexts.
 * Enhanced version based on report_coursesize approach.
 *
 * @return string SQL query that returns course ID and total file size
 */
function enhanced_course_filesize_sql() {
    // Include files from course context, block context, and module context
    $sqlunion = "UNION ALL
                SELECT c.id, f.filesize
                FROM {block_instances} bi
                JOIN {context} cx1 ON cx1.contextlevel = " . CONTEXT_BLOCK . " AND cx1.instanceid = bi.id
                JOIN {context} cx2 ON cx2.contextlevel = " . CONTEXT_COURSE . " AND cx2.id = bi.parentcontextid
                JOIN {course} c ON c.id = cx2.instanceid
                JOIN {files} f ON f.contextid = cx1.id
            UNION ALL
                SELECT c.id, f.filesize
                FROM {course_modules} cm
                JOIN {context} cx ON cx.contextlevel = " . CONTEXT_MODULE . " AND cx.instanceid = cm.id
                JOIN {course} c ON c.id = cm.course
                JOIN {files} f ON f.contextid = cx.id";

    return "SELECT id AS course, SUM(filesize) AS filesize
            FROM (SELECT c.id, f.filesize
                  FROM {course} c
                  JOIN {context} cx ON cx.contextlevel = " . CONTEXT_COURSE . " AND cx.instanceid = c.id
                  JOIN {files} f ON f.contextid = cx.id {$sqlunion}) x
            GROUP BY id";
}

/**
 * Get SQL for calculating course backup sizes.
 *
 * @return string SQL query that returns course ID and backup size
 */
function enhanced_course_backupsize_sql() {
    return "SELECT id AS course, SUM(filesize) AS filesize
            FROM (SELECT c.id, f.filesize
                  FROM {course} c
                  JOIN {context} cx ON cx.contextlevel = " . CONTEXT_COURSE . " AND cx.instanceid = c.id
                  JOIN {files} f ON f.contextid = cx.id AND f.component = 'backup') x
            GROUP BY id";
}

/**
 * Analiza el uso de disco por directorios específicos
 * Versión mejorada para análisis más detallado.
 *
 * @param string $rootdir Directorio raíz a analizar
 * @return array Arreglo con los tamaños de cada directorio específico
 */
function analyze_disk_usage_by_directory($rootdir) {
    global $CFG;
    
    // Definimos los directorios a analizar
    $directories = [
        'filedir' => $rootdir . '/filedir',
        'cache' => $rootdir . '/cache',
    ];
    
    $usage = [];
    $total_analyzed = 0;
    
    // Calculamos el tamaño de cada directorio
    foreach ($directories as $key => $dir) {
        if (is_dir($dir)) {
            $size = directory_size($dir);
            $usage[$key] = $size;
            $total_analyzed += $size;
        } else {
            $usage[$key] = 0;
        }
    }
    
    // Calculamos el tamaño total del directorio raíz
    $total_size = directory_size($rootdir);
    
    // Calculamos "others" como la diferencia
    $usage['others'] = max(0, $total_size - $total_analyzed);
    
    // Añadimos el tamaño de la base de datos
    $total_db_size = 0;
    $size = size_database();
    global $DB;
    $size_database = $DB->get_records_sql($size);
    foreach ($size_database as $item) {
        $total_db_size = $item->size;
    }
    $usage['database'] = $total_db_size;
    
    return $usage;
}

/**
 * Recupera los cursos que más espacio ocupan.
 * Versión mejorada para incluir todos los archivos relevantes.
 *
 * @param int $limit Número de cursos a recuperar
 * @return array Arreglo con información de los cursos
 */
function get_largest_courses($limit = 5) {
    global $DB;

    // Get file sizes from all relevant contexts
    $filesql = enhanced_course_filesize_sql();
    $sql = "SELECT c.id, c.fullname, c.shortname, c.category, rc.filesize
            FROM {course} c
            JOIN ($filesql) rc on rc.course = c.id
            WHERE c.id != :siteid
            ORDER BY rc.filesize DESC";
    
    $params = ['siteid' => SITEID];
    $courses = $DB->get_records_sql($sql, $params, 0, $limit);

    // Get backup sizes
    $backupsql = enhanced_course_backupsize_sql();
    $backupsizes = $DB->get_records_sql($backupsql);

    // Calculate additional data for each course
    $totalfilessize = $DB->get_field_sql("SELECT SUM(filesize) FROM {files} WHERE filesize > 0");
    
    foreach ($courses as $course) {
        // Add backup size
        $course->backupsize = isset($backupsizes[$course->id]) ? $backupsizes[$course->id]->filesize : 0;
        
        // Count backups
        $course->backupcount = $DB->count_records_sql("
            SELECT COUNT(f.id)
            FROM {files} f
            JOIN {context} ctx ON f.contextid = ctx.id
            WHERE ctx.instanceid = :courseid
              AND ctx.contextlevel = " . CONTEXT_COURSE . "
              AND f.component = 'backup'
              AND f.filearea = 'automated'
        ", ['courseid' => $course->id]);
        
        // Calculate percentage of total site files
        $course->percentage = $totalfilessize > 0
            ? round(($course->filesize / $totalfilessize) * 100, 2)
            : 0;
            
        // Calculate total size (including backups)
        $course->totalsize = $course->filesize + $course->backupsize;
    }

    return $courses;
}

/**
 * Convierte el tamaño de bytes a gigabytes.
 *
 * @param mixed $sizeInBytes El tamaño en bytes que se quiere convertir.
 * @param int $precision El número de decimales a mostrar.
 * @return string El tamaño en gigabytes, formateado como cadena.
 */
function display_size_in_gb($sizeInBytes, $precision = 2)
{
    // Verifica si el valor es numérico y no es null.
    if (!is_numeric($sizeInBytes) || $sizeInBytes === null) {
        debugging("display_size_in_gb: se esperaba un valor numérico, recibido: " . var_export($sizeInBytes, true), DEBUG_DEVELOPER);
        return '0'; // Retorna '0 GB' como un valor seguro por defecto.
    }

    // Conversión de bytes a GB.
    $sizeInGb = $sizeInBytes / (1024 * 1024 * 1024);
    return round($sizeInGb, $precision);
}

/**
 * Calcula el porcentaje de uso en relación con un umbral.
 *
 * @param int $current_value El valor actual (número de usuarios, uso del disco, etc.).
 * @param int $threshold El umbral máximo permitido.
 * @return float El porcentaje de uso.
 */
function calculate_threshold_percentage($current_value, $threshold)
{
    // Validación de parámetros
    if (!is_numeric($current_value)) {
        debugging('calculate_threshold_percentage: Valor actual no numérico: ' . var_export($current_value, true), DEBUG_DEVELOPER);
        $current_value = 0;
    }
    
    if (!is_numeric($threshold) || $threshold <= 0) {
        debugging('calculate_threshold_percentage: Umbral inválido: ' . var_export($threshold, true), DEBUG_DEVELOPER);
        return 0;
    }
    
    return ($current_value / $threshold) * 100;
}

/**
 * Calcula la tasa de crecimiento de usuarios o espacio en disco
 *
 * @param string $type Tipo de dato a analizar ('users' o 'disk')
 * @param int $days Número de días a considerar para el cálculo
 * @return float Tasa de crecimiento porcentual
 */
function calculate_growth_rate($type = 'users', $days = 30) {
    global $DB;
    
    // Validación de parámetros
    if (!in_array($type, ['users', 'disk'])) {
        debugging('calculate_growth_rate: Tipo inválido: ' . var_export($type, true), DEBUG_DEVELOPER);
        return 0;
    }
    
    if (!is_numeric($days) || $days <= 0) {
        debugging('calculate_growth_rate: Días inválidos: ' . var_export($days, true), DEBUG_DEVELOPER);
        $days = 30; // Valor por defecto
    }
    
    if ($type === 'users') {
        // Consulta optimizada para rendimiento
        $sql = "SELECT 
                  (SELECT COUNT(DISTINCT userid) 
                   FROM {logstore_standard_log} 
                   WHERE action = 'loggedin' 
                     AND timecreated BETWEEN :start1 AND :end1) as first_day_users,
                  (SELECT COUNT(DISTINCT userid) 
                   FROM {logstore_standard_log} 
                   WHERE action = 'loggedin' 
                     AND timecreated BETWEEN :start2 AND :end2) as last_day_users";
        
        $now = time();
        $day_seconds = 86400; // 24 * 60 * 60
        
        $params = [
            'start1' => $now - ($days * $day_seconds),
            'end1' => $now - (($days - 1) * $day_seconds),
            'start2' => $now - $day_seconds,
            'end2' => $now
        ];
        
        $result = $DB->get_record_sql($sql, $params);
        $first_day_users = $result ? $result->first_day_users : 0;
        $last_day_users = $result ? $result->last_day_users : 0;
        
        // Evitamos división por cero
        if ($first_day_users == 0) {
            return 0;
        }
        
        // Calculamos la tasa de crecimiento
        $growth_rate = (($last_day_users - $first_day_users) / $first_day_users) * 100;
        
    } elseif ($type === 'disk') {
        // Para el disco, calculamos el promedio de crecimiento diario usando historial
        $sql = "SELECT MIN(timecreated) AS oldest_time, MAX(timecreated) AS newest_time, 
                       MIN(value) AS oldest_size, MAX(value) AS newest_size
                FROM {report_usage_monitor_history}
                WHERE type = 'disk' 
                AND timecreated > :time_threshold";
                
        $time_threshold = time() - ($days * 86400);
        $result = $DB->get_record_sql($sql, ['time_threshold' => $time_threshold]);
        
        if ($result && $result->oldest_time && $result->oldest_size > 0) {
            $time_diff = $result->newest_time - $result->oldest_time;
            $size_diff = $result->newest_size - $result->oldest_size;
            
            // Si tenemos suficiente historia y hay diferencia de tamaño
            if ($time_diff > 0 && $size_diff != 0) {
                $days_diff = $time_diff / 86400;
                $daily_change = $size_diff / $days_diff;
                
                // Calculamos la tasa porcentual de crecimiento diario
                $daily_percent = ($daily_change / $result->oldest_size) * 100;
                
                // Proyectamos a 30 días
                $growth_rate = $daily_percent * 30;
                return round($growth_rate, 2);
            }
        }
        
        return 5; // Valor por defecto (5% mensual)
    }
    
    return round($growth_rate, 2);
}

/**
 * Proyecta la fecha en que se alcanzaría un límite basado en la tasa de crecimiento
 *
 * @param int $current_value Valor actual
 * @param int $threshold_value Valor umbral a alcanzar
 * @param float $growth_rate Tasa de crecimiento porcentual
 * @return int Número estimado de días para alcanzar el umbral o código especial
 */
function project_limit_date($current_value, $threshold_value, $growth_rate) {
    // Validación detallada de parámetros
    if (!is_numeric($current_value)) {
        debugging('project_limit_date: Valor actual no numérico: ' . var_export($current_value, true), DEBUG_DEVELOPER);
        return null;
    }
    
    if (!is_numeric($threshold_value)) {
        debugging('project_limit_date: Umbral no numérico: ' . var_export($threshold_value, true), DEBUG_DEVELOPER);
        return null;
    }
    
    if (!is_numeric($growth_rate)) {
        debugging('project_limit_date: Tasa de crecimiento no numérica: ' . var_export($growth_rate, true), DEBUG_DEVELOPER);
        return null;
    }
    
    // Códigos especiales con semántica clara
    if ($current_value >= $threshold_value) {
        return -1; // Código de "ya superado"
    }
    
    if ($growth_rate <= 0) {
        return PHP_INT_MAX; // "Nunca se alcanzará" con tasa actual
    }
    
    // Convertimos la tasa de porcentaje a decimal diario
    $daily_growth_rate = ($growth_rate / 100) / 30; // Asumiendo que la tasa es mensual
    
    // Protección contra valores extremos
    if ($daily_growth_rate < 0.000001) {
        return PHP_INT_MAX; // Prácticamente nunca se alcanzará
    }
    
    // Cálculo logarítmico mejorado con protección contra errores
    try {
        // Calculamos cuántos días tomaría alcanzar el umbral
        // Fórmula: log(threshold/current) / log(1 + daily_growth_rate)
        $ratio = $threshold_value / $current_value;
        $log_ratio = log($ratio);
        $log_growth = log(1 + $daily_growth_rate);
        
        if ($log_growth == 0) {
            return PHP_INT_MAX; // Evitar división por cero
        }
        
        $days = $log_ratio / $log_growth;
        
        // Validación del resultado
        if (!is_finite($days)) {
            return PHP_INT_MAX; // Protección contra NaN o infinitos
        }
        
        return max(1, ceil($days));
    } catch (Exception $e) {
        debugging('project_limit_date: Error en cálculo: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return PHP_INT_MAX;
    }
}

/**
 * Genera filas HTML para los datos históricos de usuarios
 *
 * @param int $limit Número de registros a incluir
 * @param int $max_threshold Umbral máximo de usuarios
 * @return string HTML generado para las filas de la tabla
 */
function generate_historical_data_html($limit = 10, $max_threshold = 100) {
    global $DB;
    
    $html = '';
    
    // Calcular timestamps para el rango de fechas
    $limit_days_ago = time() - ($limit * 86400);
    
    // Consulta optimizada usando aritmética de timestamps para agrupación por día
    $sql = "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                   COUNT(DISTINCT userid) as usuarios
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
              AND timecreated > :limit_days_ago
            GROUP BY (timecreated - (timecreated % 86400))
            ORDER BY fecha DESC
            LIMIT :limit";
    
    $records = $DB->get_records_sql($sql, ['limit_days_ago' => $limit_days_ago, 'limit' => $limit]);
    
    foreach ($records as $record) {
        // Validar que la fecha sea un timestamp válido
        if (!is_numeric($record->fecha) || $record->fecha <= 0) {
            continue; // Saltar registros con fechas inválidas
        }
        
        $percent = round(($record->usuarios / $max_threshold) * 100, 1);
        $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
        $formatted_date = is_numeric($record->fecha) && $record->fecha > 0 ? 
                         date('d/m/Y', (int)$record->fecha) : 
                         date('d/m/Y'); // Fecha actual como fallback
        
        $html .= '<tr>';
        $html .= '<td>' . $formatted_date . '</td>';
        $html .= '<td>' . $record->usuarios . '</td>';
        $html .= '<td class="' . $class . '">' . $percent . '%</td>';
        $html .= '</tr>';
    }
    
    return $html;
}

/**
 * Genera filas HTML para la tabla de cursos más grandes
 *
 * @param array $courses Arreglo con los datos de los cursos
 * @return string HTML generado para las filas de la tabla
 */
function generate_top_courses_html($courses) {
    $html = '';
    
    foreach ($courses as $course) {
        $html .= '<tr>';
        $html .= '<td>' . format_string($course->fullname) . ' (' . $course->shortname . ')</td>';
        $html .= '<td>' . display_size($course->totalsize) . '</td>';
        $html .= '<td>' . $course->percentage . '%</td>';
        $html .= '</tr>';
    }
    
    return $html;
}

/**
 * Envía una notificación por correo cuando se supera el límite de usuarios diarios.
 *
 * Versión mejorada que incluye más información y un diseño visual mejorado.
 *
 * @param int $numberofusers Número de usuarios únicos que accedieron al sistema.
 * @param string $fecha Fecha para la que se superó el umbral.
 * @param float $percentage Porcentaje de uso en relación con el umbral.
 * @return bool Devuelve true si el correo se envió correctamente, false en caso contrario.
 */
function email_notify_user_limit($numberofusers, $fecha, $percentage)
{
    global $CFG, $DB;

    // Validar el timestamp
    if (!is_numeric($fecha)) {
        debugging('email_notify_user_limit: Timestamp inválido proporcionado: ' . var_export($fecha, true), DEBUG_DEVELOPER);
        $fecha = time(); // Usar tiempo actual como fallback
    }

    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    // Información básica
    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->threshold = $reportconfig->max_daily_users_threshold;
    $a->numberofusers = $numberofusers;
    $a->lastday = is_numeric($fecha) && $fecha > 0 ? 
                 date('d/m/Y', (int)$fecha) : 
                 date('d/m/Y'); // Fecha actual como fallback
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
    $a->siteurl = $CFG->wwwroot;
    $a->percentaje = round($percentage, 2);
    $a->excess_users = max(0, $numberofusers - $a->threshold);

    // Información del sistema
    $a->moodle_version = $CFG->version;
    $a->moodle_release = $CFG->release;
    $a->courses_count = $DB->count_records('course');
    $a->backup_auto_max_kept = get_config('backup', 'backup_auto_max_kept');

    // Información de disco
    $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
    $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
    $a->diskusage = display_size($disk_usage);
    $a->quotadisk = display_size($quotadisk);
    $a->disk_percent = round(calculate_threshold_percentage($disk_usage, $quotadisk), 2);

    // Proyecciones y análisis
    $growth_rate = calculate_growth_rate('users');
    $a->days_to_critical = project_limit_date($numberofusers, $a->threshold * 1.2, $growth_rate);
    $a->critical_threshold = 120;

    // Datos históricos
    $a->historical_data_rows = generate_historical_data_html(7, $a->threshold);

    // Generar direcciones de correo
    $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));

    // Preparar el correo
    $subject = get_string('subjectemail1', 'report_usage_monitor') . " {$a->sitename}";
    $messagehtml = get_string('messagehtml_userlimit', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    // Enviar el correo
    $previous_noemailever = $CFG->noemailever ?? false;
    $CFG->noemailever = false;
    $result = email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    $CFG->noemailever = $previous_noemailever;

    return $result;
}

/**
 * Envía una notificación por correo sobre el uso del espacio en disco.
 *
 * Versión mejorada que incluye análisis detallado del espacio por directorios,
 * información sobre los cursos más grandes y recomendaciones.
 *
 * @param int $quotadisk Cuota total de disco asignada, en bytes.
 * @param int $disk_usage Uso actual del disco, en bytes.
 * @param float $disk_percent Porcentaje de uso respecto a la cuota.
 * @param int $userAccessCount Número de usuarios activos.
 * @return bool Devuelve true si el correo se envió correctamente, false en caso contrario.
 */
function email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount)
{
    global $CFG, $DB;

    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    // Información básica
    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->quotadisk = display_size($quotadisk);
    $a->diskusage = display_size($disk_usage);
    $a->percentage = round($disk_percent, 2);
    $a->databasesize = display_size($reportconfig->totalusagereadabledb);
    $a->available_space = display_size($quotadisk - $disk_usage);
    $a->available_percent = round(100 - $disk_percent, 2);
    
    // Clase de nivel de advertencia
    $a->warning_level_class = $disk_percent < 70 ? 'warning-level-low' : 
                            ($disk_percent < 90 ? 'warning-level-medium' : 'warning-level-high');

    // Información del sistema
    $a->backupcount = get_config('backup', 'backup_auto_max_kept');
    $a->threshold = $reportconfig->max_daily_users_threshold;
    $a->numberofusers = $userAccessCount;
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
    $a->siteurl = $CFG->wwwroot;
    $a->lastday = date('d/m/Y', time());
    $a->coursescount = $DB->count_records('course');
    $a->user_percent = round(calculate_threshold_percentage($userAccessCount, $a->threshold), 2);
    $a->moodle_version = $CFG->version;
    $a->moodle_release = $CFG->release;

    // Análisis por directorios
    $dir_analysis_json = $reportconfig->dir_analysis ?? '{}';
    $dir_analysis = json_decode($dir_analysis_json, true);
    if (empty($dir_analysis) || !is_array($dir_analysis)) {
        $dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);
    }
    
    // Formateamos los tamaños y calculamos porcentajes
    $a->db_percent = round(($dir_analysis['database'] / $disk_usage) * 100, 2);
    $a->filedir_size = display_size($dir_analysis['filedir']);
    $a->filedir_percent = round(($dir_analysis['filedir'] / $disk_usage) * 100, 2);
    $a->cache_size = display_size($dir_analysis['cache']);
    $a->cache_percent = round(($dir_analysis['cache'] / $disk_usage) * 100, 2);
    $a->other_size = display_size($dir_analysis['others']);
    $a->other_percent = round(($dir_analysis['others'] / $disk_usage) * 100, 2);

    // Cursos más grandes
    $largest_courses_json = $reportconfig->largest_courses ?? '[]';
    $largest_courses = json_decode($largest_courses_json);
    if (empty($largest_courses)) {
        $largest_courses = get_largest_courses(5);
    }
    $a->top_courses_rows = generate_top_courses_html($largest_courses);

    // Generar direcciones de correo
    $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));

    // Preparar el correo
    $subject = get_string('subjectemail2', 'report_usage_monitor') . " {$a->sitename}";
    $messagehtml = get_string('messagehtml_diskusage', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    // Enviar el correo
    $previous_noemailever = $CFG->noemailever ?? false;
    $CFG->noemailever = false;
    $result = email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    $CFG->noemailever = $previous_noemailever;

    return $result;
}