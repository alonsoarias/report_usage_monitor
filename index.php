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

admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'report']);
$viewtab = optional_param('view', 'usage_monitor', PARAM_ALPHA);

// Datos para las pestañas en el informe.
$tabdata = ['userstopnum' => '', 'diskusage' => ''];
if (!array_key_exists($viewtab, $tabdata)) {
    // Para un valor de parámetro inválido, utilizar 'USAGE_MONITOR'.
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
if (empty($download)) {
    print $OUTPUT->header();
    echo $OUTPUT->tabtree($tabs, $viewtab);
}
$reportconfig = get_config('report_usage_monitor');
if ($viewtab == 'userstopnum') {
    $updatestring = !empty($reportconfig->lastexecution) ? userdate($reportconfig->lastexecution) : get_string('notcalculatedyet', 'report_usage_monitor');
    $updatestring2 = !empty($reportconfig->totalusersdaily) ? ($reportconfig->totalusersdaily) : get_string('notcalculatedyet', 'report_usage_monitor');
    echo html_writer::span(get_string('lastexecution', 'report_usage_monitor', $updatestring));
    echo "<br/>";
    echo html_writer::span(get_string('users_today', 'report_usage_monitor', $updatestring2));
    echo $OUTPUT->heading(get_string('max_userdaily_for_90_days', 'report_usage_monitor'));
    $table2 = new html_table();
    $table2->colclasses[] = 'centeralign';
    $table2->attributes['cellpadding'] = '0';
    $table2->head = array(
        get_string('date', 'report_usage_monitor'),
        get_string('usersquantity', 'report_usage_monitor')
    );
    $updatestring3 = !empty($reportconfig->max_userdaily_for_90_days_date) ? date(get_string('dateformat', 'report_usage_monitor'), $reportconfig->max_userdaily_for_90_days_date) : get_string('notcalculatedyet', 'report_usage_monitor');
    $updatestring4 = !empty($reportconfig->max_userdaily_for_90_days_users) ? ($reportconfig->max_userdaily_for_90_days_users) : get_string('notcalculatedyet', 'report_usage_monitor');
    $table2->data[] =  array(($updatestring3), ($updatestring4));
    echo html_writer::table($table2);
    // Código para mostrar la subpestaña con opciones de visualización

    $subtabs = array(
        new tabobject('usertable', new moodle_url($PAGE->url, ['view' => 'userstopnum', 'subview' => 'usertable']), get_string('usertable', 'report_usage_monitor')),
        new tabobject('userchart', new moodle_url($PAGE->url, ['view' => 'userstopnum', 'subview' => 'userchart']), get_string('userchart', 'report_usage_monitor'))
    );
    echo $OUTPUT->heading(get_string('topuser', 'report_usage_monitor'));
    $subtabtree = $OUTPUT->tabtree($subtabs, optional_param('subview', 'usertable', PARAM_ALPHA));

    if ($subtabtree) {
        echo $subtabtree;
    }

    if (optional_param('subview', 'usertable', PARAM_ALPHA) == 'usertable') {
        $table1 = new html_table();
        $table1->head = array(
            get_string('date', 'report_usage_monitor'),
            get_string('usersquantity', 'report_usage_monitor')
        );
        $table1->colclasses[] = 'centeralign';
        $table1->attributes['cellpadding'] = '0';
        $userdailytop = report_user_daily_top_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $userdaily_recordstop = $DB->get_records_sql($userdailytop);
        foreach ($userdaily_recordstop as $log) {
            $table1->data[] = array(
                $log->fecha,
                $log->cantidad_usuarios
            );
        }
        echo html_writer::table($table1);
    } elseif (optional_param('subview', 'usertable', PARAM_ALPHA) == 'userchart') {
        $chart = new \core\chart_line();
        $chart->set_smooth(true);
        $userdailytop = report_user_daily_top_sql(get_string('dateformatsql', 'report_usage_monitor'));
        $userdaily_recordstop = $DB->get_records_sql($userdailytop);
        $data = [];
        foreach ($userdaily_recordstop as $log) {
            $table->data[] = array(
                $log->fecha,
                $log->cantidad_usuarios
            );
            $data[$log->fecha] = $log->cantidad_usuarios;
        }
        uksort($data, 'compararFechas');
        $chart->set_labels(array_keys($data)); // Fechas
        $series = new \core\chart_series(
            get_string('usersquantity', 'report_usage_monitor'),
            array_values($data)
        );
        $chart->add_series($series);

        // Mostrar la gráfica
        echo $OUTPUT->render($chart);
    }
    echo $OUTPUT->heading(get_string('lastusers', 'report_usage_monitor'));
    $table = new html_table();
    $table->head = array(
        get_string('date', 'report_usage_monitor'),
        get_string('usersquantity', 'report_usage_monitor'),
    );
    $table->colclasses[] = 'centeralign';
    $table->attributes['cellpadding'] = '0';
    $userdaily = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $userdaily_records = $DB->get_records_sql($userdaily);
    foreach ($userdaily_records as $log) {
        $table->data[] = array(
            $log->fecha,
            $log->conteo_accesos_unicos
        );
    }
    echo html_writer::table($table);
} elseif ($viewtab == 'diskusage') {
    echo $OUTPUT->heading(get_string('diskusage', 'report_usage_monitor'));
    $updatestring5 = !empty($reportconfig->lastexecutioncalculate) ? userdate($reportconfig->lastexecutioncalculate) : get_string('notcalculatedyet', 'report_usage_monitor');
    $updatestring6 = !empty($reportconfig->totalusagereadable) ? ($reportconfig->totalusagereadable) : get_string('notcalculatedyet', 'report_usage_monitor');
    $updatestring7 = !empty($reportconfig->totalusagereadabledb) ? ($reportconfig->totalusagereadabledb) : get_string('notcalculatedyet', 'report_usage_monitor');
    echo html_writer::span(get_string('lastexecutioncalculate', 'report_usage_monitor', $updatestring5));

    $table3 = new html_table();
    $table3->colclasses[] = 'centeralign';
    $table3->attributes['cellpadding'] = '0';
    $table3->head = array(
        get_string('sizeusage', 'report_usage_monitor'),
        get_string('avalilabledisk', 'report_usage_monitor'),
        get_string('sizedatabase', 'report_usage_monitor')
    );

    // Convertir los tamaños a GB para la tabla y agregar la etiqueta "GB"
    $used_space_gb_db = display_size_in_gb($updatestring7, 2);
    $used_space_total = display_size_in_gb((is_numeric($updatestring6) + is_numeric($updatestring7)), 2);
    // Obtener la cuota de disco desde la configuración
    $disk_quota_gb = get_config('report_usage_monitor', 'disk_quota');
    $free_space = $disk_quota_gb - $used_space_total;
    $free_space_percentage = round((($free_space / $disk_quota_gb) * 100),2) . '%';
    $table3->data[] = array($used_space_total . ' GB / ' . $disk_quota_gb . ' GB',  $free_space_percentage, $used_space_gb_db.' GB');
    echo html_writer::table($table3);

    // Crear la gráfica de barras horizontales del espacio en disco usado y el espacio restante con el límite
    echo $OUTPUT->heading(get_string('diskusage', 'report_usage_monitor'));
    $chart = new \core\chart_bar();
    $chart->set_horizontal(true);
    $chart->set_labels(array(get_string('sizeusage', 'report_usage_monitor') . ' (GB)', get_string('sizedatabase', 'report_usage_monitor') . ' (GB)')); // Agregar la etiqueta "GB" a las leyendas

    // Convertir los tamaños a GB para la gráfica
    $used_space_total_gb = display_size_in_gb((is_numeric($updatestring6) + is_numeric($updatestring7)), 2);
    $used_space_gb_db = display_size_in_gb($updatestring7, 2);


    //var_dump($disk_quota_gb);
    $total_disk_space_gb = $disk_quota_gb; // Ya estamos trabajando con GB

    // Calcular los porcentajes y colores de uso del espacio en disco
    $disk_usage = diskUsagePercentages($used_space_total_gb, $total_disk_space_gb);
    $disk_usage_db = diskUsagePercentages($used_space_gb_db, $total_disk_space_gb);
    // Verificar que $chart no sea null antes de configurar los colores
    if ($chart) {
        // Establecer los colores de las barras de la gráfica
        $series = new \core\chart_series(get_string('diskusage', 'report_usage_monitor'), array($used_space_total_gb, $used_space_gb_db));
        $series->set_colors(array($disk_usage['color'], $disk_usage_db['color']));

        // Pasar los porcentajes de uso del espacio en disco al código JavaScript
        echo '<script>var diskUsagePercentages = {' .
            'folder: ' . json_encode($disk_usage) . ', ' .
            'db: ' . json_encode($disk_usage_db) .
            '};</script>';

        // Agregar la serie a la gráfica y mostrarla
        $chart->add_series($series);
        echo $OUTPUT->render($chart);
    } else {
        echo 'Error: No se pudo crear la gráfica.';
    }
}

//créditos
echo get_string('reportinfotext', 'report_usage_monitor');
echo $OUTPUT->footer();
