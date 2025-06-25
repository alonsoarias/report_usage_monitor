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

/**
 * Scheduled task for calculating 90-day peak user statistics.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

class users_daily_90_days extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('getlastusers90days', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting 90-day peak users calculation task...");
        }

        $transaction = $DB->start_delegated_transaction();
        
        try {
            $max_users_data = usage_monitor_user_queries::get_max_users_90_days();
            $max_users = 0;
            $max_date = 0;
            
            foreach ($max_users_data as $record) {
                if (!is_numeric($record->fecha) || $record->fecha <= 0) {
                    debugging('Invalid timestamp in 90-day peak users: ' . var_export($record->fecha, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                if (isset($record->usuarios)) {
                    $max_users = $record->usuarios;
                    $max_date = $record->fecha;

                    set_config('max_userdaily_for_90_days_date', $record->fecha, 'report_usage_monitor');
                    set_config('max_userdaily_for_90_days_users', $record->usuarios, 'report_usage_monitor');
                }
            }

            // Record in history if we have valid data
            if ($max_users > 0 && $max_date > 0 && $DB->get_manager()->table_exists('report_usage_monitor_history')) {
                $existing = $DB->get_record_sql(
                    "SELECT id FROM {report_usage_monitor_history}
                    WHERE type = 'users90d' AND timecreated = ?",
                    [$max_date]
                );

                if (!$existing) {
                    $config = usage_monitor_data_manager::get_config();
                    $threshold = $config->max_daily_users_threshold ?? 100;

                    $record = new \stdClass();
                    $record->type = 'users90d';
                    $record->percentage = calculate_threshold_percentage($max_users, $threshold);
                    $record->value = $max_users;
                    $record->threshold = $threshold;
                    $record->timecreated = $max_date;

                    $DB->insert_record('report_usage_monitor_history', $record);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("90-day peak users recorded in history.");
                    }
                }
            }
            
            set_config('lastexecutioncalculateusers90days', time(), 'report_usage_monitor');
            
            // Clear cache
            usage_monitor_data_manager::clear_cache();
            
            $transaction->allow_commit();
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('Error in 90-day peak users task: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            $formatted_date = ($max_date && is_numeric($max_date) && $max_date > 0) ? date('d/m/Y', (int)$max_date) : 'N/A';
            mtrace("90-day peak users calculated: " . $max_users . " on date " . $formatted_date);
            mtrace("90-day peak users calculation task completed.");
        }

        return true;
    }
}