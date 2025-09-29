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
  * @copyright  2023 Soporte IngeWeb <soporte@ingeweb.co>
  * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'admin']);

$reportconfig = get_config('report_usage_monitor');

$disk_usage_bytes = (int)($reportconfig->totalusagereadable ?? 0)
    + (int)($reportconfig->totalusagereadabledb ?? 0);
$quotadisk_bytes  = ((int)($reportconfig->disk_quota ?? 0)) * 1024 * 1024 * 1024;

$disk_usage_gb = !empty($reportconfig->disk_usage_gb) ? $reportconfig->disk_usage_gb : display_size_in_gb($disk_usage_bytes, 2);
$quotadisk_gb = !empty($reportconfig->quotadisk_gb) ? $reportconfig->quotadisk_gb : display_size_in_gb($quotadisk_bytes, 2);

$disk_percent = !empty($reportconfig->disk_percent) ? (float)$reportconfig->disk_percent : 
    (($quotadisk_bytes > 0) ? ($disk_usage_bytes / $quotadisk_bytes * 100) : 0);
$disk_warning_class = !empty($reportconfig->disk_warning_class) ? $reportconfig->disk_warning_class : 
    (($disk_percent < 70) ? 'bg-success' : (($disk_percent < 90) ? 'bg-warning' : 'bg-danger'));

$users_today = (int)($reportconfig->totalusersdaily ?? 0);
$max_users_threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
$users_percent = !empty($reportconfig->users_percent) ? (float)$reportconfig->users_percent : 
    (($max_users_threshold > 0) ? ($users_today / $max_users_threshold * 100) : 0);
