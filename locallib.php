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
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get list of users from last 10 days.
 *
 * @param string $format Date format for SQL query.
 * @return string SQL query to get user list.
 */
function report_user_daily_sql($format) {
    return "SELECT id, FROM_UNIXTIME(`timecreated`, '$format') as fecha, count(DISTINCT`userid`) as conteo_accesos_unicos
    FROM {logstore_standard_log}
    WHERE `action`='loggedin' 
    AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') BETWEEN DATE_SUB(CURDATE(), INTERVAL 10 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    GROUP by fecha 
    ORDER BY fecha DESC";
}

/**
 * Get data from top daily maximum users.
 *
 * @param string $format Date format for SQL query.
 * @return string SQL query to get top users data.
 */
function report_user_daily_top_sql($format) {
    return "SELECT FROM_UNIXTIME(`fecha`, '$format') as fecha, cantidad_usuarios from {report_usage_monitor} ORDER BY cantidad_usuarios DESC";
}

/**
 * Get data from top daily maximum users for a specific task.
 *
 * @return string SQL query to get top users data.
 */
function report_user_daily_top_task() {
    return "SELECT fecha, cantidad_usuarios from {report_usage_monitor} ORDER BY cantidad_usuarios DESC";
}

/**
 * Update top daily users if current number of users is greater than or equal to minimum record in top.
 *
 * @param string $fecha Date to update in top.
 * @param int $usuarios Number of users to update in top.
 * @param int $min Minimum value to compare in top.
 */
function update_min_top_sql($fecha, $usuarios, $min) {
    global $DB;
    $SQL = "UPDATE {report_usage_monitor} set fecha=?,cantidad_usuarios=? where fecha=?";
    $params = array($fecha, $usuarios, $min);
    $DB->execute($SQL, $params);
}

/**
 * Insert a record if top daily users doesn't have 10 records.
 *
 * @param string $fecha Date to insert in top.
 * @param int $cantidad_usuarios Number of users to insert in top.
 */
function insert_top_sql($fecha, $cantidad_usuarios) {
    global $DB;
    $SQL = "INSERT INTO {report_usage_monitor} (fecha,cantidad_usuarios) VALUES (?,?)";
    $params = array($fecha, $cantidad_usuarios);
    $DB->execute($SQL, $params);
}

/**
 * Get number of connected users yesterday.
 *
 * @param string $format Date format for SQL query.
 * @return string SQL query to get number of connected users.
 */
function user_limit_daily_sql($format) {
    return "SELECT count(DISTINCT`userid`) as conteo_accesos_unicos, FROM_UNIXTIME(`timecreated`, '$format') as fecha
    FROM {logstore_standard_log}
    WHERE `action`='loggedin' 
    AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    GROUP by fecha";
}

/**
 * Get daily user limit for a specific task.
 *
 * @return string SQL query to get daily user limit.
 */
function user_limit_daily_task() {
    return "SELECT UNIX_TIMESTAMP(STR_TO_DATE(x.fecha, '%Y/%m/%d')) as fecha, x.conteo_accesos_unicos FROM (
        SELECT FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') as fecha, count(DISTINCT`userid`) as conteo_accesos_unicos 
        FROM {logstore_standard_log}
        WHERE `action`='loggedin' 
        AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
        GROUP by fecha) as x;";
}

/**
 * Get connected users for today.
 *
 * @return string SQL query to get users connected today.
 */
function users_today() {
    return "SELECT FROM_UNIXTIME(`lastaccess`, '%d/%m/%Y') as fecha, count(DISTINCT`id`) as conteo_accesos_unicos from {user}
     WHERE FROM_UNIXTIME(`lastaccess`, '%Y/%m/%d')>= DATE_SUB(NOW(), INTERVAL 1 DAY);";
}

/**
 * Get maximum number of accesses in last 90 days.
 *
 * @param string $format Date format for SQL query.
 * @return string SQL query to get maximum number of accesses in last 90 days.
 */
function max_userdaily_for_90_days($format) {
    return "SELECT UNIX_TIMESTAMP(STR_TO_DATE(x.fecha, '$format')) as fecha, x.conteo_accesos_unicos as usuarios FROM (
        SELECT FROM_UNIXTIME(`timecreated`, '$format') as fecha, count(DISTINCT`userid`) as conteo_accesos_unicos 
        FROM {logstore_standard_log}
        WHERE `action`='loggedin' 
        AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP by fecha) as x
        ORDER BY usuarios DESC LIMIT 1";
}

