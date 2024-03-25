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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();
 
 $plugin->component = 'report_usage_monitor'; // Nombre completo del plugin, como se declaró en el archivo `pluginname/version.php`.
 $plugin->version   = 20240401001;  // La nueva versión en formato YYYYMMDDXX.
 $plugin->requires  = 2020061500;   // Versión mínima de Moodle requerida - esto corresponde a Moodle 3.9.
 $plugin->supported = [39, 311];    // Versión de Moodle soportada - desde 3.9 (39) hasta 3.11 (311) inclusive.
 $plugin->release   = '3.11';     // El número de versión legible por humanos.
 $plugin->maturity  = MATURITY_STABLE; // El nivel de madurez del plugin.