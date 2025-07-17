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
 * Enhanced Usage Monitor Report main dashboard - Simplified and optimized
 *
 * @package    report_usage_monitor
 * @copyright  2025 Alonso Arias <alonso@aloarias.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

// Setup page
admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'admin']);

// Check for AJAX requests
$ajax = optional_param('ajax', 0, PARAM_INT);
$force_refresh = optional_param('refresh', 0, PARAM_INT);

if ($ajax) {
    // Return JSON data for AJAX requests
    header('Content-Type: application/json');
    
    try {
        $stats = usage_monitor_manager::get_usage_statistics($force_refresh);
        
        $response = [
            'success' => true,
            'disk_percentage' => round($stats->disk->percentage, 2),
            'user_percentage' => round($stats->users->percentage, 2),
            'health_score' => $stats->health_score['overall'],
            'timestamp' => time()
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Get comprehensive statistics using centralized manager
try {
    $stats = usage_monitor_manager::get_usage_statistics($force_refresh);
} catch (Exception $e) {
    debugging('Error getting usage statistics: ' . $e->getMessage(), DEBUG_DEVELOPER);
    
    // Create minimal stats object for error handling
    $stats = new stdClass();
    $stats->disk = (object)['percentage' => 0, 'current_readable' => '0 B', 'quota_readable' => '0 B', 'warning_class' => 'success'];
    $stats->users = (object)['percentage' => 0, 'current' => 0, 'threshold' => 100, 'warning_class' => 'success'];
    $stats->health_score = ['overall' => 0, 'status' => 'unknown'];
    $stats->recommendations = [];
    $stats->directories = [];
    $stats->courses = [];
}

// Get enhanced user activity data
$daily_users = usage_monitor_manager::get_daily_users(10);
$formatted_daily_users = [];

foreach ($daily_users as $record) {
    $formatted_daily_users[] = [
        'date' => $record->date_key,
        'users' => (int)$record->user_count,
        'percentage' => $stats->users->threshold > 0 ? 
                       min(100, round(($record->user_count / $stats->users->threshold) * 100, 1)) : 0,
        'actions' => (int)($record->total_actions ?? 0)
    ];
}

// Prepare enhanced chart data
$chart_data = [
    'disk_distribution' => [
        'labels' => array_keys($stats->directories),
        'data' => array_column($stats->directories, 'percentage')
    ],
    'daily_users' => $formatted_daily_users,
    'health_components' => $stats->health_score['components'] ?? []
];

// Output page
echo $OUTPUT->header();

// Show disclaimer
echo '<div class="alert alert-info mb-3 text-center small">';
echo get_string('exclusivedisclaimer', 'report_usage_monitor');
echo '</div>';

// Show refresh button for admins
if (has_capability('report/usage_monitor:manage', context_system::instance())) {
    echo '<div class="mb-3 text-end">';
    echo '<a href="' . new moodle_url('/report/usage_monitor/index.php', ['refresh' => 1]) . '" class="btn btn-outline-primary btn-sm">';
    echo '<i class="fa fa-refresh"></i> ' . get_string('refresh', 'core');
    echo '</a>';
    echo '</div>';
}

// Include the enhanced dashboard template
require_once($CFG->dirroot . '/report/usage_monitor/templates/dashboard.php');

echo $OUTPUT->footer();