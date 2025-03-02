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
 * Usage Monitor Report
 *
 * @package    report_usage_monitor
 * @copyright  2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'admin']);
$viewtab = optional_param('view', 'dashboard', PARAM_ALPHA);
$subview = optional_param('subview', '', PARAM_ALPHA);

// Datos para las pestañas en el informe.
$tabdata = ['dashboard' => '', 'userstopnum' => '', 'diskusage' => ''];
if (!array_key_exists($viewtab, $tabdata)) {
    // Para un valor de parámetro inválido, utilizar 'dashboard'.
    $viewtab = array_keys($tabdata)[0];
}

$tabs = [];
foreach ($tabdata as $tabname => $param) {
    $tabs[] = new tabobject(
        $tabname,
        new moodle_url($PAGE->url, ['view' => $tabname]),
        get_string($tabname, 'report_usage_monitor', $param)
    );
}

$reportconfig = get_config('report_usage_monitor');

// Cálculos comunes para todas las vistas
$disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
$quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
// Evitar división por cero
$disk_percent = ($quotadisk > 0) ? calculate_threshold_percentage($disk_usage, $quotadisk) : 0;
$disk_warning_class = $disk_percent < 70 ? 'bg-success' : ($disk_percent < 90 ? 'bg-warning' : 'bg-danger');

$users_today = !empty($reportconfig->totalusersdaily) ? (int)($reportconfig->totalusersdaily) : 0;
$max_users_threshold = !empty($reportconfig->max_daily_users_threshold) ? (int)($reportconfig->max_daily_users_threshold) : 100;
// Evitar división por cero
$users_percent = ($max_users_threshold > 0) ? calculate_threshold_percentage($users_today, $max_users_threshold) : 0;
$users_warning_class = $users_percent < 70 ? 'bg-success' : ($users_percent < 90 ? 'bg-warning' : 'bg-danger');

// Analizar directorios para algunas vistas
$dir_analysis = [];
if ($viewtab === 'dashboard' || $viewtab === 'diskusage') {
    $dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);
}

// CSS y JS adicional para el dashboard
// $PAGE->requires->css(new moodle_url('/report/usage_monitor/styles.css'));
// $PAGE->requires->js_call_amd('report_usage_monitor/dashboard', 'init');

// Iniciar salida del HTML
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabs, $viewtab);
?>

