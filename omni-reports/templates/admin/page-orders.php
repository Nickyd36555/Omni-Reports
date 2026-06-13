<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="orders">
	<h1><?php esc_html_e( 'Orders Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-two-col">
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Orders by Status', 'omni-reports' ); ?></h2>
			<canvas id="omni-status-pie" height="200"></canvas>
		</div>
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Orders Over Time', 'omni-reports' ); ?></h2>
			<canvas id="omni-orders-line" height="200"></canvas>
		</div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Status Breakdown', 'omni-reports' ); ?></h2>
		<div id="omni-status-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_orders_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			omniReports.renderPieChart('omni-status-pie',r.data.by_status,'status','order_count');
			omniReports.renderLineChart('omni-orders-line',r.data.over_time,'report_date','order_count','Orders');
			omniReports.renderTable('omni-status-table-wrap',r.data.by_status);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('orders');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
