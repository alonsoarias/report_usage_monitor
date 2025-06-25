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
 * Dashboard template for Usage Monitor
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Calculate warning levels
$disk_caution_level = max(70, $stats->disk->warning_level - 20);
$users_caution_level = max(70, $stats->users->warning_level - 20);
?>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4" id="usage-monitor-dashboard">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <!-- Disk Usage Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('diskusage', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $stats->disk->warning_class; ?> rounded-pill">
                        <?php echo round($stats->disk->percentage, 1); ?>%
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:25px;">
                        <div class="progress-bar <?php echo $stats->disk->warning_class; ?>"
                            role="progressbar"
                            style="width:<?php echo $stats->disk->percentage; ?>%;"
                            aria-valuenow="<?php echo $stats->disk->percentage; ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                            <?php echo round($stats->disk->percentage, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h5><?php echo $stats->disk->current_readable . ' / ' . $stats->disk->quota_readable; ?></h5>
                        <p class="text-muted">
                            <?php echo get_string('lastexecutioncalculate', 'report_usage_monitor', 
                                     date('d/m/Y H:i', $stats->disk->last_calculated)); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Usage Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('users_today_card', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $stats->users->warning_class; ?> rounded-pill">
                        <?php echo round($stats->users->percentage, 1); ?>%
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:25px;">
                        <div class="progress-bar <?php echo $stats->users->warning_class; ?>"
                            role="progressbar"
                            style="width:<?php echo $stats->users->percentage; ?>%;"
                            aria-valuenow="<?php echo $stats->users->percentage; ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                            <?php echo round($stats->users->percentage, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h5><?php echo $stats->users->current . ' / ' . $stats->users->threshold; ?></h5>
                        <p class="text-muted">
                            <?php echo get_string('lastexecution', 'report_usage_monitor', 
                                     date('d/m/Y H:i', $stats->users->last_calculated)); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Max 90 Days Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('max_userdaily_for_90_days', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="display-5">
                        <?php echo $stats->users->max_90_days . ' / ' . $stats->users->threshold; ?>
                    </h2>
                    <p class="text-muted mt-2">
                        <?php echo get_string('date', 'report_usage_monitor') . ': ' . 
                                   date('d/m/Y', $stats->users->max_90_days_date); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Disk Distribution Chart -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('disk_usage_distribution', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body" style="position:relative; min-height:400px;">
                    <?php if ($stats->disk->current_bytes > 0): ?>
                        <canvas id="diskDistributionChart"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Directory Usage Table -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('disk_usage_by_directory', 'report_usage_monitor'); ?></h5>
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
                            <?php
                            $directories = [
                                'database' => get_string('database', 'report_usage_monitor'),
                                'filedir'  => get_string('files_dir', 'report_usage_monitor'),
                                'cache'    => get_string('cache', 'report_usage_monitor'),
                                'others'   => get_string('others', 'report_usage_monitor'),
                            ];
                            foreach ($directories as $key => $label):
                                $bytes = $stats->directories[$key];
                                $readable = display_size($bytes);
                                $percent = $stats->disk->current_bytes > 0 ? 
                                          round(($bytes / $stats->disk->current_bytes) * 100, 2) : 0;
                            ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><?php echo $readable; ?></td>
                                    <td><?php echo $percent; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Activity Charts -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="userActivityTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="daily-users-tab" data-toggle="tab" 
                               href="#daily-users" role="tab" aria-controls="daily-users" aria-selected="true">
                                <?php echo get_string('lastusers', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="top-users-tab" data-toggle="tab" 
                               href="#top-users" role="tab" aria-controls="top-users" aria-selected="false">
                                <?php echo get_string('topuser', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="userActivityTabContent">
                        <!-- Daily Users Chart -->
                        <div class="tab-pane fade show active" id="daily-users" role="tabpanel" aria-labelledby="daily-users-tab">
                            <div style="position:relative; min-height:400px;">
                                <?php if (!empty($formatted_daily_users)): ?>
                                    <canvas id="dailyUsersChart"></canvas>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Top Users Table -->
                        <div class="tab-pane fade" id="top-users" role="tabpanel" aria-labelledby="top-users-tab">
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
                                        <?php if (!empty($formatted_top_users)): ?>
                                            <?php foreach ($formatted_top_users as $record): ?>
                                                <?php
                                                $class = '';
                                                if ($record['percentage'] >= $users_caution_level && $record['percentage'] < $stats->users->warning_level) {
                                                    $class = 'text-warning';
                                                } else if ($record['percentage'] >= $stats->users->warning_level) {
                                                    $class = 'text-danger';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo $record['date']; ?></td>
                                                    <td><?php echo $record['users']; ?></td>
                                                    <td class="<?php echo $class; ?>"><?php echo $record['percentage']; ?>%</td>
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
            </div>
        </div>
    </div>

    <!-- System Info and Recommendations -->
    <div class="row mb-4">
        <!-- System Information -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('system_info', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('moodle_version', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo $stats->system->moodle_release; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('total_courses', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo $stats->system->course_count; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('backup_per_course', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo $stats->system->backup_auto_max_kept; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('registered_users', 'report_usage_monitor'); ?></div>
                                    <div class="h5">
                                        <?php echo $stats->system->active_users; ?>/<?php echo $stats->system->suspended_users; ?>
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
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('recommendations', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Disk Recommendations -->
                    <?php if ($stats->disk->percentage > $disk_caution_level): ?>
                        <div class="alert alert-<?php echo ($stats->disk->percentage > $stats->disk->warning_level) ? 'danger' : 'warning'; ?>">
                            <h6><?php echo get_string('space_saving_tips', 'report_usage_monitor'); ?></h6>
                            <ul class="mb-0">
                                <li><?php echo get_string('tip_backups', 'report_usage_monitor', $stats->system->backup_auto_max_kept); ?></li>
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

                    <!-- User Recommendations -->
                    <?php if ($stats->users->percentage > $users_caution_level): ?>
                        <div class="alert alert-<?php echo ($stats->users->percentage > $stats->users->warning_level) ? 'danger' : 'warning'; ?>">
                            <h6><?php echo get_string('user_limit_tips', 'report_usage_monitor'); ?></h6>
                            <ul class="mb-0">
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

                    <!-- Projections -->
                    <?php if ($stats->projections->days_to_disk_threshold > 0 && $stats->projections->days_to_disk_threshold < 365): ?>
                        <div class="alert alert-info">
                            <strong><?php echo get_string('projections', 'report_usage_monitor'); ?>:</strong><br>
                            Disk threshold in <?php echo $stats->projections->days_to_disk_threshold; ?> days<br>
                            Users threshold in <?php echo $stats->projections->days_to_users_threshold; ?> days
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Largest Courses -->
    <?php if (!empty($stats->courses)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('largest_courses', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_string('course', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('size', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('percentage', 'report_usage_monitor'); ?></th>
                                <th><?php echo get_string('backup_count', 'report_usage_monitor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats->courses as $course): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo $CFG->wwwroot . '/course/view.php?id=' . $course->id; ?>">
                                            <?php echo format_string($course->fullname) . ' (' . $course->shortname . ')'; ?>
                                        </a>
                                    </td>
                                    <td><?php echo display_size($course->totalsize); ?></td>
                                    <td><?php echo $course->percentage; ?>%</td>
                                    <td><?php echo $course->backupcount; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Credits -->
<div class="mt-4 text-center text-muted small">
    <?php echo get_string('reportinfotext', 'report_usage_monitor'); ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Chart.js configuration
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#666';

    // Disk Distribution Chart
    const diskCtx = document.getElementById("diskDistributionChart");
    if (diskCtx && <?php echo !empty($chart_data['disk_distribution']['data']) ? 'true' : 'false'; ?>) {
        new Chart(diskCtx, {
            type: "doughnut",
            data: {
                labels: <?php echo json_encode($chart_data['disk_distribution']['labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data['disk_distribution']['data']); ?>,
                    backgroundColor: [
                        "#007bff", // primary
                        "#28a745", // success
                        "#ffc107", // warning
                        "#dee2e6"  // gray-lighter
                    ],
                    borderColor: "transparent",
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let valueGb = context.parsed;
                                return label + ': ' + valueGb + ' GB';
                            }
                        }
                    }
                }
            }
        });
    }

    // Daily Users Chart
    const dailyCtx = document.getElementById("dailyUsersChart");
    if (dailyCtx && <?php echo !empty($formatted_daily_users) ? 'true' : 'false'; ?>) {
        const dailyData = <?php echo json_encode($formatted_daily_users); ?>;
        const labels = dailyData.map(item => item.date);
        const percentages = dailyData.map(item => item.percentage);
        const userCounts = dailyData.map(item => item.users);

        new Chart(dailyCtx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>",
                        fill: true,
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        borderColor: "#007bff",
                        data: percentages,
                        tension: 0.2,
                        pointBackgroundColor: "#007bff",
                        pointBorderColor: "#fff",
                        pointBorderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: "<?php echo get_string('warning70', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#ffc107",
                        borderDash: [5, 5],
                        pointRadius: 0,
                        data: Array(labels.length).fill(<?php echo $users_caution_level; ?>)
                    },
                    {
                        label: "<?php echo get_string('critical90', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#dc3545",
                        borderDash: [5, 5],
                        pointRadius: 0,
                        data: Array(labels.length).fill(<?php echo $stats->users->warning_level; ?>)
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: {
                        grid: {
                            color: "rgba(0,0,0,0.05)"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: "rgba(0,0,0,0.05)"
                        },
                        ticks: {
                            callback: function(value) {
                                return value + "%";
                            }
                        },
                        title: {
                            display: true,
                            text: '<?php echo get_string('percent_of_threshold', 'report_usage_monitor'); ?>'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.label === "<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>") {
                                    return context.dataset.label + ": " + userCounts[context.dataIndex] + 
                                           " (" + context.parsed.y + "%)";
                                }
                                return context.dataset.label + ": " + context.parsed.y + "%";
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize Bootstrap tabs
    $('#userActivityTab a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
});
</script>

<style>
.bg-success {
    color: white !important;
}
.card {
    transition: box-shadow 0.15s ease-in-out;
}
.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.progress-bar {
    transition: width 0.6s ease;
}
.alert {
    border: none;
    border-radius: 0.5rem;
}
.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
}
.nav-tabs .nav-link.active {
    background-color: transparent;
    border-bottom: 2px solid #007bff;
    color: #007bff;
}
</style>