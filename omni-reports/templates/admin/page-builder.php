<?php
defined( 'ABSPATH' ) || exit;
$metrics    = Omni_Reports_Report_Builder::available_metrics();
$dimensions = Omni_Reports_Report_Builder::available_dimensions();
?>
<div class="wrap omni-reports-wrap omni-builder-wrap">
	<h1><?php esc_html_e( 'Custom Report Builder', 'omni-reports' ); ?></h1>

	<div class="omni-builder-layout">
		<!-- Config Panel -->
		<div class="omni-builder-panel">
			<h2><?php esc_html_e( 'Saved Reports', 'omni-reports' ); ?></h2>
			<select id="omni-saved-select"><option value=""><?php esc_html_e( '— Load a saved report —', 'omni-reports' ); ?></option></select>
			<button class="button" id="omni-load-saved"><?php esc_html_e( 'Load', 'omni-reports' ); ?></button>
			<button class="button omni-delete-saved" id="omni-delete-saved"><?php esc_html_e( 'Delete', 'omni-reports' ); ?></button>

			<hr>
			<h2><?php esc_html_e( 'Date Range', 'omni-reports' ); ?></h2>
			<label><?php esc_html_e( 'From', 'omni-reports' ); ?><br><input type="date" id="builder-date-from" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '-30 days' ) ) ); ?>"></label>
			<label><?php esc_html_e( 'To', 'omni-reports' ); ?><br><input type="date" id="builder-date-to" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></label>

			<hr>
			<h2><?php esc_html_e( 'Metrics', 'omni-reports' ); ?></h2>
			<div class="omni-checkboxes" id="builder-metrics">
				<?php foreach ( $metrics as $key => $label ) : ?>
				<label><input type="checkbox" name="metric" value="<?php echo esc_attr( $key ); ?>" <?php echo in_array( $key, [ 'revenue', 'orders' ], true ) ? 'checked' : ''; ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
			</div>

			<hr>
			<h2><?php esc_html_e( 'Dimension (Group By)', 'omni-reports' ); ?></h2>
			<select id="builder-dimension">
				<?php foreach ( $dimensions as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<hr>
			<h2><?php esc_html_e( 'Filters', 'omni-reports' ); ?></h2>
			<label><?php esc_html_e( 'Min Order Value', 'omni-reports' ); ?><br><input type="number" id="filter-min-order" min="0" step="0.01" placeholder="0.00"></label>
			<label><?php esc_html_e( 'Max Order Value', 'omni-reports' ); ?><br><input type="number" id="filter-max-order" min="0" step="0.01" placeholder=""></label>

			<hr>
			<h2><?php esc_html_e( 'Visualization', 'omni-reports' ); ?></h2>
			<select id="builder-chart-type">
				<option value="table"><?php esc_html_e( 'Table', 'omni-reports' ); ?></option>
				<option value="line"><?php esc_html_e( 'Line Chart', 'omni-reports' ); ?></option>
				<option value="bar"><?php esc_html_e( 'Bar Chart', 'omni-reports' ); ?></option>
				<option value="pie"><?php esc_html_e( 'Pie Chart', 'omni-reports' ); ?></option>
			</select>

			<hr>
			<div class="omni-builder-actions">
				<button class="button button-primary" id="omni-run-report"><?php esc_html_e( 'Run Report', 'omni-reports' ); ?></button>
				<button class="button" id="omni-export-builder-csv"><?php esc_html_e( 'Export CSV', 'omni-reports' ); ?></button>
			</div>

			<hr>
			<div class="omni-save-report">
				<input type="text" id="omni-report-name" placeholder="<?php esc_attr_e( 'Report name...', 'omni-reports' ); ?>">
				<button class="button" id="omni-save-report"><?php esc_html_e( 'Save Report', 'omni-reports' ); ?></button>
			</div>
		</div>

		<!-- Results Panel -->
		<div class="omni-builder-results">
			<div id="omni-builder-chart-wrap" style="display:none">
				<canvas id="omni-builder-chart" height="80"></canvas>
			</div>
			<div id="omni-builder-table-wrap">
				<p class="omni-empty-state"><?php esc_html_e( 'Configure your report on the left and click "Run Report".', 'omni-reports' ); ?></p>
			</div>
			<div id="omni-builder-notice" class="notice" style="display:none"></div>
		</div>
	</div>
</div>
