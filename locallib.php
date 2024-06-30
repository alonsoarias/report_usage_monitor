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
    global $DB;

    // Obtener la fecha actual y calcular las fechas límite para los últimos 10 días.
    $end_date = strtotime(date('Y-m-d', strtotime('-1 day')));
    $start_date = strtotime(date('Y-m-d', strtotime('-10 days')));

    return "SELECT FROM_UNIXTIME(`timecreated`, '$format') as fecha, count(DISTINCT `userid`) as conteo_accesos_unicos
    FROM {logstore_standard_log}
    WHERE `action` = 'loggedin'
    AND `timecreated` BETWEEN $start_date AND $end_date
    GROUP BY fecha
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
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootdir, RecursiveDirectoryIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if ($file->isFile() && $file->getFilename() !== $excludefile) {
            // Sumamos el tamaño del archivo si no está excluido.
            $size += $file->getSize();
        }
    }

    return $size;
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
        return '0 GB'; // Retorna '0 GB' como un valor seguro por defecto.
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
 * Sends an email notification when the daily user limit is exceeded.
 *
 * This function constructs and sends an email when the number of unique users for the previous day
 * exceeds the configured threshold. It uses Moodle's internal email system to handle the sending process.
 *
 * @param int $usage The number of unique users that accessed the system.
 * @param string $fecha The date for which the threshold was exceeded.
 *
 * @return bool Returns true if the email was successfully queued for sending, false otherwise.
 */
function email_notify_user_limit($usage, $fecha, $percentage)
{
    global $CFG, $DB;
    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->threshold = $reportconfig->max_daily_users_threshold;
    $a->usage = $usage;
    $a->lastday = $fecha;
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php?view=userstopnum';
    $a->siteurl = $CFG->wwwroot;
    $a->percentage = $percentage; // Pasamos el porcentaje como argumento

    // Agregar detalles de uso de disco
    $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
    $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;

    $a->diskusage = display_size($disk_usage);
    $a->quotadisk = display_size($quotadisk);

    $a->table = notification_table();

    // Generate email addresses for sender and recipient.
    $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));

    $subject = get_string('subjectemail1', 'report_usage_monitor') . " {$a->sitename}";
    $messagehtml = get_string('messagehtml1', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    $previous_noemailever = false;
    if (isset($CFG->noemailever)) $previous_noemailever = $CFG->noemailever;
    $CFG->noemailever = false;
    email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    if ($previous_noemailever) $CFG->noemailever = $previous_noemailever;

    return true;
}


/**
 * Sends an email notification based on disk usage limits.
 *
 * This function receives disk usage data and the calculated disk usage percentage as parameters,
 * constructs a notification email based on these values.
 * The approach ensures that the calculation logic is kept separate from the mailing logic,
 * enhancing maintainability and testing.
 *
 * @param int $quotadisk The total disk quota assigned, in bytes.
 * @param int $disk_usage The current disk usage, in bytes.
 * @param float $disk_percent The percentage of disk quota used.
 *
 * @return bool Returns true if the email is successfully sent, otherwise returns false.
 */
function email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount)
{
    global $CFG, $DB;

    $site = get_site();
    $reportconfig = get_config('report_usage_monitor');

    $a = new stdClass();
    $a->sitename = format_string($site->fullname);
    $a->quotadisk = display_size($quotadisk);
    $a->diskusage = display_size($disk_usage);
    $a->percentage = round($disk_percent, 2);
    $a->databasesize = display_size($reportconfig->totalusagereadabledb);
    $a->backupcount = $reportconfig->backup_auto_max_kept;
    $a->threshold = $reportconfig->max_daily_users_threshold;
    $a->numberofusers = $userAccessCount;
    $a->userpercentage = calculate_user_threshold_percentage($a->numberofusers, $a->threshold);
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php?view=diskusage';
    $a->siteurl = $CFG->wwwroot;
    $a->coursescount = $DB->count_records('course'); // Contar la cantidad de cursos

    // Generate email addresses for sender and recipient.
    $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));

    $subject = get_string('subjectemail2', 'report_usage_monitor') . " {$a->sitename}";
    $messagehtml = get_string('messagehtml2', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    $previous_noemailever = false;
    if (isset($CFG->noemailever)) $previous_noemailever = $CFG->noemailever;
    $CFG->noemailever = false;
    email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    if ($previous_noemailever) $CFG->noemailever = $previous_noemailever;

    return true;
}


function calculate_user_threshold_percentage($usage, $threshold)
{
    return ($threshold > 0) ? round(($usage / $threshold) * 100, 2) : 0;
}
