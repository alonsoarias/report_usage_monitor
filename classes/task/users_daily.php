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
 * Scheduled task for calculating daily unique users.
 *
 * @package    report_usage_monitor
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task class for calculating daily unique users.
 */
class users_daily extends \core\task\scheduled_task {

    /**
     * Returns the name of task for display.
     *
     * @return string Task name
     */
    public function get_name() {
        return get_string('getlastusers', 'report_usage_monitor');
    }

    /**
     * Executes the task.
     *
     * @return bool True if task completes successfully
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting daily unique users calculation task...");
        }

        try {
            // Get current top users
            $array_daily_top = [];
            $topsql = report_user_daily_top_task();
            $toprecords = $DB->get_records_sql(
                "SELECT DISTINCT fecha, cantidad_usuarios 
                 FROM ($topsql) AS t 
                 ORDER BY cantidad_usuarios DESC"
            );

            foreach ($toprecords as $record) {
                $array_daily_top[] = [
                    "usuarios" => (int)$record->cantidad_usuarios,
                    "fecha" => $record->fecha
                ];
            }

            // Find minimum users count if records exist
            $menor = null;
            if (!empty($array_daily_top)) {
                $menor = min(array_column($array_daily_top, 'usuarios'));
            }

            // Get yesterday's data
            $yesterday_sql = user_limit_daily_task();
            $yesterday_records = $DB->get_records_sql($yesterday_sql);

            // Default values for yesterday
            $users = [
                "usuarios" => 0,
                "fecha" => date('Y-m-d', strtotime('-1 day'))
            ];

            // Update with actual data if available
            if (!empty($yesterday_records)) {
                foreach ($yesterday_records as $record) {
                    if (!empty($record->conteo_accesos_unicos) && !empty($record->fecha)) {
                        $users["usuarios"] = (int)$record->conteo_accesos_unicos;
                        $users["fecha"] = $record->fecha;
                    }
                    break; // Only need first record
                }
            }

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Processing data: fecha={$users['fecha']}, usuarios={$users['usuarios']}");
            }

            // Check if the record already exists
            $exists = $DB->record_exists('report_usage_monitor', ['fecha' => $users['fecha']]);

            if ($exists) {
                // Update existing record
                $DB->execute(
                    "UPDATE {report_usage_monitor} 
                     SET cantidad_usuarios = ? 
                     WHERE fecha = ?",
                    [$users['usuarios'], $users['fecha']]
                );

                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Updated existing record for fecha={$users['fecha']}.");
                }
            } else {
                // Insert or replace record based on conditions
                if (empty($array_daily_top) || count($array_daily_top) < 10) {
                    insert_top_sql($users['fecha'], $users['usuarios']);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Inserted new record in top.");
                    }
                } else {
                    if (!is_null($menor) && $users['usuarios'] >= $menor) {
                        update_min_top_sql($users['fecha'], $users['usuarios'], $menor);
                        if (debugging('', DEBUG_DEVELOPER)) {
                            mtrace("Updated existing record (replacing min={$menor}).");
                        }
                    }
                }
            }

            // Update last execution time
            set_config('lastexecution', time(), 'report_usage_monitor');

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Daily unique users calculation task completed.");
            }

            return true;

        } catch (\Exception $e) {
            mtrace("Error in daily users calculation: " . $e->getMessage());
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace($e->getTraceAsString());
            }
            return false;
        }
    }

    /**
     * Indicates whether this task can be run from CLI.
     *
     * @return bool
     */
    public static function can_run_from_cli() {
        return true;
    }
}
