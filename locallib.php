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
 * Local functions.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Obtener la lista de usuarios de los últimos 10 días.
 *
 * @param string $format Formato de fecha para la consulta SQL.
 * @return string Consulta SQL para obtener la lista de usuarios.
 */
function report_user_daily_sql($format)
{
    return "SELECT FROM_UNIXTIME(`timecreated`, '$format') as fecha, count(DISTINCT`userid`) as conteo_accesos_unicos
    FROM {logstore_standard_log}
    WHERE `action`='loggedin' 
    AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') BETWEEN DATE_SUB(CURDATE(), INTERVAL 10 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    GROUP by fecha 
    ORDER BY fecha DESC";
}


/**
 * Obtener datos del top de usuarios máximos diarios.
 *
 * @param string $format Formato de fecha para la consulta SQL.
 * @return string Consulta SQL para obtener los datos del top de usuarios.
 */
function report_user_daily_top_sql($format)
{
    return "SELECT FROM_UNIXTIME(`fecha`, '$format') as fecha, cantidad_usuarios from {report_usage_monitor}  ORDER BY cantidad_usuarios DESC";
}

/**
 * Obtener datos del top de usuarios máximos diarios para una tarea específica.
 *
 * @return string Consulta SQL para obtener los datos del top de usuarios.
 */
function report_user_daily_top_task()
{
    return "SELECT fecha, cantidad_usuarios from {report_usage_monitor}  ORDER BY cantidad_usuarios DESC";
}

/**
 * Actualizar el top de usuarios diarios si el número de usuarios actuales es mayor o igual al menor registro en el top.
 *
 * @param string $fecha Fecha a actualizar en el top.
 * @param int $usuarios Cantidad de usuarios a actualizar en el top.
 * @param int $min Valor mínimo a comparar en el top.
 * @return void
 */
function update_min_top_sql($fecha, $usuarios, $min)
{
    global $DB;
    $SQL = "UPDATE {report_usage_monitor} set fecha=?,cantidad_usuarios=? where fecha=?";
    $params = array($fecha, $usuarios, $min);
    $DB->execute($SQL, $params);
}

/**
 * Insertar un registro si el top de usuarios diarios no tiene 10 registros.
 *
 * @param string $fecha Fecha a insertar en el top.
 * @param int $cantidad_usuarios Cantidad de usuarios a insertar en el top.
 * @return void
 */
function insert_top_sql($fecha, $cantidad_usuarios)
{
    global $DB;
    $SQL = "INSERT INTO {report_usage_monitor} (fecha,cantidad_usuarios) VALUES (?,?)";
    $params = array($fecha, $cantidad_usuarios);
    $DB->execute($SQL, $params);
}

/**
 * Obtener la cantidad de usuarios conectados el día de ayer.
 *
 * @param string $format Formato de fecha para la consulta SQL.
 * @return string Consulta SQL para obtener la cantidad de usuarios conectados.
 */
function user_limit_daily_sql($format)
{
    return "SELECT count(DISTINCT`userid`) as conteo_accesos_unicos ,FROM_UNIXTIME(`timecreated`, '$format') as fecha
    FROM {logstore_standard_log}
    WHERE `action`='loggedin' 
    AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    GROUP by fecha";
}

/*Obtener el límite diario de usuarios.*/
/**
 * Obtener el límite diario de usuarios para una tarea específica.
 *
 * @return string Consulta SQL para obtener el límite diario de usuarios.
 */
function user_limit_daily_task()
{
    return "SELECT UNIX_TIMESTAMP(STR_TO_DATE(x.fecha, '%Y/%m/%d')) as fecha,x.conteo_accesos_unicos FROM (
        SELECT FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') as fecha, count(DISTINCT`userid`) as conteo_accesos_unicos 
        FROM {logstore_standard_log}
        WHERE `action`='loggedin' 
        AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
        GROUP by fecha) as x;";
}

/**
 * Recuperar los usuarios conectados recientemente para hoy.
 *
 * @return string Consulta SQL para obtener los usuarios conectados hoy.
 */
