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

namespace report_usage_monitor\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

use stdClass;

/**
 * Centralises data retrieval logic for the Usage Monitor dashboard and API.
 *
 * Every method in this class normalises the values coming from plugin tasks
 * so that the dashboard tables, charts and the REST endpoints use the same
 * source of truth.
 *
 * @package     report_usage_monitor
 * @copyright   2024
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_data {

    /**
     * Returns disk usage information ready for presentation.
     *
     * @return array
     */
    public static function get_disk_summary(): array {
        $config = self::get_config();

        $filesystembytes = (int)($config->totalusagereadable ?? 0);
        $databasebytes = (int)($config->totalusagereadabledb ?? 0);
        $totalbytes = max(0, $filesystembytes + $databasebytes);

        $quotagb = (int)($config->disk_quota ?? 0);
        $quotabytes = $quotagb > 0 ? $quotagb * 1024 * 1024 * 1024 : 0;

        $percentage = self::safe_percentage($totalbytes, $quotabytes);
        $warningclass = !empty($config->disk_warning_class) ? $config->disk_warning_class
            : self::determine_warning_class($percentage);

        $details = self::prepare_directory_breakdown($config->dir_analysis ?? '[]', $filesystembytes, $databasebytes);
        $detailtotal = array_sum(array_map(static function(array $detail): int {
            return $detail['bytes'];
        }, $details));

        if ($detailtotal <= 0 && $totalbytes > 0) {
            // Fall back to placing everything under "others" so that charts still render.
            $details = [
                'database' => ['bytes' => $databasebytes, 'gb' => display_size_in_gb($databasebytes, 2)],
                'filedir' => ['bytes' => 0, 'gb' => 0],
                'cache' => ['bytes' => 0, 'gb' => 0],
                'others' => ['bytes' => $filesystembytes, 'gb' => display_size_in_gb($filesystembytes, 2)],
            ];
            $detailtotal = $databasebytes + $filesystembytes;
        }

        // Calculate percentages using the total of the detailed breakdown to keep
        // the table and the doughnut chart in sync.
        foreach ($details as $key => $detail) {
            $bytes = $detail['bytes'];
            $details[$key]['percentage'] = self::safe_percentage($bytes, $detailtotal);
        }

        $lastcalculated = self::timestamp_or_null($config->lastexecutioncalculatedisk ?? $config->lastexecutioncalculate ?? 0);

        return [
            'total_bytes' => $totalbytes,
            'total_gb' => display_size_in_gb($totalbytes, 2),
            'quota_bytes' => $quotabytes,
            'quota_gb' => display_size_in_gb($quotabytes, 2),
            'percentage' => round($percentage, 2),
            'warning_class' => $warningclass,
            'last_calculated' => $lastcalculated,
            'details' => $details,
            'details_total_bytes' => $detailtotal,
        ];
    }

    /**
     * Returns aggregated information about user activity.
     *
     * @return array
     */
    public static function get_user_summary(): array {
        $config = self::get_config();

        $users = (int)($config->totalusersdaily ?? 0);
        $threshold = (int)($config->max_daily_users_threshold ?? 0);
        $percentage = self::safe_percentage($users, $threshold);
        $warningclass = !empty($config->users_warning_class) ? $config->users_warning_class
            : self::determine_warning_class($percentage);

        $lastcalculated = self::timestamp_or_null($config->lastexecutioncalculateuserdaily ?? $config->lastexecution ?? 0);
        $max90users = (int)($config->max_userdaily_for_90_days_users ?? 0);
        $max90date = self::timestamp_or_null($config->max_userdaily_for_90_days_date ?? 0);
        $lastcalc90 = self::timestamp_or_null($config->lastexecutioncalculateusers90days ?? 0);

        return [
            'current' => $users,
            'threshold' => $threshold,
            'percentage' => round($percentage, 2),
            'warning_class' => $warningclass,
            'last_calculated' => $lastcalculated,
            'max_90_users' => $max90users,
            'max_90_date' => $max90date,
            'last_90_calculated' => $lastcalc90,
        ];
    }

    /**
     * Returns daily user data for charts and the table.
     *
     * @param int $days Number of days to retrieve.
     * @return array[] Array ordered chronologically.
     */
    public static function get_daily_user_trend(int $days = 10): array {
        global $DB;

        $days = max(1, $days);
        $sql = report_user_daily_sql();
        $records = $DB->get_records_sql($sql);

        $result = [];
        foreach ($records as $record) {
            $timestamp = (int)($record->timestamp_fecha ?? 0);
            if ($timestamp <= 0) {
                continue;
            }
            $count = (int)($record->conteo_accesos_unicos ?? 0);
            $result[$timestamp] = [
                'timestamp' => $timestamp,
                'label' => userdate($timestamp, get_string('strftimedate', 'langconfig')),
                'count' => $count,
            ];
        }

        if (empty($result)) {
            return [];
        }

        ksort($result);
        $result = array_slice($result, -$days, null, true);

        $summary = self::get_user_summary();
        $threshold = $summary['threshold'] > 0 ? $summary['threshold'] : 0;

        foreach ($result as &$entry) {
            $entry['percentage'] = $threshold > 0 ? round(min(100, ($entry['count'] / $threshold) * 100), 2) : 0;
        }
        unset($entry);

        return array_values($result);
    }

    /**
     * Returns the top daily user records stored by the scheduled task.
     *
     * @param int $limit
     * @return array[]
     */
    public static function get_daily_user_top(int $limit = 10): array {
        global $DB;

        $limit = max(1, $limit);
        $sql = report_user_daily_top_sql();
        $records = $DB->get_records_sql($sql, [], 0, $limit);

        $result = [];
        foreach ($records as $record) {
            $timestamp = (int)($record->timestamp_fecha ?? $record->fecha ?? 0);
            if ($timestamp <= 0) {
                continue;
            }
            $count = (int)($record->cantidad_usuarios ?? 0);
            $result[] = [
                'timestamp' => $timestamp,
                'label' => userdate($timestamp, get_string('strftimedate', 'langconfig')),
                'count' => $count,
            ];
        }

        return $result;
    }

    /**
     * Returns disk usage history for the provided number of days.
     *
     * @param int $days
     * @return array
     */
    public static function get_disk_history(int $days = 30): array {
        global $DB;

        $days = max(1, $days);
        $cutoff = time() - ($days * DAYSECS);
        $records = $DB->get_records_select('report_usage_monitor_history',
            'type = :type AND timecreated >= :cutoff',
            ['type' => 'disk', 'cutoff' => $cutoff], 'timecreated ASC',
            'timecreated, percentage');

        $byday = [];
        foreach ($records as $record) {
            $timestamp = (int)($record->timecreated ?? 0);
            if ($timestamp <= 0) {
                continue;
            }
            $daykey = usergetmidnight($timestamp);
            $byday[$daykey] = round((float)$record->percentage, 2);
        }

        $history = [];
        $lastvalue = null;
        for ($i = $days - 1; $i >= 0; $i--) {
            $daytimestamp = usergetmidnight(time() - ($i * DAYSECS));
            if (array_key_exists($daytimestamp, $byday)) {
                $lastvalue = $byday[$daytimestamp];
            }
            if ($lastvalue === null) {
                $lastvalue = 0;
            }
            $history[] = [
                'timestamp' => $daytimestamp,
                'label' => userdate($daytimestamp, get_string('strftimedate', 'langconfig')),
                'percentage' => round((float)$lastvalue, 2),
            ];
        }

        return $history;
    }

    /**
     * Returns information about the largest courses.
     *
     * @param int $limit
     * @return array
     */
    public static function get_largest_courses(int $limit = 5): array {
        $config = self::get_config();
        $limit = max(1, $limit);

        $coursesjson = $config->largest_courses ?? '';
        $courses = [];
        if (!empty($coursesjson)) {
            $decoded = json_decode($coursesjson);
            if (is_array($decoded)) {
                $courses = $decoded;
            } elseif ($decoded instanceof stdClass) {
                $courses = [$decoded];
            }
        }

        if (empty($courses)) {
            $courses = get_largest_courses($limit);
        }

        $courses = array_slice($courses, 0, $limit);
        $result = [];
        foreach ($courses as $course) {
            $filesize = (int)($course->filesize ?? 0);
            $backupsize = (int)($course->backupsize ?? 0);
            $totalsize = (int)($course->totalsize ?? ($filesize + $backupsize));
            $percentage = round((float)($course->percentage ?? 0), 2);

            $record = new stdClass();
            $record->id = (int)($course->id ?? 0);
            $record->fullname = format_string($course->fullname ?? '');
            $record->shortname = format_string($course->shortname ?? '');
            $record->filesize = $filesize;
            $record->backupsize = $backupsize;
            $record->totalsize = $totalsize;
            $record->percentage = $percentage;
            $record->backupcount = (int)($course->backupcount ?? 0);

            $result[] = $record;
        }

        return $result;
    }

    /**
     * Returns site level counters used on the dashboard and API.
     *
     * @return array
     */
    public static function get_site_overview(): array {
        global $DB;

        $totalcourses = $DB->count_records('course');
        $activeusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]) - 1;
        $suspended = $DB->count_records('user', ['deleted' => 0, 'suspended' => 1]);
        $registered = $activeusers + $suspended;
        $backupmaxkept = (int)(get_config('backup', 'backup_auto_max_kept') ?? 0);

        return [
            'total_courses' => $totalcourses,
            'active_users' => max(0, $activeusers),
            'suspended_users' => $suspended,
            'registered_users' => max(0, $registered),
            'backup_auto_max_kept' => $backupmaxkept,
        ];
    }

    /**
     * Returns plugin configuration.
     *
     * @return stdClass
     */
    private static function get_config(): stdClass {
        return get_config('report_usage_monitor');
    }

    /**
     * Normalises directory analysis information coming from the scheduled task.
     *
     * @param string $json
     * @param int $filesystembytes
     * @param int $databasebytes
     * @return array
     */
    private static function prepare_directory_breakdown(string $json, int $filesystembytes, int $databasebytes): array {
        $analysis = [];
        if (!empty($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $analysis = $decoded;
            }
        }

        $keys = ['database', 'filedir', 'cache', 'others'];
        $details = [];
        foreach ($keys as $key) {
            $value = isset($analysis[$key]) ? (int)$analysis[$key] : 0;
            $details[$key] = [
                'bytes' => max(0, $value),
                'gb' => display_size_in_gb($value, 2),
            ];
        }

        // Always trust the freshly calculated database size from config to avoid drifts.
        $details['database']['bytes'] = max(0, $databasebytes);
        $details['database']['gb'] = display_size_in_gb($databasebytes, 2);

        $knownfilesystem = $details['filedir']['bytes'] + $details['cache']['bytes'];
        $details['others']['bytes'] = max(0, $filesystembytes - $knownfilesystem);
        $details['others']['gb'] = display_size_in_gb($details['others']['bytes'], 2);

        return $details;
    }

    /**
     * Returns the Bootstrap contextual class based on the usage percentage.
     *
     * @param float $percentage
     * @return string
     */
    private static function determine_warning_class(float $percentage): string {
        if ($percentage < 70) {
            return 'bg-success';
        }
        if ($percentage < 90) {
            return 'bg-warning';
        }
        return 'bg-danger';
    }

    /**
     * Calculates a percentage while guarding against division by zero.
     *
     * @param float|int $value
     * @param float|int $total
     * @return float
     */
    private static function safe_percentage($value, $total): float {
        if ($total <= 0) {
            return 0.0;
        }
        if ($value <= 0) {
            return 0.0;
        }
        return min(100, ($value / $total) * 100);
    }

    /**
     * Validates timestamp like values and returns null when not valid.
     *
     * @param mixed $value
     * @return int|null
     */
    private static function timestamp_or_null($value): ?int {
        if (!is_numeric($value)) {
            return null;
        }
        $timestamp = (int)$value;
        return $timestamp > 0 ? $timestamp : null;
    }
}
