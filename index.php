<?php
// This file is part of Moodle - https://moodle.org/
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
 * Usage Monitor Report main dashboard.
 *
 * @package    report_usage_monitor
 * @copyright  2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use report_usage_monitor\helper;

// Page setup.
admin_externalpage_setup('report_usage_monitor');

// Check permissions.
$context = context_system::instance();
require_capability('report/usage_monitor:view', $context);

// Get report configuration.
$reportconfig = get_config('report_usage_monitor');

// Process disk usage data.
$diskusagebytes = (int)($reportconfig->totalusagereadable ?? 0) + 
                  (int)($reportconfig->totalusagereadabledb ?? 0);
$quotadiskbytes = ((int)($reportconfig->disk_quota ?? 10) * 1024 * 1024 * 1024);

$diskusagegb = !empty($reportconfig->disk_usage_gb) ? 
               $reportconfig->disk_usage_gb : 
               round($diskusagebytes / (1024 * 1024 * 1024), 2);
$quotadiskgb = !empty($reportconfig->quotadisk_gb) ? 
               $reportconfig->quotadisk_gb : 
               round($quotadiskbytes / (1024 * 1024 * 1024), 2);

$diskpercent = !empty($reportconfig->disk_percent) ? 
               (float)$reportconfig->disk_percent : 
               helper::calculate_percentage($diskusagebytes, $quotadiskbytes);

$diskwarningclass = !empty($reportconfig->disk_warning_class) ? 
                    $reportconfig->disk_warning_class : 
                    (($diskpercent < 70) ? 'bg-success' : 
                     (($diskpercent < 90) ? 'bg-warning' : 'bg-danger'));

// Process user data.
$userstoday = (int)($reportconfig->totalusersdaily ?? 0);
$maxusersthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
$userspercent = !empty($reportconfig->users_percent) ? 
                (float)$reportconfig->users_percent : 
                helper::calculate_percentage($userstoday, $maxusersthreshold);

$userswarningclass = !empty($reportconfig->users_warning_class) ? 
                     $reportconfig->users_warning_class : 
                     (($userspercent < 70) ? 'bg-success' : 
                      (($userspercent < 90) ? 'bg-warning' : 'bg-danger'));

// Format last execution times.
$lastexecdisk = helper::validate_timestamp($reportconfig->lastexecutioncalculate ?? 0);
$lastexecdisk = $lastexecdisk ? userdate($lastexecdisk, get_string('strftimedatetimeshort')) : 
                get_string('notcalculatedyet', 'report_usage_monitor');

$lastexecusers = helper::validate_timestamp($reportconfig->lastexecution ?? 0);
$lastexecusers = $lastexecusers ? userdate($lastexecusers, get_string('strftimedatetimeshort')) : 
                 get_string('notcalculatedyet', 'report_usage_monitor');

// Process 90-day maximum data.
$max90daysusers = $reportconfig->max_userdaily_for_90_days_users ?? 
                  get_string('notcalculatedyet', 'report_usage_monitor');
$max90daysdate = helper::validate_timestamp($reportconfig->max_userdaily_for_90_days_date ?? 0);
$max90daysdate = $max90daysdate ? userdate($max90daysdate, get_string('strftimedate')) : 
                 get_string('notcalculatedyet', 'report_usage_monitor');

$lastcalc90days = helper::validate_timestamp($reportconfig->lastexecutioncalculateusers90days ?? 0);
$lastcalc90days = $lastcalc90days ? userdate($lastcalc90days, get_string('strftimedatetimeshort')) : 
                  get_string('notcalculatedyet', 'report_usage_monitor');

// Process directory analysis.
$diranalysis = json_decode($reportconfig->dir_analysis ?? '{}', true);
if (empty($diranalysis) || !is_array($diranalysis)) {
    $diranalysis = [
        'database' => 0,
        'filedir' => 0,
        'cache' => 0,
        'others' => 0
    ];
}

// Calculate directory sizes in GB.
$databasegb = round(($diranalysis['database'] ?? 0) / (1024 * 1024 * 1024), 2);
$filedirgb = round(($diranalysis['filedir'] ?? 0) / (1024 * 1024 * 1024), 2);
$cachegb = round(($diranalysis['cache'] ?? 0) / (1024 * 1024 * 1024), 2);
$othersgb = round(($diranalysis['others'] ?? 0) / (1024 * 1024 * 1024), 2);

// Data for doughnut chart.
$doughnutlabels = [
    get_string('database', 'report_usage_monitor'),
    get_string('files_dir', 'report_usage_monitor'),
    get_string('cache', 'report_usage_monitor'),
    get_string('others', 'report_usage_monitor')
];
$doughnutdata = [$databasegb, $filedirgb, $cachegb, $othersgb];

