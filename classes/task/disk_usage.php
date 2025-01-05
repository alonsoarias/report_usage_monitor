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
 * Scheduled task for calculating disk usage.
 *
 * @package    report_usage_monitor
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task class for calculating disk usage.
 */
class disk_usage extends \core\task\scheduled_task {

    /**
     * Returns the name of task for display.
     *
     * @return string Task name
     */
    public function get_name() {
        return get_string('calculatediskusagetask', 'report_usage_monitor');
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
            mtrace("Starting disk usage calculation task...");
        }

        try {
            // Calculate database size
            $sql = size_database();
            $dbsize = $DB->get_records_sql($sql);
            foreach ($dbsize as $item) {
                $totalusagereadabledb = $item->size;
                set_config('totalusagereadabledb', $totalusagereadabledb, 'report_usage_monitor');
            }

            // Calculate dataroot directory size
            $totalusagedataroot = directory_size($CFG->dataroot);

            // Calculate dirroot directory size
            $totalusagedirroot = directory_size($CFG->dirroot);

            // Calculate total readable disk usage
            $totalusagereadable = $totalusagedataroot + $totalusagedirroot;
            set_config('totalusagereadable', $totalusagereadable, 'report_usage_monitor');
            set_config('lastexecutioncalculate', time(), 'report_usage_monitor');

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Disk usage calculated:");
                mtrace("- Database size: " . display_size($totalusagereadabledb));
                mtrace("- Dataroot size: " . display_size($totalusagedataroot));
                mtrace("- Dirroot size: " . display_size($totalusagedirroot));
                mtrace("- Total usage: " . display_size($totalusagereadable));
                mtrace("Disk usage calculation task completed.");
            }

            return true;

        } catch (\Exception $e) {
            mtrace("Error in disk usage calculation: " . $e->getMessage());
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