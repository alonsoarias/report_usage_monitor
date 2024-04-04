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
 * Retorna la tabla que se envía al correo con los datos de la cantidad de usuarios.
 *
 * @return string Tabla HTML con los datos de la cantidad de usuarios.
 */
function notification_table()
{
    global $DB;
    $table = '<h2>' . get_string('lastusers', 'report_usage_monitor') . '</h2>
    <table class="text-center" border="1" style="width: 30%;">
    <tr>
        <th>' . get_string('date', 'report_usage_monitor') . '</th>
        <th>' . get_string('usersquantity', 'report_usage_monitor') . '</th>
    </tr>';
    $userdaily = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $userdaily_records = $DB->get_records_sql($userdaily);
    foreach ($userdaily_records as $log) {
        $table .= '<tr>
        <td>' . $log->fecha . '</td>
        <td>' . $log->conteo_accesos_unicos . '</td>
        </tr>'; ?>
            <?php
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
        // Do it this way if we can, it's much faster.
        if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
            if (PHP_OS === 'Linux') {
                // Verificamos si el sistema operativo es Linux para usar 'nice' y 'ionice'.
                $command = 'nice -n 19 ionice -c3 ' . trim($CFG->pathtodu) . ' -Lsk ' . escapeshellarg($rootdir);
            } else {
                $command = trim($CFG->pathtodu) . ' -Lsk ' . escapeshellarg($rootdir);
            }
            $output = null;
            $return = null;
            exec($command, $output, $return);
            if (is_array($output)) {
                // El comando 'du' devuelve el tamaño en kilobytes, así que lo convertimos a bytes.
                return get_real_size(intval($output[0]) . 'k');
            }
        }
        // Si no se puede usar 'du', hacemos el cálculo recursivamente.
        if (!is_dir($rootdir)) {
            // Debe ser un directorio.
            return 0;
        }
        if (!$dir = @opendir($rootdir)) {
            // No se puede abrir por alguna razón.
            return 0;
        }
        $size = 0;
        $files = glob($rootdir . '/*');
        foreach ($files as $path) {
            // Sumamos el tamaño de los archivos.
            is_file($path) && $size += filesize($path);
            // Si es un directorio, llamamos recursivamente para obtener el tamaño de sus contenidos.
            if (is_dir($path)) {
                $size += directory_size($path);
            }
        }
        // Cerramos el directorio abierto.
        closedir($dir);
        return $size;
    }
    /**
 * Convierte el tamaño de bytes a gigabytes.
 *
 * @param mixed $sizeInBytes El tamaño en bytes que se quiere convertir.
 * @param int $precision El número de decimales a mostrar.
 * @return string El tamaño en gigabytes, formateado como cadena.
 */
function display_size_in_gb($sizeInBytes, $precision = 2) {
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
     * @param      string  $email  plain text email address.
     * @param      string  $name   (optional) plain text real name.
     * @param      int     $id     (optional) user ID
     *
     * @return     object  user info.
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
    function email_notify_user_limit($numberofusers, $fecha)
    {
        global $CFG;
        $site = get_site();
        $a = new stdClass();
        $a->sitename  = format_string($site->fullname);
        $a->threshold = get_config('report_usage_monitor', 'max_daily_users_threshold');
        $a->numberofusers = $numberofusers;
        $a->lastday = $fecha;
        $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php?view=userstopnum';
        $a->siteurl = $CFG->wwwroot;
        $a->percentaje = ((($numberofusers - get_config('report_usage_monitor', 'max_daily_users_threshold')) / get_config('report_usage_monitor', 'max_daily_users_threshold')) * 100);
        $a->table = notification_table();
        $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
        $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));
        $subject = get_string('subjectemail1', 'report_usage_monitor');
        $messagehtml = get_string('messagehtml1', 'report_usage_monitor', $a);
        $messagetext = html_to_text($messagehtml);
        $previous_noemailever = false;
        if (isset($CFG->noemailever)) $previous_noemailever = $CFG->noemailever;
        $CFG->noemailever = false;
        email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
        if ($previous_noemailever) $CFG->noemailever = $previous_noemailever;
        return true;
    }

    function email_notify_disk_limit($quotadisk, $disk_usage)
    {
        global $CFG;
        $site = get_site();
        $a = new stdClass();
        $a->sitename  = format_string($site->fullname);
        $a->quotadisk = display_size($quotadisk);
        $a->diskusage = display_size($disk_usage);
        $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php?view=diskusage';
        $a->siteurl = $CFG->wwwroot;
        $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
        $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));
        $subject = get_string('subjectemail2', 'report_usage_monitor');
        $messagehtml = get_string('messagehtml2', 'report_usage_monitor', $a);
        $messagetext = html_to_text($messagehtml);
        $previous_noemailever = false;
        if (isset($CFG->noemailever)) $previous_noemailever = $CFG->noemailever;
        $CFG->noemailever = false;
        email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
        if ($previous_noemailever) $CFG->noemailever = $previous_noemailever;
        return true;
    }
