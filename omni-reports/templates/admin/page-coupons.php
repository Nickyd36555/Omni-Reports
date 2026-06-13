<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="coupons">
	<h1><?php esc_html_e( 'Coupons Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Discount by Coupon', 'omni-reports' ); ?></h2>
		<canvas id="omni-coupon-bar" height="80"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Coupon Data', 'omni-reports' ); ?></h2>
		<div id="omni-coupons-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_coupons_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			omniReports.renderBarChart('omni-coupon-bar',r.data,'coupon_code','discount_amount','Discount Amount');
			omniReports.renderTable('omni-coupons-table-wrap',r.data);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('coupons');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
