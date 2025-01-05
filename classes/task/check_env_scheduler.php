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
 * Scheduled task to queue the check_php_functions ad-hoc task.
 * 
 * This task runs periodically (by default, every 3 hours) to ensure
 * that the system environment is regularly checked and task frequencies
 * are adjusted accordingly.
 *
 * @package     report_usage_monitor
 * @category    task
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task class for environment checking scheduler.
 */
class check_env_scheduler extends \core\task\scheduled_task {

    /**
     * Returns the name of task for display in admin screens.
     *
     * @return string Task name
     */
    public function get_name() {
        return get_string('check_env_scheduler_taskname', 'report_usage_monitor');
    }

    /**
     * Executes the scheduled task.
     * 
     * This task queues the check_php_functions ad-hoc task if certain
     * conditions are met.
     *
     * @return bool True if task execution was successful
     */
    public function execute() {
        global $CFG;
        
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace('Starting check_env_scheduler...');
        }

        try {
            // Check if we should queue a new check
            if ($this->should_queue_check()) {
                // Create and configure the ad-hoc task
                $adhoc = new check_php_functions();
                
                // Add custom data if needed
                $data = new \stdClass();
                $data->timequeued = time();
                $adhoc->set_custom_data($data);

                // Queue the task
                \core\task\manager::queue_adhoc_task($adhoc);

                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace('Successfully queued check_php_functions ad-hoc task');
                }

                // Update last queued time
                set_config('last_check_queued', time(), 'report_usage_monitor');
            } else {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace('No need to queue environment check at this time');
                }
            }

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace('check_env_scheduler completed successfully');
            }

            return true;

        } catch (\Exception $e) {
            mtrace('Error in check_env_scheduler: ' . $e->getMessage());
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace($e->getTraceAsString());
            }
            return false;
        }
    }

    /**
     * Determines if a new environment check should be queued.
     * 
     * Checks are queued if:
     * - No previous check has been queued, or
     * - The environment status is unknown, or
     * - The last check was more than 3 hours ago
     *
     * @return bool True if a new check should be queued
     */
    private function should_queue_check() {
        // Get timestamps of last activities
        $lastqueued = (int)get_config('report_usage_monitor', 'last_check_queued');
        $lastcheck = (int)get_config('report_usage_monitor', 'last_environment_check');
        $currenttime = time();

        // Queue if no previous check
        if (empty($lastqueued) || empty($lastcheck)) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace('No previous environment check found');
            }
            return true;
        }

        // Get current environment status
        $shellexec = (bool)get_config('report_usage_monitor', 'shell_exec_available');
        $ducommand = (bool)get_config('report_usage_monitor', 'du_command_available');

        // Queue if status unknown
        if ($shellexec === null || $ducommand === null) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace('Environment status is unknown');
            }
            return true;
        }

        // Calculate time since last check
        $timesince = $currenttime - $lastcheck;
        $checkinterval = 3 * HOURSECS; // 3 hours

        // Queue if enough time has passed
        if ($timesince >= $checkinterval) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Time since last check ({$timesince}s) exceeds interval ({$checkinterval}s)");
            }
            return true;
        }

        return false;
    }

    /**
     * Indicates whether this task can be run from CLI.
     *
     * @return bool
     */
    public static function can_run_from_cli() {
        return true;
    }

    /**
     * Indicates whether this task can be run from cron.
     *
     * @return bool
     */
    public static function can_run_from_cron() {
        return true;
    }

    /**
     * Indicates whether this task should run in a separate process.
     *
     * @return bool
     */
    public static function should_run_in_separate_process() {
        return false; // Lightweight task, no need for separate process
    }
}