// Get largest courses.
$largestcourses = json_decode($reportconfig->largest_courses ?? '[]');
if (empty($largestcourses)) {
    $largestcourses = helper::get_largest_courses(5);
}

// Get last 10 days of user data.
$tendaysago = time() - (10 * 24 * 60 * 60);
$yesterday = time() - (24 * 60 * 60);

$sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as day,
               COUNT(DISTINCT userid) as usercount
        FROM {logstore_standard_log}
        WHERE action = :action
          AND timecreated BETWEEN :from AND :to
        GROUP BY DATE(FROM_UNIXTIME(timecreated))
        ORDER BY day DESC";

$params = ['action' => 'loggedin', 'from' => $tendaysago, 'to' => $yesterday];
$userdailyrecords = $DB->get_records_sql($sql, $params);

$last10dayslabels = [];
$last10daysdata = [];
$last10daysdataraw = [];

foreach ($userdailyrecords as $record) {
    $last10dayslabels[] = $record->day;
    $percent = helper::calculate_percentage($record->usercount, $maxusersthreshold);
    $last10daysdata[] = min(100, $percent);
    $last10daysdataraw[] = (int)$record->usercount;
}

// Get top daily users.
$topusers = $DB->get_records('report_usage_monitor', null, 'usercount DESC', '*', 0, 10);

// Get system info.
$totalcourses = $DB->count_records('course');
$activeusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
$suspendedusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
$registeredusers = $activeusers + $suspendedusers;
$backupmaxkept = get_config('backup', 'backup_auto_max_kept') ?? 0;

// Get disk usage history (last 30 days).
$monthago = time() - (30 * 24 * 60 * 60);
$sql = "SELECT timecreated, value, percentage 
        FROM {report_usage_monitor_history} 
        WHERE type = :type AND timecreated > :from 
        ORDER BY timecreated ASC";

$diskhistory = $DB->get_records_sql($sql, ['type' => 'disk', 'from' => $monthago]);

$diskhistorylabels = [];
$diskhistorydata = [];

foreach ($diskhistory as $record) {
    $timestamp = helper::validate_timestamp($record->timecreated);
    if ($timestamp) {
        $diskhistorylabels[] = userdate($timestamp, get_string('strftimedate'));
        $diskhistorydata[] = round($record->percentage, 1);
    }
}

// Start output.
echo $OUTPUT->header();

// Alert message.
echo html_writer::div(
    get_string('exclusivedisclaimer', 'report_usage_monitor'),
    'alert alert-info mb-2 text-center small'
);

