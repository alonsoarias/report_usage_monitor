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

$plugin->component = 'report_usage_monitor'; // Nombre completo del plugin
$plugin->version   = 2025030402;  // Versión actualizada para refactorización de manejo de fechas y timestamps
$plugin->requires  = 2020061500;  // Versión mínima de Moodle requerida - Moodle 3.9
$plugin->release   = '4.5.3';     // Versión legible actualizada para reflejar mejoras en manejo de fechas y proyecciones
$plugin->maturity  = MATURITY_STABLE; // El nivel de madurez del plugin