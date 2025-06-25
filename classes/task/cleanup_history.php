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
 * Scheduled task for cleaning up historical data.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2025 Alonso Arias <alonso@aloarias.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

class cleanup_history extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('cleanuphistorytask', 'report_usage_monitor');
    }

    public function execute()
    {
        global $DB;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Starting history cleanup task...");
        }

        $config = usage_monitor_data_manager::get_config();
        $retention_days = (int)($config->data_retention_days ?? 90);
        $cutoff_time = time() - ($retention_days * 24 * 60 * 60);

        $transaction = $DB->start_delegated_transaction();
        
        try {
            // Clean up history table
            if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
                $old_count = $DB->count_records_select('report_usage_monitor_history', 'timecreated < ?', [$cutoff_time]);
                
                if ($old_count > 0) {
                    $DB->delete_records_select('report_usage_monitor_history', 'timecreated < ?', [$cutoff_time]);
                    
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Removed $old_count old records from history table.");
                    }
                }
            }

            // Clean up main usage monitor table (keep only top 10 records)
            $total_records = $DB->count_records('report_usage_monitor');
            if ($total_records > 10) {
                $sql = "SELECT id FROM {report_usage_monitor} ORDER BY fecha ASC LIMIT " . ($total_records - 10);
                $records_to_delete = $DB->get_records_sql($sql);
                
                if (!empty($records_to_delete)) {
                    $ids = array_keys($records_to_delete);
                    $DB->delete_records_list('report_usage_monitor', 'id', $ids);
                    
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Removed " . count($ids) . " old records from main table to maintain 10 record limit.");
                    }
                }
            }
            
            $transaction->allow_commit();
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('Error in cleanup history task: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("History cleanup task completed.");
        }

        return true;
    }
}