echo $OUTPUT->heading(get_string('dashboard_title', 'report_usage_monitor'));
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<div class="container-fluid mt-4">
    <!-- Summary Cards Row -->
    <div class="row">
        <!-- Disk Usage Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('diskusage', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $diskwarningclass; ?> rounded-pill">
                        <?php echo round($diskpercent, 1); ?>%
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:25px;">
                        <div class="progress-bar <?php echo $diskwarningclass; ?>"
                             role="progressbar"
                             style="width:<?php echo $diskpercent; ?>%;"
                             aria-valuenow="<?php echo $diskpercent; ?>"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <?php echo round($diskpercent, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h5><?php echo $diskusagegb . ' GB / ' . $quotadiskgb . ' GB'; ?></h5>
                        <p class="text-muted">
                            <?php echo get_string('lastexecutioncalculate', 'report_usage_monitor', $lastexecdisk); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Today Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('users_today_card', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $userswarningclass; ?> rounded-pill">
                        <?php echo round($userspercent, 1); ?>%
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:25px;">
                        <div class="progress-bar <?php echo $userswarningclass; ?>"
                             role="progressbar"
                             style="width:<?php echo $userspercent; ?>%;"
                             aria-valuenow="<?php echo $userspercent; ?>"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <?php echo round($userspercent, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h5><?php echo $userstoday; ?> / <?php echo $maxusersthreshold; ?></h5>
                        <p class="text-muted">
                            <?php echo get_string('lastexecution', 'report_usage_monitor', $lastexecusers); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Max 90 Days Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('max_userdaily_for_90_days', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="display-5">
                        <?php echo $max90daysusers; ?> / <?php echo $maxusersthreshold; ?>
                    </h2>
                    <p class="text-muted mt-2">
                        <?php if ($max90daysdate != get_string('notcalculatedyet', 'report_usage_monitor')): ?>
                            <?php echo get_string('date', 'report_usage_monitor'); ?>: <?php echo $max90daysdate; ?><br>
                        <?php endif; ?>
                        <?php echo get_string('last_calculation', 'report_usage_monitor'); ?>: <?php echo $lastcalc90days; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Distribution and Tables Row -->
    <div class="row">
        <!-- Doughnut Chart -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('disk_usage_distribution', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body" style="position:relative; min-height:400px;">
                    <?php if ($diskusagebytes > 0): ?>
                        <canvas id="chartjs-doughnut"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tables -->
        <div class="col-md-6 mb-4">
            <!-- Directory Usage Table -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('disk_usage_by_directory', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_string('directory', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('size', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('percentage', 'report_usage_monitor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($diranalysis) && $diskusagebytes > 0): ?>
                                <?php
                                $directories = [
                                    'database' => get_string('database', 'report_usage_monitor'),
                                    'filedir' => get_string('files_dir', 'report_usage_monitor'),
                                    'cache' => get_string('cache', 'report_usage_monitor'),
                                    'others' => get_string('others', 'report_usage_monitor'),
                                ];
                                foreach ($directories as $key => $label):
                                    $bytes = (int)($diranalysis[$key] ?? 0);
                                    $gb = round($bytes / (1024 * 1024 * 1024), 2);
                                    $percent = helper::calculate_percentage($bytes, $diskusagebytes);
                                ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><?php echo $gb . ' GB'; ?></td>
                                    <td><?php echo $percent; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Largest Courses Table -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('largest_courses', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_string('course', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('size', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('percentage', 'report_usage_monitor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($largestcourses)): ?>
                                <?php foreach ($largestcourses as $course): ?>
                                    <?php
                                    $course = (object)$course;
                                    $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo $courseurl; ?>">
                                                <?php echo format_string($course->fullname) . ' (' . $course->shortname . ')'; ?>
                                            </a>
                                        </td>
                                        <td><?php echo display_size($course->totalsize ?? $course->filesize ?? 0); ?></td>
                                        <td><?php echo round($course->percentage ?? 0, 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($diskhistorylabels)): ?>
    <!-- Disk Usage History -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('disk_usage_history', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="chartjs-disk-history" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Last 10 Days Users -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('lastusers', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabla10" role="tab">
                                <?php echo get_string('usertable', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#grafica10" role="tab">
                                <?php echo get_string('userchart', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="tabla10" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_string('date', 'report_usage_monitor'); ?></th>
                                            <th><?php echo get_string('usersquantity', 'report_usage_monitor'); ?></th>
                                            <th><?php echo get_string('percentage', 'report_usage_monitor'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($userdailyrecords)): ?>
                                            <?php foreach ($userdailyrecords as $record): ?>
                                                <?php
                                                $percent = helper::calculate_percentage($record->usercount, $maxusersthreshold);
                                                $class = '';
                                                if ($percent >= 90) {
                                                    $class = 'text-danger';
                                                } else if ($percent >= 70) {
                                                    $class = 'text-warning';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo $record->day; ?></td>
                                                    <td><?php echo $record->usercount; ?></td>
                                                    <td class="<?php echo $class; ?>"><?php echo round($percent, 1); ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">
                                                    <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="grafica10" role="tabpanel">
                            <?php if (!empty($last10dayslabels)): ?>
                                <canvas id="chartjs-last10days" style="height: 400px;"></canvas>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 Daily Users -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('topuser', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_string('date', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('usersquantity', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('percentage', 'report_usage_monitor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topusers)): ?>
                                <?php foreach ($topusers as $record): ?>
                                    <?php
                                    $timestamp = helper::validate_timestamp($record->timecreated);
                                    $date = $timestamp ? userdate($timestamp, get_string('strftimedate')) : '-';
                                    $percent = helper::calculate_percentage($record->usercount, $maxusersthreshold);
                                    $class = '';
                                    if ($percent >= 90) {
                                        $class = 'text-danger';
                                    } else if ($percent >= 70) {
                                        $class = 'text-warning';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $date; ?></td>
                                        <td><?php echo $record->usercount; ?></td>
                                        <td class="<?php echo $class; ?>"><?php echo round($percent, 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info and Recommendations -->
    <div class="row">
        <!-- System Info -->
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('system_info', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted">
                                        <?php echo get_string('moodle_version', 'report_usage_monitor'); ?>
                                    </div>
                                    <div class="h5"><?php echo $CFG->release; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted">
                                        <?php echo get_string('total_courses', 'report_usage_monitor'); ?>
                                    </div>
                                    <div class="h5"><?php echo $totalcourses; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted">
                                        <?php echo get_string('backup_per_course', 'report_usage_monitor'); ?>
                                    </div>
                                    <div class="h5"><?php echo $backupmaxkept; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted">
                                        <?php echo get_string('registered_users', 'report_usage_monitor'); ?>
                                    </div>
                                    <div class="h5">
                                        <?php echo $activeusers; ?>/<?php echo $suspendedusers; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo get_string('active_users', 'report_usage_monitor'); ?>/<?php echo get_string('suspended_users', 'report_usage_monitor'); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('recommendations', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($diskpercent > 70): ?>
                        <div class="alert alert-<?php echo ($diskpercent > 90) ? 'danger' : 'warning'; ?>">
                            <h6><?php echo get_string('space_saving_tips', 'report_usage_monitor'); ?></h6>
                            <ul class="mb-0 small">
                                <li><?php echo get_string('tip_backups', 'report_usage_monitor', $backupmaxkept); ?></li>
                                <li><?php echo get_string('tip_files', 'report_usage_monitor'); ?></li>
                                <li><?php echo get_string('tip_courses', 'report_usage_monitor'); ?></li>
                                <li><?php echo get_string('tip_cache', 'report_usage_monitor'); ?></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i>
                            <?php echo get_string('disk_usage_ok', 'report_usage_monitor'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($userspercent > 70): ?>
                        <div class="alert alert-<?php echo ($userspercent > 90) ? 'danger' : 'warning'; ?>">
                            <h6><?php echo get_string('user_limit_tips', 'report_usage_monitor'); ?></h6>
                            <ul class="mb-0 small">
                                <li><?php echo get_string('tip_user_inactive', 'report_usage_monitor'); ?></li>
                                <li><?php echo get_string('tip_user_limit', 'report_usage_monitor'); ?></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i>
                            <?php echo get_string('user_count_ok', 'report_usage_monitor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Credits -->
<div class="mt-4 text-center text-muted small">
    <?php echo get_string('reportinfotext', 'report_usage_monitor'); ?>
</div>

<!-- Chart.js Initialization -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Doughnut Chart
    <?php if ($diskusagebytes > 0): ?>
    var doughnutCtx = document.getElementById("chartjs-doughnut");
    if (doughnutCtx) {
        new Chart(doughnutCtx, {
            type: "doughnut",
            data: {
                labels: <?php echo json_encode($doughnutlabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($doughnutdata); ?>,
                    backgroundColor: ["#007bff", "#28a745", "#ffc107", "#dee2e6"],
                    borderColor: "transparent"
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' GB';
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Last 10 Days Chart
    <?php if (!empty($last10dayslabels)): ?>
    var last10Ctx = document.getElementById("chartjs-last10days");
    if (last10Ctx) {
        new Chart(last10Ctx, {
            type: "line",
            data: {
                labels: <?php echo json_encode($last10dayslabels); ?>,
                datasets: [
                    {
                        label: "<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>",
                        fill: true,
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        borderColor: "#007bff",
                        data: <?php echo json_encode($last10daysdata); ?>
                    },
                    {
                        label: "<?php echo get_string('warning70', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#ffc107",
                        borderDash: [5, 5],
                        pointRadius: 0,
                        data: Array(<?php echo count($last10dayslabels); ?>).fill(70)
                    },
                    {
                        label: "<?php echo get_string('critical90', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#dc3545",
                        borderDash: [5, 5],
                        pointRadius: 0,
                        data: Array(<?php echo count($last10dayslabels); ?>).fill(90)
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + "%";
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.label === "<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>") {
                                    var raw = <?php echo json_encode($last10daysdataraw); ?>;
                                    return context.dataset.label + ": " + raw[context.dataIndex] + " (" + context.parsed.y + "%)";
                                }
                                return context.dataset.label;
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Disk History Chart
    <?php if (!empty($diskhistorylabels)): ?>
    var diskHistoryCtx = document.getElementById("chartjs-disk-history");
    if (diskHistoryCtx) {
        new Chart(diskHistoryCtx, {
            type: "line",
            data: {
                labels: <?php echo json_encode($diskhistorylabels); ?>,
                datasets: [{
                    label: "<?php echo get_string('percentage_used', 'report_usage_monitor'); ?>",
                    fill: true,
                    backgroundColor: "rgba(0, 123, 255, 0.1)",
                    borderColor: "#007bff",
                    data: <?php echo json_encode($diskhistorydata); ?>,
                    tension: 0.2
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { return value + "%"; }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<style>
.bg-success { color: white; }
.bg-warning { color: white; }
.bg-danger { color: white; }
</style>

<?php
echo $OUTPUT->footer();