<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Página principal del reporte "Usage Monitor".
 *
 * @package     report_usage_monitor
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'report']);

// Pestaña principal
$viewtab = optional_param('view', 'usage_monitor', PARAM_ALPHA);
// Subpestaña para la sección de usuarios
$subview = optional_param('subview', 'usertable', PARAM_ALPHA);

// Definimos las pestañas principales
$tabdata = [
    'userstopnum' => '',
    'diskusage'   => ''
];

// Validar la pestaña
if (!array_key_exists($viewtab, $tabdata)) {
    $viewtab = array_keys($tabdata)[0];
}

// Construir las pestañas de nivel superior
$tabs = [];
foreach ($tabdata as $tabname => $unused) {
    $tabs[] = new tabobject(
        $tabname,
        new moodle_url($PAGE->url, ['view' => $tabname]),
        get_string($tabname, 'report_usage_monitor')
    );
}

// Imprimir header y tabs
echo $OUTPUT->header();
echo $OUTPUT->tabtree($tabs, $viewtab);

// Configuración del plugin
$reportconfig = get_config('report_usage_monitor');

// -------------------------------------------------------------------
// PESTAÑA: USUARIOS DIARIOS (userstopnum)
// -------------------------------------------------------------------
if ($viewtab === 'userstopnum') {
    // Título principal
    echo $OUTPUT->heading(get_string('userstopnum', 'report_usage_monitor'));

    // Texto de alerta
    echo html_writer::tag(
        'div',
        get_string('exclusivedisclaimer', 'report_usage_monitor'),
        ['class' => 'alert alert-warning']
    );

    // ---------------------------------------------------------------
    // SUBTABS: usertable, userchart (para los últimos 10 días)
    // ---------------------------------------------------------------
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
        ),
    ];

    // Mostrar subtabs
    echo $OUTPUT->tabtree($subtabs, $subview);

    // ---------------------------------------------------------------
    // 1) Sección de "últimos 10 días" (tabla o gráfica según $subview)
    // ---------------------------------------------------------------
    // Consulta para los últimos 10 días
    $last10sql     = report_user_daily_sql(get_string('dateformatsql', 'report_usage_monitor'));
    $last10records = $DB->get_records_sql($last10sql);

    // Preparar arrays para tabla / gráfica
    $labelsLast10 = [];
    $dataLast10   = [];

    foreach ($last10records as $r) {
        // Almacenar para potencial gráfica
        $labelsLast10[] = $r->fecha;
        $dataLast10[]   = (int)$r->conteo_accesos_unicos;
    }

    // Si subview = usertable => tabla
    if ($subview === 'usertable') {
        echo $OUTPUT->heading(get_string('lastusers', 'report_usage_monitor'));
        $table_last10 = new html_table();
        $table_last10->head = [
            get_string('date', 'report_usage_monitor'),
            get_string('usersquantity', 'report_usage_monitor')
        ];
        foreach ($last10records as $row) {
            $table_last10->data[] = [
                $row->fecha,
                $row->conteo_accesos_unicos
            ];
        }
        echo html_writer::table($table_last10);

    // Si subview = userchart => gráfica
    } elseif ($subview === 'userchart') {
        echo $OUTPUT->heading(get_string('lastusers', 'report_usage_monitor'));
        $chartLast10 = new \core\chart_line();
        $chartLast10->set_smooth(true);
        $chartLast10->set_labels($labelsLast10);

        $seriesLast10 = new \core\chart_series(
            get_string('usersquantity', 'report_usage_monitor'),
            $dataLast10
        );
        $chartLast10->add_series($seriesLast10);
        $chartLast10->set_title(get_string('lastusers', 'report_usage_monitor'));

        echo $OUTPUT->render($chartLast10);
    }

    // ---------------------------------------------------------------
    // 2) Sección "Top 10 Usuarios"
    // ---------------------------------------------------------------
    echo $OUTPUT->heading(get_string('topuser', 'report_usage_monitor'));

    // Consulta del top de usuarios guardado en la tabla local
    $topsql     = report_user_daily_top_sql(get_string('dateformat', 'report_usage_monitor'));
    $toprecords = $DB->get_records_sql($topsql);

    $table_top = new html_table();
    $table_top->head = [
        get_string('date', 'report_usage_monitor'),
        get_string('usersquantity', 'report_usage_monitor')
    ];
    if (!empty($toprecords)) {
        foreach ($toprecords as $row) {
            $table_top->data[] = [
                $row->fecha,
                $row->cantidad_usuarios
            ];
        }
    }
    echo html_writer::table($table_top);

