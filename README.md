Based on the content of the repository, here is an updated `README.md` for the `report_usage_monitor` plugin:
# Usage Monitor Report

The `report_usage_monitor` plugin for Moodle provides administrators with the capability to monitor and report on various aspects of usage within their Moodle installation, including user login activity and disk usage. It includes several scheduled tasks that generate reports and send notifications when thresholds are exceeded.

## Features

- **User Activity Monitoring**: Tracks the number of unique users logging in daily.
- **Disk Usage Monitoring**: Monitors the disk space usage of your Moodle installation, including database size and filesystem usage.
- **Threshold Notifications**: Sends email notifications when user activity or disk usage exceeds predefined thresholds.
- **Configurable Settings**: Admins can set thresholds for daily user logins and disk usage limits.

## Installation

### Installing via Uploaded ZIP File

1. Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

### Installing Manually

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/report/usage_monitor

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php --non-interactive

to complete the installation from the command line.

## Configuration

1. Navigate to _Site administration > Plugins > Reports > Usage Monitor_.
2. Configure the maximum daily users threshold and disk quota.
3. Set the email address for receiving notifications.

## Scheduled Tasks

The plugin includes several scheduled tasks:

- **Disk Usage Calculation (`report_usage_monitor\task\disk_usage`)**: Calculates the usage of the disk space and stores the information.
- **Recent Users Calculation (`report_usage_monitor\task\last_users`)**: Calculates the number of users that have logged in recently.
- **Daily User Limit Notification (`report_usage_monitor\task\notification_userlimit`)**: Sends notifications if the daily user login threshold is exceeded.
- **Disk Usage Notification (`report_usage_monitor\task\notification_disk`)**: Sends notifications if the disk usage exceeds the defined quota.
- **Daily Users Calculation (`report_usage_monitor\task\users_daily`)**: Calculates the top number of daily unique users.
- **Users in Last 90 Days (`report_usage_monitor\task\users_daily_90_days`)**: Calculates the number of users in the last 90 days.

## Usage

Once installed and configured, the plugin will automatically monitor the usage and send notifications based on the defined thresholds. The notifications include detailed information about the number of users and disk usage, helping administrators to keep track of their Moodle site's performance and take necessary actions.

## License

2024 Soporte IngeWeb <soporte@ingeweb.co>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <https://www.gnu.org/licenses/>.
