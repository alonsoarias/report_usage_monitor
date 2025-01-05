<?php
// This file is part of Moodle - https://moodle.org/
// Moodle is free software...

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

class users_daily extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('getlastusers', 'report_usage_monitor');
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea para calcular el top de accesos únicos diarios...");
        }

        // 1) Obtener top actual
        $array_daily_top = [];
        $userdailytop_sql = report_user_daily_top_task();
        $toprecords = $DB->get_records_sql("SELECT fecha, cantidad_usuarios FROM ($userdailytop_sql) AS t ORDER BY cantidad_usuarios DESC");
        foreach ($toprecords as $r) {
            $array_daily_top[] = ["usuarios" => (int)$r->cantidad_usuarios, "fecha" => $r->fecha];
        }
        $menor = null;
        if (!empty($array_daily_top)) {
            $menor = min(array_column($array_daily_top, 'usuarios'));
        }

        // 2) Consulta para el día anterior
        $users_daily_sql = user_limit_daily_task();
        $users_daily_record = $DB->get_records_sql($users_daily_sql);

        // Por defecto: 0 usuarios para ayer
        $users = [
            "usuarios" => 0,
            "fecha" => date('Y-m-d', strtotime('-1 day'))
        ];

        // Si la consulta arrojó algo, actualizar
        if (!empty($users_daily_record)) {
            foreach ($users_daily_record as $log) {
                if (!empty($log->conteo_accesos_unicos) && !empty($log->fecha)) {
                    $users["usuarios"] = (int)$log->conteo_accesos_unicos;
                    $users["fecha"]    = $log->fecha;
                }
                break; // Se espera 1 registro
            }
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Datos a procesar: fecha={$users['fecha']}, usuarios={$users['usuarios']}");
        }

        // 3) Insertar/actualizar en la tabla local
        if (empty($array_daily_top) || count($array_daily_top) < 10) {
            insert_top_sql($users['fecha'], $users['usuarios']);
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Insertando nuevo registro en top.");
            }
        } else {
            if (!is_null($menor) && $users['usuarios'] >= $menor) {
                update_min_top_sql($users['fecha'], $users['usuarios'], $menor);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Actualizando registro existente (reemplazando el menor={$menor}).");
                }
            }
        }

        // 4) Guardar fecha/hora de última ejecución
        set_config('lastexecution', time(), 'report_usage_monitor');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Tarea completada: users_daily.");
        }
        return true;
    }
}
