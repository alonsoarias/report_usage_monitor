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
 * API documentation for Usage Monitor plugin
 *
 * @package    report_usage_monitor
 * @copyright  2025 Alonso Arias <alonso@aloarias.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Verify permissions
require_login();
$context = context_system::instance();
require_capability('report/usage_monitor:view', $context);

// Page configuration
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/report/usage_monitor/api-documentation.php'));
$PAGE->set_title(get_string('api_documentation', 'report_usage_monitor'));
$PAGE->set_heading(get_string('api_documentation', 'report_usage_monitor'));
$PAGE->set_pagelayout('admin');

// Get configuration for examples
$webservice_admin_url = new moodle_url('/admin/settings.php', array('section' => 'webservicesoverview'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('api_documentation', 'report_usage_monitor'));

?>

<div class="api-documentation">
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">API Overview</h3>
        </div>
        <div class="card-body">
            <p>The Usage Monitor API v5.0 provides comprehensive access to usage statistics, notifications, and configuration management for Moodle hosting platforms. This RESTful API enables integration with monitoring dashboards, alerting systems, and automated management tools.</p>
            
            <div class="alert alert-info">
                <strong>Version 5.0 Features:</strong>
                <ul class="mb-0">
                    <li>Unified data management with caching</li>
                    <li>Enhanced security and permission controls</li>
                    <li>Optimized endpoints for dashboard integration</li>
                    <li>Comprehensive historical data access</li>
                    <li>Real-time threshold management</li>
                </ul>
            </div>
            
            <p><strong>To use this API, you need to:</strong></p>
            <ol>
                <li>Enable web services in Moodle</li>
                <li>Enable the Usage Monitor API service</li>
                <li>Create a user with appropriate permissions</li>
                <li>Create a token for that user</li>
            </ol>
            
            <p><a href="<?php echo $webservice_admin_url; ?>" class="btn btn-primary">Go to Web Services Setup</a></p>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Available Endpoints</h3>
        </div>
        <div class="card-body">
            <div class="accordion" id="apiEndpoints">
                
                <!-- get_usage_statistics endpoint -->
                <div class="card mb-3">
                    <div class="card-header" id="headingStats">
                        <h5 class="mb-0">
                            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseStats" aria-expanded="true" aria-controls="collapseStats">
                                <code>report_usage_monitor_get_usage_statistics</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseStats" class="collapse show" aria-labelledby="headingStats" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Retrieves comprehensive usage statistics including disk usage, user activity, system information, and projections.</p>
                            <p><strong>Parameters:</strong></p>
                            <ul>
                                <li><code>include_history</code> (boolean, optional): Include historical data in response</li>
                                <li><code>history_days</code> (integer, optional): Number of days for historical data (default: 30)</li>
                            </ul>
                            <p><strong>Returns:</strong> Complete usage statistics with optional historical data.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_statistics&include_history=1&history_days=30&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "site_info": {
    "name": "My Moodle Site",
    "shortname": "moodle",
    "moodle_version": 2023042400,
    "moodle_release": "4.2.0",
    "course_count": 150,
    "user_count": 1200,
    "active_users": 1150,
    "suspended_users": 50,
    "backup_auto_max_kept": 2
  },
  "disk_usage": {
    "current_bytes": 15728640000,
    "current_readable": "14.6 GB",
    "quota_bytes": 21474836480,
    "quota_readable": "20 GB",
    "percentage": 73.2,
    "warning_level": 90,
    "warning_class": "bg-success",
    "last_calculated": 1698159284,
    "directories": {
      "database": {
        "bytes": 2147483648,
        "readable": "2 GB",
        "percentage": 13.7
      },
      "filedir": {
        "bytes": 10737418240,
        "readable": "10 GB",
        "percentage": 68.3
      },
      "cache": {
        "bytes": 1073741824,
        "readable": "1 GB",
        "percentage": 6.8
      },
      "others": {
        "bytes": 1769996288,
        "readable": "1.6 GB",
        "percentage": 11.2
      }
    }
  },
  "user_usage": {
    "current": 450,
    "threshold": 1000,
    "percentage": 45.0,
    "warning_level": 90,
    "warning_class": "bg-success",
    "last_calculated": 1698159350,
    "max_90_days": 650,
    "max_90_days_date": 1644934800
  },
  "projections": {
    "disk_growth_rate": 3.5,
    "users_growth_rate": 2.1,
    "days_to_disk_threshold": 95,
    "days_to_users_threshold": 215
  },
  "largest_courses": [
    {
      "id": 123,
      "fullname": "Advanced Mathematics",
      "shortname": "MATH401",
      "size_bytes": 1073741824,
      "size_readable": "1 GB",
      "backup_size_bytes": 536870912,
      "backup_size_readable": "512 MB",
      "total_size_bytes": 1610612736,
      "total_size_readable": "1.5 GB",
      "percentage": 8.7,
      "backup_count": 3
    }
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- get_dashboard_data endpoint -->
                <div class="card mb-3">
                    <div class="card-header" id="headingDashboard">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseDashboard" aria-expanded="false" aria-controls="collapseDashboard">
                                <code>report_usage_monitor_get_dashboard_data</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseDashboard" class="collapse" aria-labelledby="headingDashboard" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Retrieves optimized data specifically for dashboard display with minimal overhead.</p>
                            <p><strong>Parameters:</strong> None</p>
                            <p><strong>Returns:</strong> Essential usage data optimized for real-time dashboard updates.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_dashboard_data&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "disk_usage": {
    "current": 15728640000,
    "current_readable": "14.6 GB",
    "threshold": 21474836480,
    "threshold_readable": "20 GB",
    "percentage": 73.2,
    "warning_class": "bg-success",
    "last_calculated": 1698159284
  },
  "user_usage": {
    "current": 450,
    "threshold": 1000,
    "percentage": 45.0,
    "warning_class": "bg-success",
    "last_calculated": 1698159350,
    "max_90_days": 650,
    "max_90_days_date": 1644934800
  },
  "projections": {
    "disk_growth_rate": 3.5,
    "users_growth_rate": 2.1,
    "days_to_disk_threshold": 95,
    "days_to_users_threshold": 215
  }
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- update_thresholds endpoint -->
                <div class="card mb-3">
                    <div class="card-header" id="headingUpdate">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseUpdate" aria-expanded="false" aria-controls="collapseUpdate">
                                <code>report_usage_monitor_update_thresholds</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseUpdate" class="collapse" aria-labelledby="headingUpdate" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Updates configuration thresholds and warning levels.</p>
                            <p><strong>Parameters:</strong></p>
                            <ul>
                                <li><code>user_threshold</code> (integer, optional): New threshold for daily users</li>
                                <li><code>disk_threshold</code> (integer, optional): New threshold for disk space in GB</li>
                                <li><code>disk_warning_level</code> (float, optional): Disk warning level percentage</li>
                                <li><code>users_warning_level</code> (float, optional): Users warning level percentage</li>
                            </ul>
                            <p><strong>Returns:</strong> Result of the update operation with success status and messages.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl -X POST '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php' \
  -d 'wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_update_thresholds&user_threshold=1500&disk_threshold=30&disk_warning_level=85&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "success": true,
  "user_threshold_updated": true,
  "disk_threshold_updated": true,
  "disk_warning_level_updated": true,
  "messages": [
    "User threshold updated successfully.",
    "Disk threshold updated successfully.",
    "Disk warning level updated successfully."
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- get_notification_history endpoint -->
                <div class="card mb-3">
                    <div class="card-header" id="headingHistory">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseHistory" aria-expanded="false" aria-controls="collapseHistory">
                                <code>report_usage_monitor_get_notification_history</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseHistory" class="collapse" aria-labelledby="headingHistory" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Retrieves the history of notifications sent with filtering and pagination.</p>
                            <p><strong>Parameters:</strong></p>
                            <ul>
                                <li><code>type</code> (string, optional): Type of notification ('disk', 'users', or 'all'). Default: 'all'</li>
                                <li><code>limit</code> (integer, optional): Maximum number of records to return. Default: 30</li>
                                <li><code>offset</code> (integer, optional): Offset for pagination. Default: 0</li>
                            </ul>
                            <p><strong>Returns:</strong> List of historical notifications with pagination info.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_notification_history&type=disk&limit=10&offset=0&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "total": 45,
  "limit": 10,
  "offset": 0,
  "items": [
    {
      "id": 123,
      "type": "disk",
      "percentage": 95.5,
      "value": "19.1 GB",
      "value_raw": 20503707648,
      "threshold": "20 GB",
      "threshold_raw": 21474836480,
      "timecreated": 1698159284,
      "timereadable": "Oct 24, 2023 15:34"
    }
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Code Examples</h3>
        </div>
        <div class="card-body">
            <h5>PHP Example</h5>
<pre><code>// Initialize cURL
$curl = new curl();

// Get comprehensive usage statistics
$response = $curl->get('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_statistics&include_history=1&moodlewsrestformat=json');
$usage_data = json_decode($response);

if ($usage_data && isset($usage_data->disk_usage)) {
    echo "Disk usage: " . $usage_data->disk_usage->percentage . "%\n";
    echo "User usage: " . $usage_data->user_usage->percentage . "%\n";
    
    // Check projections
    if ($usage_data->projections->days_to_disk_threshold < 30) {
        echo "Warning: Disk threshold will be reached in " . $usage_data->projections->days_to_disk_threshold . " days\n";
    }
}

// Update thresholds
$post_params = array(
    'wstoken' => 'YOUR_TOKEN',
    'wsfunction' => 'report_usage_monitor_update_thresholds',
    'user_threshold' => 2000,
    'disk_warning_level' => 85,
    'moodlewsrestformat' => 'json'
);
$response = $curl->post('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php', $post_params);
$result = json_decode($response);

if ($result->success) {
    echo "Thresholds updated successfully\n";
}</code></pre>

            <h5>JavaScript Example</h5>
<pre><code>// Get dashboard data for real-time updates
async function getDashboardData() {
    try {
        const response = await fetch('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_dashboard_data&moodlewsrestformat=json');
        const data = await response.json();
        
        // Update dashboard elements
        updateDiskUsage(data.disk_usage);
        updateUserUsage(data.user_usage);
        updateProjections(data.projections);
        
    } catch (error) {
        console.error('API Error:', error);
    }
}

function updateDiskUsage(diskData) {
    document.getElementById('disk-percentage').textContent = diskData.percentage.toFixed(1) + '%';
    document.getElementById('disk-usage').textContent = diskData.current_readable + ' / ' + diskData.threshold_readable;
    
    // Update warning class
    const progressBar = document.getElementById('disk-progress');
    progressBar.className = 'progress-bar ' + diskData.warning_class;
    progressBar.style.width = diskData.percentage + '%';
}

// Update thresholds via API
async function updateThresholds(userThreshold, diskThreshold) {
    const formData = new FormData();
    formData.append('wstoken', 'YOUR_TOKEN');
    formData.append('wsfunction', 'report_usage_monitor_update_thresholds');
    formData.append('user_threshold', userThreshold);
    formData.append('disk_threshold', diskThreshold);
    formData.append('moodlewsrestformat', 'json');

    try {
        const response = await fetch('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            console.log('Thresholds updated successfully');
            // Refresh dashboard data
            getDashboardData();
        } else {
            console.error('Error:', result.messages.join(', '));
        }
    } catch (error) {
        console.error('API Error:', error);
    }
}

// Auto-refresh dashboard every 5 minutes
setInterval(getDashboardData, 300000);</code></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Authentication & Security</h3>
        </div>
        <div class="card-body">
            <p>The Usage Monitor API v5.0 uses Moodle's built-in web services authentication system with enhanced security features:</p>
            
            <h5>Token-based Authentication</h5>
            <ol>
                <li>Go to Site Administration > Plugins > Web Services > Manage tokens</li>
                <li>Create a token for a user with the required capabilities</li>
                <li>Select the 'Usage Monitor API' service</li>
                <li>Include your token in every API request using the 'wstoken' parameter</li>
            </ol>
            
            <h5>Required Capabilities</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Required Capability</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>get_usage_statistics</code></td>
                        <td><code>report/usage_monitor:view</code></td>
                    </tr>
                    <tr>
                        <td><code>get_dashboard_data</code></td>
                        <td><code>report/usage_monitor:view</code></td>
                    </tr>
                    <tr>
                        <td><code>get_notification_history</code></td>
                        <td><code>report/usage_monitor:view</code></td>
                    </tr>
                    <tr>
                        <td><code>update_thresholds</code></td>
                        <td><code>report/usage_monitor:manage</code></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> <strong>Security Best Practices:</strong>
                <ul class="mb-0">
                    <li>Keep your tokens secure and rotate them regularly</li>
                    <li>Use HTTPS for all API communications</li>
                    <li>Implement proper error handling in your applications</li>
                    <li>Monitor API usage and implement rate limiting if necessary</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Version 5.0 Improvements</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Performance Enhancements</h5>
                    <ul>
                        <li>Unified data management with intelligent caching</li>
                        <li>Optimized database queries with proper indexing</li>
                        <li>Reduced API response times by up to 60%</li>
                        <li>Efficient memory usage for large datasets</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>New Features</h5>
                    <ul>
                        <li>Real-time dashboard data endpoint</li>
                        <li>Enhanced historical data management</li>
                        <li>Improved projection algorithms</li>
                        <li>Comprehensive error handling and validation</li>
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-success mt-3">
                <strong>Backward Compatibility:</strong> Version 5.0 maintains compatibility with existing integrations while providing enhanced functionality and performance improvements.
            </div>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
?>