<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="sales">
	<h1><?php esc_html_e( 'Sales Overview', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid">
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Gross Revenue', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-revenue">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Net Revenue', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-net_revenue">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Orders', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-orders">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Avg Order Value', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-avg">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Refunds', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-refunds">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Tax', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-tax">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Shipping', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-shipping">—</span></div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Sales Over Time', 'omni-reports' ); ?>
			<span class="omni-group-toggle">
				<button class="button omni-group active" data-group="day"><?php esc_html_e( 'Day', 'omni-reports' ); ?></button>
				<button class="button omni-group" data-group="week"><?php esc_html_e( 'Week', 'omni-reports' ); ?></button>
				<button class="button omni-group" data-group="month"><?php esc_html_e( 'Month', 'omni-reports' ); ?></button>
			</span>
		</h2>
		<canvas id="omni-sales-chart" height="80"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Daily Data Table', 'omni-reports' ); ?></h2>
		<div id="omni-sales-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	var currentGroup = 'day';

	function load(from, to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_sales_overview',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			var k=r.data, f=omniReports.formatCurrency;
			$('#kpi-revenue').text(f(k.revenue));
			$('#kpi-net_revenue').text(f(k.net_revenue));
			$('#kpi-orders').text(parseInt(k.orders).toLocaleString());
			$('#kpi-avg').text(f(k.avg_order_value));
			$('#kpi-refunds').text(f(k.refunds));
			$('#kpi-tax').text(f(k.tax));
			$('#kpi-shipping').text(f(k.shipping));
		});
		$.post(omniReports.ajaxUrl,{action:'omni_get_sales_over_time',nonce:omniReports.nonce,date_from:from,date_to:to,group:currentGroup},function(r){
			if(!r.success)return;
			omniReports.renderLineChart('omni-sales-chart',r.data,'report_date','revenue','Revenue');
			omniReports.renderTable('omni-sales-table-wrap',r.data);
		});
	}

	$('.omni-group').on('click',function(){
		$('.omni-group').removeClass('active');
		$(this).addClass('active');
		currentGroup=$(this).data('group');
		load(omniReports.currentFrom(),omniReports.currentTo());
	});

	$('#omni-export-csv').on('click',function(){
		omniReports.exportCsv('sales',currentGroup);
	});

	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
