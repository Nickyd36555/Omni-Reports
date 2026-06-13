<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="categories">
	<h1><?php esc_html_e( 'Categories Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Revenue by Category', 'omni-reports' ); ?></h2>
		<div class="omni-two-col">
			<canvas id="omni-cat-pie" height="200"></canvas>
			<canvas id="omni-cat-bar" height="200"></canvas>
		</div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Category Data', 'omni-reports' ); ?></h2>
		<div id="omni-cat-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_categories_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			omniReports.renderPieChart('omni-cat-pie',r.data,'category_name','revenue');
			omniReports.renderBarChart('omni-cat-bar',r.data,'category_name','revenue','Revenue');
			omniReports.renderTable('omni-cat-table-wrap',r.data);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('categories');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
