<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="tax">
	<h1><?php esc_html_e( 'Tax Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Tax Collected Over Time', 'omni-reports' ); ?></h2>
		<canvas id="omni-tax-line" height="80"></canvas>
	</div>

	<div class="omni-two-col">
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Tax by Rate', 'omni-reports' ); ?></h2>
			<canvas id="omni-tax-pie" height="200"></canvas>
		</div>
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Tax Rate Breakdown', 'omni-reports' ); ?></h2>
			<div id="omni-tax-rate-table"></div>
		</div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_tax_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			omniReports.renderLineChart('omni-tax-line',r.data.by_day,'report_date','tax_total','Tax');
			omniReports.renderPieChart('omni-tax-pie',r.data.by_rate,'rate_label','tax_amount');
			omniReports.renderTable('omni-tax-rate-table',r.data.by_rate);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('tax');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