function users_today()
{
    return "SELECT FROM_UNIXTIME(`lastaccess`, '%d/%m/%Y') as fecha, count(DISTINCT`id`) as conteo_accesos_unicos from {user}
     WHERE FROM_UNIXTIME(`lastaccess`, '%Y/%m/%d')>= DATE_SUB(NOW(), INTERVAL 1 DAY);";
}

/**
 * Obtener el número máximo de accesos en los últimos 90 días.
 *
 * @param string $format Formato de fecha para la consulta SQL.
 * @return string Consulta SQL para obtener el número máximo de accesos en los últimos 90 días.
 */
function max_userdaily_for_90_days($format)
{
    return "SELECT UNIX_TIMESTAMP(STR_TO_DATE(x.fecha, '$format')) as fecha, x.conteo_accesos_unicos as usuarios FROM (
        SELECT FROM_UNIXTIME(`timecreated`, '$format') as fecha ,count(DISTINCT`userid`) as conteo_accesos_unicos 
        FROM {logstore_standard_log}
        WHERE `action`='loggedin' 
        AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP by fecha) as x
        ORDER BY usuarios DESC LIMIT 1";
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
    WHERE TABLE_SCHEMA='$CFG->dbname'";
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
 * Analiza el uso de disco por directorios específicos
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
        'temp' => $rootdir . '/temp',
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
 * Recupera los cursos que más espacio ocupan
 *
 * @param int $limit Número de cursos a recuperar
 * @return array Arreglo con información de los cursos
 */
function get_largest_courses($limit = 5) {
    global $DB, $CFG;
    
    // Esta consulta es una aproximación, ya que calcular el tamaño real de los cursos
    // requeriría escanear el sistema de archivos para cada curso, lo cual es costoso
    $sql = "SELECT c.id, c.fullname, c.shortname, COUNT(f.id) AS filecount, 
                  SUM(f.filesize) AS totalsize
           FROM {course} c
           JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
           JOIN {files} f ON f.contextid = ctx.id
           WHERE c.id != :siteid 
             AND f.filesize > 0
             AND f.component != 'backup'
           GROUP BY c.id, c.fullname, c.shortname
           ORDER BY totalsize DESC
           LIMIT :limit";
    
    $params = [
        'siteid' => SITEID,
        'limit' => $limit
    ];
    
    $courses = $DB->get_records_sql($sql, $params);
    
    // Ahora, para cada curso, calculamos también cuántos backups tienen
    foreach ($courses as $course) {
        $course->backupcount = $DB->count_records_sql(
            "SELECT COUNT(f.id) 
             FROM {files} f
             JOIN {context} ctx ON f.contextid = ctx.id 
             WHERE ctx.instanceid = :courseid 
               AND ctx.contextlevel = 50
               AND f.component = 'backup'
               AND f.filearea = 'automated'",
            ['courseid' => $course->id]
        );
        
        // Calculamos el porcentaje del tamaño total
        // Primero necesitamos el tamaño total de todos los archivos
        $totalfilessize = $DB->get_field_sql("SELECT SUM(filesize) FROM {files} WHERE filesize > 0");
        $course->percentage = $totalfilessize > 0 ? round(($course->totalsize / $totalfilessize) * 100, 2) : 0;
    }
    
    return $courses;
}

/**
 * Calcula la tasa de crecimiento de usuarios o espacio en disco
 *
 * @param string $type Tipo de dato a analizar ('users' o 'disk')
 * @param int $days Número de días a considerar para el cálculo
 * @return float Tasa de crecimiento porcentual
 */
