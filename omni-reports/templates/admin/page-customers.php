<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="customers">
	<h1><?php esc_html_e( 'Customers Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid">
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Unique Customers', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-unique">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'New Customers', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-new">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Returning Customers', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-returning">—</span></div>
	</div>

	<div class="omni-two-col">
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'New vs Returning', 'omni-reports' ); ?></h2>
			<canvas id="omni-customer-pie" height="200"></canvas>
		</div>
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Revenue by Country', 'omni-reports' ); ?></h2>
			<canvas id="omni-geo-bar" height="200"></canvas>
		</div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Top Customers by Lifetime Value', 'omni-reports' ); ?></h2>
		<div id="omni-customers-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_customers_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			var d=r.data, s=d.summary;
			$('#kpi-unique').text(parseInt(s.unique_customers).toLocaleString());
			$('#kpi-new').text(parseInt(s.new_customers).toLocaleString());
			$('#kpi-returning').text(parseInt(s.returning_customers).toLocaleString());
			omniReports.renderPieChart('omni-customer-pie',[
				{label:'New',value:s.new_customers},
				{label:'Returning',value:s.returning_customers}
			],'label','value');
			omniReports.renderBarChart('omni-geo-bar',d.geography,'country','revenue','Revenue');
			omniReports.renderTable('omni-customers-table-wrap',d.top_customers);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('customers');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