/**
 * Calculate database size.
 *
 * @return string SQL query to get database size.
 */
function size_database() {
    global $CFG;
    return "SELECT TABLE_SCHEMA AS `database_name`, 
    ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)) AS size
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA='$CFG->dbname'";
}

/**
 * Calculate size of all files in a directory.
 *
 * @param string $rootdir Directory to calculate size
 * @param string $excludefile File to exclude from calculation
 * @return int Total size in bytes
 */
function directory_size($rootdir, $excludefile = '') {
    global $CFG;

    if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
        $escapedRootdir = escapeshellarg($rootdir);
        $command = trim($CFG->pathtodu) . ' -Lsk ' . $escapedRootdir;

        if (PHP_OS === 'Linux') {
            $command = 'nice -n 19 ionice -c3 ' . $command;
        }

        if (!empty($excludefile)) {
            $escapedExcludefile = escapeshellarg($excludefile);
            $command .= ' --exclude=' . $escapedExcludefile;
        }

        $output = null;
        $return = null;
        exec($command, $output, $return);
        if (is_array($output) && isset($output[0])) {
            return intval($output[0]) * 1024;
        }
    }

    if (!is_dir($rootdir)) {
        return 0;
    }

    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootdir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && ($excludefile === '' || $file->getFilename() !== $excludefile)) {
            $size += $file->getSize();
        }
    }

    return $size;
}

/**
 * Convert size from bytes to gigabytes.
 *
 * @param mixed $sizeInBytes Size in bytes to convert
 * @param int $precision Number of decimal places
 * @return string Size in gigabytes as string
 */
function display_size_in_gb($sizeInBytes, $precision = 2) {
    if (!is_numeric($sizeInBytes) || $sizeInBytes === null) {
        debugging("display_size_in_gb: expected numeric value, received: " . var_export($sizeInBytes, true), DEBUG_DEVELOPER);
        return '0';
    }

    $sizeInGb = $sizeInBytes / (1024 * 1024 * 1024);
    return round($sizeInGb, $precision);
}

/**
 * Generate user info object for email operations.
 *
 * @param string $email Email address
 * @param string $name Real name (optional)
 * @param int $id User ID (optional)
 * @return object User object
 */
function generate_email_user($email, $name = '', $id = -99) {
    $emailuser = new stdClass();
    $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailuser->email = '';
    }
    $name = format_text($name, FORMAT_HTML, ['trusted' => false, 'noclean' => false]);
    $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
    $emailuser->lastname = '';
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1;
    $emailuser->id = $id;
    $emailuser->firstnamephonetic = '';
    $emailuser->lastnamephonetic = '';
    $emailuser->middlename = '';
    $emailuser->alternatename = '';
    return $emailuser;
}

/**
 * Calculate threshold percentage.
 *
 * @param int $current_value Current value
 * @param int $threshold Threshold value
 * @return float Percentage
 */
function calculate_threshold_percentage($current_value, $threshold) {
    if ($threshold == 0) {
        return 0;
    }
    return ($current_value / $threshold) * 100;
}

/**
 * Calculate disk usage percentages and return color based on usage range.
 *
 * @param float $usedSpaceGB Used space in GB
 * @param float $totalDiskSpace Total disk space in GB
 * @return array Array with percentage and color
 */
function diskUsagePercentages($usedSpaceGB, $totalDiskSpace)
{
    $usedSpacePercentage = ($usedSpaceGB / $totalDiskSpace) * 100;
    $color = "";
    if ($usedSpacePercentage >= 95) {
        $color = '#ff4c4c'; // CRÍTICO
    } else if ($usedSpacePercentage >= 90) {
        $color = '#ff8c4c'; // ALTO
    } else if ($usedSpacePercentage >= 80) {
        $color = '#ffd54f'; // MEDIO
    } else {
        $color = '#66bb6a'; // NORMAL
    }
    return ['percentage' => $usedSpacePercentage, 'color' => $color];
}