function calculate_growth_rate($type = 'users', $days = 30) {
    global $DB, $CFG;
    
    if ($type === 'users') {
        // Obtenemos el número de usuarios conectados en el primer día del período
        $first_day_sql = "SELECT COUNT(DISTINCT userid) 
                          FROM {logstore_standard_log} 
                          WHERE action = 'loggedin' 
                          AND timecreated >= :start1 AND timecreated <= :end1";
        
        // Obtenemos el número de usuarios conectados en el último día del período
        $last_day_sql = "SELECT COUNT(DISTINCT userid) 
                         FROM {logstore_standard_log} 
                         WHERE action = 'loggedin' 
                         AND timecreated >= :start2 AND timecreated <= :end2";
        
        $now = time();
        $day_seconds = 86400; // 24 * 60 * 60
        
        $params = [
            'start1' => $now - ($days * $day_seconds),
            'end1' => $now - (($days - 1) * $day_seconds),
            'start2' => $now - $day_seconds,
            'end2' => $now
        ];
        
        $first_day_users = $DB->get_field_sql($first_day_sql, $params);
        $last_day_users = $DB->get_field_sql($last_day_sql, $params);
        
        // Evitamos división por cero
        if ($first_day_users == 0) {
            return 0;
        }
        
        // Calculamos la tasa de crecimiento
        $growth_rate = (($last_day_users - $first_day_users) / $first_day_users) * 100;
        
    } elseif ($type === 'disk') {
        // Para el disco, utilizamos la configuración almacenada
        // Nota: Esto requiere que tengamos un historial de uso de disco almacenado
        $reportconfig = get_config('report_usage_monitor');
        
        // Si no tenemos datos históricos, estimamos basado en la tasa actual
        // Esta es una aproximación simple
        if (empty($reportconfig->disk_history)) {
            return 5; // Asumimos un 5% de crecimiento mensual por defecto
        }
        
        // Implementar lógica basada en datos históricos si están disponibles
        // ...
        
        $growth_rate = 5; // Valor por defecto para esta versión
    } else {
        $growth_rate = 0;
    }
    
    return round($growth_rate, 2);
}

/**
 * Proyecta la fecha en que se alcanzaría un límite basado en la tasa de crecimiento
 *
 * @param int $current_value Valor actual
 * @param int $threshold_value Valor umbral a alcanzar
 * @param float $growth_rate Tasa de crecimiento porcentual
 * @return int Número estimado de días para alcanzar el umbral
 */
function project_limit_date($current_value, $threshold_value, $growth_rate) {
    // Si ya superamos el umbral o el crecimiento es cero o negativo
    if ($current_value >= $threshold_value || $growth_rate <= 0) {
        return 0;
    }
    
    // Convertimos la tasa de porcentaje a decimal diario
    $daily_growth_rate = ($growth_rate / 100) / 30; // Asumiendo que la tasa es mensual
    
    // Calculamos cuántos días tomaría alcanzar el umbral
    // Fórmula: log(threshold/current) / log(1 + daily_growth_rate)
    $days = log($threshold_value / $current_value) / log(1 + $daily_growth_rate);
    
    return max(1, ceil($days));
}

/**
 * Genera filas HTML para los datos históricos de usuarios
 *
 * @param int $limit Número de registros a incluir
 * @param int $max_threshold Umbral máximo de usuarios
 * @return string HTML generado para las filas de la tabla
 */
