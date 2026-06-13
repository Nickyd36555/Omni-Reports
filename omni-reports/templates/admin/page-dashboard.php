<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap">
	<h1><?php esc_html_e( 'Omni Reports — Dashboard', 'omni-reports' ); ?></h1>

	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid" id="omni-kpi-grid">
		<div class="omni-kpi-card" data-metric="revenue"><span class="omni-kpi-label"><?php esc_html_e( 'Gross Revenue', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-revenue">—</span></div>
		<div class="omni-kpi-card" data-metric="net_revenue"><span class="omni-kpi-label"><?php esc_html_e( 'Net Revenue', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-net_revenue">—</span></div>
		<div class="omni-kpi-card" data-metric="orders"><span class="omni-kpi-label"><?php esc_html_e( 'Orders', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-orders">—</span></div>
		<div class="omni-kpi-card" data-metric="avg_order_value"><span class="omni-kpi-label"><?php esc_html_e( 'Avg Order Value', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-avg_order_value">—</span></div>
		<div class="omni-kpi-card" data-metric="refunds"><span class="omni-kpi-label"><?php esc_html_e( 'Refunds', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-refunds">—</span></div>
		<div class="omni-kpi-card" data-metric="tax"><span class="omni-kpi-label"><?php esc_html_e( 'Tax Collected', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-tax">—</span></div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Revenue Over Time', 'omni-reports' ); ?></h2>
		<canvas id="omni-dashboard-chart" height="80"></canvas>
	</div>

	<div class="omni-nav-links">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=omni-reports-sales' ) ); ?>" class="button"><?php esc_html_e( 'Sales Report →', 'omni-reports' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=omni-reports-products' ) ); ?>" class="button"><?php esc_html_e( 'Products →', 'omni-reports' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=omni-reports-customers' ) ); ?>" class="button"><?php esc_html_e( 'Customers →', 'omni-reports' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=omni-reports-builder' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Report Builder →', 'omni-reports' ); ?></a>
	</div>
</div>
<script>
jQuery(function($){
	omniReports.loadDashboard = function(from, to){
		$.post(omniReports.ajaxUrl, {action:'omni_get_dashboard', nonce:omniReports.nonce, date_from:from, date_to:to}, function(r){
			if(!r.success) return;
			var k = r.data.kpis;
			var fmt = omniReports.formatCurrency;
			$('#kpi-revenue').text(fmt(k.revenue));
			$('#kpi-net_revenue').text(fmt(k.net_revenue));
			$('#kpi-orders').text(parseInt(k.orders).toLocaleString());
			$('#kpi-avg_order_value').text(fmt(k.avg_order_value));
			$('#kpi-refunds').text(fmt(k.refunds));
			$('#kpi-tax').text(fmt(k.tax));
			omniReports.renderLineChart('omni-dashboard-chart', r.data.over_time, 'report_date', 'revenue', '<?php esc_js( __( 'Revenue', 'omni-reports' ) ); ?>');
		});
	};
	omniReports.onDateChange(omniReports.loadDashboard);
	omniReports.loadDashboard(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