<?php if ($viewtab === 'dashboard'): ?>
<div class="container-fluid mt-4">
    <!-- Resumen en tarjetas -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('diskusage', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $disk_warning_class; ?> rounded-pill"><?php echo round($disk_percent, 1); ?>%</span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar <?php echo $disk_warning_class; ?>" role="progressbar" 
                            style="width: <?php echo $disk_percent; ?>%;" 
                            aria-valuenow="<?php echo $disk_percent; ?>" 
                            aria-valuemin="0" aria-valuemax="100">
                            <?php echo round($disk_percent, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h4><?php echo display_size($disk_usage); ?> / <?php echo display_size($quotadisk); ?></h4>
                        <p class="text-muted"><?php echo get_string('lastexecutioncalculate', 'report_usage_monitor', !empty($reportconfig->lastexecutioncalculate) ? userdate($reportconfig->lastexecutioncalculate) : get_string('notcalculatedyet', 'report_usage_monitor')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo get_string('users_today_card', 'report_usage_monitor'); ?></h5>
                    <span class="badge <?php echo $users_warning_class; ?> rounded-pill"><?php echo round($users_percent, 1); ?>%</span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar <?php echo $users_warning_class; ?>" role="progressbar" 
                            style="width: <?php echo $users_percent; ?>%;" 
                            aria-valuenow="<?php echo $users_percent; ?>" 
                            aria-valuemin="0" aria-valuemax="100">
                            <?php echo round($users_percent, 1); ?>%
                        </div>
                    </div>
                    <div class="text-center">
                        <h4><?php echo $users_today; ?> / <?php echo $max_users_threshold; ?></h4>
                        <p class="text-muted"><?php echo get_string('lastexecution', 'report_usage_monitor', !empty($reportconfig->lastexecution) ? userdate($reportconfig->lastexecution) : get_string('notcalculatedyet', 'report_usage_monitor')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('max_userdaily_for_90_days', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h1 class="display-4"><?php echo !empty($reportconfig->max_userdaily_for_90_days_users) ? $reportconfig->max_userdaily_for_90_days_users : '0'; ?></h1>
                        <p class="text-muted">
                            <?php echo !empty($reportconfig->max_userdaily_for_90_days_date) ? date(get_string('dateformat', 'report_usage_monitor'), $reportconfig->max_userdaily_for_90_days_date) : get_string('notcalculatedyet', 'report_usage_monitor'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Distribución del espacio en disco -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('disk_usage_distribution', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <?php
                    // Gráfico de dona para distribución de espacio en disco
                    if (!empty($dir_analysis) && $disk_usage > 0) {
                        $chart = new \core\chart_pie();
                        $chart->set_doughnut(true);
                        
                        $labels = [
                            get_string('database', 'report_usage_monitor'),
                            get_string('files_dir', 'report_usage_monitor'),
                            get_string('cache', 'report_usage_monitor'),
                            get_string('others', 'report_usage_monitor')
                        ];
                        
                        $values = [
                            $dir_analysis['database'],
                            $dir_analysis['filedir'],
                            $dir_analysis['cache'],
                            $dir_analysis['others']
                        ];
                        
                        $colors = ['#9b59b6', '#2ecc71', '#f1c40f', '#e67e22', '#95a5a6'];
                        
                        $chart->set_labels($labels);
                        $series = new \core\chart_series('', $values);
                        $series->set_colors($colors);
                        $chart->add_series($series);
                        
                        echo $OUTPUT->render($chart);
                    } else {
                        echo '<div class="alert alert-info">' . get_string('notcalculatedyet', 'report_usage_monitor') . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
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
                            if (!empty($dir_analysis) && $disk_usage > 0) {
                                $directories = [
                                    'database' => get_string('database', 'report_usage_monitor'),
                                    'filedir' => get_string('files_dir', 'report_usage_monitor'),
                                    'cache' => get_string('cache', 'report_usage_monitor'),
                                    'others' => get_string('others', 'report_usage_monitor')
                                ];
                                
                                foreach ($directories as $key => $label) {
                                    $size = $dir_analysis[$key];
                                    $percent = round(($size / $disk_usage) * 100, 2);
                                    ?>
                                    <tr>
                                        <td><?php echo $label; ?></td>
                                        <td><?php echo display_size($size); ?></td>
                                        <td><?php echo $percent; ?>%</td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="3" class="text-center">' . get_string('notcalculatedyet', 'report_usage_monitor') . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información del sistema y recomendaciones -->
    <div class="row">
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
                                    <div class="small text-muted"><?php echo get_string('moodle_version', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo $CFG->release; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('total_courses', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo $DB->count_records('course'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('backup_per_course', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo get_config('backup', 'backup_auto_max_kept'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="small text-muted"><?php echo get_string('registered_users', 'report_usage_monitor'); ?></div>
                                    <div class="h5"><?php echo $DB->count_records('user', array('deleted' => 0)) - 1; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('recommendations', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($disk_percent > 70): ?>
                    <div class="alert alert-<?php echo $disk_percent > 90 ? 'danger' : 'warning'; ?>">
                        <h5><?php echo get_string('space_saving_tips', 'report_usage_monitor'); ?></h5>
                        <ul class="mb-0">
                            <li><?php echo get_string('tip_backups', 'report_usage_monitor', get_config('backup', 'backup_auto_max_kept')); ?></li>
                            <li><?php echo get_string('tip_files', 'report_usage_monitor'); ?></li>
                            <li><?php echo get_string('tip_courses', 'report_usage_monitor'); ?></li>
                            <li><?php echo get_string('tip_cache', 'report_usage_monitor'); ?></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <?php echo get_string('disk_usage_ok', 'report_usage_monitor'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($users_percent > 70): ?>
                    <div class="alert alert-<?php echo $users_percent > 90 ? 'danger' : 'warning'; ?>">
                        <h5><?php echo get_string('user_limit_tips', 'report_usage_monitor'); ?></h5>
                        <ul class="mb-0">
                            <li><?php echo get_string('tip_user_inactive', 'report_usage_monitor'); ?></li>
                            <li><?php echo get_string('tip_user_limit', 'report_usage_monitor'); ?></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle"></i> <?php echo get_string('user_count_ok', 'report_usage_monitor'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($viewtab == 'userstopnum'): ?>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('users_today', 'report_usage_monitor', !empty($reportconfig->totalusersdaily) ? $reportconfig->totalusersdaily : get_string('notcalculatedyet', 'report_usage_monitor')); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo get_string('lastexecution', 'report_usage_monitor', !empty($reportconfig->lastexecution) ? userdate($reportconfig->lastexecution) : get_string('notcalculatedyet', 'report_usage_monitor')); ?></p>
                    
                    <h5 class="mt-4"><?php echo get_string('max_userdaily_for_90_days', 'report_usage_monitor'); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo get_string('date', 'report_usage_monitor'); ?></th>
                                    <th><?php echo get_string('usersquantity', 'report_usage_monitor'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo !empty($reportconfig->max_userdaily_for_90_days_date) ? date(get_string('dateformat', 'report_usage_monitor'), $reportconfig->max_userdaily_for_90_days_date) : get_string('notcalculatedyet', 'report_usage_monitor'); ?></td>
                                    <td><?php echo !empty($reportconfig->max_userdaily_for_90_days_users) ? $reportconfig->max_userdaily_for_90_days_users : get_string('notcalculatedyet', 'report_usage_monitor'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pestañas para Top Usuarios -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('topuser', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="usersTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="table-tab" data-toggle="tab" href="#table-view" role="tab" aria-controls="table-view" aria-selected="true">
                                <?php echo get_string('usertable', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="chart-tab" data-toggle="tab" href="#chart-view" role="tab" aria-controls="chart-view" aria-selected="false">
                                <?php echo get_string('userchart', 'report_usage_monitor'); ?>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="usersTabContent">
                        <div class="tab-pane fade show active" id="table-view" role="tabpanel" aria-labelledby="table-tab">
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
                                        <?php
                                        $userdailytop = report_user_daily_top_sql(get_string('dateformatsql', 'report_usage_monitor'));
                                        $userdaily_recordstop = $DB->get_records_sql($userdailytop);
                                        foreach ($userdaily_recordstop as $log) {
                                            $percent = ($max_users_threshold > 0) ? round(($log->cantidad_usuarios / $max_users_threshold) * 100, 1) : 0;
                                            $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
                                            echo '<tr>';
                                            echo '<td>' . $log->fecha . '</td>';
                                            echo '<td>' . $log->cantidad_usuarios . '</td>';
                                            echo '<td class="' . $class . '">' . $percent . '%</td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="chart-view" role="tabpanel" aria-labelledby="chart-tab">
                            <?php
                            $chart = new \core\chart_line();
                            $chart->set_smooth(true);
                            $userdailytop = report_user_daily_top_sql(get_string('dateformatsql', 'report_usage_monitor'));
                            $userdaily_recordstop = $DB->get_records_sql($userdailytop);
                            $data = [];
                            foreach ($userdaily_recordstop as $log) {
                                $data[$log->fecha] = $log->cantidad_usuarios;
                            }
                            uksort($data, 'compararFechas');
                            $chart->set_labels(array_keys($data));
                            $series = new \core\chart_series(
                                get_string('usersquantity', 'report_usage_monitor'),
                                array_values($data)
                            );
                            $chart->add_series($series);
                            
                            // Añadir serie del umbral
                            $threshold_values = array_fill(0, count($data), $max_users_threshold);
                            $threshold_series = new \core\chart_series(get_string('threshold', 'report_usage_monitor'), $threshold_values);
                            $threshold_series->set_type(\core\chart_series::TYPE_LINE);
                            $threshold_series->set_color('#dc3545');
                            $chart->add_series($threshold_series);
                            
                            echo $OUTPUT->render($chart);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Usuarios últimos 10 días -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('lastusers', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
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
                                <?php
                                $userdaily = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
                                $userdaily_records = $DB->get_records_sql($userdaily);
                                foreach ($userdaily_records as $log) {
                                    $percent = ($max_users_threshold > 0) ? round(($log->conteo_accesos_unicos / $max_users_threshold) * 100, 1) : 0;
                                    $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
                                    echo '<tr>';
                                    echo '<td>' . $log->fecha . '</td>';
                                    echo '<td>' . $log->conteo_accesos_unicos . '</td>';
                                    echo '<td class="' . $class . '">' . $percent . '%</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($viewtab == 'diskusage'): ?>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('diskusage', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo get_string('lastexecutioncalculate', 'report_usage_monitor', !empty($reportconfig->lastexecutioncalculate) ? userdate($reportconfig->lastexecutioncalculate) : get_string('notcalculatedyet', 'report_usage_monitor')); ?></p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar <?php echo $disk_warning_class; ?>" role="progressbar" 
                                    style="width: <?php echo $disk_percent; ?>%;" 
                                    aria-valuenow="<?php echo $disk_percent; ?>" 
                                    aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($disk_percent, 1); ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <th><?php echo get_string('sizeusage', 'report_usage_monitor'); ?></th>
                                        <td><?php echo display_size($disk_usage); ?> / <?php echo display_size($quotadisk); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo get_string('avalilabledisk', 'report_usage_monitor'); ?></th>
                                        <td><?php echo $quotadisk > 0 ? round(100 - $disk_percent, 2) : 0; ?>%</td>
                                    </tr>
                                    <tr>
                                        <th><?php echo get_string('sizedatabase', 'report_usage_monitor'); ?></th>
                                        <td><?php echo !empty($dir_analysis['database']) ? display_size($dir_analysis['database']) : 0; ?> (<?php echo ($disk_usage > 0 && !empty($dir_analysis['database'])) ? round(($dir_analysis['database'] / $disk_usage) * 100, 2) : 0; ?>%)</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('disk_usage_by_directory', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <?php
                    // Crear gráfico horizontal de barras para uso de disco por directorio
                    if (!empty($dir_analysis) && $disk_usage > 0) {
                        $chart = new \core\chart_bar();
                        $chart->set_horizontal(true);
                        
                        $dir_labels = [
                            get_string('database', 'report_usage_monitor'),
                            get_string('files_dir', 'report_usage_monitor'),
                            get_string('backups', 'report_usage_monitor'),
                            get_string('cache', 'report_usage_monitor'),
                            get_string('others', 'report_usage_monitor')
                        ];
                        
                        // Convertir bytes a GB para la gráfica
                        $dir_sizes = [
                            display_size_in_gb($dir_analysis['database']),
                            display_size_in_gb($dir_analysis['filedir']),
                            display_size_in_gb($dir_analysis['cache']),
                            display_size_in_gb($dir_analysis['others'])
                        ];
                        
                        $chart->set_labels($dir_labels);
                        $series = new \core\chart_series(get_string('size_in_gb', 'report_usage_monitor'), $dir_sizes);
                        $series->set_colors(['#9b59b6', '#2ecc71', '#f1c40f', '#e67e22', '#95a5a6']);
                        $chart->add_series($series);
                        
                        echo $OUTPUT->render($chart);
                    } else {
                        echo '<div class="alert alert-info">' . get_string('notcalculatedyet', 'report_usage_monitor') . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <?php
            // Obtener cursos más grandes
            $largest_courses = get_largest_courses(5);
            ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('largest_courses', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
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
                                <?php
                                if (!empty($largest_courses)) {
                                    foreach ($largest_courses as $course) {
                                        ?>
                                        <tr>
                                            <td><?php echo format_string($course->fullname) . ' (' . $course->shortname . ')'; ?></td>
                                            <td><?php echo display_size($course->totalsize); ?></td>
                                            <td><?php echo $course->percentage; ?>%</td>
                                            <td><?php echo $course->backupcount; ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">' . get_string('notcalculatedyet', 'report_usage_monitor') . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo get_string('recommendations', 'report_usage_monitor'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><?php echo get_string('space_saving_tips', 'report_usage_monitor'); ?></h5>
                        <ul>
                            <li><?php echo get_string('tip_backups', 'report_usage_monitor', get_config('backup', 'backup_auto_max_kept')); ?></li>
                            <li><?php echo get_string('tip_files', 'report_usage_monitor'); ?></li>
                            <li><?php echo get_string('tip_courses', 'report_usage_monitor'); ?></li>
                            <li><?php echo get_string('tip_cache', 'report_usage_monitor'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Créditos del plugin -->
<div class="mt-4 text-center text-muted small">
    <?php echo get_string('reportinfotext', 'report_usage_monitor'); ?>
</div>

<?php
echo $OUTPUT->footer();