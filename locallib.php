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
 * Envía un correo unificado que incluye toda la información
 * de límite de usuarios diarios y uso de disco, más cualquier
 * dato adicional que antes se manejaba por separado.
 *
 * @param bool   $exceededUsers   Indica si el umbral de usuarios se superó.
 * @param bool   $exceededDisk    Indica si el umbral de disco se superó.
 * @param object $info            Objeto con los datos (usuarios, disco, extras).
 *
 * @return bool  true si se envía el correo exitosamente.
 */
function email_notify_usage_monitor($exceededUsers, $exceededDisk, $info) {
    global $CFG, $DB;

    // Objeto $a para pasar a la plantilla de notificación.
    $a = new stdClass();

    // 1) Información general
    $a->sitename  = $info->sitename  ?? format_string(get_site()->fullname);
    $a->siteurl   = $info->siteurl   ?? $CFG->wwwroot;

    // 2) Datos sobre usuarios (día anterior)
    $a->userthreshold  = $info->userthreshold ?? 100;
    $a->users          = $info->users         ?? 0;
    $a->userpercent    = round($info->userpercent ?? 0, 2);
    $a->exceededUsersLabel = $exceededUsers
        ? get_string('yes', 'moodle')
        : get_string('no', 'moodle');

    // 3) Datos sobre disco
    $a->diskusage   = $info->diskusage   ?? '0 B';
    $a->diskquota   = $info->diskquota   ?? '0 B';
    $a->diskpercent = round($info->diskpercent ?? 0, 2);
    $a->exceededDiskLabel = $exceededDisk
        ? get_string('yes', 'moodle')
        : get_string('no', 'moodle');

    // 4) Extras (tamaño base de datos, número de cursos, backups, etc.)
    $a->databasesize  = $info->databasesize   ?? '';
    $a->coursescount  = $info->coursescount   ?? 0;
    $a->backupcount   = $info->backupcount    ?? '-';

    // 5) Si quieres incluir la tabla de últimos 10 días, p. ej.:
    $a->table = $info->table ?? '';

    // Prepara y envía el correo
    $reportconfig  = get_config('report_usage_monitor');
    $toemail       = generate_email_user($reportconfig->email ?? '', '');
    $fromemail     = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));

    // Sujeto y mensaje
    // Usamos un string existente o uno nuevo, p. ej. subjectemail_unified
    $subject = get_string('subjectemail_unified', 'report_usage_monitor', $a);
    $messagehtml = get_string('messagehtml_unified', 'report_usage_monitor', $a);

    $messagetext = html_to_text($messagehtml);

    // Enviar
    $prev_noemailever = $CFG->noemailever ?? false;
    $CFG->noemailever = false;
    $success = email_to_user(
        $toemail,
        $fromemail,
        $subject,
        $messagetext,
        $messagehtml,
        '',
        '',
        true,
        $fromemail->email
    );
    $CFG->noemailever = $prev_noemailever;

    return $success;
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

