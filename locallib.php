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
 * Library of functions for report_usage_monitor
 *
 * @package    report_usage_monitor
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get list of users from the last 10 days.
 *
 * @param string $format Date format for SQL query
 * @return string SQL query
 */
function report_user_daily_sql($format) {
    return "SELECT FROM_UNIXTIME(`timecreated`, '$format') as fecha, 
                   count(DISTINCT`userid`) as conteo_accesos_unicos
            FROM {logstore_standard_log}
            WHERE `action`='loggedin' 
            AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') 
                BETWEEN DATE_SUB(CURDATE(), INTERVAL 10 DAY) 
                AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            GROUP by fecha 
            ORDER BY fecha DESC";
}

/**
 * Get data for top daily users maximum.
 *
 * @param string $format Date format for SQL query
 * @return string SQL query
 */
function report_user_daily_top_sql($format) {
    return "SELECT FROM_UNIXTIME(`fecha`, '$format') as fecha, 
                   cantidad_usuarios 
            FROM {report_usage_monitor}  
            ORDER BY cantidad_usuarios DESC";
}

/**
 * Get data for top daily users maximum for a specific task.
 *
 * @return string SQL query
 */
function report_user_daily_top_task() {
    return "SELECT fecha, cantidad_usuarios 
            FROM {report_usage_monitor}  
            ORDER BY cantidad_usuarios DESC";
}

/**
 * Update top daily users if current users is greater than or equal to lowest in top.
 *
 * @param string $fecha Date to update in top
 * @param int $usuarios Number of users to update
 * @param int $min Minimum value to compare in top
 * @return void
 */
function update_min_top_sql($fecha, $usuarios, $min) {
    global $DB;
    $params = array($fecha, $usuarios, $min);
    $DB->execute(
        "UPDATE {report_usage_monitor} 
         SET fecha=?, cantidad_usuarios=? 
         WHERE fecha=?", 
        $params
    );
}

/**
 * Insert a record if top daily users doesn't have 10 records.
 *
 * @param string $fecha Date to insert
 * @param int $cantidad_usuarios Number of users to insert
 * @return void
 */
function insert_top_sql($fecha, $cantidad_usuarios) {
    global $DB;
    $params = array($fecha, $cantidad_usuarios);
    $DB->execute(
        "INSERT INTO {report_usage_monitor} (fecha,cantidad_usuarios) 
         VALUES (?,?)", 
        $params
    );
}

/**
 * Get number of users connected yesterday.
 *
 * @param string $format Date format for SQL query
 * @return string SQL query
 */
function user_limit_daily_sql($format) {
    return "SELECT count(DISTINCT`userid`) as conteo_accesos_unicos,
                   FROM_UNIXTIME(`timecreated`, '$format') as fecha
            FROM {logstore_standard_log}
            WHERE `action`='loggedin' 
            AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') = 
                DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            GROUP by fecha";
}

/**
 * Get daily user limit for a specific task.
 *
 * @return string SQL query
 */
function user_limit_daily_task() {
    return "SELECT UNIX_TIMESTAMP(STR_TO_DATE(x.fecha, '%Y/%m/%d')) as fecha,
                   x.conteo_accesos_unicos 
            FROM (
                SELECT FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') as fecha, 
                       count(DISTINCT`userid`) as conteo_accesos_unicos 
                FROM {logstore_standard_log}
                WHERE `action`='loggedin' 
                AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') = 
                    DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
                GROUP by fecha
            ) as x";
}

/**
 * Get recently connected users for today.
 *
 * @return string SQL query
 */
function users_today() {
    return "SELECT FROM_UNIXTIME(`lastaccess`, '%d/%m/%Y') as fecha, 
                   count(DISTINCT`id`) as conteo_accesos_unicos 
            FROM {user}
            WHERE FROM_UNIXTIME(`lastaccess`, '%Y/%m/%d') >= 
                DATE_SUB(NOW(), INTERVAL 1 DAY)";
}

/**
 * Get maximum number of accesses in last 90 days.
 *
 * @param string $format Date format for SQL query
 * @return string SQL query
 */