/**
 * Send unified system notification.
 *
 * @param array $data Array with all system metrics
 * @return bool Success status
 */
function email_notify_unified($data) {
    global $CFG, $DB;

    $site = get_site();
    $a = new stdClass();
    
    // Site information
    $a->sitename = format_string($site->fullname);
    $a->siteurl = $CFG->wwwroot;
    $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';

    // Disk metrics with formatting
    $a->diskusage = display_size($data['disk_usage']);
    $a->quotadisk = display_size($data['disk_quota']);
    $a->disk_percent = round($data['disk_percent'], 2);
    $a->databasesize = display_size($data['database_size']);

    // User metrics
    $a->numberofusers = $data['user_count'];
    $a->threshold = $data['user_threshold'];
    $a->user_percent = round($data['user_percent'], 2);
    $a->lastday = $data['fecha'];

    // Alert levels and styling
    $a->disk_alert = get_string($data['disk_alert_level'] . '_threshold', 'report_usage_monitor');
    $a->user_alert = get_string($data['user_alert_level'] . '_threshold', 'report_usage_monitor');
    $a->disk_alert_class = strtolower($data['disk_alert_level']);
    $a->user_alert_class = strtolower($data['user_alert_level']);

    // Process 90-day peak data
    $a->max_90_days_date = ($data['max_90_days']['date']);
    $a->max_90_days_users = ($data['max_90_days']['users']);
    $a->max_90_days_percent = ($data['max_90_days']['percent']);

    // Additional metrics
    $a->backupcount = get_config('backup', 'backup_auto_max_kept');
    $a->coursescount = $data['coursescount'];

    // Generate metrics table
    $a->table = notification_table_unified($data);

    // Email configuration
    $toemail = generate_email_user(get_config('report_usage_monitor', 'email'), '');
    $fromemail = generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));

    $subject = get_string('unifiednotification_subject', 'report_usage_monitor', $a->sitename);
    $messagehtml = get_string('unifiednotification_html', 'report_usage_monitor', $a);
    $messagetext = html_to_text($messagehtml);

    return email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml);
}

/**
 * Generate unified metrics table for notification.
 *
 * @param array $data System metrics data
 * @return string HTML table
 */
function notification_table_unified($data) {
    $table = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';

    // System Status Section
    $table .= '<tr>
        <th colspan="2" style="padding: 12px; background-color: #f5f5f5; text-align: left; border: 1px solid #ddd;">
            ' . get_string('system_status', 'report_usage_monitor') . '
        </th>
    </tr>';

    // Disk Usage Status
    $table .= generate_alert_row(
        get_string('disk_usage_title', 'report_usage_monitor'),
        display_size($data['disk_usage']) . ' / ' . display_size($data['disk_quota']) .
            ' (' . round($data['disk_percent'], 2) . '%)',
        $data['disk_alert_level']
    );

    // User Count Status
    $table .= generate_alert_row(
        get_string('user_count_title', 'report_usage_monitor'),
        $data['user_count'] . ' / ' . $data['user_threshold'] .
            ' (' . round($data['user_percent'], 2) . '%)',
        $data['user_alert_level']
    );

    // 90-Day Peak Users Section
    $table .= '<tr>
        <th colspan="2" style="padding: 12px; background-color: #f5f5f5; text-align: left; border: 1px solid #ddd;">
            ' . get_string('peak_users_title', 'report_usage_monitor') . '
        </th>
    </tr>';

    if (isset($data['max_90_days'])) {
        $peak_date = ($data['max_90_days']['date']);
        $peak_users = ($data['max_90_days']['users']);
        $peak_percent = ($data['max_90_days']['percent']);

        $table .= '<tr>
            <td style="padding: 10px; border: 1px solid #ddd; width: 50%;">
                ' . get_string('peak_date_label', 'report_usage_monitor') . '
            </td>
            <td style="padding: 10px; border: 1px solid #ddd;">
                <strong>' . $peak_date . '</strong>
            </td>
        </tr>';
        $table .= '<tr>
            <td style="padding: 10px; border: 1px solid #ddd;">
                ' . get_string('peak_users_label', 'report_usage_monitor') . '
            </td>
            <td style="padding: 10px; border: 1px solid #ddd;">
                <strong>' . $peak_users . ' (' . round($peak_percent, 2) . '%)</strong>
            </td>
        </tr>';
    } else {
        $table .= '<tr>
            <td colspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                ' . get_string('notcalculatedyet', 'report_usage_monitor') . '
            </td>
        </tr>';
    }

    // Additional Information Section
    $table .= '<tr>
        <th colspan="2" style="padding: 12px; background-color: #f5f5f5; text-align: left; border: 1px solid #ddd;">
            ' . get_string('additional_info', 'report_usage_monitor') . '
        </th>
    </tr>';

    // Database Size
    $table .= '<tr>
        <td style="padding: 10px; border: 1px solid #ddd; width: 50%;">
            ' . get_string('database_size_title', 'report_usage_monitor') . '
        </td>
        <td style="padding: 10px; border: 1px solid #ddd;">
            ' . display_size($data['database_size']) . '
        </td>
    </tr>';

    // Course Count
    $table .= '<tr>
        <td style="padding: 10px; border: 1px solid #ddd;">
            ' . get_string('total_courses', 'report_usage_monitor') . '
        </td>
        <td style="padding: 10px; border: 1px solid #ddd;">
            ' . $data['coursescount'] . '
        </td>
    </tr>';

    // Backup Retention
    $table .= '<tr>
        <td style="padding: 10px; border: 1px solid #ddd;">
            ' . get_string('backup_retention', 'report_usage_monitor') . '
        </td>
        <td style="padding: 10px; border: 1px solid #ddd;">
            ' . $data['backupcount'] . ' ' . get_string('per_course', 'report_usage_monitor') . '
        </td>
    </tr>';

    $table .= '</table>';

    // Add threshold information
    $table .= '<div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
        <strong>' . get_string('threshold_info', 'report_usage_monitor') . '</strong>
    </div>';

    return $table;
}

