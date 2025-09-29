# Usage Monitor Plugin Audit Report

## 1. Executive Summary
- Normalised all dashboard data sources through a new `classes/local/dashboard_data.php` aggregator so the UI and REST API render the same metrics without double rounding or stale configuration values.
- Corrected multiple data inconsistencies between tables and charts (disk usage percentages, user trends, largest courses) and aligned timestamp handling with the scheduled tasks.
- Refactored the REST endpoints in `classes/external.php` to follow Moodle's external API best practices, reuse the central data aggregator, and return predictable, validated payloads.
- Removed the obsolete `api-documentation.php` entry point and documented the current behaviour for future maintenance.

## 2. File-by-File Findings

### `index.php`
- **Issue:** The dashboard computed chart datasets and table rows directly from configuration values, mixing totals that included `dirroot` with per-directory metrics from `dataroot`, producing mismatched percentages and inconsistent tooltips.
  - **Fix:** Replaced the ad-hoc logic with calls to `dashboard_data` and derived display structures (`$directory_rows`, `$last10daysLabels`, etc.) to keep all components synchronised.
- **Issue:** Daily user data and disk history queries returned descending results and filled missing dates with `null`, leaving charts unsorted and sometimes blank.
  - **Fix:** Added chronological ordering, default fallbacks, and percentage clamping through the aggregator so both tables and charts share the same sequences.
- **Issue:** Hard-coded threshold displays (e.g., `users_today / $max_users_threshold`) showed misleading values when thresholds were unset.
  - **Fix:** Added display helpers (`$max_users_threshold_display`) and conditional rendering for unreadable figures.

### `classes/local/dashboard_data.php`
- **Purpose:** New helper encapsulating all read-only data assembly for the dashboard and API.
- **Key Features:**
  - Centralises access to plugin configuration and database queries.
  - Normalises directory analysis, course sizing, disk history, and user trend data with safeguards for missing timestamps and division-by-zero scenarios.
  - Provides reusable methods (`get_disk_summary`, `get_user_summary`, `get_disk_history`, etc.) for consistent UI/API output.

### `classes/external.php`
- **Issue:** Each external method recomputed values independently, ignored parameter validation in `get_monitor_stats`, and exposed inconsistent detail (e.g., missing `safe_percentage` clamping).
  - **Fix:** Added parameter validation, reused `dashboard_data`, introduced helper methods for formatting disk details and timestamps, and consolidated growth projections with guard clauses.
- **Issue:** `set_usage_thresholds` relied on raw configuration reads and wrote unclamped percentages.
  - **Fix:** Recomputed derived values through the aggregator, ensured percentages are clamped/rounded, and kept warning classes aligned with dashboard logic.

### Scheduled Tasks (`classes/task/*.php`)
- **Observation:** Tasks already populate configuration with raw counts/sizes. No code changes were required, but the new aggregator now interprets their outputs consistently.

### `docs/audit-report.md`
- **Addition:** This document summarises the full audit, identified issues, applied fixes, and future recommendations.

### Removed `api-documentation.php`
- **Reason:** The standalone documentation file exposed outdated content and was not part of Moodle's recommended delivery approach.

## 3. Dashboard Data Inconsistencies (Before vs After)

| Component | Previous Problem | Resolution |
|-----------|-----------------|------------|
| Disk usage table vs doughnut chart | Percentages used different totals (`dataroot + dirroot` vs per-directory), making slices and table rows disagree. | `dashboard_data::get_disk_summary()` now computes a single breakdown, and both visualisations consume the same dataset. |
| Last 10 days user trend | SQL returned descending dates; chart rendered reversed order and tooltips lacked raw counts. | Trend data now sorted chronologically with raw counts stored for tooltips and percentage limits capped at 100%. |
| Disk history chart | Sparse days produced `null` values and jagged lines, sometimes hiding the chart. | Aggregator fills gaps with the last known percentage so charts render continuous lines. |
| Largest courses table | Cached JSON lacked sanitation; missing fields caused PHP notices and blank rows. | Aggregator converts cached data into typed objects with defaults and ensures `totalsize`/`percentage` are always present. |

## 4. API Refactor Highlights
- `get_monitor_stats` and `get_usage_data` now:
  - Call `self::validate_parameters` and guard contexts/capabilities at entry.
  - Reuse aggregated data, returning consistent disk/user summaries and course listings.
  - Provide stable timestamps (`safe_timestamp`) and deterministic disk detail structures (including a backward-compatible `backup` key).
  - Clamp growth projections when thresholds or current values are zero to avoid math exceptions.
- `set_usage_thresholds` uses aggregated current values to recompute stored percentages and warning classes, preventing stale or over-100 readings.

## 5. Recommendations for Future Maintenance
- **Automated Tests:** Add PHPUnit/Behat coverage for the new `dashboard_data` class and REST endpoints to catch regressions when tasks or configuration formats evolve.
- **Performance Monitoring:** The aggregator queries log tables for trend data; consider caching these summaries if the dashboard becomes a high-traffic page.
- **Data Retention:** Review the size of `report_usage_monitor_history` periodically. Currently, old entries are purged in tasks, but configurable retention could offer more flexibility.
- **API Documentation:** Generate REST documentation using Moodle's built-in service descriptions instead of a standalone PHP file to keep help content in sync.
- **Accessibility & UX:** With consistent data sources, future work can focus on responsive chart scaling and accessibility (ARIA descriptions for cards and charts).

## 6. Conclusion
All identified inconsistencies were addressed, the API now mirrors dashboard figures, and the plugin has a documented data pipeline, making future enhancements safer and easier to verify.
