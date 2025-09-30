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
        'trashdir' => 0,
        'others' => 0
    ];
}

// Calculate directory sizes in GB.
$databasegb = round(($diranalysis['database'] ?? 0) / (1024 * 1024 * 1024), 2);
$filedirgb = round(($diranalysis['filedir'] ?? 0) / (1024 * 1024 * 1024), 2);
$cachegb = round(($diranalysis['cache'] ?? 0) / (1024 * 1024 * 1024), 2);
$trashdirgb = round(($diranalysis['trashdir'] ?? 0) / (1024 * 1024 * 1024), 2);
$othersgb = round(($diranalysis['others'] ?? 0) / (1024 * 1024 * 1024), 2);

// Data for doughnut chart.
$doughnutlabels = [
    get_string('database', 'report_usage_monitor'),
    get_string('files_dir', 'report_usage_monitor'),
    get_string('cache', 'report_usage_monitor'),
    get_string('trashdir', 'report_usage_monitor'),
    get_string('others', 'report_usage_monitor')
];
$doughnutdata = [$databasegb, $filedirgb, $cachegb, $trashdirgb, $othersgb];

// Get largest courses.
$largestcourses = json_decode($reportconfig->largest_courses ?? '[]');
if (empty($largestcourses)) {
    $largestcourses = helper::get_largest_courses(5);
}

// Get last 10 days of user data using helper.
$tendaysago = time() - (10 * 24 * 60 * 60);
$yesterday = time() - (24 * 60 * 60);
$userdailyrecords = helper::get_user_daily_records($tendaysago, $yesterday);

$last10dayslabels = [];
$last10daysdata = [];
$last10daysdataraw = [];

foreach ($userdailyrecords as $record) {
    $last10dayslabels[] = $record->day;
    $percent = helper::calculate_percentage($record->usercount, $maxusersthreshold);
    $last10daysdata[] = min(100, $percent);
    $last10daysdataraw[] = (int)$record->usercount;
}

// Get top daily users using helper.
$topusers = helper::get_top_daily_users(10);

// Get system info using helper.
$systeminfo = helper::get_system_info();

