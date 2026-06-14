<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="shipping">
	<h1><?php esc_html_e( 'Shipping Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- KPI Cards -->
	<div class="omni-kpi-grid" id="omni-shipping-kpis">
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Shipping Revenue', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-shipping-total">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Orders with Shipping', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-shipping-orders">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Avg Shipping per Order', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-shipping-avg">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Top Shipping Method', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-shipping-top" style="font-size:14px;word-break:break-word">—</span>
		</div>
	</div>

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
	var fmt = omniReports.formatCurrency;

	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_shipping_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;

			// KPIs from by_method
			var byMethod = r.data.by_method || [];
			var totalShipping = 0, totalOrders = 0, topMethod = '—';
			byMethod.forEach(function(d){
				totalShipping += parseFloat(d.shipping_total) || 0;
				totalOrders   += parseInt(d.orders) || 0;
			});
			if(byMethod.length) topMethod = byMethod[0].shipping_method || '—';

			$('#kpi-shipping-total').text(fmt(totalShipping));
			$('#kpi-shipping-orders').text(totalOrders.toLocaleString());
			$('#kpi-shipping-avg').text(totalOrders ? fmt(totalShipping / totalOrders) : fmt(0));
			$('#kpi-shipping-top').text(topMethod);

			omniReports.renderLineChart('omni-shipping-line',r.data.over_time,'report_date','shipping_total','Shipping');
			omniReports.renderPieChart('omni-shipping-pie',r.data.by_method,'shipping_method','shipping_total');

			// Custom table with formatted numbers
			if(!byMethod.length){
				$('#omni-shipping-table-wrap').html('<p class="omni-empty-state">No shipping data for this period.</p>');
				return;
			}
			var html = '<table class="omni-data-table"><thead><tr>' +
				'<th>Shipping Method</th>' +
				'<th class="num">Shipping Revenue</th>' +
				'<th class="num">Orders</th>' +
				'</tr></thead><tbody>';
			byMethod.forEach(function(d){
				html += '<tr>' +
					'<td>' + $('<span>').text(d.shipping_method || '—').html() + '</td>' +
					'<td class="num">' + fmt(parseFloat(d.shipping_total) || 0) + '</td>' +
					'<td class="num">' + (parseInt(d.orders) || 0).toLocaleString() + '</td>' +
					'</tr>';
			});
			html += '</tbody></table>';
			$('#omni-shipping-table-wrap').html(html);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('shipping');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
