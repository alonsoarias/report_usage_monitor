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
 * Ad-hoc task to check PHP functions and system requirements.
 * 
 * This task verifies if shell_exec is active and if pathtodu is executable,
 * then adjusts the disk_usage task frequency in the scheduled tasks table.
 *
 * @package     report_usage_monitor
 * @category    task
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class check_php_functions is an ad-hoc task that extends \core\task\adhoc_task.
 * It is queued to dynamically verify shell_exec/pathtodu availability.
 */
class check_php_functions extends \core\task\adhoc_task {

    /**
     * Returns the name of the task for display in logs.
     *
     * @return string
     */
    public function get_name() {
        return get_string('check_php_functions_taskname', 'report_usage_monitor');
    }

    /**
     * Main logic for the ad-hoc task.
     * Checks PHP functions and system requirements, then updates task frequencies.
     *
     * @return bool True if all operations completed successfully
     */
    public function execute() {
        global $CFG, $DB;

        mtrace('[Ad-hoc] check_php_functions started...');

        try {
            // 1. Verify shell_exec availability
            $disabledfunctions = explode(',', (string) ini_get('disable_functions'));
            $disabledfunctions = array_map('trim', $disabledfunctions);
            $shellExecOk = function_exists('shell_exec') && 
                          !in_array('shell_exec', $disabledfunctions, true);

            mtrace(' - shell_exec available? ' . ($shellExecOk ? 'YES' : 'NO'));

            // 2. Verify pathtodu configuration and executability
            $duOk = false;
            if ($shellExecOk) {
                if (PHP_OS_FAMILY === 'Linux') {
                    // Try to auto-detect du path on Linux
                    $pathToDu = trim(shell_exec('which du') ?? '');
                    
                    if (!empty($pathToDu) && is_executable($pathToDu)) {
                        // Update Moodle config if path changed
                        if (empty($CFG->pathtodu) || $CFG->pathtodu !== $pathToDu) {
                            set_config('pathtodu', $pathToDu);
                            mtrace(" - Updated pathtodu config to: $pathToDu");
                        }
                        $duOk = true;
                    } else {
                        mtrace(' - Could not find executable du command');
                    }
                } else {
                    // Check existing pathtodu config on non-Linux systems
                    if (!empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu))) {
                        $duOk = true;
                        mtrace(" - Verified existing pathtodu: {$CFG->pathtodu}");
                    } else {
                        mtrace(' - Invalid or missing pathtodu configuration');
                    }
                }
            }

            mtrace(' - du command available and executable? ' . ($duOk ? 'YES' : 'NO'));

            // 3. Update disk_usage task frequency based on capabilities
            $classname = 'report_usage_monitor\task\disk_usage';
            $record = $DB->get_record('task_scheduled', 
                ['classname' => $classname], 
                '*', 
                IGNORE_MISSING
            );

            if ($record) {
                $oldHour = $record->hour;
                // Set frequency based on du availability
                $record->hour = $duOk ? '*/6' : '12';

                if ($oldHour !== $record->hour) {
                    $DB->update_record('task_scheduled', $record);
                    mtrace(" - Updated disk_usage task frequency: {$record->hour}");
                } else {
                    mtrace(' - No change needed in task frequency');
                }
            } else {
                mtrace(' - Warning: disk_usage task not found in scheduled tasks');
            }

            // 4. Log environment status to plugin config for reference
            set_config('shell_exec_available', $shellExecOk ? '1' : '0', 'report_usage_monitor');
            set_config('du_command_available', $duOk ? '1' : '0', 'report_usage_monitor');
            set_config('last_environment_check', time(), 'report_usage_monitor');

            mtrace('[Ad-hoc] check_php_functions completed successfully');
            return true;

        } catch (\Exception $e) {
            mtrace('Error in check_php_functions task: ' . $e->getMessage());
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace($e->getTraceAsString());
            }
            return false;
        }
    }

    /**
     * Returns if this task can be run using CLI.
     *
     * @return bool
     */
    public static function can_run_from_cli() {
        return true;
    }

    /**
     * Returns if this task should run during cron.
     *
     * @return bool
     */
    public static function can_run_from_cron() {
        return true;
    }

    /**
     * Returns if this task should be run in a separate process.
     *
     * @return bool
     */
    public static function should_run_in_separate_process() {
        return true;
    }
}