function max_userdaily_for_90_days($format) {
    return "SELECT UNIX_TIMESTAMP(STR_TO_DATE(x.fecha, '$format')) as fecha, 
                   x.conteo_accesos_unicos as usuarios 
            FROM (
                SELECT FROM_UNIXTIME(`timecreated`, '$format') as fecha,
                       count(DISTINCT`userid`) as conteo_accesos_unicos 
                FROM {logstore_standard_log}
                WHERE `action`='loggedin' 
                AND FROM_UNIXTIME(`timecreated`, '%Y/%m/%d') >= 
                    DATE_SUB(NOW(), INTERVAL 90 DAY) 
                GROUP by fecha
            ) as x
            ORDER BY usuarios DESC 
            LIMIT 1";
}

/**
 * Calculate database size.
 *
 * @return string SQL query
 */
function size_database() {
    global $CFG;
    return "SELECT TABLE_SCHEMA AS `database_name`, 
                   ROUND(SUM(DATA_LENGTH + INDEX_LENGTH)) AS size
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA='$CFG->dbname'";
}

/**
 * Calculate size of a directory recursively.
 *
 * @param string $rootdir The directory to analyze
 * @param string $excludefile Optional file to exclude
 * @return int Total size in bytes
 */
function directory_size($rootdir, $excludefile = '') {
    global $CFG;

    // Check if du command is available
    if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
        $escapedRootdir = escapeshellarg($rootdir);
        $command = trim($CFG->pathtodu) . ' -Lsk ' . $escapedRootdir;

        // Use nice/ionice on Linux
        if (PHP_OS === 'Linux') {
            $command = 'nice -n 19 ionice -c3 ' . $command;
        }

        // Add exclusion if specified
        if (!empty($excludefile)) {
            $escapedExcludefile = escapeshellarg($excludefile);
            $command .= ' --exclude=' . $escapedExcludefile;
        }

        // Execute command
        $output = null;
        $return = null;
        exec($command, $output, $return);
        if (is_array($output) && isset($output[0])) {
            return intval($output[0]) * 1024;
        }
    }

    // Fallback to PHP recursive calculation
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
 * Convert size from bytes to GB.
 *
 * @param mixed $sizeInBytes Size in bytes
 * @param int $precision Number of decimal places
 * @return string Size in GB
 */
function display_size_in_gb($sizeInBytes, $precision = 2) {
    if (!is_numeric($sizeInBytes) || $sizeInBytes === null) {
        debugging("display_size_in_gb: expected numeric value, received: " . 
                 var_export($sizeInBytes, true), DEBUG_DEVELOPER);
        return '0';
    }
    return round($sizeInBytes / (1024 * 1024 * 1024), $precision);
}

/**
 * Calculate disk usage percentages and get corresponding color.
 *
 * @param float $usedSpaceGB Used space in GB
 * @param float $totalDiskSpace Total space in GB
 * @return array Percentage and color
 */
function diskUsagePercentages($usedSpaceGB, $totalDiskSpace) {
    $usedSpacePercentage = ($usedSpaceGB / $totalDiskSpace) * 100;
    
    if ($usedSpacePercentage < 70) {
        $color = '#088A08'; // Green
    } else if ($usedSpacePercentage <= 85) {
        $color = '#FFFF00'; // Yellow
    } else {
        $color = '#DF0101'; // Red
    }
    
    return [
        'percentage' => $usedSpacePercentage, 
        'color' => $color
    ];
}

/**
 * Compare dates in d/m/Y format.
 *
 * @param string $fecha1 First date
 * @param string $fecha2 Second date
 * @return int Comparison result
 */
function compararFechas($fecha1, $fecha2) {
    $date1 = DateTime::createFromFormat('d/m/Y', $fecha1);
    $date2 = DateTime::createFromFormat('d/m/Y', $fecha2);
    return $date1 <=> $date2;
}

/**
 * Generate standardized email user object.
 *
 * @param string $email Email address
 * @param string $name Optional name
 * @param int $id Optional user ID
 * @return object Email user object
 */
function generate_email_user($email, $name = '', $id = -99) {
    $emailuser = new stdClass();
    
    // Validate and sanitize email
    $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailuser->email = '';
    }
    
    // Sanitize name
    $name = format_text($name, FORMAT_HTML, ['trusted' => false, 'noclean' => false]);
    $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
    
    // Set default values
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
 * Gather all system usage information.
 *
 * @return object System usage information
 */
