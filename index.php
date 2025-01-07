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
 * Main page for the Usage Monitor Report.
 *
 * This page displays usage statistics including user activity and disk usage.
 * It provides a tabbed interface to view different aspects of the monitoring data.
 *
 * @package     report_usage_monitor
 * @category    report
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

// Setup page access and layout.
admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'report']);

// Get view parameters.
$viewtab = optional_param('view', 'userstopnum', PARAM_ALPHA);
$subview = optional_param('subview', 'usertable', PARAM_ALPHA);

// Define available tabs.
$tabdata = [
    'userstopnum' => '',
    'diskusage'   => ''
];

// Validate selected tab.
if (!array_key_exists($viewtab, $tabdata)) {
    $viewtab = array_keys($tabdata)[0];
}

// Build main navigation tabs.
$tabs = [];
foreach ($tabdata as $tabname => $unused) {
    $tabs[] = new tabobject(
        $tabname,
        new moodle_url($PAGE->url, ['view' => $tabname]),
        get_string($tabname, 'report_usage_monitor')
    );
}

// Print page header and tabs.
echo $OUTPUT->header();
// Display exclusive use message.
echo html_writer::tag(
    'div',
    html_writer::tag('i', '', ['class' => 'fa fa-circle-info fa-fw']) . ' ' . get_string('exclusivedisclaimer', 'report_usage_monitor'),
    ['class' => 'alert alert-info mb-3']
);
echo $OUTPUT->tabtree($tabs, $viewtab);

// Get plugin configuration.
$reportconfig = get_config('report_usage_monitor');