// -------------------------------------------------------------------
// PESTAÑA: USO DE DISCO (diskusage)
// -------------------------------------------------------------------
} elseif ($viewtab === 'diskusage') {
    echo $OUTPUT->heading(get_string('diskusage', 'report_usage_monitor'));

    echo html_writer::tag(
        'div',
        get_string('exclusivedisclaimer', 'report_usage_monitor'),
        ['class' => 'alert alert-warning']
    );

    // Último cálculo del uso de disco
    $updatestringcalculate = !empty($reportconfig->lastexecutioncalculate)
        ? userdate($reportconfig->lastexecutioncalculate)
        : get_string('notcalculatedyet', 'report_usage_monitor');

    echo html_writer::span(
        get_string('lastexecutioncalculate', 'report_usage_monitor', $updatestringcalculate)
    );

    // Calcular el uso real
    $usedFS  = (float)($reportconfig->totalusagereadable   ?? 0);
    $usedDB  = (float)($reportconfig->totalusagereadabledb ?? 0);
    $usedTotalBytes = $usedFS + $usedDB;

    // Cuota en GB -> bytes
    $disk_quota_gb    = (float)get_config('report_usage_monitor', 'disk_quota');
    $disk_quota_bytes = $disk_quota_gb * 1024 * 1024 * 1024;

    // Convertir a string legible
    $usedTotalStr = display_size($usedTotalBytes);
    $dbSizeStr    = display_size($usedDB);

    // Tabla con info de disco
    $table_disk = new html_table();
    $table_disk->head = [
        get_string('sizeusage', 'report_usage_monitor'),
        get_string('avalilabledisk', 'report_usage_monitor'),
        get_string('sizedatabase', 'report_usage_monitor')
    ];

    // Calcular % uso
    $usedPercent = 0;
    if ($disk_quota_bytes > 0) {
        $usedPercent = ($usedTotalBytes / $disk_quota_bytes) * 100;
    }
    $freePercent = 100 - $usedPercent;

    // Fila en la tabla
    $table_disk->data[] = [
        $usedTotalStr . ' / ' . $disk_quota_gb . ' GB',
        round($freePercent, 2) . '%',
        $dbSizeStr
    ];
    echo html_writer::table($table_disk);

    // Gráfico pastel (pie)
    $chartPie = new \core\chart_pie();
    $chartPie->set_title(get_string('diskusage', 'report_usage_monitor'));

    // Cálculo en GB
    $usedGB = round($usedTotalBytes / (1024 * 1024 * 1024), 2);
    $freeGB = $disk_quota_gb - $usedGB;
    if ($freeGB < 0) {
        $freeGB = 0;
    }

    $labelsPie = [
        get_string('sizeusage', 'report_usage_monitor') . " ({$usedGB} GB)",
        get_string('avalilabledisk', 'report_usage_monitor') . " ({$freeGB} GB)"
    ];
    $dataPie = [$usedGB, $freeGB];

    $seriesPie = new \core\chart_series(
        get_string('diskusage', 'report_usage_monitor'),
        $dataPie
    );
    $chartPie->add_series($seriesPie);
    $chartPie->set_labels($labelsPie);

    echo $OUTPUT->render($chartPie);
}

// Créditos finales
echo get_string('reportinfotext', 'report_usage_monitor');
echo $OUTPUT->footer();