$users_warning_class = !empty($reportconfig->users_warning_class) ? $reportconfig->users_warning_class : 
    (($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger'));

$lastexec_disk_ts = !empty($reportconfig->lastexecutioncalculate) ? $reportconfig->lastexecutioncalculate : 0;
if (!is_numeric($lastexec_disk_ts) || $lastexec_disk_ts <= 0) {
    $lastexec_disk = get_string('notcalculatedyet', 'report_usage_monitor');
} else {
    $lastexec_disk = date('d/m/Y H:i', (int)$lastexec_disk_ts);
}

$lastexec_users_ts = !empty($reportconfig->lastexecution) ? $reportconfig->lastexecution : 0;
if (!is_numeric($lastexec_users_ts) || $lastexec_users_ts <= 0) {
    $lastexec_users = get_string('notcalculatedyet', 'report_usage_monitor');
} else {
    $lastexec_users = date('d/m/Y H:i', (int)$lastexec_users_ts);
}
    
$max_90_days_users   = $reportconfig->max_userdaily_for_90_days_users ?? get_string('notcalculatedyet', 'report_usage_monitor');
$max_90_days_date_ts = $reportconfig->max_userdaily_for_90_days_date  ?? 0;
$last_calc_90days_ts = $reportconfig->lastexecutioncalculateusers90days ?? 0;

if (!is_numeric($max_90_days_date_ts) || $max_90_days_date_ts <= 0) {
    $max_90_days_date = get_string('notcalculatedyet', 'report_usage_monitor');
} else {
    $max_90_days_date = date('d/m/Y', (int)$max_90_days_date_ts);
}

if (!is_numeric($last_calc_90days_ts) || $last_calc_90days_ts <= 0) {
    $last_calc_90days = get_string('notcalculatedyet', 'report_usage_monitor');
} else {
    $last_calc_90days = date('d/m/Y H:i', (int)$last_calc_90days_ts);
}

$dir_analysis_json = $reportconfig->dir_analysis ?? '{}';
$dir_analysis = json_decode($dir_analysis_json, true);

if (empty($dir_analysis) || !is_array($dir_analysis)) {
    $dir_analysis = [
        'database' => 0,
        'filedir' => 0,
        'cache' => 0,
        'others' => 0
    ];
}

$database_gb = display_size_in_gb($dir_analysis['database'] ?? 0, 2);
$filedir_gb  = display_size_in_gb($dir_analysis['filedir']  ?? 0, 2);
$cache_gb    = display_size_in_gb($dir_analysis['cache']    ?? 0, 2);
$others_gb   = display_size_in_gb($dir_analysis['others']   ?? 0, 2);

// Inicialización de variables para el gráfico de dona (distribución de disco)
$doughnutLabels = [
    get_string('database', 'report_usage_monitor'),
    get_string('files_dir', 'report_usage_monitor'),
    get_string('cache', 'report_usage_monitor'),
    get_string('others', 'report_usage_monitor')
];
$doughnutData = [
    $database_gb,
    $filedir_gb,
    $cache_gb,
    $others_gb,
];

$largest_courses_json = $reportconfig->largest_courses ?? '[]';
$largest_courses = json_decode($largest_courses_json);

if (empty($largest_courses)) {
    $largest_courses = get_largest_courses(5);
}

$userdaily_sql = report_user_daily_sql();
$userdaily_records = $DB->get_records_sql($userdaily_sql);

$formatted_userdaily_records = array();
foreach ($userdaily_records as $record) {
    $new_record = new stdClass();
    $new_record->conteo_accesos_unicos = $record->conteo_accesos_unicos;
    
    if (is_numeric($record->timestamp_fecha)) {
        $new_record->fecha_formateada = date('d/m/Y', (int)$record->timestamp_fecha);
    } else {
        $new_record->fecha_formateada = date('d/m/Y');
    }
    
    $formatted_userdaily_records[] = $new_record;
}

// Inicialización de variables para el gráfico de líneas (usuarios últimos 10 días)
$last10daysLabels = [];
$last10daysData = [];
$last10daysDataRaw = []; // Añadimos un array para mantener también los valores originales
if (!empty($formatted_userdaily_records)) {
    foreach ($formatted_userdaily_records as $day) {
        $last10daysLabels[] = $day->fecha_formateada;
        // Calculamos el porcentaje en relación al umbral
        $percent = ($max_users_threshold > 0) ? 
                   min(100, round(($day->conteo_accesos_unicos / $max_users_threshold) * 100, 1)) : 0;
        $last10daysData[] = $percent;
        $last10daysDataRaw[] = (int)$day->conteo_accesos_unicos;
    }
}

$userdailytop_sql = report_user_daily_top_sql();
$userdaily_recordstop = $DB->get_records_sql($userdailytop_sql);

$formatted_userdaily_recordstop = array();
foreach ($userdaily_recordstop as $record) {
    $new_record = new stdClass();
    $new_record->cantidad_usuarios = $record->cantidad_usuarios;
    
    if (is_numeric($record->timestamp_fecha)) {
        $new_record->fecha_formateada = date('d/m/Y', (int)$record->timestamp_fecha);
    } else {
        $new_record->fecha_formateada = date('d/m/Y');
    }
    
    $formatted_userdaily_recordstop[] = $new_record;
}

$totalcourses    = $DB->count_records('course');
$activeusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
$suspendedusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
$registeredusers = $activeusers + $suspendedusers;
$backup_max_kept = get_config('backup', 'backup_auto_max_kept') ?? 0;

$month_ago = time() - (30 * 24 * 60 * 60);
$sql = "SELECT timecreated, value, percentage 
        FROM {report_usage_monitor_history} 
        WHERE type = 'disk' AND timecreated > ? 
        ORDER BY timecreated ASC";
$disk_history = $DB->get_records_sql($sql, [$month_ago]);

$disk_history_labels = [];
$disk_history_data = [];

$daily_data = [];

foreach ($disk_history as $record) {
    if (is_numeric($record->timecreated) && $record->timecreated > 0) {
        $date_key = date('Y-m-d', (int)$record->timecreated);
        
        $daily_data[$date_key] = [
            'label' => date('d/m/Y', (int)$record->timecreated),
            'percentage' => round($record->percentage, 1)
        ];
    }
}

if (count($daily_data) < 30) {
    $all_dates = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = time() - ($i * 24 * 60 * 60);
        $date_key = date('Y-m-d', $date);
        $all_dates[$date_key] = date('d/m/Y', $date);
    }
    
    $combined_data = [];
    $last_value = null;
    
    foreach ($all_dates as $date_key => $formatted_date) {
        if (isset($daily_data[$date_key])) {
            $last_value = $daily_data[$date_key]['percentage'];
            $combined_data[$date_key] = $daily_data[$date_key];
        } else {
            $combined_data[$date_key] = [
                'label' => $formatted_date,
                'percentage' => $last_value
            ];
        }
    }
    
    ksort($combined_data);
    $daily_data = $combined_data;
}

foreach ($daily_data as $data) {
    $disk_history_labels[] = $data['label'];
    $disk_history_data[] = $data['percentage'];
}

echo $OUTPUT->header();

echo '<div class="alert alert-info mb-2 text-center small">';
echo (get_string('exclusivedisclaimer', 'report_usage_monitor'));
echo '</div>';

echo $OUTPUT->heading(get_string('dashboard_title', 'report_usage_monitor'));
?>

<!-- Cargar librería Chart.js (ejemplo CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4">
    <!-- SECCIÓN A: Tarjetas resumen (disco, usuarios, max 90d) -->
    <div class="row">
        <!-- Disco -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('diskusage', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $disk_warning_class; ?> rounded-pill">
                        <?php echo round($disk_percent, 1); ?>%
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:25px;">
                        <div class="progress-bar <?php echo $disk_warning_class; ?>"
                            role="progressbar"
                            style="width:<?php echo $disk_percent; ?>%;"
                            aria-valuenow="<?php echo $disk_percent; ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                            <?php echo round($disk_percent, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h5>
                            <?php echo $disk_usage_gb . ' GB / ' . $quotadisk_gb . ' GB'; ?>
                        </h5>
                        <p class="text-muted">
                            <?php echo get_string('lastexecutioncalculate', 'report_usage_monitor', $lastexec_disk); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuarios hoy -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('users_today_card', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $users_warning_class; ?> rounded-pill">
                        <?php echo round($users_percent, 1); ?>%
                    </span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:25px;">
                        <div class="progress-bar <?php echo $users_warning_class; ?>"
                            role="progressbar"
                            style="width:<?php echo $users_percent; ?>%;"
                            aria-valuenow="<?php echo $users_percent; ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                            <?php echo round($users_percent, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h5>
                            <?php echo $users_today; ?> / <?php echo $max_users_threshold; ?>
                        </h5>
                        <p class="text-muted">
                            <?php echo get_string('lastexecution', 'report_usage_monitor', $lastexec_users); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Máximo 90 días -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('max_userdaily_for_90_days', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="display-5">
                    <?php echo $max_90_days_users; ?> / <?php echo $max_users_threshold; ?>
                    </h2>
                    <p class="text-muted mt-2">
                        <?php if ($max_90_days_date != get_string('notcalculatedyet', 'report_usage_monitor')): ?>
                            <?php echo get_string('date', 'report_usage_monitor'); ?>: <?php echo $max_90_days_date; ?><br>
                        <?php endif; ?>
                        <?php echo get_string('last_calculation', 'report_usage_monitor'); ?>: <?php echo $last_calc_90days; ?>
                    </p>
                </div>
            </div>
        </div>
    </div><!-- fin row tarjetas -->
    
    <!-- SECCIÓN B: Distribución disco (gráfica + tablas) -->
    <div class="row">
        <!-- Columna izquierda: Gráfico doughnut Chart.js -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('disk_usage_distribution', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body" style="position:relative; min-height:400px;">
                    <?php if ($disk_usage_bytes > 0): ?>
                        <canvas id="chartjs-doughnut"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Tablas de uso por directorios y cursos más grandes -->
        <div class="col-md-6 mb-4">
            <!-- Primera tabla: Uso por directorios -->
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
                            <?php
                            $total_bytes = $disk_usage_bytes;
                            if (!empty($dir_analysis) && $total_bytes > 0):
                                $directories = [
                                    'database' => get_string('database', 'report_usage_monitor'),
                                    'filedir'  => get_string('files_dir', 'report_usage_monitor'),
                                    'cache'    => get_string('cache', 'report_usage_monitor'),
                                    'others'   => get_string('others', 'report_usage_monitor'),
                                ];
                                foreach ($directories as $key => $label):
                                    $sub_bytes = (int)($dir_analysis[$key] ?? 0);
                                    $sub_gb    = display_size_in_gb($sub_bytes, 2);
                                    $percent   = round(($sub_bytes / $total_bytes) * 100, 2);
                            ?>
                                    <tr>
                                        <td><?php echo $label; ?></td>
                                        <td><?php echo $sub_gb . ' GB'; ?></td>
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

            <!-- Segunda tabla: Cursos más grandes -->
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
                                <th><?php echo get_string('backup_count', 'report_usage_monitor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($largest_courses)): ?>
                                <?php foreach ($largest_courses as $course): ?>
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
    </div><!-- fin row -->

    <!-- SECCIÓN: Historial de uso de disco (últimos 30 días) -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('disk_usage_history', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($disk_history_labels)): ?>
                        <canvas id="chartjs-disk-history" style="height: 300px;"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div><!-- fin row historial disco -->

    <!-- SECCIÓN C: Usuarios últimos 10 días (con tab para Tabla / Gráfica) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('lastusers', 'report_usage_monitor'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs" id="last10daysTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tabla10-tab" data-toggle="tab"
                                href="#tabla10" role="tab" aria-controls="tabla10"
                                aria-selected="true">
                                <?php echo get_string('usertable', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="grafica10-tab" data-toggle="tab"
                                href="#grafica10" role="tab" aria-controls="grafica10"
                                aria-selected="false">
                                <?php echo get_string('userchart', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="last10daysTabContent">
                        <!-- Pane 1: Tabla -->
                        <div class="tab-pane fade show active" id="tabla10" role="tabpanel"
                            aria-labelledby="tabla10-tab">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_string('date', 'report_usage_monitor'); ?></th>
                                            <th><?php echo get_string('usersquantity', 'report_usage_monitor'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($formatted_userdaily_records)): ?>
                                            <?php foreach ($formatted_userdaily_records as $daylog): ?>
                                                <tr>
                                                    <td><?php echo $daylog->fecha_formateada; ?></td>
                                                    <td><?php echo $daylog->conteo_accesos_unicos; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center">
                                                    <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Pane 2: Gráfica -->
                        <div class="tab-pane fade" id="grafica10" role="tabpanel"
                            aria-labelledby="grafica10-tab">
                            <div style="position:relative; min-height:400px;">
                                <?php if (!empty($formatted_userdaily_records)): ?>
                                    <canvas id="chartjs-last10days"></canvas>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <?php echo get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div> <!-- .card-body -->
            </div>
        </div>
    </div><!-- fin row últimos 10 días -->

    <!-- SECCIÓN D: Top 10 usuarios diarios -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
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
                            <?php if (!empty($formatted_userdaily_recordstop)): ?>
                                <?php foreach ($formatted_userdaily_recordstop as $log): ?>
                                    <?php
                                    $percent = 0;
                                    if ($max_users_threshold > 0) {
                                        $percent = round(($log->cantidad_usuarios / $max_users_threshold) * 100, 1);
                                    }
                                    $class = '';
                                    if ($percent >= 70 && $percent < 90) {
                                        $class = 'text-warning';
                                    } else if ($percent >= 90) {
                                        $class = 'text-danger';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $log->fecha_formateada; ?></td>
                                        <td><?php echo $log->cantidad_usuarios; ?></td>
                                        <td class="<?php echo $class; ?>"><?php echo $percent; ?>%</td>
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
    </div><!-- fin row top 10 usuarios diarios -->

    <!-- SECCIÓN E: Info sistema + recomendaciones -->
    <div class="row">
        <!-- Info sistema -->
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo get_string('system_info', 'report_usage_monitor'); ?>
                    </h5>
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
                                    <div class="h5"><?php echo $backup_max_kept; ?></div>
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
                    </div><!-- fin row interna -->
                </div>
            </div>
        </div>

        <!-- Recomendaciones -->
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('recommendations', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Alerta disco -->
                    <?php if ($disk_percent > 70): ?>
                        <div class="alert alert-<?php echo ($disk_percent > 90) ? 'danger' : 'warning'; ?>">
                            <h5><?php echo get_string('space_saving_tips', 'report_usage_monitor'); ?></h5>
                            <ul class="mb-0">
                                <li><?php echo get_string('tip_backups', 'report_usage_monitor', $backup_max_kept); ?></li>
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

                    <!-- Alerta usuarios -->
                    <?php if ($users_percent > 70): ?>
                        <div class="alert alert-<?php echo ($users_percent > 90) ? 'danger' : 'warning'; ?>">
                            <h5><?php echo get_string('user_limit_tips', 'report_usage_monitor'); ?></h5>
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
                </div>
            </div>
        </div>
    </div><!-- fin row -->
</div><!-- fin container-fluid -->

<!-- Créditos plugin -->
<div class="mt-4 text-center text-muted small">
    <?php echo get_string('reportinfotext', 'report_usage_monitor'); ?>
</div>

<!-- Scripts para inicializar las gráficas con Chart.js (doughnut + line) -->
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // ========== Gráfico Doughnut (distribución de disco) ==========
        var donutCtx = document.getElementById("chartjs-doughnut");
        if (donutCtx && <?php echo !empty($doughnutData) ? 'true' : 'false'; ?>) {
            new Chart(donutCtx, {
                type: "doughnut",
                data: {
                    labels: <?php echo json_encode($doughnutLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($doughnutData); ?>,
                        backgroundColor: [
                            "#007bff", // primary
                            "#28a745", // success
                            "#ffc107", // warning
                            "#dee2e6" // gray-lighter
                        ],
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

        // ========== Gráfico Line (últimos 10 días - Usuarios) ==========
        var last10Ctx = document.getElementById("chartjs-last10days");
        if (last10Ctx && <?php echo !empty($last10daysLabels) ? 'true' : 'false'; ?>) {
            new Chart(last10Ctx, {
                type: "line",
                data: {
                    labels: <?php echo json_encode($last10daysLabels); ?>,
                    datasets: [
                        {
                            label: "<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>",
                            fill: true,
                            backgroundColor: "rgba(0, 123, 255, 0.1)",
                            borderColor: "#007bff",
                            data: <?php echo json_encode($last10daysData); ?>,
                            yAxisID: 'percentage'
                        },
                        {
                            label: "<?php echo get_string('warning70', 'report_usage_monitor'); ?>",
                            fill: false,
                            borderColor: "#ffc107",
                            borderDash: [5, 5],
                            pointRadius: 0,
                            data: Array(<?php echo json_encode($last10daysLabels); ?>.length).fill(70),
                            yAxisID: 'percentage'
                        },
                        {
                            label: "<?php echo get_string('critical90', 'report_usage_monitor'); ?>",
                            fill: false,
                            borderColor: "#dc3545",
                            borderDash: [5, 5],
                            pointRadius: 0,
                            data: Array(<?php echo json_encode($last10daysLabels); ?>.length).fill(90),
                            yAxisID: 'percentage'
                        },
                        {
                            label: "<?php echo get_string('limit100', 'report_usage_monitor'); ?>",
                            fill: false,
                            borderColor: "#6c757d",
                            borderDash: [2, 2],
                            pointRadius: 0,
                            data: Array(<?php echo json_encode($last10daysLabels); ?>.length).fill(100),
                            yAxisID: 'percentage'
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                color: "rgba(0,0,0,0.05)"
                            }
                        },
                        percentage: {
                            type: 'linear',
                            position: 'left',
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
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.dataset.label === "<?php echo get_string('usersquantity', 'report_usage_monitor'); ?>") {
                                        return context.dataset.label + ": " + <?php echo json_encode($last10daysDataRaw); ?>[context.dataIndex] + 
                                               " (" + context.parsed.y + "<?php echo get_string('percent_of_threshold', 'report_usage_monitor'); ?>)";
                                    }
                                    return context.dataset.label + ": " + context.parsed.y + "%";
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // ========== Gráfico Line (historial de uso de disco) ==========
        var diskHistoryCtx = document.getElementById("chartjs-disk-history");
        if (diskHistoryCtx && <?php echo !empty($disk_history_labels) ? 'true' : 'false'; ?>) {
            new Chart(diskHistoryCtx, {
                type: "line",
                data: {
                    labels: <?php echo json_encode($disk_history_labels); ?>,
                    datasets: [{
                        label: "<?php echo get_string('percentage_used', 'report_usage_monitor'); ?>",
                        fill: true,
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        borderColor: "#007bff",
                        data: <?php echo json_encode($disk_history_data); ?>,
                        spanGaps: true,
                        tension: 0.2,
                        yAxisID: 'percentage'
                    },
                    {
                        label: "<?php echo get_string('warning70', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#ffc107",
                        borderDash: [5, 5],
                        pointRadius: 0,
                        data: Array(<?php echo !empty($disk_history_labels) ? count($disk_history_labels) : 0; ?>).fill(70),
                        yAxisID: 'percentage'
                    },
                    {
                        label: "<?php echo get_string('critical90', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#dc3545",
                        borderDash: [5, 5],
                        pointRadius: 0,
                        data: Array(<?php echo !empty($disk_history_labels) ? count($disk_history_labels) : 0; ?>).fill(90),
                        yAxisID: 'percentage'
                    },
                    {
                        label: "<?php echo get_string('limit100', 'report_usage_monitor'); ?>",
                        fill: false,
                        borderColor: "#6c757d",
                        borderDash: [2, 2],
                        pointRadius: 0,
                        data: Array(<?php echo !empty($disk_history_labels) ? count($disk_history_labels) : 0; ?>).fill(100),
                        yAxisID: 'percentage'
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        x: {
                            grid: {
                                color: "rgba(0,0,0,0.05)"
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        },
                        percentage: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: "rgba(0,0,0,0.05)"
                            },
                            ticks: {
                                callback: function(value) { return value + "%"; }
                            },
                            title: {
                                display: true,
                                text: '<?php echo get_string('percent_of_threshold', 'report_usage_monitor'); ?>'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.dataset.label === "<?php echo get_string('percentage_used', 'report_usage_monitor'); ?>") {
                                        return context.parsed.y + '<?php echo get_string('percent_of_threshold', 'report_usage_monitor'); ?>';
                                    }
                                    return context.dataset.label;
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }

    });
</script>
<style>
    .bg-success {
        color: white;
    }
</style>
<?php
echo $OUTPUT->footer();
?>