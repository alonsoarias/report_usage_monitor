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
 * Scheduled task to calculate daily users top.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_daily extends \core\task\scheduled_task {
    
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('getlastusers', 'report_usage_monitor');
    }
    
    /**
     * Execute the task.
     *
     * @return bool
     */
    public function execute() {
        global $DB;
        
        mtrace("Starting calculation of daily users top...");
        
        // Get yesterday's user count.
        $yesterdaystart = helper::get_day_start(strtotime('yesterday'));
        $yesterdayend = helper::get_day_end(strtotime('yesterday'));
        
        $yesterdayusers = helper::get_users_logged_in($yesterdaystart, $yesterdayend);
        
        if ($yesterdayusers > 0) {
            // Get current top records.
            $toprecords = $DB->get_records('report_usage_monitor', null, 'usercount DESC', '*', 0, 10);
            
            if (count($toprecords) < 10) {
                // If we have less than 10 records, just insert.
                $record = new \stdClass();
                $record->timecreated = $yesterdaystart;
                $record->usercount = $yesterdayusers;
                $DB->insert_record('report_usage_monitor', $record);
                
                mtrace("Inserted new record: $yesterdayusers users on " . userdate($yesterdaystart));
            } else {
                // Find the minimum and check if yesterday's count is higher.
                $minrecord = null;
                foreach ($toprecords as $rec) {
                    if (!$minrecord || $rec->usercount < $minrecord->usercount) {
                        $minrecord = $rec;
                    }
                }
                
                if ($minrecord && $yesterdayusers > $minrecord->usercount) {
                    // Update the minimum record.
                    $minrecord->timecreated = $yesterdaystart;
                    $minrecord->usercount = $yesterdayusers;
                    $DB->update_record('report_usage_monitor', $minrecord);
                    
                    mtrace("Updated record: replaced $minrecord->usercount with $yesterdayusers users");
                } else {
                    mtrace("Yesterday's count ($yesterdayusers) doesn't make it to top 10");
                }
            }
        }
        
        // Calculate percentage for today.
        $reportconfig = get_config('report_usage_monitor');
        $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
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
        
        set_config('users_percent', $userspercent, 'report_usage_monitor');
        set_config('users_warning_class', $warningclass, 'report_usage_monitor');
        
        // Clean up old history records (older than 6 months).
        $sixmonthsago = time() - (180 * 24 * 60 * 60);
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $DB->delete_records_select('report_usage_monitor_history', 
                                      'timecreated < :threshold', 
                                      ['threshold' => $sixmonthsago]);
        }
        
        mtrace("Daily users top calculation completed.");
        
        return true;
    }
}