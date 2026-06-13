<?php defined( 'ABSPATH' ) || exit; ?>
<div class="omni-date-filter">
	<div class="omni-presets">
		<button class="omni-preset button" data-range="today"><?php esc_html_e( 'Today', 'omni-reports' ); ?></button>
		<button class="omni-preset button" data-range="7days"><?php esc_html_e( 'Last 7 Days', 'omni-reports' ); ?></button>
		<button class="omni-preset button active" data-range="30days"><?php esc_html_e( 'Last 30 Days', 'omni-reports' ); ?></button>
		<button class="omni-preset button" data-range="thismonth"><?php esc_html_e( 'This Month', 'omni-reports' ); ?></button>
		<button class="omni-preset button" data-range="lastmonth"><?php esc_html_e( 'Last Month', 'omni-reports' ); ?></button>
		<button class="omni-preset button" data-range="custom"><?php esc_html_e( 'Custom', 'omni-reports' ); ?></button>
	</div>
	<div class="omni-custom-dates" id="omni-custom-dates" style="display:none">
		<label><?php esc_html_e( 'From', 'omni-reports' ); ?> <input type="date" id="omni-date-from" /></label>
		<label><?php esc_html_e( 'To', 'omni-reports' ); ?> <input type="date" id="omni-date-to" /></label>
		<button class="button button-primary" id="omni-apply-dates"><?php esc_html_e( 'Apply', 'omni-reports' ); ?></button>
	</div>
	<div class="omni-export-bar">
		<button class="button omni-export-btn" id="omni-export-csv"><?php esc_html_e( 'Export CSV', 'omni-reports' ); ?></button>
		<button class="button omni-print-btn" onclick="window.print()"><?php esc_html_e( 'Print', 'omni-reports' ); ?></button>
	</div>
</div>
