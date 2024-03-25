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

$plugin->version   = 2023080201;  // Versión del plugin (compatible con Moodle 3.5 y superiores).
$plugin->requires  = 2018050800;  // Versión mínima requerida de Moodle (Moodle 3.5).
$plugin->component = 'report_usage_monitor'; // Nombre del componente del plugin.
$plugin->maturity = MATURITY_STABLE; // Nivel de madurez del plugin.
$plugin->release  = '3.0.1';        // Número de versión de la versión estable.
