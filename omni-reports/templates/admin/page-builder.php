<?php
/**
 * Drag-and-Drop Report Builder page.
 *
 * @package OmniReports
 */
defined( 'ABSPATH' ) || exit;
$metrics    = class_exists( 'Omni_Reports_Report_Builder' ) ? Omni_Reports_Report_Builder::available_metrics()    : [];
$dimensions = class_exists( 'Omni_Reports_Report_Builder' ) ? Omni_Reports_Report_Builder::available_dimensions() : [];
?>
<!-- Drag-and-Drop Builder -->
<div class="omni-dnd-builder" id="omni-dnd-builder">

	<!-- Top Toolbar -->
	<div class="omni-builder-toolbar">
		<input type="text" id="omni-builder-report-name" class="omni-input omni-builder-name-input" placeholder="Untitled Report" value="Untitled Report" />
		<div class="omni-builder-toolbar-actions">
			<select id="omni-builder-grid-width" class="omni-input omni-builder-width-select">
				<option value="full">Full Width</option>
				<option value="2col">2 Column</option>
				<option value="3col">3 Column</option>
			</select>
			<button class="omni-btn omni-btn-ghost" id="omni-builder-clear"><span class="dashicons dashicons-trash"></span> Clear</button>
			<button class="omni-btn omni-btn-secondary" id="omni-builder-preview"><span class="dashicons dashicons-visibility"></span> Preview</button>
			<button class="omni-btn omni-btn-primary" id="omni-builder-save"><span class="dashicons dashicons-saved"></span> Save</button>
		</div>
	</div>

	<div class="omni-builder-layout">

		<!-- Left Sidebar: Widget Library -->
		<div class="omni-builder-sidebar" id="omni-widget-library">
			<div class="omni-builder-sidebar-header">Widget Library</div>

			<div class="omni-widget-section">
				<div class="omni-widget-section-title">KPI Widgets</div>
				<div class="omni-widget-list">
					<?php
					$kpi_widgets = [
						[ 'type' => 'kpi-revenue',      'label' => 'Revenue',       'icon' => 'dashicons-chart-line' ],
						[ 'type' => 'kpi-orders',       'label' => 'Orders',        'icon' => 'dashicons-list-view' ],
						[ 'type' => 'kpi-aov',          'label' => 'Avg Order Value','icon' => 'dashicons-cart' ],
						[ 'type' => 'kpi-profit',       'label' => 'Profit',        'icon' => 'dashicons-chart-area' ],
						[ 'type' => 'kpi-refund-rate',  'label' => 'Refund Rate',   'icon' => 'dashicons-undo' ],
						[ 'type' => 'kpi-customers',    'label' => 'New Customers', 'icon' => 'dashicons-groups' ],
						[ 'type' => 'kpi-stock-value',  'label' => 'Stock Value',   'icon' => 'dashicons-archive' ],
						[ 'type' => 'kpi-coupon-usage', 'label' => 'Coupon Usage',  'icon' => 'dashicons-tag' ],
					];
					foreach ( $kpi_widgets as $w ) :
					?>
					<div class="omni-widget-card" draggable="true" data-widget-type="<?php echo esc_attr( $w['type'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $w['icon'] ); ?>"></span>
						<span class="omni-widget-card-label"><?php echo esc_html( $w['label'] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="omni-widget-section">
				<div class="omni-widget-section-title">Chart Widgets</div>
				<div class="omni-widget-list">
					<?php
					$chart_widgets = [
						[ 'type' => 'chart-line', 'label' => 'Line Chart',    'icon' => 'dashicons-chart-line' ],
						[ 'type' => 'chart-bar',  'label' => 'Bar Chart',     'icon' => 'dashicons-chart-bar' ],
						[ 'type' => 'chart-pie',  'label' => 'Pie / Donut',   'icon' => 'dashicons-chart-pie' ],
						[ 'type' => 'chart-area', 'label' => 'Area Chart',    'icon' => 'dashicons-chart-area' ],
					];
					foreach ( $chart_widgets as $w ) :
					?>
					<div class="omni-widget-card" draggable="true" data-widget-type="<?php echo esc_attr( $w['type'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $w['icon'] ); ?>"></span>
						<span class="omni-widget-card-label"><?php echo esc_html( $w['label'] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="omni-widget-section">
				<div class="omni-widget-section-title">Table Widgets</div>
				<div class="omni-widget-list">
					<?php
					$table_widgets = [
						[ 'type' => 'table-products',   'label' => 'Products Table',   'icon' => 'dashicons-products' ],
						[ 'type' => 'table-customers',  'label' => 'Customers Table',  'icon' => 'dashicons-groups' ],
						[ 'type' => 'table-orders',     'label' => 'Orders Table',     'icon' => 'dashicons-list-view' ],
						[ 'type' => 'table-categories', 'label' => 'Categories Table', 'icon' => 'dashicons-category' ],
						[ 'type' => 'table-coupons',    'label' => 'Coupons Table',    'icon' => 'dashicons-tag' ],
					];
					foreach ( $table_widgets as $w ) :
					?>
					<div class="omni-widget-card" draggable="true" data-widget-type="<?php echo esc_attr( $w['type'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $w['icon'] ); ?>"></span>
						<span class="omni-widget-card-label"><?php echo esc_html( $w['label'] ); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="omni-widget-section">
				<div class="omni-widget-section-title">Layout</div>
				<div class="omni-widget-list">
					<div class="omni-widget-card" draggable="true" data-widget-type="layout-divider">
						<span class="dashicons dashicons-minus"></span>
						<span class="omni-widget-card-label">Divider</span>
					</div>
					<div class="omni-widget-card" draggable="true" data-widget-type="layout-heading">
						<span class="dashicons dashicons-editor-paragraph"></span>
						<span class="omni-widget-card-label">Heading</span>
					</div>
					<div class="omni-widget-card" draggable="true" data-widget-type="layout-spacer">
						<span class="dashicons dashicons-editor-kitchensink"></span>
						<span class="omni-widget-card-label">Spacer</span>
					</div>
				</div>
			</div>

			<!-- Saved reports section (legacy query builder) -->
			<div class="omni-widget-section omni-builder-legacy-section">
				<div class="omni-widget-section-title">Query Builder Reports</div>
				<select id="omni-saved-select" class="omni-input" style="width:100%;margin-bottom:6px;">
					<option value="">— Load saved report —</option>
				</select>
				<div style="display:flex;gap:4px;">
					<button class="omni-btn omni-btn-secondary omni-btn-sm" id="omni-load-saved" style="flex:1;">Load</button>
					<button class="omni-btn omni-btn-ghost omni-btn-sm" id="omni-delete-saved">Delete</button>
				</div>
			</div>
		</div>

		<!-- Center Canvas -->
		<div class="omni-builder-canvas-wrap">
			<div class="omni-builder-canvas omni-drop-zone" id="omni-builder-canvas">
				<div class="omni-canvas-empty-state" id="omni-canvas-empty">
					<span class="dashicons dashicons-layout" style="font-size:48px;color:#CBD5E0;"></span>
					<p>Drag widgets here to build your report</p>
				</div>
			</div>

			<!-- Legacy query-builder results (shown when legacy mode active) -->
			<div id="omni-legacy-results" style="display:none; margin-top:16px;">
				<div class="omni-card">
					<div class="omni-card-header">
						<span class="omni-card-title">Query Builder</span>
						<button class="omni-btn omni-btn-ghost omni-btn-sm" id="omni-close-legacy">Close</button>
					</div>
					<div class="omni-builder-legacy-config" style="display:flex;gap:16px;flex-wrap:wrap;padding:12px;">
						<label style="flex:1;min-width:120px;">
							From<br>
							<input type="date" id="builder-date-from" class="omni-input" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '-30 days' ) ) ); ?>">
						</label>
						<label style="flex:1;min-width:120px;">
							To<br>
							<input type="date" id="builder-date-to" class="omni-input" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
						</label>
						<label style="flex:1;min-width:120px;">
							Group By<br>
							<select id="builder-dimension" class="omni-input">
								<?php foreach ( $dimensions as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label style="flex:1;min-width:120px;">
							Visualization<br>
							<select id="builder-chart-type" class="omni-input">
								<option value="table">Table</option>
								<option value="line">Line Chart</option>
								<option value="bar">Bar Chart</option>
								<option value="pie">Pie Chart</option>
							</select>
						</label>
					</div>
					<div class="omni-builder-legacy-metrics" style="padding:0 12px 12px;">
						<div style="font-size:12px;font-weight:600;margin-bottom:8px;color:#718096;">Metrics</div>
						<div style="display:flex;flex-wrap:wrap;gap:8px;" id="builder-metrics">
							<?php foreach ( $metrics as $key => $label ) : ?>
							<label style="font-size:13px;display:flex;align-items:center;gap:4px;">
								<input type="checkbox" name="metric" value="<?php echo esc_attr( $key ); ?>" <?php echo in_array( $key, [ 'revenue', 'orders' ], true ) ? 'checked' : ''; ?>>
								<?php echo esc_html( $label ); ?>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div style="padding:0 12px 12px;display:flex;gap:8px;align-items:center;">
						<button class="omni-btn omni-btn-primary" id="omni-run-report">Run Report</button>
						<button class="omni-btn omni-btn-secondary" id="omni-export-builder-csv">Export CSV</button>
						<input type="text" class="omni-input" id="omni-report-name" placeholder="Report name…" style="flex:1;">
						<button class="omni-btn omni-btn-ghost" id="omni-save-report">Save</button>
					</div>
					<div id="omni-builder-chart-wrap" style="display:none;padding:0 12px 12px;">
						<canvas id="omni-builder-chart" height="80"></canvas>
					</div>
					<div id="omni-builder-table-wrap" style="padding:0 12px 12px;">
						<p class="omni-empty-state">Configure and click "Run Report".</p>
					</div>
					<div id="omni-builder-notice" class="notice" style="display:none;"></div>
				</div>
			</div>
		</div>

		<!-- Right Config Panel -->
		<div class="omni-builder-config" id="omni-builder-config-panel" style="display:none;">
			<div class="omni-builder-config-header">
				<span id="omni-config-title">Widget Config</span>
				<button class="omni-btn omni-btn-ghost omni-btn-sm" id="omni-config-close">×</button>
			</div>
			<div id="omni-config-body">
				<div class="omni-config-field">
					<label class="omni-config-label">Title Override</label>
					<input type="text" class="omni-input" id="omni-config-title-input" placeholder="Widget title…" />
				</div>
				<div class="omni-config-field">
					<label class="omni-config-label">Width</label>
					<select class="omni-input" id="omni-config-width">
						<option value="1/4">1/4</option>
						<option value="1/3">1/3</option>
						<option value="1/2" selected>1/2</option>
						<option value="2/3">2/3</option>
						<option value="3/4">3/4</option>
						<option value="full">Full</option>
					</select>
				</div>
				<div class="omni-config-field">
					<label class="omni-config-label">Color Theme</label>
					<select class="omni-input" id="omni-config-color">
						<option value="teal">Teal (Primary)</option>
						<option value="blue">Blue</option>
						<option value="purple">Purple</option>
						<option value="green">Green</option>
						<option value="orange">Orange</option>
						<option value="red">Red</option>
					</select>
				</div>
				<div class="omni-config-field omni-config-chart-only" style="display:none;">
					<label class="omni-config-label">Chart Type</label>
					<select class="omni-input" id="omni-config-chart-type">
						<option value="bar">Bar</option>
						<option value="line">Line</option>
						<option value="pie">Pie / Donut</option>
						<option value="area">Area</option>
					</select>
				</div>
				<div class="omni-config-field omni-config-metric-only" style="display:none;">
					<label class="omni-config-label">Metric</label>
					<select class="omni-input" id="omni-config-metric">
						<option value="revenue">Revenue</option>
						<option value="orders">Orders</option>
						<option value="profit">Profit</option>
						<option value="aov">Avg Order Value</option>
					</select>
				</div>
				<button class="omni-btn omni-btn-primary omni-btn-sm" id="omni-config-apply" style="width:100%;margin-top:8px;">Apply</button>
			</div>
		</div>

	</div>
</div>

<div id="omni-builder-notice-global" class="omni-notice" style="display:none;margin-top:12px;"></div>