if ($viewtab === 'userstopnum') {
    // Create container for better spacing
    echo html_writer::start_div('container-fluid p-3');

    // Display execution info in cards
    echo html_writer::start_div('row mb-4');
    
    // Last Execution Card
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('last_execution_title', 'report_usage_monitor'), ['class' => 'card-title']);
    $lastExecution = !empty($reportconfig->lastexecution) 
        ? userdate($reportconfig->lastexecution) 
        : get_string('notcalculatedyet', 'report_usage_monitor');
    echo html_writer::tag('p', get_string('lastexecution', 'report_usage_monitor', $lastExecution), ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Today's Users Card
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('today_users_title', 'report_usage_monitor'), ['class' => 'card-title']);
    $totalUsersDaily = !empty($reportconfig->totalusersdaily) 
        ? $reportconfig->totalusersdaily 
        : get_string('notcalculatedyet', 'report_usage_monitor');
    echo html_writer::tag('p', get_string('users_today', 'report_usage_monitor', $totalUsersDaily), ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // End row

    // Maximum Users in 90 Days Section
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h4', get_string('max_userdaily_for_90_days', 'report_usage_monitor'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    
    $table_90days = new html_table();
    $table_90days->attributes['class'] = 'table table-striped table-hover';
    $table_90days->head = [
        get_string('date', 'report_usage_monitor'),
        get_string('usersquantity', 'report_usage_monitor')
    ];
    
    $max90DaysDate = !empty($reportconfig->max_userdaily_for_90_days_date) 
        ? date(get_string('dateformat', 'report_usage_monitor'), $reportconfig->max_userdaily_for_90_days_date) 
        : get_string('notcalculatedyet', 'report_usage_monitor');
    $max90DaysUsers = !empty($reportconfig->max_userdaily_for_90_days_users) 
        ? $reportconfig->max_userdaily_for_90_days_users 
        : get_string('notcalculatedyet', 'report_usage_monitor');
    
    $table_90days->data[] = [$max90DaysDate, $max90DaysUsers];
    echo html_writer::table($table_90days);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Last 10 Days Users with tabs
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h4', get_string('lastusers', 'report_usage_monitor'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    // Build subtabs for view options
    $subtabs = [
        new tabobject(
            'usertable',
            new moodle_url($PAGE->url, ['view' => 'userstopnum', 'subview' => 'usertable']),
            get_string('usertable', 'report_usage_monitor')
        ),
        new tabobject(
            'userchart',
            new moodle_url($PAGE->url, ['view' => 'userstopnum', 'subview' => 'userchart']),
            get_string('userchart', 'report_usage_monitor')
        )
    ];

    echo $OUTPUT->tabtree($subtabs, $subview);

    // Get data for last 10 days
    $last10sql = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $last10records = $DB->get_records_sql($last10sql);

    if ($subview === 'usertable') {
        // Display as table
        $table = new html_table();
        $table->attributes['class'] = 'table table-striped table-hover';
        $table->head = [
            get_string('date', 'report_usage_monitor'),
            get_string('usersquantity', 'report_usage_monitor')
        ];
        foreach ($last10records as $record) {
            $table->data[] = [
                $record->fecha,
                $record->conteo_accesos_unicos
            ];
        }
        echo html_writer::table($table);

    } else if ($subview === 'userchart') {
        // Display as chart
        $chart = new \core\chart_line();
        $chart->set_smooth(true);

        $labels = [];
        $values = [];
        foreach ($last10records as $record) {
            $labels[] = $record->fecha;
            $values[] = (int)$record->conteo_accesos_unicos;
        }

        $chart->set_labels($labels);
        $series = new \core\chart_series(
            get_string('usersquantity', 'report_usage_monitor'),
            $values
        );
        $chart->add_series($series);
        
        echo $OUTPUT->render($chart);
    }

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Top 10 Users section
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h4', get_string('topuser', 'report_usage_monitor'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    $topsql = report_user_daily_top_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $toprecords = $DB->get_records_sql($topsql);
    
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover';
    $table->head = [
        get_string('date', 'report_usage_monitor'),
        get_string('usersquantity', 'report_usage_monitor')
    ];
    
    if (!empty($toprecords)) {
        foreach ($toprecords as $record) {
            $table->data[] = [
                $record->fecha,
                $record->cantidad_usuarios
            ];
        }
    }
    echo html_writer::table($table);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // End container-fluid

} else if ($viewtab === 'diskusage') {
    echo html_writer::start_div('container-fluid p-3');

    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h4', get_string('diskusage', 'report_usage_monitor'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    // Show last calculation time
    $lastcalc = !empty($reportconfig->lastexecutioncalculate) 
        ? userdate($reportconfig->lastexecutioncalculate)
        : get_string('notcalculatedyet', 'report_usage_monitor');
    
    echo html_writer::tag('p', get_string('lastexecutioncalculate', 'report_usage_monitor', $lastcalc), ['class' => 'mb-4']);

    // Calculate actual usage
    $usedFS = (float)($reportconfig->totalusagereadable ?? 0);
    $usedDB = (float)($reportconfig->totalusagereadabledb ?? 0);
    $usedTotalBytes = $usedFS + $usedDB;

    // Convert quota from GB to bytes
    $disk_quota_gb = (float)get_config('report_usage_monitor', 'disk_quota');
    $disk_quota_bytes = $disk_quota_gb * 1024 * 1024 * 1024;

    // Format sizes for display
    $usedTotalStr = display_size($usedTotalBytes);
    $dbSizeStr = display_size($usedDB);

    // Calculate usage percentages
    $usedPercent = $disk_quota_bytes > 0 ? ($usedTotalBytes / $disk_quota_bytes) * 100 : 0;
    $freePercent = 100 - $usedPercent;

    // Display disk usage metrics in cards
    echo html_writer::start_div('row mb-4');
    
    // Total Usage Card
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h5', get_string('sizeusage', 'report_usage_monitor'), ['class' => 'card-title']);
    echo html_writer::tag('p', $usedTotalStr . ' / ' . $disk_quota_gb . ' GB', ['class' => 'card-text h4']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Available Space Card
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h5', get_string('avalilabledisk', 'report_usage_monitor'), ['class' => 'card-title']);
    echo html_writer::tag('p', round($freePercent, 2) . '%', ['class' => 'card-text h4']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Database Size Card
    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('h5', get_string('sizedatabase', 'report_usage_monitor'), ['class' => 'card-title']);
    echo html_writer::tag('p', $dbSizeStr, ['class' => 'card-text h4']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // End row

    // Display disk usage pie chart
    echo html_writer::start_div('mt-4');
    $chart = new \core\chart_pie();
    $chart->set_title(get_string('diskusage', 'report_usage_monitor'));

    // Calculate values in GB
    $usedGB = round($usedTotalBytes / (1024 * 1024 * 1024), 2);
    $freeGB = max(0, $disk_quota_gb - $usedGB);

    // Get color based on usage percentage using the existing function
    $diskMetrics = diskUsagePercentages($usedGB, $disk_quota_gb);
    $usageColor = $diskMetrics['color'];

    // Prepare chart data
    $labels = [
        get_string('sizeusage', 'report_usage_monitor') . " ({$usedGB} GB)",
        get_string('avalilabledisk', 'report_usage_monitor') . " ({$freeGB} GB)"
    ];
    $data = [$usedGB, $freeGB];

    $series = new \core\chart_series(get_string('diskusage', 'report_usage_monitor'), $data);
    
    // Set colors for the chart
    $series->set_colors([$usageColor, '#E0E0E0']); // Color del espacio usado según porcentaje, gris claro para espacio libre

    $chart->add_series($series);
    $chart->set_labels($labels);
    echo $OUTPUT->render($chart);
    echo html_writer::end_div();

    echo html_writer::end_div(); // End card-body
    echo html_writer::end_div(); // End card

    // Disk usage metrics table
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-hover';
    $table->head = [
        get_string('sizeusage', 'report_usage_monitor'),
        get_string('avalilabledisk', 'report_usage_monitor'),
        get_string('sizedatabase', 'report_usage_monitor')
    ];
    $table->data[] = [
        $usedTotalStr . ' / ' . $disk_quota_gb . ' GB',
        round($freePercent, 2) . '%',
        $dbSizeStr
    ];
    
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-header bg-light');
    echo html_writer::tag('h4', get_string('disk_metrics_details', 'report_usage_monitor'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    echo html_writer::table($table);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // End container-fluid
}

// Display footer information
echo html_writer::tag('div', get_string('reportinfotext', 'report_usage_monitor'), ['class' => 'mt-4 text-center text-muted']);
echo $OUTPUT->footer();