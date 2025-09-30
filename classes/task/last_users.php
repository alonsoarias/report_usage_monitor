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

namespace report_usage_monitor\task;

use report_usage_monitor\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to calculate recently connected users.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class last_users extends \core\task\scheduled_task {
    
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('getlastusersconnected', 'report_usage_monitor');
    }
    
    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        global $DB;
        
        mtrace("Starting calculation of recently connected users...");
        
        // Calculate users for today.
        $todaystart = helper::get_day_start();
        $now = time();
        
        $userstoday = helper::get_users_logged_in($todaystart, $now);
        set_config('totalusersdaily', $userstoday, 'report_usage_monitor');
        
        // Get threshold and calculate percentage.
        $reportconfig = get_config('report_usage_monitor');
        $maxusersthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $userspercent = helper::calculate_percentage($userstoday, $maxusersthreshold);
        
        // Determine warning class.
        if ($userspercent < 70) {
            $warningclass = 'bg-success';
        } else if ($userspercent < 90) {
            $warningclass = 'bg-warning';
        } else {
            $warningclass = 'bg-danger';
        }
        
        // Store calculated values.
        set_config('users_percent', $userspercent, 'report_usage_monitor');
        set_config('users_warning_class', $warningclass, 'report_usage_monitor');
        set_config('lastexecution', time(), 'report_usage_monitor');
        set_config('lastexecutioncalculateuserdaily', time(), 'report_usage_monitor');
        
        mtrace("Recently connected users: $userstoday ($userspercent% of threshold)");
        mtrace("Task completed successfully.");
        
        return true;
    }
}