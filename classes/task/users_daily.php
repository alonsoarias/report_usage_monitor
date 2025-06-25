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
 * Scheduled task for calculating daily user statistics.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

class users_daily extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('getlastusers', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB, $CFG;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting daily users calculation task...");
        }

        $transaction = $DB->start_delegated_transaction();
        
        try {
            // Get current top records
            $top_records = usage_monitor_user_queries::get_top_user_days();
            $array_daily_top = [];
            
            foreach ($top_records as $record) {
                if (!is_numeric($record->timestamp_fecha) || $record->timestamp_fecha <= 0) {
                    debugging('Invalid timestamp in top records: ' . var_export($record->timestamp_fecha, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                $array_daily_top[] = [
                    "usuarios" => $record->cantidad_usuarios,
                    "fecha" => $record->timestamp_fecha,
                ];
            }

            $menor = !empty($array_daily_top) ? min(array_column($array_daily_top, 'usuarios')) : null;

            // Get yesterday's users
            $yesterday_users = usage_monitor_user_queries::get_yesterday_users();
            $users = [];
            
            foreach ($yesterday_users as $log) {
                if (!is_numeric($log->fecha) || $log->fecha <= 0) {
                    debugging('Invalid timestamp in yesterday users: ' . var_export($log->fecha, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                $users = [
                    "usuarios" => $log->conteo_accesos_unicos,
                    "fecha" => $log->fecha,
                ];
                break;
            }

            if (!empty($users)) {
                if (empty($array_daily_top) || count($array_daily_top) < 10) {
                    usage_monitor_user_queries::insert_top_record($users['fecha'], $users['usuarios']);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Inserted new record with {$users['usuarios']} users for timestamp {$users['fecha']}.");
                    }
                } else {
                    if (!is_null($menor) && $users['usuarios'] >= $menor) {
                        usage_monitor_user_queries::update_min_top_record($users['fecha'], $users['usuarios'], $menor);
                        if (debugging('', DEBUG_DEVELOPER)) {
                            mtrace("Updated record with minimum value ($menor) to new value ({$users['usuarios']}).");
                        }
                    }
                }
            }

            // Update current daily users
            $today_users = usage_monitor_user_queries::get_today_users();
            $users_today = 0;
            
            foreach ($today_users as $log) {
                if (!is_numeric($log->timestamp_fecha) || $log->timestamp_fecha <= 0) {
                    debugging('Invalid timestamp in today users: ' . var_export($log->timestamp_fecha, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                $users_today = $log->conteo_accesos_unicos;
                set_config('totalusersdaily', $users_today, 'report_usage_monitor');
                break;
            }

            // Calculate and store precomputed values
            $config = usage_monitor_data_manager::get_config();
            $max_users_threshold = (int)($config->max_daily_users_threshold ?? 100);
            $users_percent = calculate_threshold_percentage($users_today, $max_users_threshold);
            $warning_level = (float)($config->users_warning_level ?? 90);
            $caution_level = max(70, $warning_level - 20);
            
            $warning_class = ($users_percent < $caution_level) ? 'bg-success' : 
                            (($users_percent < $warning_level) ? 'bg-warning' : 'bg-danger');
            
            set_config('users_percent', $users_percent, 'report_usage_monitor');
            set_config('users_warning_class', $warning_class, 'report_usage_monitor');

            // Clean old data
            $six_months_ago = time() - (180 * 24 * 60 * 60);
            
            if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
                $old_count = $DB->count_records_select('report_usage_monitor_history', 'timecreated < ?', [$six_months_ago]);
                
                if ($old_count > 0) {
                    $DB->delete_records_select('report_usage_monitor_history', 'timecreated < ?', [$six_months_ago]);
                    
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Removed $old_count old records from history.");
                    }
                }
            }

            // Save execution timestamp
            set_config('lastexecution', time(), 'report_usage_monitor');

            // Clear cache
            usage_monitor_data_manager::clear_cache();
            
            $transaction->allow_commit();
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('Error in daily users task: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }
        
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Daily users calculation task completed.");
        }

        return true;
    }
}