<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="revenue">
	<h1><?php esc_html_e( 'Revenue Trends', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Revenue', 'omni-reports' ); ?>
			<span class="omni-group-toggle">
				<button class="button omni-group active" data-group="day"><?php esc_html_e( 'Day', 'omni-reports' ); ?></button>
				<button class="button omni-group" data-group="week"><?php esc_html_e( 'Week', 'omni-reports' ); ?></button>
				<button class="button omni-group" data-group="month"><?php esc_html_e( 'Month', 'omni-reports' ); ?></button>
			</span>
		</h2>
		<canvas id="omni-revenue-chart" height="80"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Orders', 'omni-reports' ); ?></h2>
		<canvas id="omni-orders-chart" height="60"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Data Table', 'omni-reports' ); ?></h2>
		<div id="omni-revenue-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	var currentGroup='day';

	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_revenue_trends',nonce:omniReports.nonce,date_from:from,date_to:to,group:currentGroup},function(r){
			if(!r.success)return;
			omniReports.renderLineChart('omni-revenue-chart',r.data,'report_date','revenue','Gross Revenue');
			omniReports.renderBarChart('omni-orders-chart',r.data,'report_date','orders','Orders');
			omniReports.renderTable('omni-revenue-table-wrap',r.data);
		});
	}

	$('.omni-group').on('click',function(){
		$('.omni-group').removeClass('active');
		$(this).addClass('active');
		currentGroup=$(this).data('group');
		load(omniReports.currentFrom(),omniReports.currentTo());
	});

	$('#omni-export-csv').on('click',function(){
		omniReports.exportCsv('revenue',currentGroup);
	});

	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
