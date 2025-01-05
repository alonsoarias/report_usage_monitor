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
 * Unified notification task that handles both disk and user monitoring.
 *
 * @package    report_usage_monitor
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task class for sending unified usage notifications.
 */
class notification_usage extends \core\task\scheduled_task {

    /**
     * Returns the name of task for display.
     *
     * @return string Task name
     */
    public function get_name() {
        return get_string('notification_usage_taskname', 'report_usage_monitor');
    }

    /**
     * Executes the task.
     *
     * @return bool True if task completes successfully
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace('Starting unified usage notification task...');
        }

        try {
            // Gather all usage information
            $info = usage_monitor_gather_info();

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace('Usage information gathered:');
                mtrace('- Users: ' . $info->users . '/' . $info->userthreshold . 
                      ' (' . round($info->userpercent, 2) . '%)');
                mtrace('- Disk: ' . display_size($info->diskusage) . '/' . 
                      display_size($info->diskquota) . 
                      ' (' . round($info->diskpercent, 2) . '%)');
            }

            // Check if notification is needed
            if (usage_monitor_should_notify($info)) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace('Notification criteria met, sending notification...');
                }

                // Send notification
                if (usage_monitor_send_notification($info)) {
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace('Notification sent successfully.');
                    }
                } else {
                    mtrace('Error: Failed to send notification.');
                    return false;
                }
            } else {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace('No notification needed at this time.');
                }
            }

            // Check environment and update task frequency if needed
            $env = usage_monitor_check_environment();
            if (usage_monitor_update_task_frequency($env)) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace('Task frequency updated based on environment check.');
                }
            }

        } catch (\Exception $e) {
            mtrace('Error in usage notification task: ' . $e->getMessage());
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace($e->getTraceAsString());
            }
            return false;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace('Unified usage notification task completed.');
        }

        return true;
    }
}