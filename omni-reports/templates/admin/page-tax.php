<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="tax">
	<h1><?php esc_html_e( 'Tax Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- KPI Cards -->
	<div class="omni-kpi-grid" id="omni-tax-kpis">
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Tax Collected', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-tax-total">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Orders with Tax', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-tax-orders">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Avg Tax per Order', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-tax-avg">—</span>
		</div>
	</div>

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
	var fmt = omniReports.formatCurrency;

	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_tax_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;

			// KPIs from by_day
			var byDay = r.data.by_day || [];
			var totalTax = 0, totalOrders = 0;
			byDay.forEach(function(d){
				totalTax    += parseFloat(d.tax_total) || 0;
				totalOrders += parseInt(d.orders) || 0;
			});
			$('#kpi-tax-total').text(fmt(totalTax));
			$('#kpi-tax-orders').text(totalOrders.toLocaleString());
			$('#kpi-tax-avg').text(totalOrders ? fmt(totalTax / totalOrders) : fmt(0));

			omniReports.renderLineChart('omni-tax-line',r.data.by_day,'report_date','tax_total','Tax');
			omniReports.renderPieChart('omni-tax-pie',r.data.by_rate,'rate_label','tax_amount');

			// Custom table with formatted numbers
			var rates = r.data.by_rate || [];
			if(!rates.length){
				$('#omni-tax-rate-table').html('<p class="omni-empty-state">No tax rate data for this period.</p>');
				return;
			}
			var html = '<table class="omni-data-table"><thead><tr>' +
				'<th>Rate Name</th>' +
				'<th class="num">Tax Collected</th>' +
				'<th class="num">Orders</th>' +
				'</tr></thead><tbody>';
			rates.forEach(function(d){
				html += '<tr>' +
					'<td>' + $('<span>').text(d.rate_label || '—').html() + '</td>' +
					'<td class="num">' + fmt(parseFloat(d.tax_amount) || 0) + '</td>' +
					'<td class="num">' + (parseInt(d.orders) || 0).toLocaleString() + '</td>' +
					'</tr>';
			});
			html += '</tbody></table>';
			$('#omni-tax-rate-table').html(html);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('tax');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
