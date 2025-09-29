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
 * Documentación de la API del plugin report_usage_monitor
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Verificar permisos
require_login();
$context = context_system::instance();
require_capability('report/usage_monitor:view', $context);

// Configuración de página
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/report/usage_monitor/api-documentation.php'));
$PAGE->set_title(get_string('api_documentation', 'report_usage_monitor'));
$PAGE->set_heading(get_string('api_documentation', 'report_usage_monitor'));
$PAGE->set_pagelayout('admin');

// Obtener datos para mostrar en la documentación
$webservice_admin_url = new moodle_url('/admin/settings.php', array('section' => 'webservicesoverview'));
$external_service = $DB->get_record('external_services', array('shortname' => 'report_usage_monitor'));
$service_id = $external_service ? $external_service->id : 0;

// Iniciar salida de la página
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('api_documentation', 'report_usage_monitor'));

?>

<div class="api-documentation">
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">API Overview</h3>
        </div>
        <div class="card-body">
            <p>The Usage Monitor API allows external systems to retrieve usage statistics and notifications from your Moodle site. This enables integration with monitoring dashboards, alerting systems, and other tools to help manage your Moodle installation.</p>
            
            <div class="alert alert-info">
                <strong>Important Date Handling:</strong> All dates in this API are represented as UNIX timestamps (seconds since January 1, 1970, 00:00:00 UTC). When providing dates to the API or processing responses, ensure proper timestamp validation and conversion.
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
                <!-- get_usage_data endpoint (Simplified GET method) -->
                <div class="card mb-3">
                    <div class="card-header" id="headingSimpleGet">
                        <h5 class="mb-0">
                            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseSimpleGet" aria-expanded="true" aria-controls="collapseSimpleGet">
                                <code>report_usage_monitor_get_usage_data</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseSimpleGet" class="collapse show" aria-labelledby="headingSimpleGet" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Retrieves precalculated usage data for disk and users with minimal overhead.</p>
                            <p><strong>Parameters:</strong> None</p>
                            <p><strong>Returns:</strong> Simplified usage statistics for disk and users.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_data&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "disk_usage": {
    "current": 12345678901,            // Current usage in bytes
    "current_readable": "11.5 GB",     // Human-readable current usage
    "threshold": 21474836480,          // Configured threshold in bytes
    "threshold_readable": "20 GB",     // Human-readable threshold
    "percentage": 57.5,                // Current usage percentage
    "last_calculated": 1698159284      // Timestamp of last calculation
  },
  "user_usage": {
    "current": 450,                    // Current users
    "threshold": 1000,                 // Configured user threshold
    "percentage": 45.0,                // Current usage percentage
    "last_calculated": 1698159350,     // Timestamp of last calculation
    "max_90_days": 650,                // Maximum users in the last 90 days
    "max_90_days_date": 1644934800     // Timestamp of the date with maximum users
  },
  "projections": {
    "disk_growth_rate": 5.2,           // Monthly disk growth rate in percentage
    "users_growth_rate": 2.3,          // Monthly users growth rate in percentage
    "days_to_disk_threshold": 120,     // Projected days to reach disk warning threshold
    "days_to_users_threshold": 420     // Projected days to reach users warning threshold
  }
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- set_usage_thresholds endpoint (SET method) -->
                <div class="card mb-3">
                    <div class="card-header" id="headingSet">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseSet" aria-expanded="false" aria-controls="collapseSet">
                                <code>report_usage_monitor_set_usage_thresholds</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseSet" class="collapse" aria-labelledby="headingSet" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Updates the configured thresholds for users and disk space.</p>
                            <p><strong>Parameters:</strong></p>
                            <ul>
                                <li><code>user_threshold</code> (integer, optional): New threshold for daily users</li>
                                <li><code>disk_threshold</code> (integer, optional): New threshold for disk space in GB</li>
                            </ul>
                            <p><strong>Note:</strong> At least one parameter must be provided.</p>
                            <p><strong>Returns:</strong> Result of the update operation with success status and messages.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl -X POST '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php' \
  -d 'wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_set_usage_thresholds&user_threshold=1500&disk_threshold=30&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "success": true,
  "user_threshold_updated": true,
  "disk_threshold_updated": true,
  "messages": [
    "User threshold updated successfully.",
    "Disk threshold updated successfully."
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- get_monitor_stats endpoint (Complete statistics) -->
                <div class="card mb-3">
                    <div class="card-header" id="headingOne">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <code>report_usage_monitor_get_monitor_stats</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Retrieves comprehensive usage statistics for the site.</p>
                            <p><strong>Parameters:</strong> None</p>
                            <p><strong>Returns:</strong> Detailed data about disk usage, user counts, system information, projections, and largest courses.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_monitor_stats&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "site_info": {
    "name": "Site Name",
    "shortname": "site",
    "moodle_version": 2023042400,
    "moodle_release": "4.2.0",
    "course_count": 120,
    "user_count": 1500,
    "backup_auto_max_kept": 1
  },
  "disk_usage": {
    "total_bytes": 12345678901,
    "total_readable": "11.5 GB",
    "quota_bytes": 21474836480,
    "quota_readable": "20 GB",
    "percentage": 57.5,
    "details": {
      "database": {
        "bytes": 2147483648,
        "readable": "2 GB",
        "percentage": 17.4
      },
      "filedir": {
        "bytes": 6442450944,
        "readable": "6 GB",
        "percentage": 52.2
      },
      "backup": {
        "bytes": 2147483648,
        "readable": "2 GB",
        "percentage": 17.4
      },
      "cache": {
        "bytes": 536870912,
        "readable": "512 MB",
        "percentage": 4.3
      },
      "others": {
        "bytes": 1073741824,
        "readable": "1 GB",
        "percentage": 8.7
      }
    }
  },
  "user_usage": {
    "daily_users": 450,
    "threshold": 1000,
    "percentage": 45.0,
    "max_90_days": 650,
    "max_90_days_date": "2024-02-15"
  },
  "largest_courses": [
    {
      "id": 123,
      "fullname": "Course 1",
      "shortname": "C1",
      "size_bytes": 1073741824,
      "size_readable": "1 GB",
      "backup_size_bytes": 536870912,
      "backup_size_readable": "512 MB",
      "percentage": 8.7,
      "backup_count": 3
    }
  ],
  "timestamps": {
    "disk_calculation": 1698159284,
    "users_calculation": 1698159350
  },
  "growth_rates": {
    "disk": {
      "monthly_percent": 3.5,
      "projected_days_to_threshold": 95
    },
    "users": {
      "monthly_percent": 2.1,
      "projected_days_to_threshold": 215
    }
  }
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- get_notification_history endpoint -->
                <div class="card mb-3">
                    <div class="card-header" id="headingTwo">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <code>report_usage_monitor_get_notification_history</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Retrieves the history of notifications sent.</p>
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
      "timecreated": 1698159284,            // UNIX timestamp
      "timereadable": "Mon, 24 Oct 2023, 15:34"
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
<pre><code>// Get usage data with timestamp validation
$curl = new curl();
$response = $curl->get('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_data&moodlewsrestformat=json');
$usage_data = json_decode($response);

// Processing the data with timestamp validation
if ($usage_data && isset($usage_data->disk_usage->last_calculated)) {
    // Validate the timestamp
    $last_calculated = $usage_data->disk_usage->last_calculated;
    if (is_numeric($last_calculated) && $last_calculated > 0) {
        $date_readable = date('Y-m-d H:i:s', $last_calculated);
        echo "Last calculation: " . $date_readable . "\n";
    } else {
        echo "Invalid timestamp in response\n";
    }
    
    echo "Disk usage: " . $usage_data->disk_usage->percentage . "%\n";
    echo "User usage: " . $usage_data->user_usage->percentage . "%\n";
}

// Update user threshold
$post_params = array(
    'wstoken' => 'YOUR_TOKEN',
    'wsfunction' => 'report_usage_monitor_set_usage_thresholds',
    'user_threshold' => 2000,
    'moodlewsrestformat' => 'json'
);
$response = $curl->post('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php', $post_params);
$result = json_decode($response);</code></pre>

            <h5>JavaScript Example</h5>
<pre><code>// Get usage data with timestamp handling
fetch('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_data&moodlewsrestformat=json')
  .then(response => response.json())
  .then(data => {
    // Validate timestamps before using them
    const lastDiskCalc = data.disk_usage.last_calculated;
    const lastUserCalc = data.user_usage.last_calculated;
    
    if (lastDiskCalc && typeof lastDiskCalc === 'number' && lastDiskCalc > 0) {
      const diskDate = new Date(lastDiskCalc * 1000); // Convert to milliseconds
      console.log('Last disk calculation:', diskDate.toLocaleString());
    }
    
    if (lastUserCalc && typeof lastUserCalc === 'number' && lastUserCalc > 0) {
      const userDate = new Date(lastUserCalc * 1000); // Convert to milliseconds
      console.log('Last user calculation:', userDate.toLocaleString());
    }
    
    console.log('Disk usage:', data.disk_usage.percentage + '%');
    console.log('User usage:', data.user_usage.percentage + '%');
    
    // Check growth projections
    if (data.projections) {
      console.log('Days to reach disk threshold:', data.projections.days_to_disk_threshold);
      console.log('Days to reach user threshold:', data.projections.days_to_users_threshold);
    }
  })
  .catch(error => console.error('API Error:', error));

// Update thresholds
const formData = new FormData();
formData.append('wstoken', 'YOUR_TOKEN');
formData.append('wsfunction', 'report_usage_monitor_set_usage_thresholds');
formData.append('disk_threshold', 50);
formData.append('moodlewsrestformat', 'json');

fetch('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php', {
  method: 'POST',
  body: formData
})
  .then(response => response.json())
  .then(result => {
    if (result.success) {
      console.log('Thresholds updated successfully');
    } else {
      console.error('Error:', result.messages.join(', '));
    }
  });</code></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Authentication</h3>
        </div>
        <div class="card-body">
            <p>To authenticate with the API, you need to use a security token. You can create a token in the Moodle Web Services settings:</p>
            
            <ol>
                <li>Go to Site Administration > Plugins > Web Services > Manage tokens</li>
                <li>Create a token for a user with the 'report/usage_monitor:apiuse' capability</li>
                <li>Select the 'Usage Monitor API' service</li>
                <li>Include your token in every API request using the 'wstoken' parameter</li>
            </ol>
            
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> <strong>Warning:</strong> Keep your tokens secure. They provide access to your Moodle data.
            </div>
            
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <strong>Best Practice:</strong> For enhanced security, consider using POST requests with tokens in the request body rather than including them in URL parameters.
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Required Permissions</h3>
        </div>
        <div class="card-body">
            <p>The following permissions are required for using the API endpoints:</p>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Required Capability</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>report_usage_monitor_get_usage_data</code></td>
                        <td><code>report/usage_monitor:view</code></td>
                    </tr>
                    <tr>
                        <td><code>report_usage_monitor_set_usage_thresholds</code></td>
                        <td><code>report/usage_monitor:manage</code></td>
                    </tr>
                    <tr>
                        <td><code>report_usage_monitor_get_monitor_stats</code></td>
                        <td><code>report/usage_monitor:view</code></td>
                    </tr>
                    <tr>
                        <td><code>report_usage_monitor_get_notification_history</code></td>
                        <td><code>report/usage_monitor:view</code></td>
                    </tr>
                </tbody>
            </table>
            
            <p>Ensure that the user associated with your token has all the required capabilities for the endpoints you intend to use.</p>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Date and Time Handling</h3>
        </div>
        <div class="card-body">
            <p>All timestamps in the API are UNIX timestamps (seconds since January 1, 1970, 00:00:00 UTC). When working with these timestamps:</p>
            
            <ul>
                <li><strong>Validation:</strong> Always validate timestamps before using them. Check that they are numeric and greater than zero.</li>
                <li><strong>Conversion:</strong> To convert a UNIX timestamp to a human-readable date:
                    <ul>
                        <li>PHP: <code>date('Y-m-d H:i:s', $timestamp)</code></li>
                        <li>JavaScript: <code>new Date(timestamp * 1000).toLocaleString()</code></li>
                    </ul>
                </li>
                <li><strong>Current Time:</strong> To get the current UNIX timestamp:
                    <ul>
                        <li>PHP: <code>time()</code></li>
                        <li>JavaScript: <code>Math.floor(Date.now() / 1000)</code></li>
                    </ul>
                </li>
            </ul>
            
            <div class="alert alert-warning">
                <strong>Important:</strong> Dates in the API responses may occasionally be invalid or zero if data hasn't been calculated yet. Always check for these cases in your integration code.
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la página
echo $OUTPUT->footer();