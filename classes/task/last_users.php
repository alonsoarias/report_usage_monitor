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
 * Scheduled task for calculating recently connected users.
 *
 * @package    report_usage_monitor
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task class for calculating recently connected users.
 */
class last_users extends \core\task\scheduled_task {

    /**
     * Returns the name of task for display.
     *
     * @return string Task name
     */
    public function get_name() {
        return get_string('getlastusersconnected', 'report_usage_monitor');
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
            mtrace("Starting recent users calculation task...");
        }

        try {
            // Get recently connected users for today
            $records = $DB->get_records_sql(users_today());
            
            $users_today = 0;
            foreach ($records as $record) {
                $users_today = $record->conteo_accesos_unicos;
                set_config('totalusersdaily', $users_today, 'report_usage_monitor');
            }

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Recent users count: $users_today");
                mtrace("Recent users calculation task completed.");
            }

            return true;

        } catch (\Exception $e) {
            mtrace("Error calculating recent users: " . $e->getMessage());
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