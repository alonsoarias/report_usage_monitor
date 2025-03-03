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
    "current": 12345678901,            // Uso actual en bytes
    "current_readable": "11.5 GB",     // Uso actual en formato legible
    "threshold": 21474836480,          // Umbral configurado en bytes
    "threshold_readable": "20 GB",     // Umbral en formato legible
    "percentage": 57.5,                // Porcentaje de uso actual
    "last_calculated": 1698159284      // Timestamp del último cálculo
  },
  "user_usage": {
    "current": 450,                    // Usuarios actuales
    "threshold": 1000,                 // Umbral de usuarios configurado
    "percentage": 45.0,                // Porcentaje de uso actual
    "last_calculated": 1698159350,     // Timestamp del último cálculo
    "max_90_days": 650,                // Máximo de usuarios en los últimos 90 días
    "max_90_days_date": 1644934800     // Timestamp de la fecha con máximo de usuarios
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
                            <p><strong>Returns:</strong> Detailed data about disk usage, user counts, system information, and largest courses.</p>
                            
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
      "percentage": 8.7,
      "backup_count": 3
    },
    ...
  ],
  "timestamps": {
    "disk_calculation": 1698159284,
    "users_calculation": 1698159350
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
      "timecreated": 1698159284,
      "timereadable": "Mon, 24 Oct 2023, 15:34"
    },
    ...
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <!-- register_webhook endpoint -->
                <div class="card mb-3">
                    <div class="card-header" id="headingThree">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <code>report_usage_monitor_register_webhook</code>
                            </button>
                        </h5>
                    </div>
                    <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#apiEndpoints">
                        <div class="card-body">
                            <p><strong>Description:</strong> Registers a webhook to receive notifications.</p>
                            <p><strong>Parameters:</strong></p>
                            <ul>
                                <li><code>url</code> (string, required): URL of the webhook</li>
                                <li><code>events</code> (array, optional): Types of events to subscribe to ('disk_warning', 'user_warning'). Default: ['disk_warning', 'user_warning']</li>
                                <li><code>secret</code> (string, optional): Secret key for signing notifications. Default: ''</li>
                            </ul>
                            <p><strong>Returns:</strong> Result of the registration.</p>
                            
                            <h6>Example Request:</h6>
<pre><code>curl -X POST '<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php' \
  -d 'wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_register_webhook&url=https://example.com/webhook&events[0]=disk_warning&events[1]=user_warning&secret=your_secret_key&moodlewsrestformat=json'</code></pre>

                            <h6>Example Response:</h6>
<pre><code>{
  "success": true,
  "message": "Webhook registered successfully",
  "webhook_id": 12
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
<pre><code>// Obtener datos de uso
$curl = new curl();
$response = $curl->get('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_data&moodlewsrestformat=json');
$usage_data = json_decode($response);

// Actualizar umbral de usuarios
$post_params = array(
    'wstoken' => 'YOUR_TOKEN',
    'wsfunction' => 'report_usage_monitor_set_usage_thresholds',
    'user_threshold' => 2000,
    'moodlewsrestformat' => 'json'
);
$response = $curl->post('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php', $post_params);
$result = json_decode($response);</code></pre>

            <h5>JavaScript Example</h5>
<pre><code>// Obtener datos de uso
fetch('<?php echo $CFG->wwwroot; ?>/webservice/rest/server.php?wstoken=YOUR_TOKEN&wsfunction=report_usage_monitor_get_usage_data&moodlewsrestformat=json')
  .then(response => response.json())
  .then(data => {
    console.log('Uso de disco:', data.disk_usage.percentage + '%');
    console.log('Uso de usuarios:', data.user_usage.percentage + '%');
  });

// Actualizar umbrales
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
      console.log('Umbrales actualizados correctamente');
    } else {
      console.error('Error:', result.messages.join(', '));
    }
  });</code></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0">Webhook Payloads</h3>
        </div>
        <div class="card-body">
            <p>When an event occurs, the following payloads will be sent to the registered webhook URLs:</p>
            
            <h5>Disk Warning Event</h5>
<pre><code>{
  "event": "disk_warning",
  "timestamp": 1698159284,
  "site": "Site Name",
  "site_url": "<?php echo $CFG->wwwroot; ?>",
  "data": {
    "disk_usage": "19.1 GB",
    "disk_quota": "20 GB",
    "percentage": 95.5,
    "database_size": "2 GB",
    "warning_level": "critical"
  },
  "signature": "HMAC_SIGNATURE_IF_SECRET_PROVIDED"
}</code></pre>

            <h5>User Warning Event</h5>
<pre><code>{
  "event": "user_warning",
  "timestamp": 1698159284,
  "site": "Site Name",
  "site_url": "<?php echo $CFG->wwwroot; ?>",
  "data": {
    "users_count": 950,
    "users_threshold": 1000,
    "percentage": 95.0,
    "date": "2024-03-02",
    "warning_level": "high"
  },
  "signature": "HMAC_SIGNATURE_IF_SECRET_PROVIDED"
}</code></pre>
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
                    <tr>
                        <td><code>report_usage_monitor_register_webhook</code></td>
                        <td><code>moodle/site:config</code></td>
                    </tr>
                </tbody>
            </table>
            
            <p>Ensure that the user associated with your token has all the required capabilities for the endpoints you intend to use.</p>
        </div>
    </div>
</div>

<?php
// Finalizar la página
echo $OUTPUT->footer();