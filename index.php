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
 * Usage Monitor Report main dashboard
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'admin']);

// Get comprehensive statistics
$stats = usage_monitor_data_manager::get_usage_statistics();

// Get user activity data for charts
$daily_users = usage_monitor_user_queries::get_daily_users(10);
$top_users = usage_monitor_user_queries::get_top_user_days();

// Format data for JavaScript
$formatted_daily_users = [];
foreach ($daily_users as $record) {
    $formatted_daily_users[] = [
        'date' => is_numeric($record->timestamp_fecha) ? date('d/m/Y', (int)$record->timestamp_fecha) : date('d/m/Y'),
        'users' => (int)$record->conteo_accesos_unicos,
        'percentage' => $stats->users->threshold > 0 ? 
                       min(100, round(($record->conteo_accesos_unicos / $stats->users->threshold) * 100, 1)) : 0
    ];
}

$formatted_top_users = [];
foreach ($top_users as $record) {
    $formatted_top_users[] = [
        'date' => is_numeric($record->timestamp_fecha) ? date('d/m/Y', (int)$record->timestamp_fecha) : date('d/m/Y'),
        'users' => (int)$record->cantidad_usuarios,
        'percentage' => $stats->users->threshold > 0 ? 
                       round(($record->cantidad_usuarios / $stats->users->threshold) * 100, 1) : 0
    ];
}

// Prepare chart data
$chart_data = [
    'disk_distribution' => [
        'labels' => [
            get_string('database', 'report_usage_monitor'),
            get_string('files_dir', 'report_usage_monitor'),
            get_string('cache', 'report_usage_monitor'),
            get_string('others', 'report_usage_monitor')
        ],
        'data' => [
            display_size_in_gb($stats->directories['database'], 2),
            display_size_in_gb($stats->directories['filedir'], 2),
            display_size_in_gb($stats->directories['cache'], 2),
            display_size_in_gb($stats->directories['others'], 2)
        ]
    ],
    'daily_users' => $formatted_daily_users,
    'disk_history' => array_values($stats->history['disk'] ?? [])
];

echo $OUTPUT->header();

echo '<div class="alert alert-info mb-2 text-center small">';
echo get_string('exclusivedisclaimer', 'report_usage_monitor');
echo '</div>';

echo $OUTPUT->heading(get_string('dashboard_title', 'report_usage_monitor'));

// Include the page template
require_once($CFG->dirroot . '/report/usage_monitor/templates/dashboard.php');

echo $OUTPUT->footer();