// Get disk usage history using helper.
$diskhistory = helper::get_usage_history(30, 'disk');

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
    <!-- Threshold Legend Card -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <strong><?php echo get_string('threshold_conventions', 'report_usage_monitor'); ?>:</strong>
                        </div>
                        <div class="col">
                            <span class="badge bg-success me-2">
                                <i class="fa fa-check-circle"></i> <?php echo get_string('status_normal', 'report_usage_monitor'); ?> (&lt; 70%)
                            </span>
                            <span class="badge bg-warning me-2">
                                <i class="fa fa-exclamation-triangle"></i> <?php echo get_string('status_warning', 'report_usage_monitor'); ?> (70% - 90%)
                            </span>
                            <span class="badge bg-danger">
                                <i class="fa fa-times-circle"></i> <?php echo get_string('status_critical', 'report_usage_monitor'); ?> (&gt; 90%)
                            </span>
                        </div>
                        <div class="col-auto">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i> <?php echo get_string('threshold_info', 'report_usage_monitor'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i> 
                                <?php echo get_string('total_usage', 'report_usage_monitor'); ?>: <?php echo $diskusagegb; ?> GB 
                                (<?php echo round($diskpercent, 1); ?>% <?php echo get_string('of_limit', 'report_usage_monitor'); ?>)
                            </small>
                        </div>
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
                                    'trashdir' => get_string('trashdir', 'report_usage_monitor'),
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
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $percent; ?>%"
                                                 aria-valuenow="<?php echo $percent; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $percent; ?>%
                                            </div>
                                        </div>
                                    </td>
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
                    <canvas id="chartjs-disk-history" style="height: 400px;"></canvas>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> 
                            <?php echo get_string('chart_info', 'report_usage_monitor'); ?>
                        </small>
                    </div>
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
                                            <th><?php echo get_string('status', 'report_usage_monitor'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($userdailyrecords)): ?>
                                            <?php foreach ($userdailyrecords as $record): ?>
                                                <?php
                                                $percent = helper::calculate_percentage($record->usercount, $maxusersthreshold);
                                                $class = '';
                                                $badge = '';
                                                if ($percent >= 90) {
                                                    $class = 'text-danger';
                                                    $badge = '<span class="badge bg-danger">' . get_string('status_critical', 'report_usage_monitor') . '</span>';
                                                } else if ($percent >= 70) {
                                                    $class = 'text-warning';
                                                    $badge = '<span class="badge bg-warning">' . get_string('status_warning', 'report_usage_monitor') . '</span>';
                                                } else {
                                                    $badge = '<span class="badge bg-success">' . get_string('status_normal', 'report_usage_monitor') . '</span>';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo $record->day; ?></td>
                                                    <td><?php echo $record->usercount; ?></td>
                                                    <td class="<?php echo $class; ?>"><?php echo round($percent, 1); ?>%</td>
                                                    <td><?php echo $badge; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">
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
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="fa fa-info-circle"></i> 
                                        <?php echo get_string('chart_info', 'report_usage_monitor'); ?>
                                    </small>
                                </div>
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
                                <th><?php echo get_string('status', 'report_usage_monitor'); ?></th>
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
                                    $badge = '';
                                    if ($percent >= 90) {
                                        $class = 'text-danger';
                                        $badge = '<span class="badge bg-danger">' . get_string('status_critical', 'report_usage_monitor') . '</span>';
                                    } else if ($percent >= 70) {
                                        $class = 'text-warning';
                                        $badge = '<span class="badge bg-warning">' . get_string('status_warning', 'report_usage_monitor') . '</span>';
                                    } else {
                                        $badge = '<span class="badge bg-success">' . get_string('status_normal', 'report_usage_monitor') . '</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $date; ?></td>
                                        <td><?php echo $record->usercount; ?></td>
                                        <td class="<?php echo $class; ?>"><?php echo round($percent, 1); ?>%</td>
                                        <td><?php echo $badge; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">
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
                                    <div class="h5"><?php echo $systeminfo->moodlerelease; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted">
                                        <?php echo get_string('total_courses', 'report_usage_monitor'); ?>
                                    </div>
                                    <div class="h5"><?php echo $systeminfo->totalcourses; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted">
                                        <?php echo get_string('backup_per_course', 'report_usage_monitor'); ?>
                                    </div>
                                    <div class="h5"><?php echo $systeminfo->backupmaxkept; ?></div>
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
                                        <?php echo $systeminfo->activeusers; ?>/<?php echo $systeminfo->suspendedusers; ?>
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
                                <li><?php echo get_string('tip_backups', 'report_usage_monitor', $systeminfo->backupmaxkept); ?></li>
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
                    backgroundColor: ["#007bff", "#28a745", "#ffc107", "#17a2b8", "#6c757d"],
                    borderColor: "#ffffff",
                    borderWidth: 2,
                    hoverBorderWidth: 3,
                    hoverOffset: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => ({
                                    text: label + ' (' + ((data.datasets[0].data[i] / total) * 100).toFixed(1) + '%)',
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: '#fff',
                                    lineWidth: 2,
                                    hidden: false,
                                    index: i
                                }));
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value.toFixed(2) + ' GB (' + percentage + '%)';
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10
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
        const usersData = <?php echo json_encode($last10daysdata); ?>;
        const usersAvg = usersData.reduce((a, b) => a + b, 0) / usersData.length;
        
        new Chart(last10Ctx, {
            type: "line",
            data: {
                labels: <?php echo json_encode($last10dayslabels); ?>,
                datasets: [
                    {
                        label: "<?php echo get_string('users_percentage', 'report_usage_monitor'); ?>",
                        fill: true,
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        borderColor: "#007bff",
                        data: usersData,
                        tension: 0.3,
                        pointBackgroundColor: function(context) {
                            const value = context.parsed.y;
                            if (value < 70) return '#28a745';
                            if (value < 90) return '#ffc107';
                            return '#dc3545';
                        },
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: "<?php echo get_string('warning70', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#ffc107",
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        data: Array(<?php echo count($last10dayslabels); ?>).fill(70),
                        pointHoverRadius: 0
                    },
                    {
                        label: "<?php echo get_string('critical90', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#dc3545",
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        data: Array(<?php echo count($last10dayslabels); ?>).fill(90),
                        pointHoverRadius: 0
                    },
                    {
                        label: "<?php echo get_string('limit100', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#333333",
                        borderDash: [10, 5],
                        borderWidth: 3,
                        pointRadius: 0,
                        data: Array(<?php echo count($last10dayslabels); ?>).fill(100),
                        pointHoverRadius: 0
                    },
                    {
                        label: "<?php echo get_string('average', 'report_usage_monitor'); ?> (" + usersAvg.toFixed(1) + "%)",
                        fill: false,
                        borderColor: "rgba(128, 128, 128, 0.5)",
                        borderDash: [3, 3],
                        borderWidth: 1,
                        pointRadius: 0,
                        data: Array(<?php echo count($last10dayslabels); ?>).fill(usersAvg),
                        pointHoverRadius: 0
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + "%";
                            },
                            stepSize: 10
                        },
                        grid: {
                            drawBorder: false,
                            color: function(context) {
                                if (context.tick.value === 70) return 'rgba(255, 193, 7, 0.2)';
                                if (context.tick.value === 90) return 'rgba(220, 53, 69, 0.2)';
                                if (context.tick.value === 100) return 'rgba(51, 51, 51, 0.3)';
                                return 'rgba(0, 0, 0, 0.1)';
                            }
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 11
                            },
                            generateLabels: function(chart) {
                                return chart.data.datasets.map((dataset, i) => ({
                                    text: dataset.label,
                                    fillStyle: dataset.borderColor,
                                    strokeStyle: dataset.borderColor,
                                    lineWidth: dataset.borderWidth,
                                    lineDash: dataset.borderDash,
                                    hidden: !chart.isDatasetVisible(i),
                                    index: i,
                                    datasetIndex: i
                                }));
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const chart = legend.chart;
                            const meta = chart.getDatasetMeta(index);
                            
                            // Toggle visibility
                            meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : !meta.hidden;
                            chart.update();
                        }
                    },
                    tooltip: {
                        filter: function(tooltipItem) {
                            return tooltipItem.datasetIndex === 0;
                        },
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    const raw = <?php echo json_encode($last10daysdataraw); ?>;
                                    const percent = context.parsed.y;
                                    const users = raw[context.dataIndex];
                                    const status = percent < 70 ? '<?php echo get_string('status_normal', 'report_usage_monitor'); ?>' : 
                                                  (percent < 90 ? '<?php echo get_string('status_warning', 'report_usage_monitor'); ?>' : 
                                                  '<?php echo get_string('status_critical', 'report_usage_monitor'); ?>');
                                    return [
                                        '<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>: ' + users,
                                        '<?php echo get_string('percentage', 'report_usage_monitor'); ?>: ' + percent.toFixed(1) + '%',
                                        '<?php echo get_string('status', 'report_usage_monitor'); ?>: ' + status
                                    ];
                                }
                                return null;
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        displayColors: false
                    }
                }
            }
        });
    }
    <?php endif; ?>

    // Disk History Chart - Similar to Users Chart
    <?php if (!empty($diskhistorylabels)): ?>
    var diskHistoryCtx = document.getElementById("chartjs-disk-history");
    if (diskHistoryCtx) {
        const diskData = <?php echo json_encode($diskhistorydata); ?>;
        const diskAvg = diskData.reduce((a, b) => a + b, 0) / diskData.length;
        
        new Chart(diskHistoryCtx, {
            type: "line",
            data: {
                labels: <?php echo json_encode($diskhistorylabels); ?>,
                datasets: [
                    {
                        label: "<?php echo get_string('disk_usage_percentage', 'report_usage_monitor'); ?>",
                        fill: true,
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        borderColor: "#007bff",
                        data: diskData,
                        tension: 0.3,
                        pointBackgroundColor: function(context) {
                            const value = context.parsed.y;
                            if (value < 70) return '#28a745';
                            if (value < 90) return '#ffc107';
                            return '#dc3545';
                        },
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: "<?php echo get_string('warning70', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#ffc107",
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        data: Array(diskData.length).fill(70),
                        pointHoverRadius: 0
                    },
                    {
                        label: "<?php echo get_string('critical90', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#dc3545",
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 0,
                        data: Array(diskData.length).fill(90),
                        pointHoverRadius: 0
                    },
                    {
                        label: "<?php echo get_string('limit100', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#333333",
                        borderDash: [10, 5],
                        borderWidth: 3,
                        pointRadius: 0,
                        data: Array(diskData.length).fill(100),
                        pointHoverRadius: 0
                    },
                    {
                        label: "<?php echo get_string('average', 'report_usage_monitor'); ?> (" + diskAvg.toFixed(1) + "%)",
                        fill: false,
                        borderColor: "rgba(128, 128, 128, 0.5)",
                        borderDash: [3, 3],
                        borderWidth: 1,
                        pointRadius: 0,
                        data: Array(diskData.length).fill(diskAvg),
                        pointHoverRadius: 0
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) { 
                                return value + "%"; 
                            },
                            stepSize: 10
                        },
                        grid: {
                            drawBorder: false,
                            color: function(context) {
                                if (context.tick.value === 70) return 'rgba(255, 193, 7, 0.2)';
                                if (context.tick.value === 90) return 'rgba(220, 53, 69, 0.2)';
                                if (context.tick.value === 100) return 'rgba(51, 51, 51, 0.3)';
                                return 'rgba(0, 0, 0, 0.1)';
                            }
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 11
                            },
                            generateLabels: function(chart) {
                                return chart.data.datasets.map((dataset, i) => ({
                                    text: dataset.label,
                                    fillStyle: dataset.borderColor,
                                    strokeStyle: dataset.borderColor,
                                    lineWidth: dataset.borderWidth,
                                    lineDash: dataset.borderDash,
                                    hidden: !chart.isDatasetVisible(i),
                                    index: i,
                                    datasetIndex: i
                                }));
                            }
                        },
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const chart = legend.chart;
                            const meta = chart.getDatasetMeta(index);
                            
                            // Toggle visibility
                            meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : !meta.hidden;
                            chart.update();
                        }
                    },
                    tooltip: {
                        filter: function(tooltipItem) {
                            return tooltipItem.datasetIndex === 0;
                        },
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    const value = context.parsed.y;
                                    const status = value < 70 ? '<?php echo get_string('status_normal', 'report_usage_monitor'); ?>' : 
                                                  (value < 90 ? '<?php echo get_string('status_warning', 'report_usage_monitor'); ?>' : 
                                                  '<?php echo get_string('status_critical', 'report_usage_monitor'); ?>');
                                    return [
                                        context.dataset.label + ': ' + value.toFixed(1) + '%',
                                        '<?php echo get_string('status', 'report_usage_monitor'); ?>: ' + status
                                    ];
                                }
                                return null;
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 10,
                        displayColors: false
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
.progress { position: relative; }
.progress-bar { transition: width 0.6s ease; }
canvas { max-height: 400px; }
.badge { font-weight: 500; }
.card-header .badge { font-size: 0.875rem; }
.table .badge { font-size: 0.75rem; }
.nav-tabs .nav-link { font-weight: 500; }
.nav-tabs .nav-link.active { 
    color: #007bff;
    border-color: #dee2e6 #dee2e6 #fff;
}
/* Chart legend styling */
.chart-container {
    position: relative;
    height: 400px;
}
/* Ensure legend items are clickable */
canvas {
    user-select: none;
}
/* Legend hover effect */
.chart-legend-item:hover {
    opacity: 0.7;
    cursor: pointer;
}
</style>

<?php
echo $OUTPUT->footer();