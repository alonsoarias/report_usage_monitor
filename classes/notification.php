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
 * Notification handler for report_usage_monitor.
 *
 * @package     report_usage_monitor
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * Notification class for sending alerts.
 */
class notification {
    
    /**
     * Send disk usage notification.
     *
     * @param int $diskusage Current disk usage in bytes
     * @param int $quotadisk Disk quota in bytes
     * @param float $percentage Usage percentage
     * @return bool
     */
    public function send_disk_notification($diskusage, $quotadisk, $percentage) {
        global $CFG, $DB;
        
        $reportconfig = get_config('report_usage_monitor');
        $email = $reportconfig->email;
        
        if (empty($email)) {
            debugging('No notification email configured', DEBUG_DEVELOPER);
            return false;
        }
        
        // Prepare email data.
        $a = new \stdClass();
        $a->sitename = format_string(get_site()->fullname);
        $a->siteurl = $CFG->wwwroot;
        $a->percentage = round($percentage, 2);
        $a->diskusage = helper::format_bytes($diskusage);
        $a->quotadisk = helper::format_bytes($quotadisk);
        $a->available_space = helper::format_bytes($quotadisk - $diskusage);
        $a->available_percent = round(100 - $percentage, 2);
        $a->lastday = userdate(time());
        $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
        
        // Get directory analysis.
        $diranalysis = json_decode($reportconfig->dir_analysis ?? '{}', true);
        $a->databasesize = helper::format_bytes($diranalysis['database'] ?? 0);
        $a->filedir_size = helper::format_bytes($diranalysis['filedir'] ?? 0);
        $a->cache_size = helper::format_bytes($diranalysis['cache'] ?? 0);
        $a->other_size = helper::format_bytes($diranalysis['others'] ?? 0);
        
        // Calculate percentages.
        $a->db_percent = helper::calculate_percentage($diranalysis['database'] ?? 0, $diskusage);
        $a->filedir_percent = helper::calculate_percentage($diranalysis['filedir'] ?? 0, $diskusage);
        $a->cache_percent = helper::calculate_percentage($diranalysis['cache'] ?? 0, $diskusage);
        $a->other_percent = helper::calculate_percentage($diranalysis['others'] ?? 0, $diskusage);
        
        // Additional system information.
        $a->moodle_version = $CFG->version;
        $a->moodle_release = $CFG->release;
        $a->coursescount = $DB->count_records('course');
        $a->backupcount = get_config('backup', 'backup_auto_max_kept');
        $a->numberofusers = (int)($reportconfig->totalusersdaily ?? 0);
        $a->threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $a->user_percent = helper::calculate_percentage($a->numberofusers, $a->threshold);
        
        // Get largest courses.
        $largestcourses = json_decode($reportconfig->largest_courses ?? '[]', true);
        $a->top_courses_rows = $this->generate_courses_html($largestcourses);
        
        // Warning level class.
        if ($percentage < 70) {
            $a->warning_level_class = 'warning-level-low';
        } else if ($percentage < 90) {
            $a->warning_level_class = 'warning-level-medium';
        } else {
            $a->warning_level_class = 'warning-level-high';
        }
        
        // Prepare email.
        $subject = get_string('subjectemail2', 'report_usage_monitor') . " {$a->sitename}";
        $messagehtml = get_string('messagehtml_diskusage', 'report_usage_monitor', $a);
        $messagetext = html_to_text($messagehtml);
        
        // Send email.
        $toemail = \core_user::get_user_by_email($email);
        if (!$toemail) {
            $toemail = new \stdClass();
            $toemail->email = $email;
            $toemail->firstname = '';
            $toemail->lastname = '';
            $toemail->maildisplay = true;
            $toemail->mailformat = 1;
            $toemail->id = -99;
        }
        
        $fromemail = \core_user::get_noreply_user();
        
        return email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml);
    }
    
    /**
     * Send user limit notification.
     *
     * @param int $userscount Current user count
     * @param int $threshold User threshold
     * @param float $percentage Usage percentage
     * @param int $timestamp Timestamp of the check
     * @return bool
     */
    public function send_user_notification($userscount, $threshold, $percentage, $timestamp) {
        global $CFG, $DB;
        
        $reportconfig = get_config('report_usage_monitor');
        $email = $reportconfig->email;
        
        if (empty($email)) {
            debugging('No notification email configured', DEBUG_DEVELOPER);
            return false;
        }
        
        // Prepare email data.
        $a = new \stdClass();
        $a->sitename = format_string(get_site()->fullname);
        $a->siteurl = $CFG->wwwroot;
        $a->threshold = $threshold;
        $a->numberofusers = $userscount;
        $a->lastday = userdate($timestamp);
        $a->referer = $CFG->wwwroot . '/report/usage_monitor/index.php';
        $a->percentaje = round($percentage, 2);
        $a->excess_users = max(0, $userscount - $threshold);
        
        // System information.
        $a->moodle_version = $CFG->version;
        $a->moodle_release = $CFG->release;
        $a->courses_count = $DB->count_records('course');
        $a->backup_auto_max_kept = get_config('backup', 'backup_auto_max_kept');
        
        // Disk information.
        $quotadisk = ((int)($reportconfig->disk_quota ?? 10) * 1024 * 1024 * 1024);
        $diskusage = ((int)($reportconfig->totalusagereadable ?? 0) + 
                      (int)($reportconfig->totalusagereadabledb ?? 0));
        $a->diskusage = helper::format_bytes($diskusage);
        $a->quotadisk = helper::format_bytes($quotadisk);
        $a->disk_percent = helper::calculate_percentage($diskusage, $quotadisk);
        
        // Projections.
        $a->days_to_critical = 30; // Placeholder - implement projection calculation if needed.
        $a->critical_threshold = 120;
        
        // Historical data.
        $a->historical_data_rows = $this->generate_historical_html($threshold);
        
        // Prepare email.
        $subject = get_string('subjectemail1', 'report_usage_monitor') . " {$a->sitename}";
        $messagehtml = get_string('messagehtml_userlimit', 'report_usage_monitor', $a);
        $messagetext = html_to_text($messagehtml);
        
        // Send email.
        $toemail = \core_user::get_user_by_email($email);
        if (!$toemail) {
            $toemail = new \stdClass();
            $toemail->email = $email;
            $toemail->firstname = '';
            $toemail->lastname = '';
            $toemail->maildisplay = true;
            $toemail->mailformat = 1;
            $toemail->id = -99;
        }
        
        $fromemail = \core_user::get_noreply_user();
        
        return email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml);
    }
    
    /**
     * Generate HTML for course list.
     *
     * @param array $courses Course data
     * @return string HTML
     */
    private function generate_courses_html($courses) {
        if (empty($courses)) {
            return '';
        }
        
        $html = '';
        foreach ($courses as $course) {
            $html .= '<tr>';
            $html .= '<td>' . format_string($course['fullname'] ?? '') . 
                     ' (' . ($course['shortname'] ?? '') . ')</td>';
            $html .= '<td>' . helper::format_bytes($course['filesize'] ?? 0) . '</td>';
            $html .= '<td>' . round($course['percentage'] ?? 0, 1) . '%</td>';
            $html .= '</tr>';
        }
        
        return $html;
    }
    
    /**
     * Generate HTML for historical user data.
     *
     * @param int $threshold User threshold
     * @return string HTML
     */
    private function generate_historical_html($threshold) {
        global $DB;
        
        $sevendaysago = time() - (7 * 24 * 60 * 60);
        
        $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as day,
                       COUNT(DISTINCT userid) as users
                FROM {logstore_standard_log}
                WHERE action = :action
                  AND timecreated > :from
                GROUP BY DATE(FROM_UNIXTIME(timecreated))
                ORDER BY day DESC
                LIMIT 7";
        
        $params = ['action' => 'loggedin', 'from' => $sevendaysago];
        
        $html = '';
        try {
            $records = $DB->get_records_sql($sql, $params);
            foreach ($records as $record) {
                $percent = helper::calculate_percentage($record->users, $threshold);
                $class = $percent < 70 ? '' : ($percent < 90 ? 'text-warning' : 'text-danger');
                
                $html .= '<tr>';
                $html .= '<td>' . $record->day . '</td>';
                $html .= '<td>' . $record->users . '</td>';
                $html .= '<td class="' . $class . '">' . $percent . '%</td>';
                $html .= '</tr>';
            }
        } catch (\Exception $e) {
            debugging('Failed to get historical data: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        
        return $html;
    }
}