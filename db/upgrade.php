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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for report_usage_monitor plugin.
 *
 * @package     report_usage_monitor
 * @category    upgrade
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the report_usage_monitor plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_report_usage_monitor_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();

    if ($oldversion < 2025030500) {
        // Rename fecha field to timecreated and cantidad_usuarios to usercount.
        $table = new xmldb_table('report_usage_monitor');
        
        // Check if old fields exist and rename them.
        if ($dbman->field_exists($table, 'fecha')) {
            // Rename fecha to timecreated.
            $field = new xmldb_field('fecha', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->rename_field($table, $field, 'timecreated');
        }
        
        if ($dbman->field_exists($table, 'cantidad_usuarios')) {
            // Rename cantidad_usuarios to usercount.
            $field = new xmldb_field('cantidad_usuarios', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->rename_field($table, $field, 'usercount');
        }
        
        // Ensure indexes are correct.
        $index = new xmldb_index('idx_fecha', XMLDB_INDEX_NOTUNIQUE, ['fecha']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        
        $index = new xmldb_index('idx_timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        $index = new xmldb_index('idx_cantidad_usuarios', XMLDB_INDEX_NOTUNIQUE, ['cantidad_usuarios']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        
        $index = new xmldb_index('idx_usercount', XMLDB_INDEX_NOTUNIQUE, ['usercount']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        // Clean up any invalid timestamp data.
        $DB->execute("UPDATE {report_usage_monitor} SET timecreated = ? WHERE timecreated <= 0", [time()]);
        
        // Create notification history table if it doesn't exist.
        $table = new xmldb_table('report_usage_monitor_history');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('percentage', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, null);
            $table->add_field('value', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('threshold', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_type', XMLDB_INDEX_NOTUNIQUE, ['type']);
            $table->add_index('idx_timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
            
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2025030500, 'report', 'usage_monitor');
    }

    return true;
}