function usage_monitor_gather_info() {
    global $CFG, $DB;
    
    $reportconfig = get_config('report_usage_monitor');
    $info = new stdClass();

    // Site info
    $info->sitename = format_string(get_site()->fullname);
    $info->siteurl = $CFG->wwwroot;
    
    // User info
    $info->userthreshold = (int)$reportconfig->max_daily_users_threshold;
    $info->users = usage_monitor_get_yesterday_users();
    $info->userpercent = calculate_threshold_percentage(
        $info->users, 
        $info->userthreshold
    );
    
    // Disk info
    $quotadisk = ((int)$reportconfig->disk_quota * 1024) * 1024 * 1024;
    $totalFS = (int)($reportconfig->totalusagereadable ?? 0);
    $totalDB = (int)($reportconfig->totalusagereadabledb ?? 0);
    
    $info->diskusage = $totalFS + $totalDB;
    $info->diskquota = $quotadisk;
    $info->diskpercent = calculate_threshold_percentage(
        $info->diskusage, 
        $info->diskquota
    );
    
    // Additional info
    $info->databasesize = $totalDB;
    $info->coursescount = $DB->count_records('course');
    $info->backupcount = get_config('backup', 'backup_auto_max_kept');
    
    // Generate last 10 days table
    $info->table = notification_table();
    
    return $info;
}

/**
 * Get number of users from yesterday.
 *
 * @return int Number of users
 */
function usage_monitor_get_yesterday_users() {
    global $DB;
    $sql = user_limit_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    return (int)$DB->get_field_sql($sql);
}

/**
 * Check if notification should be sent.
 *
 * @param object $info Usage information
 * @return bool True if notification should be sent
 */
function usage_monitor_should_notify($info) {
    $lastNotificationTime = (int)get_config('report_usage_monitor', 'last_notification_time');
    $currentTime = time();

    // Check thresholds
    $userExceeded = $info->users > $info->userthreshold;
    $diskExceeded = $info->diskpercent >= 90;

    // Calculate interval
    $notifyInterval = usage_monitor_calculate_interval(
        $info->userpercent,
        $info->diskpercent
    );

    return ($userExceeded || $diskExceeded) &&
           ($currentTime - $lastNotificationTime >= $notifyInterval);
}

/**
 * Calculate notification interval based on usage percentages.
 *
 * @param float $userPercent User usage percentage
 * @param float $diskPercent Disk usage percentage
 * @return int Interval in seconds
 */
function usage_monitor_calculate_interval($userPercent, $diskPercent) {
    $maxPercent = max($userPercent, $diskPercent);
    
    if ($maxPercent >= 95) {
        return 12 * HOURSECS; // 12 hours
    } else if ($maxPercent >= 90) {
        return DAYSECS; // 24 hours
    }
    return 7 * DAYSECS; // 1 week
}

/**
 * Send unified notification email.
 *
 * @param object $info Usage information object
 * @return bool True if notification was sent successfully
 */
function usage_monitor_send_notification($info) {
    global $CFG;
    
    // Prepare email addresses
    $reportconfig = get_config('report_usage_monitor');
    $toemail = generate_email_user($reportconfig->email);
    $fromemail = generate_email_user(
        $CFG->noreplyaddress,
        format_string($CFG->supportname)
    );

    // Generate email content
    $subject = get_string('subjectemail_unified', 'report_usage_monitor', $info);
    $messagehtml = get_string('messagehtml_unified', 'report_usage_monitor', $info);
    $messagetext = html_to_text($messagehtml);

    // Temporarily disable noemailever if set
    $prev_noemailever = $CFG->noemailever ?? false;
    $CFG->noemailever = false;

    // Send email
    $success = email_to_user(
        $toemail,
        $fromemail,
        $subject,
        $messagetext,
        $messagehtml,
        '', // attachment
        '', // attachname
        true, // usetrueaddress
        $fromemail->email // replyto
    );

    // Restore noemailever setting
    $CFG->noemailever = $prev_noemailever;

    // Update last notification time if successful
    if ($success) {
        set_config('last_notification_time', time(), 'report_usage_monitor');
    }

    return $success;
}

