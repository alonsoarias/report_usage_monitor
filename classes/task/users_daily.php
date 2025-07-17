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
 * Enhanced scheduled task for calculating daily user statistics - Uses centralized manager
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
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
            mtrace("Starting enhanced daily users calculation task...");
        }

        $start_time = microtime(true);
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // Get configuration using centralized manager
            $config = usage_monitor_manager::get_config();
            
            // Process yesterday's data for top records
            $this->process_yesterday_data();
            
            // Update current daily users with enhanced metrics
            $current_users = $this->get_current_daily_users();
            set_config('totalusersdaily', $current_users, 'report_usage_monitor');
            
            // Calculate and store enhanced metrics
            $this->calculate_enhanced_metrics($current_users, $config);
            
            // Update peak usage data
            $this->update_peak_usage();
            
            // Clean old data with intelligent retention
            $this->cleanup_old_data();
            
            // Save execution timestamp
            set_config('lastexecution', time(), 'report_usage_monitor');
            
            // Clear cache to ensure fresh data
            usage_monitor_manager::clear_cache();
            
            // Check for notifications
            $this->check_user_notifications($current_users, $config);
            
            $transaction->allow_commit();
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Enhanced daily users calculation completed in {$execution_time} seconds.");
                mtrace("Current daily users: {$current_users}");
            }
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            mtrace("Error in daily users calculation: " . $e->getMessage());
            debugging('Daily users task error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }

        return true;
    }

    /**
     * Process yesterday's user data for top records
     */
    private function process_yesterday_data() {
        global $DB;
        
        // Get current top records
        $top_records = $this->get_top_user_records();
        $top_users_data = [];
        
        foreach ($top_records as $record) {
            if (!is_numeric($record->fecha) || $record->fecha <= 0) {
                debugging('Invalid timestamp in top records: ' . var_export($record->fecha, true), DEBUG_DEVELOPER);
                continue;
            }
            
            $top_users_data[] = [
                'users' => $record->cantidad_usuarios,
                'date' => $record->fecha,
            ];
        }

        $min_users = !empty($top_users_data) ? min(array_column($top_users_data, 'users')) : null;

        // Get yesterday's users with enhanced data
        $yesterday_data = $this->get_yesterday_users();
        
        foreach ($yesterday_data as $record) {
            if (!is_numeric($record->fecha) || $record->fecha <= 0) {
                debugging('Invalid timestamp in yesterday users: ' . var_export($record->fecha, true), DEBUG_DEVELOPER);
                continue;
            }
            
            $users_data = [
                'users' => $record->conteo_accesos_unicos,
                'date' => $record->fecha,
            ];
            
            // Update top records logic
            if (empty($top_users_data) || count($top_users_data) < 10) {
                $this->insert_top_record($users_data['date'], $users_data['users']);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Inserted new top record: {$users_data['users']} users for " . date('Y-m-d', $users_data['date']));
                }
            } else {
                if (!is_null($min_users) && $users_data['users'] >= $min_users) {
                    $this->update_min_top_record($users_data['date'], $users_data['users'], $min_users);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Updated top record: replaced {$min_users} with {$users_data['users']} users");
                    }
                }
            }
            break; // Process only the first (most recent) record
        }
    }

    /**
     * Get current daily users with enhanced calculation
     */
    private function get_current_daily_users() {
        global $DB;
        
        // Get users active in the last 24 hours
        $sql = "SELECT COUNT(DISTINCT userid) as user_count
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated > :threshold";
        
        $threshold = time() - 86400; // Last 24 hours
        $result = $DB->get_field_sql($sql, ['threshold' => $threshold]);
        
        return (int)($result ?? 0);
    }

    /**
     * Calculate and store enhanced metrics
     */
    private function calculate_enhanced_metrics($current_users, $config) {
        $max_users_threshold = (int)($config->max_daily_users_threshold ?? 100);
        $users_percent = calculate_threshold_percentage($current_users, $max_users_threshold);
        $warning_level = (float)($config->users_warning_level ?? 90);
        $caution_level = max(70, $warning_level - 20);
        
        $warning_class = ($users_percent < $caution_level) ? 'success' : 
                        (($users_percent < $warning_level) ? 'warning' : 'danger');
        
        // Store enhanced metrics
        set_config('users_percent', $users_percent, 'report_usage_monitor');
        set_config('users_warning_class', $warning_class, 'report_usage_monitor');
        
        // Calculate and store growth rate
        $growth_rate = $this->calculate_user_growth_rate();
        set_config('users_growth_rate', $growth_rate, 'report_usage_monitor');
        
        // Store peak hours data
        $peak_hours = $this->get_peak_usage_hours();
        set_config('peak_usage_hours', json_encode($peak_hours), 'report_usage_monitor');
    }

    /**
     * Update 90-day peak usage data
     */
    private function update_peak_usage() {
        global $DB;
        
        $sql = "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                       COUNT(DISTINCT userid) as usuarios
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated >= :threshold
                GROUP BY (timecreated - (timecreated % 86400))
                ORDER BY usuarios DESC 
                LIMIT 1";
        
        $ninety_days_ago = time() - (90 * 86400);
        $max_record = $DB->get_record_sql($sql, ['threshold' => $ninety_days_ago]);
        
        if ($max_record && is_numeric($max_record->fecha) && $max_record->fecha > 0) {
            set_config('max_userdaily_for_90_days_date', $max_record->fecha, 'report_usage_monitor');
            set_config('max_userdaily_for_90_days_users', $max_record->usuarios, 'report_usage_monitor');
            
            // Record in history
            $this->record_peak_history($max_record);
        }
    }

    /**
     * Calculate user growth rate
     */
    private function calculate_user_growth_rate() {
        global $DB;
        
        $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as date_key,
                       COUNT(DISTINCT userid) as users
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' AND timecreated > :threshold
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY date_key ASC";
        
        $thirty_days_ago = time() - (30 * 86400);
        $records = $DB->get_records_sql($sql, ['threshold' => $thirty_days_ago]);
        
        if (count($records) >= 7) {
            $values = array_values($records);
            $first_week = array_slice($values, 0, 7);
            $last_week = array_slice($values, -7);
            
            $first_avg = array_sum(array_column($first_week, 'users')) / 7;
            $last_avg = array_sum(array_column($last_week, 'users')) / 7;
            
            if ($first_avg > 0) {
                $growth = (($last_avg - $first_avg) / $first_avg) * 100;
                return round($growth * 4, 2); // Monthly estimate
            }
        }
        
        return 1.5; // Default conservative estimate
    }

    /**
     * Get peak usage hours
     */
    private function get_peak_usage_hours() {
        global $DB;
        
        $sql = "SELECT HOUR(FROM_UNIXTIME(timecreated)) as hour,
                       COUNT(DISTINCT userid) as user_count
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated > :threshold
                GROUP BY HOUR(FROM_UNIXTIME(timecreated))
                ORDER BY user_count DESC
                LIMIT 5";
        
        $week_ago = time() - (7 * 86400);
        $records = $DB->get_records_sql($sql, ['threshold' => $week_ago]);
        
        return array_values($records);
    }

    /**
     * Enhanced cleanup with intelligent retention
     */
    private function cleanup_old_data() {
        global $DB;
        
        $config = usage_monitor_manager::get_config();
        $retention_days = (int)($config->data_retention_days ?? 90);
        $cutoff_time = time() - ($retention_days * 86400);
        
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $old_count = $DB->count_records_select('report_usage_monitor_history', 'timecreated < ?', [$cutoff_time]);
            
            if ($old_count > 0) {
                $DB->delete_records_select('report_usage_monitor_history', 'timecreated < ?', [$cutoff_time]);
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Cleaned up {$old_count} old history records");
                }
            }
        }
        
        // Maintain top 10 records in main table
        $total_records = $DB->count_records('report_usage_monitor');
        if ($total_records > 10) {
            $sql = "SELECT id FROM {report_usage_monitor} ORDER BY fecha ASC LIMIT " . ($total_records - 10);
            $records_to_delete = $DB->get_records_sql($sql);
            
            if (!empty($records_to_delete)) {
                $ids = array_keys($records_to_delete);
                $DB->delete_records_list('report_usage_monitor', 'id', $ids);
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Removed " . count($ids) . " old records to maintain 10 record limit");
                }
            }
        }
    }

    /**
     * Check and send user notifications if needed
     */
    private function check_user_notifications($current_users, $config) {
        $threshold = (int)($config->max_daily_users_threshold ?? 100);
        $warning_level = (float)($config->users_warning_level ?? 90);
        $percentage = calculate_threshold_percentage($current_users, $threshold);
        
        if ($percentage >= $warning_level) {
            $notification_data = [
                'percentage' => $percentage,
                'current_users' => $current_users,
                'threshold' => $threshold,
                'value' => $current_users
            ];
            
            try {
                usage_monitor_manager::send_notification('users', $notification_data);
            } catch (Exception $e) {
                debugging('Error sending user notification: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Helper methods for database operations
     */
    private function get_top_user_records() {
        global $DB;
        
        return $DB->get_records_sql(
            "SELECT fecha, cantidad_usuarios FROM {report_usage_monitor} ORDER BY cantidad_usuarios DESC, fecha DESC"
        );
    }

    private function get_yesterday_users() {
        global $DB;
        
        $yesterday_start = strtotime('yesterday midnight');
        $today_start = strtotime('today midnight');
        
        $sql = "SELECT (timecreated - (timecreated % 86400)) as fecha, 
                       COUNT(DISTINCT userid) as conteo_accesos_unicos 
                FROM {logstore_standard_log}
                WHERE action = 'loggedin' 
                  AND timecreated BETWEEN :start AND :end
                GROUP BY fecha";
        
        return $DB->get_records_sql($sql, ['start' => $yesterday_start, 'end' => $today_start]);
    }

    private function insert_top_record($fecha, $cantidad_usuarios) {
        global $DB;
        
        if (!is_numeric($fecha) || $fecha <= 0) {
            debugging('Invalid timestamp for insert: ' . var_export($fecha, true), DEBUG_DEVELOPER);
            return;
        }
        
        try {
            $record = new \stdClass();
            $record->fecha = $fecha;
            $record->cantidad_usuarios = $cantidad_usuarios;
            
            $DB->insert_record('report_usage_monitor', $record);
        } catch (Exception $e) {
            debugging('Error inserting top record: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    private function update_min_top_record($fecha, $usuarios, $min_value) {
        global $DB;
        
        if (!is_numeric($fecha) || $fecha <= 0) {
            debugging('Invalid timestamp for update: ' . var_export($fecha, true), DEBUG_DEVELOPER);
            return;
        }
        
        try {
            $sql = "SELECT id FROM {report_usage_monitor} 
                    WHERE cantidad_usuarios = ? 
                    ORDER BY fecha ASC LIMIT 1";
            $record_id = $DB->get_field_sql($sql, [$min_value]);
            
            if ($record_id) {
                $DB->set_field('report_usage_monitor', 'fecha', $fecha, ['id' => $record_id]);
                $DB->set_field('report_usage_monitor', 'cantidad_usuarios', $usuarios, ['id' => $record_id]);
            }
        } catch (Exception $e) {
            debugging('Error updating top record: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    private function record_peak_history($max_record) {
        global $DB;
        
        if (!$DB->get_manager()->table_exists('report_usage_monitor_history')) {
            return;
        }
        
        // Check if already recorded
        $existing = $DB->get_record_sql(
            "SELECT id FROM {report_usage_monitor_history}
             WHERE type = 'users90d' AND timecreated = ?",
            [$max_record->fecha]
        );

        if (!$existing) {
            $config = usage_monitor_manager::get_config();
            $threshold = $config->max_daily_users_threshold ?? 100;

            $record = new \stdClass();
            $record->type = 'users90d';
            $record->percentage = calculate_threshold_percentage($max_record->usuarios, $threshold);
            $record->value = $max_record->usuarios;
            $record->threshold = $threshold;
            $record->timecreated = $max_record->fecha;

            try {
                $DB->insert_record('report_usage_monitor_history', $record);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("90-day peak users recorded in history");
                }
            } catch (Exception $e) {
                debugging('Error recording peak history: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }
}