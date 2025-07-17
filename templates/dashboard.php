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
 * Enhanced Dashboard template for Usage Monitor - Modern and responsive design
 *
 * @package    report_usage_monitor
 * @copyright  2025 Alonso Arias <alonso@aloarias.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Calculate warning levels and enhanced metrics
$disk_caution_level = max(70, $stats->disk->warning_level - 20);
$users_caution_level = max(70, $stats->users->warning_level - 20);

// Prepare chart data with enhanced formatting
$chart_data = [
    'disk_distribution' => [
        'labels' => array_keys($stats->directories),
        'data' => array_column($stats->directories, 'percentage'),
        'colors' => ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d']
    ],
    'daily_users' => $formatted_daily_users ?? [],
    'health_score' => $stats->health_score
];
?>

<!-- Enhanced CSS and JavaScript libraries -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<style>
/* Enhanced modern styling */
.usage-monitor-dashboard {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.dashboard-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.card-header-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border: none;
    position: relative;
}

.card-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
}

.metric-card {
    text-align: center;
    padding: 30px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 10px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.metric-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.progress-modern {
    height: 12px;
    border-radius: 10px;
    background: #e9ecef;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.progress-bar-modern {
    height: 100%;
    border-radius: 10px;
    transition: all 0.6s ease;
    position: relative;
    overflow: hidden;
}

.progress-bar-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.health-score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    position: relative;
    background: conic-gradient(from 0deg, #28a745 0deg, #28a745 var(--score-deg), #e9ecef var(--score-deg), #e9ecef 360deg);
}

.health-score-inner {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.recommendation-card {
    border-left: 4px solid;
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.recommendation-critical { border-left-color: #dc3545; }
.recommendation-warning { border-left-color: #ffc107; }
.recommendation-info { border-left-color: #17a2b8; }

.chart-container {
    position: relative;
    height: 400px;
    padding: 20px;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-excellent { background: #d4edda; color: #155724; }
.status-good { background: #d1ecf1; color: #0c5460; }
.status-fair { background: #fff3cd; color: #856404; }
.status-poor { background: #f8d7da; color: #721c24; }
.status-critical { background: #f5c6cb; color: #721c24; }

.trend-indicator {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    padding: 4px 8px;
    border-radius: 12px;
    background: #f8f9fa;
}

.trend-up { color: #dc3545; }
.trend-down { color: #28a745; }
.trend-stable { color: #6c757d; }

.nav-tabs-modern {
    border: none;
    background: #f8f9fa;
    border-radius: 10px;
    padding: 5px;
}

.nav-tabs-modern .nav-link {
    border: none;
    border-radius: 8px;
    color: #6c757d;
    font-weight: 600;
    transition: all 0.3s ease;
}

.nav-tabs-modern .nav-link.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-modern {
    border: none;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.table-modern thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.8rem;
}

.table-modern tbody tr {
    transition: all 0.3s ease;
}

.table-modern tbody tr:hover {
    background: #f8f9fa;
    transform: scale(1.01);
}

@media (max-width: 768px) {
    .metric-value { font-size: 2rem; }
    .chart-container { height: 300px; padding: 10px; }
    .dashboard-card { margin-bottom: 20px; }
}
</style>

<div class="usage-monitor-dashboard">
    <div class="container-fluid">
        
        <!-- Header with Health Score -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header-modern">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    <?php echo get_string('dashboard_title', 'report_usage_monitor'); ?>
                                </h2>
                                <p class="mb-0 mt-2 opacity-75">
                                    Real-time monitoring and analytics for your Moodle platform
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="health-score-circle" style="--score-deg: <?php echo $stats->health_score['overall'] * 3.6; ?>deg;">
                                    <div class="health-score-inner">
                                        <?php echo $stats->health_score['overall']; ?>%
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo $stats->health_score['status']; ?>">
                                    <?php echo ucfirst($stats->health_score['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="row mb-4">
            <!-- Disk Usage Metric -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="metric-label">
                        <i class="fas fa-hdd me-1"></i>
                        Disk Usage
                    </div>
                    <div class="metric-value"><?php echo round($stats->disk->percentage, 1); ?>%</div>
                    <div class="progress-modern mb-2">
                        <div class="progress-bar-modern bg-<?php echo $stats->disk->warning_class; ?>" 
                             style="width: <?php echo $stats->disk->percentage; ?>%"></div>
                    </div>
                    <small class="text-muted">
                        <?php echo $stats->disk->current_readable; ?> / <?php echo $stats->disk->quota_readable; ?>
                    </small>
                    <div class="trend-indicator trend-<?php echo $stats->disk->trend; ?> mt-2">
                        <i class="fas fa-arrow-<?php echo $stats->disk->trend === 'increasing' ? 'up' : ($stats->disk->trend === 'decreasing' ? 'down' : 'right'); ?>"></i>
                        <?php echo $stats->disk->growth_rate; ?>% monthly
                    </div>
                </div>
            </div>

            <!-- User Usage Metric -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="metric-label">
                        <i class="fas fa-users me-1"></i>
                        Daily Users
                    </div>
                    <div class="metric-value"><?php echo round($stats->users->percentage, 1); ?>%</div>
                    <div class="progress-modern mb-2">
                        <div class="progress-bar-modern bg-<?php echo $stats->users->warning_class; ?>" 
                             style="width: <?php echo $stats->users->percentage; ?>%"></div>
                    </div>
                    <small class="text-muted">
                        <?php echo $stats->users->current; ?> / <?php echo $stats->users->threshold; ?>
                    </small>
                    <div class="trend-indicator trend-<?php echo $stats->users->trend; ?> mt-2">
                        <i class="fas fa-arrow-<?php echo $stats->users->trend === 'increasing' ? 'up' : ($stats->users->trend === 'decreasing' ? 'down' : 'right'); ?>"></i>
                        <?php echo $stats->users->growth_rate; ?>% monthly
                    </div>
                </div>
            </div>

            <!-- Peak Users Metric -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="metric-label">
                        <i class="fas fa-chart-bar me-1"></i>
                        Peak (90 days)
                    </div>
                    <div class="metric-value"><?php echo $stats->users->max_90_days; ?></div>
                    <small class="text-muted">
                        <?php echo date('M d, Y', $stats->users->max_90_days_date); ?>
                    </small>
                    <div class="mt-2">
                        <span class="badge bg-info">
                            <?php echo round(($stats->users->max_90_days / $stats->users->threshold) * 100, 1); ?>% of limit
                        </span>
                    </div>
                </div>
            </div>

            <!-- Projections Metric -->
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="metric-label">
                        <i class="fas fa-crystal-ball me-1"></i>
                        Next Alert
                    </div>
                    <div class="metric-value">
                        <?php 
                        $next_alert = min($stats->projections->days_to_disk_threshold, $stats->projections->days_to_users_threshold);
                        echo $next_alert > 365 ? '∞' : $next_alert;
                        ?>
                    </div>
                    <small class="text-muted">
                        <?php echo $next_alert > 365 ? 'No alerts projected' : 'days until threshold'; ?>
                    </small>
                    <div class="mt-2">
                        <span class="badge bg-<?php echo $next_alert < 30 ? 'danger' : ($next_alert < 90 ? 'warning' : 'success'); ?>">
                            <?php echo $next_alert < 30 ? 'Critical' : ($next_alert < 90 ? 'Warning' : 'Healthy'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analysis Row -->
        <div class="row mb-4">
            <!-- Disk Distribution Chart -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>
                            Storage Distribution
                        </h5>
                    </div>
                    <div class="chart-container">
                        <?php if ($stats->disk->current_bytes > 0): ?>
                            <canvas id="diskDistributionChart"></canvas>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                    <p>No data available yet</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- User Activity Chart -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2 text-success"></i>
                            User Activity Trend
                        </h5>
                    </div>
                    <div class="chart-container">
                        <?php if (!empty($formatted_daily_users)): ?>
                            <canvas id="userActivityChart"></canvas>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                                    <p>No activity data available</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Information Row -->
        <div class="row mb-4">
            <!-- System Information -->
            <div class="col-lg-4 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-server me-2 text-info"></i>
                            System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-muted small">Moodle Version</div>
                                    <div class="fw-bold"><?php echo $stats->system->moodle_release; ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-muted small">PHP Version</div>
                                    <div class="fw-bold"><?php echo $stats->system->php_version; ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-muted small">Total Courses</div>
                                    <div class="fw-bold"><?php echo number_format($stats->system->course_count); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="text-muted small">Active Users</div>
                                    <div class="fw-bold"><?php echo number_format($stats->system->active_users); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="col-lg-8 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2 text-warning"></i>
                            Smart Recommendations
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats->recommendations)): ?>
                            <?php foreach ($stats->recommendations as $category => $recommendations): ?>
                                <?php foreach ($recommendations as $rec): ?>
                                    <div class="recommendation-card recommendation-<?php echo $rec['type']; ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="fas fa-<?php echo $rec['type'] === 'critical' ? 'exclamation-triangle' : ($rec['type'] === 'warning' ? 'exclamation-circle' : 'info-circle'); ?> fa-lg"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo $rec['title']; ?></h6>
                                                <p class="mb-2 text-muted"><?php echo $rec['message']; ?></p>
                                                <?php if (!empty($rec['actions'])): ?>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($rec['actions'] as $action): ?>
                                                            <li class="small">
                                                                <i class="fas fa-check-circle me-1 text-success"></i>
                                                                <?php echo $action; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h6>All systems optimal!</h6>
                                <p class="text-muted">No recommendations at this time.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Tables Row -->
        <div class="row mb-4">
            <!-- Directory Breakdown -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-folder-open me-2 text-primary"></i>
                            Storage Breakdown
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Directory</th>
                                        <th>Size</th>
                                        <th>Percentage</th>
                                        <th>Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats->directories as $name => $data): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-folder me-2"></i>
                                                <?php echo ucfirst($name); ?>
                                            </td>
                                            <td><?php echo $data['readable']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress-modern me-2" style="width: 60px; height: 6px;">
                                                        <div class="progress-bar-modern bg-primary" style="width: <?php echo $data['percentage']; ?>%"></div>
                                                    </div>
                                                    <?php echo $data['percentage']; ?>%
                                                </div>
                                            </td>
                                            <td>
                                                <span class="trend-indicator trend-<?php echo $data['trend']; ?>">
                                                    <i class="fas fa-arrow-<?php echo $data['trend'] === 'increasing' ? 'up' : ($data['trend'] === 'decreasing' ? 'down' : 'right'); ?>"></i>
                                                    <?php echo ucfirst($data['trend']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Largest Courses -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2 text-success"></i>
                            Largest Courses
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Size</th>
                                        <th>Users</th>
                                        <th>Efficiency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($stats->courses, 0, 5) as $course): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo format_string($course->fullname); ?></div>
                                                    <small class="text-muted"><?php echo $course->shortname; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php echo usage_monitor_manager::format_bytes($course->totalsize); ?>
                                                    <small class="text-muted d-block"><?php echo $course->percentage; ?>%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $course->enrolled_users ?? 0; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress-modern me-2" style="width: 40px; height: 6px;">
                                                        <div class="progress-bar-modern bg-<?php echo $course->efficiency_score > 70 ? 'success' : ($course->efficiency_score > 40 ? 'warning' : 'danger'); ?>" 
                                                             style="width: <?php echo $course->efficiency_score; ?>%"></div>
                                                    </div>
                                                    <?php echo $course->efficiency_score; ?>%
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Enhanced JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js global configuration
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#6c757d';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 20;

    // Enhanced color palette
    const colorPalette = {
        primary: '#667eea',
        success: '#28a745',
        warning: '#ffc107',
        danger: '#dc3545',
        info: '#17a2b8',
        gradient: {
            primary: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            success: 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
            warning: 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)'
        }
    };

    // Disk Distribution Chart with enhanced styling
    const diskCtx = document.getElementById('diskDistributionChart');
    if (diskCtx && <?php echo !empty($chart_data['disk_distribution']['data']) ? 'true' : 'false'; ?>) {
        new Chart(diskCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', $chart_data['disk_distribution']['labels'])); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data['disk_distribution']['data']); ?>,
                    backgroundColor: [
                        colorPalette.primary,
                        colorPalette.success,
                        colorPalette.warning,
                        colorPalette.danger,
                        colorPalette.info
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#667eea',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 2000
                }
            }
        });
    }

    // User Activity Chart with enhanced styling
    const userCtx = document.getElementById('userActivityChart');
    if (userCtx && <?php echo !empty($formatted_daily_users) ? 'true' : 'false'; ?>) {
        const userData = <?php echo json_encode($formatted_daily_users); ?>;
        const labels = userData.map(item => item.date);
        const userCounts = userData.map(item => item.users);
        const percentages = userData.map(item => item.percentage);

        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Users',
                    data: userCounts,
                    borderColor: colorPalette.primary,
                    backgroundColor: colorPalette.primary + '20',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colorPalette.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'Warning Level',
                    data: Array(labels.length).fill(<?php echo $users_caution_level; ?>),
                    borderColor: colorPalette.warning,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }, {
                    label: 'Critical Level',
                    data: Array(labels.length).fill(<?php echo $stats->users->warning_level; ?>),
                    borderColor: colorPalette.danger,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: colorPalette.primary,
                        borderWidth: 1,
                        cornerRadius: 8
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    // Auto-refresh functionality
    let refreshInterval;
    
    function startAutoRefresh() {
        refreshInterval = setInterval(function() {
            // Refresh dashboard data every 5 minutes
            fetch(window.location.href + '?ajax=1')
                .then(response => response.json())
                .then(data => {
                    // Update key metrics without full page reload
                    updateMetrics(data);
                })
                .catch(error => console.log('Auto-refresh failed:', error));
        }, 300000); // 5 minutes
    }

    function updateMetrics(data) {
        // Update metric values with smooth animation
        const diskPercentage = document.querySelector('.metric-value');
        if (diskPercentage && data.disk_percentage) {
            animateValue(diskPercentage, parseFloat(diskPercentage.textContent), data.disk_percentage, 1000);
        }
    }

    function animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(function() {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = current.toFixed(1) + '%';
        }, 16);
    }

    // Initialize auto-refresh
    startAutoRefresh();

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });

    // Enhanced tooltips for progress bars
    document.querySelectorAll('.progress-bar-modern').forEach(function(bar) {
        bar.addEventListener('mouseenter', function() {
            const percentage = this.style.width;
            this.setAttribute('title', 'Usage: ' + percentage);
        });
    });

    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<!-- Footer -->
<div class="text-center mt-4 py-3">
    <small class="text-muted">
        <?php echo get_string('reportinfotext', 'report_usage_monitor'); ?>
    </small>
</div>