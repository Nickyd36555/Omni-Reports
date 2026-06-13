<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="shipping">
	<h1><?php esc_html_e( 'Shipping Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Shipping Revenue Over Time', 'omni-reports' ); ?></h2>
		<canvas id="omni-shipping-line" height="80"></canvas>
	</div>

	<div class="omni-two-col">
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'By Shipping Method', 'omni-reports' ); ?></h2>
			<canvas id="omni-shipping-pie" height="200"></canvas>
		</div>
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Method Breakdown', 'omni-reports' ); ?></h2>
			<div id="omni-shipping-table-wrap"></div>
		</div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_shipping_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			omniReports.renderLineChart('omni-shipping-line',r.data.over_time,'report_date','shipping_total','Shipping');
			omniReports.renderPieChart('omni-shipping-pie',r.data.by_method,'shipping_method','shipping_total');
			omniReports.renderTable('omni-shipping-table-wrap',r.data.by_method);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('shipping');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