function generate_historical_data_html($limit = 7, $max_threshold = 100) {
    global $DB;
    
    $html = '';
    
    // Obtenemos los últimos días con datos de usuarios
    $sql = "SELECT FROM_UNIXTIME(timecreated, '%d/%m/%Y') as fecha, 
                   COUNT(DISTINCT userid) as usuarios
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
            AND FROM_UNIXTIME(timecreated, '%Y/%m/%d') > DATE_SUB(CURDATE(), INTERVAL :limit DAY)
            GROUP BY fecha
            ORDER BY MIN(timecreated) DESC
            LIMIT :limit";
    
    $records = $DB->get_records_sql($sql, ['limit' => $limit]);
    
    foreach ($records as $record) {
        $percent = round(($record->usuarios / $max_threshold) * 100, 1);
        $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
        
        $html .= '<tr>';
        $html .= '<td>' . $record->fecha . '</td>';
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
 * Retorna la tabla que se envía al correo con los datos de la cantidad de usuarios.
 *
 * @return string Tabla HTML con los datos de la cantidad de usuarios.
 */
function notification_table($disk_usage = null, $disk_percent = null, $quotadisk = null)
{
    global $DB;

    $table = '<h2>' . get_string('lastusers', 'report_usage_monitor') . '</h2>
    <table border="1" style="border-collapse: collapse; width: 50%;">
    <tr>
        <th style="padding: 8px; background-color: #f2f2f2;">' . get_string('date', 'report_usage_monitor') . '</th>
        <th style="padding: 8px; background-color: #f2f2f2;">' . get_string('usersquantity', 'report_usage_monitor') . '</th>
    </tr>';

    $userdaily = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $userdaily_records = $DB->get_records_sql($userdaily);

    foreach ($userdaily_records as $log) {
        $table .= '<tr>
        <td style="padding: 8px;">' . $log->fecha . '</td>
        <td style="padding: 8px;">' . $log->conteo_accesos_unicos . '</td>
        </tr>';
    }

    if ($disk_usage !== null && $disk_percent !== null && $quotadisk !== null) {
        $table .= '</table><br><h2>' . get_string('diskusage', 'report_usage_monitor') . '</h2>
        <table border="1" style="border-collapse: collapse; width: 50%;">
        <tr>
            <th style="padding: 8px; background-color: #f2f2f2;">' . get_string('totaldiskusage', 'report_usage_monitor') . '</th>
            <td style="padding: 8px;">' . display_size($disk_usage) . ' (' . round($disk_percent, 2) . '%)</td>
        </tr>
        <tr>
            <th style="padding: 8px; background-color: #f2f2f2;">' . get_string('diskquota', 'report_usage_monitor') . '</th>
            <td style="padding: 8px;">' . display_size($quotadisk) . '</td>
        </tr>';
    }

    $table .= '</table>';
    return $table;
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
 * Calcula el porcentaje de uso del espacio en disco y devuelve un color según el rango de uso.
 *
 * @param float $usedSpaceGB Espacio en disco utilizado en GB.
 * @param float $totalDiskSpace Tamaño total del disco en GB.
 * @return array Arreglo con el porcentaje de uso y el color correspondiente.
 */
function diskUsagePercentages($usedSpaceGB, $totalDiskSpace)
{
    $usedSpacePercentage = ($usedSpaceGB / $totalDiskSpace) * 100;
    $color = "";
    if ($usedSpacePercentage < 70) {
        $color = '#088A08'; // Verde
    } else if ($usedSpacePercentage <= 85) {
        $color = '#FFFF00'; // Amarillo
    } else {
        $color = '#DF0101'; // Rojo
    }
    return ['percentage' => $usedSpacePercentage, 'color' => $color];
}

// Función para comparar las fechas en formato 'd/m/Y' y ordenar en orden ascendente.
function compararFechas($fecha1, $fecha2)
{
    $date1 = DateTime::createFromFormat('d/m/Y', $fecha1);
    $date2 = DateTime::createFromFormat('d/m/Y', $fecha2);
    return $date1 <=> $date2;
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
    if ($threshold == 0) {
        return 0;
    }
    return ($current_value / $threshold) * 100;
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

    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    // Información básica
    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->threshold = $reportconfig->max_daily_users_threshold;
    $a->numberofusers = $numberofusers;
    $a->lastday = $fecha;
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php?view=userstopnum';
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
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php?view=diskusage';
    $a->siteurl = $CFG->wwwroot;
    $a->lastday = date('d/m/Y');
    $a->coursescount = $DB->count_records('course');
    $a->user_percent = round(calculate_threshold_percentage($userAccessCount, $a->threshold), 2);
    $a->moodle_version = $CFG->version;
    $a->moodle_release = $CFG->release;

    // Análisis por directorios
    $usage_by_dir = analyze_disk_usage_by_directory($CFG->dataroot);
    
    // Formateamos los tamaños y calculamos porcentajes
    $a->db_percent = round(($usage_by_dir['database'] / $disk_usage) * 100, 2);
    $a->filedir_size = display_size($usage_by_dir['filedir']);
    $a->filedir_percent = round(($usage_by_dir['filedir'] / $disk_usage) * 100, 2);
    $a->backup_size = display_size($usage_by_dir['backup']);
    $a->backup_percent = round(($usage_by_dir['backup'] / $disk_usage) * 100, 2);
    $a->cache_size = display_size($usage_by_dir['cache']);
    $a->cache_percent = round(($usage_by_dir['cache'] / $disk_usage) * 100, 2);
    $a->other_size = display_size($usage_by_dir['others']);
    $a->other_percent = round(($usage_by_dir['others'] / $disk_usage) * 100, 2);

    // Cursos más grandes
    $largest_courses = get_largest_courses(5);
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