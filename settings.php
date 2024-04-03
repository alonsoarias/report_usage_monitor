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
 * Plugin administration pages are defined here.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Verificar si la función shell_exec está activa en el servidor
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/max_daily_users_threshold',
        get_string('max_daily_users_threshold', 'report_usage_monitor'), // Umbral máximo de usuarios diarios
        get_string('configmax_daily_users_threshold', 'report_usage_monitor'), // Configure el umbral máximo de usuarios diarios permitidos para la notificación.
        100,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/disk_quota',
        get_string('disk_quota', 'report_usage_monitor'), // Cuota de disco
        get_string('configdisk_quota', 'report_usage_monitor'), // Configure la cuota de disco (en GB) para la notificación.
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/email',
        get_string('email', 'report_usage_monitor'), // Correo electrónico
        get_string('configemail', 'report_usage_monitor'), // Configure el correo electrónico para recibir las notificaciones.
        'hostingmoodle@ingeweb.co',
        PARAM_EMAIL,
        50
    ));

    // Shell_exec está activo, intentar localizar la ruta de 'du'
    if (function_exists('shell_exec')) {
        // Detectar el path de 'du' automáticamente si el sistema operativo es Linux
        if (PHP_OS_FAMILY === 'Linux') {
            $pathToDu = shell_exec('which du');
            $pathToDu = trim($pathToDu ?? ''); // Asegura que no se pase null a trim()

            // Comprobar que $pathToDu no esté vacío y que el archivo exista.
            if (!empty($pathToDu) && file_exists($pathToDu)) {
                $defaultPathToDu = $pathToDu;

                // Obtenemos el valor de pathtodu guardado en la configuración (si existe)
                $currentPathToDu = get_config('pathtodu');

                // Si no existe un valor en la configuración o el valor encontrado es diferente, lo asignamos.
                if (empty($currentPathToDu) || $currentPathToDu !== $defaultPathToDu) {
                    set_config('pathtodu', $defaultPathToDu);
                }
            } else {
                // 'du' no se encontró, mostrar recomendación para configurar pathtodu
                $infocontent = html_writer::tag('div', get_string('pathtodurecommendation', 'report_usage_monitor'), array('class' => 'alert alert-info'));
                $settings->add(new admin_setting_heading(
                    'report_usage_monitor/pathtodurecommendation',
                    '', // No se requiere texto aquí
                    $infocontent // información del reporte
                ));
                $defaultPathToDu = ''; // Usa una cadena vacía como valor por defecto si 'du' no se encontró.
            }
        } else {
            // No es sistema operativo Linux, mostrar recomendación para configurar pathtodu
                // 'du' no se encontró, mostrar recomendación para configurar pathtodu
                $infocontent = html_writer::tag('div', get_string('pathtodurecommendation', 'report_usage_monitor'), array('class' => 'alert alert-info'));
                $settings->add(new admin_setting_heading(
                    'report_usage_monitor/pathtodurecommendation',
                    '', // No se requiere texto aquí
                    $infocontent // información del reporte
                ));
        }

        // Se añade la configuración para pathtodu en la página de configuración del plugin.
        $settings->add(new admin_setting_configexecutable(
            'pathtodu', 
            get_string('pathtodu', 'report_usage_monitor'),
            get_string('configpathtodu', 'report_usage_monitor') . 
            '<br>' . 
            get_string('pathtodunote', 'report_usage_monitor'), // Aquí se añade la nota sobre la detección de 'du' en Linux.
            $defaultPathToDu ?? '', // Mostrar el valor de $defaultPathToDu si está configurado, de lo contrario mostrar cadena vacía.
            PARAM_PATH,
            255
        ));
    } else {
        // Shell_exec no está activo, mostrar notificación de advertencia para activarlo.
        $alertcontent = html_writer::tag('div', get_string('activateshellexec', 'report_usage_monitor'), array('class' => 'alert alert-danger'));
        $settings->add(new admin_setting_heading(
            'report_usage_monitor/activateshellexec',
            '', // No se requiere texto aquí
            $alertcontent // Información del reporte
        ));
    }

    $settings->add(new admin_setting_heading(
        'report_usage_monitor/reportinfotext',
        '', // No se requiere texto aquí
        get_string('reportinfotext', 'report_usage_monitor') // Información del reporte
    ));
}

$ADMIN->add('reports', new admin_externalpage(
    'report_usage_monitor',
    get_string('pluginname', 'report_usage_monitor'), // Monitor de Uso del Reporte
    new moodle_url('/report/usage_monitor/index.php')
));
?>