/**
 * Generate HTML table for notification email.
 *
 * @param int|null $disk_usage Optional disk usage in bytes
 * @param float|null $disk_percent Optional disk usage percentage
 * @param int|null $quotadisk Optional disk quota in bytes
 * @return string HTML table
 */
function notification_table($disk_usage = null, $disk_percent = null, $quotadisk = null) {
    global $DB;

    $table = '<h2>' . get_string('lastusers', 'report_usage_monitor') . '</h2>
    <table border="1" style="border-collapse: collapse; width: 50%;">
        <tr>
            <th style="padding: 8px; background-color: #f2f2f2;">' . 
                get_string('date', 'report_usage_monitor') . 
            '</th>
            <th style="padding: 8px; background-color: #f2f2f2;">' . 
                get_string('usersquantity', 'report_usage_monitor') . 
            '</th>
        </tr>';

    // Get last 10 days data
    $userdaily = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $records = $DB->get_records_sql($userdaily);

    foreach ($records as $log) {
        $table .= '<tr>
            <td style="padding: 8px;">' . $log->fecha . '</td>
            <td style="padding: 8px;">' . $log->conteo_accesos_unicos . '</td>
        </tr>';
    }

    // Add disk usage information if provided
    if ($disk_usage !== null && $disk_percent !== null && $quotadisk !== null) {
        $table .= '</table><br>
        <h2>' . get_string('diskusage', 'report_usage_monitor') . '</h2>
        <table border="1" style="border-collapse: collapse; width: 50%;">
            <tr>
                <th style="padding: 8px; background-color: #f2f2f2;">' . 
                    get_string('totaldiskusage', 'report_usage_monitor') . 
                '</th>
                <td style="padding: 8px;">' . 
                    display_size($disk_usage) . ' (' . 
                    round($disk_percent, 2) . '%)' . 
                '</td>
            </tr>
            <tr>
                <th style="padding: 8px; background-color: #f2f2f2;">' . 
                    get_string('diskquota', 'report_usage_monitor') . 
                '</th>
                <td style="padding: 8px;">' . display_size($quotadisk) . '</td>
            </tr>';
    }

    $table .= '</table>';
    return $table;
}

/**
 * Calculate percentage of usage against threshold.
 *
 * @param int $current_value Current value (users, disk usage, etc.)
 * @param int $threshold Maximum threshold value
 * @return float Usage percentage
 */
function calculate_threshold_percentage($current_value, $threshold) {
    if ($threshold == 0) {
        return 0;
    }
    return ($current_value / $threshold) * 100;
}

/**
 * Check environment status and adjust task frequencies.
 *
 * @return object Environment status information
 */
function usage_monitor_check_environment() {
    global $CFG;
    
    $status = new stdClass();
    
    // Check shell_exec availability
    $disabledfunctions = explode(',', (string)ini_get('disable_functions'));
    $disabledfunctions = array_map('trim', $disabledfunctions);
    $status->shell_exec_available = function_exists('shell_exec') && 
                                  !in_array('shell_exec', $disabledfunctions, true);

    // Check du command
    $status->du_command_available = false;
    if ($status->shell_exec_available && PHP_OS === 'Linux') {
        $pathToDu = shell_exec('which du');
        $pathToDu = trim($pathToDu ?? '');
        
        if (!empty($pathToDu) && is_executable($pathToDu)) {
            $status->du_command_available = true;
            $status->du_path = $pathToDu;
        }
    }
    
    return $status;
}

/**
 * Update disk_usage task frequency based on environment.
 *
 * @param object $env Environment status from usage_monitor_check_environment()
 * @return bool True if task was updated
 */
function usage_monitor_update_task_frequency($env) {
    global $DB;
    
    $classname = 'report_usage_monitor\task\disk_usage';
    $record = $DB->get_record('task_scheduled', ['classname' => $classname]);
    
    if ($record) {
        // Set frequency based on du availability
        $record->hour = $env->du_command_available ? '*/6' : '12';
        return $DB->update_record('task_scheduled', $record);
    }
    
    return false;
}