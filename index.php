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
$viewtab = optional_param('view', 'usage_monitor', PARAM_ALPHA);
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
echo $OUTPUT->tabtree($tabs, $viewtab);

// Get plugin configuration.
$reportconfig = get_config('report_usage_monitor');

// Display message for exclusive use.
echo html_writer::tag(
    'div',
    get_string('exclusivedisclaimer', 'report_usage_monitor'),
    ['class' => 'alert alert-warning']
);

// Process and display content based on selected tab.
if ($viewtab === 'userstopnum') {
    // USERS TAB.
    echo $OUTPUT->heading(get_string('userstopnum', 'report_usage_monitor'));

    // Build subtabs for user views.
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

    // Show subtabs.
    echo $OUTPUT->tabtree($subtabs, $subview);

    // Get data for last 10 days.
    $last10sql = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $last10records = $DB->get_records_sql($last10sql);

    // Prepare data arrays for table/chart.
    $labelsLast10 = [];
    $dataLast10 = [];
    foreach ($last10records as $record) {
        $labelsLast10[] = $record->fecha;
        $dataLast10[] = (int)$record->conteo_accesos_unicos;
    }

    // Display data according to selected subview.
    echo $OUTPUT->heading(get_string('lastusers', 'report_usage_monitor'));
    
    if ($subview === 'usertable') {
        // Display as table.
        $table = new html_table();
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
        // Display as chart.
        $chart = new \core\chart_line();
        $chart->set_smooth(true);
        $chart->set_labels($labelsLast10);
        
        $series = new \core\chart_series(
            get_string('usersquantity', 'report_usage_monitor'),
            $dataLast10
        );
        $chart->add_series($series);
        
        echo $OUTPUT->render($chart);
    }

    // Display Top 10 Users section.
    echo $OUTPUT->heading(get_string('topuser', 'report_usage_monitor'));
    
    $topsql = report_user_daily_top_sql(get_string('dateformat', 'report_usage_monitor'));
    $toprecords = $DB->get_records_sql($topsql);

    $table = new html_table();
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

} else if ($viewtab === 'diskusage') {
    // DISK USAGE TAB.
    echo $OUTPUT->heading(get_string('diskusage', 'report_usage_monitor'));

    // Show last calculation time.
    $lastcalc = !empty($reportconfig->lastexecutioncalculate) 
        ? userdate($reportconfig->lastexecutioncalculate)
        : get_string('notcalculatedyet', 'report_usage_monitor');
    
    echo html_writer::span(
        get_string('lastexecutioncalculate', 'report_usage_monitor', $lastcalc)
    );

    // Calculate actual usage.
    $usedFS = (float)($reportconfig->totalusagereadable ?? 0);
    $usedDB = (float)($reportconfig->totalusagereadabledb ?? 0);
    $usedTotalBytes = $usedFS + $usedDB;

    // Convert quota from GB to bytes.
    $disk_quota_gb = (float)get_config('report_usage_monitor', 'disk_quota');
    $disk_quota_bytes = $disk_quota_gb * 1024 * 1024 * 1024;

    // Format sizes for display.
    $usedTotalStr = display_size($usedTotalBytes);
    $dbSizeStr = display_size($usedDB);

    // Calculate usage percentages.
    $usedPercent = $disk_quota_bytes > 0 ? ($usedTotalBytes / $disk_quota_bytes) * 100 : 0;
    $freePercent = 100 - $usedPercent;

    // Display disk usage table.
    $table = new html_table();
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
    echo html_writer::table($table);

    // Display disk usage chart.
    $chart = new \core\chart_pie();
    $chart->set_title(get_string('diskusage', 'report_usage_monitor'));

    // Calculate values in GB.
    $usedGB = round($usedTotalBytes / (1024 * 1024 * 1024), 2);
    $freeGB = max(0, $disk_quota_gb - $usedGB);

    // Prepare chart data.
    $labels = [
        get_string('sizeusage', 'report_usage_monitor') . " ({$usedGB} GB)",
        get_string('avalilabledisk', 'report_usage_monitor') . " ({$freeGB} GB)"
    ];
    $data = [$usedGB, $freeGB];

    $series = new \core\chart_series(
        get_string('diskusage', 'report_usage_monitor'),
        $data
    );
    $chart->add_series($series);
    $chart->set_labels($labels);

    echo $OUTPUT->render($chart);
}

// Display footer information.
echo get_string('reportinfotext', 'report_usage_monitor');
echo $OUTPUT->footer();