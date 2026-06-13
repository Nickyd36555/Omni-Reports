<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="products">
	<h1><?php esc_html_e( 'Products Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Top Products by Revenue', 'omni-reports' ); ?></h2>
		<canvas id="omni-products-chart" height="100"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Product Data', 'omni-reports' ); ?></h2>
		<div id="omni-products-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_products_report',nonce:omniReports.nonce,date_from:from,date_to:to,limit:20},function(r){
			if(!r.success)return;
			omniReports.renderBarChart('omni-products-chart',r.data,'product_name','revenue','Revenue');
			omniReports.renderTable('omni-products-table-wrap',r.data);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('products');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