/**
 * Generate a table row with alert styling.
 *
 * @param string $label Row label
 * @param string $value Row value
 * @param string $alert_level Alert level (critical, high, medium, normal)
 * @return string HTML row
 */
function generate_alert_row($label, $value, $alert_level) {
    $colors = [
        'critical' => '#ffebee',
        'high' => '#fff3e0',
        'medium' => '#fff8e1',
        'normal' => '#f1f8e9'
    ];

    $background_color = $colors[$alert_level] ?? '#ffffff';

    return '<tr style="background-color: ' . $background_color . ';">
        <td style="padding: 10px; border: 1px solid #ddd; width: 50%;">
            ' . $label . '
            <div style="font-size: 0.9em; color: #666;">
                ' . get_string($alert_level . '_threshold', 'report_usage_monitor') . '
            </div>
        </td>
        <td style="padding: 10px; border: 1px solid #ddd;">
            <strong>' . $value . '</strong>
        </td>
    </tr>';
}

/**
 * Compare dates in d/m/Y format for sorting.
 *
 * @param string $fecha1 First date
 * @param string $fecha2 Second date
 * @return int Comparison result
 */
function compararFechas($fecha1, $fecha2)
{
    $date1 = DateTime::createFromFormat('d/m/Y', $fecha1);
    $date2 = DateTime::createFromFormat('d/m/Y', $fecha2);
    return $date1 <=> $date2;
}

/**
 * Gets historical user access data.
 *
 * @param array $data System metrics data
 * @return string HTML table with historical data
 */
function get_historical_data_table($data) {
    global $DB;

    $table = '<h3>' . get_string('historical_data', 'report_usage_monitor') . '</h3>';
    $table .= '<table border="1" style="border-collapse: collapse; width: 100%; margin-top: 10px;">
            <tr>
                <th style="padding: 8px; background-color: #f5f5f5;">' . get_string('date', 'report_usage_monitor') . '</th>
                <th style="padding: 8px; background-color: #f5f5f5;">' . get_string('usersquantity', 'report_usage_monitor') . '</th>
            </tr>';

    $userdaily = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $records = $DB->get_records_sql($userdaily);

    foreach ($records as $record) {
        $table .= '<tr>
                <td style="padding: 8px;">' . $record->fecha . '</td>
                <td style="padding: 8px;">' . $record->conteo_accesos_unicos . '</td>
            </tr>';
    }

    $table .= '</table>';
    return $table;
}