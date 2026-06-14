<?php defined( 'ABSPATH' ) || exit; ?>
<div class="omni-date-filter">
	<div class="omni-presets">
		<button class="omni-preset" data-range="today"><?php esc_html_e( 'Today', 'omni-reports' ); ?></button>
		<button class="omni-preset" data-range="7days"><?php esc_html_e( 'Last 7 Days', 'omni-reports' ); ?></button>
		<button class="omni-preset active" data-range="30days"><?php esc_html_e( 'Last 30 Days', 'omni-reports' ); ?></button>
		<button class="omni-preset" data-range="thismonth"><?php esc_html_e( 'This Month', 'omni-reports' ); ?></button>
		<button class="omni-preset" data-range="lastmonth"><?php esc_html_e( 'Last Month', 'omni-reports' ); ?></button>
		<button class="omni-preset" data-range="custom"><?php esc_html_e( 'Custom', 'omni-reports' ); ?></button>
	</div>
	<div class="omni-custom-dates" id="omni-custom-dates" style="display:none">
		<label><?php esc_html_e( 'From', 'omni-reports' ); ?> <input type="date" id="omni-date-from" /></label>
		<label><?php esc_html_e( 'To', 'omni-reports' ); ?> <input type="date" id="omni-date-to" /></label>
		<button class="omni-btn omni-btn-primary omni-btn-sm" id="omni-apply-dates"><?php esc_html_e( 'Apply', 'omni-reports' ); ?></button>
	</div>
	<input type="hidden" id="omni-comp-from" />
	<input type="hidden" id="omni-comp-to" />
	<div class="omni-export-bar">
		<button class="omni-export-btn" id="omni-export-csv"><?php esc_html_e( 'Export CSV', 'omni-reports' ); ?></button>
		<button class="omni-print-btn" onclick="window.print()"><?php esc_html_e( 'Print', 'omni-reports' ); ?></button>
	</div>
</div>
