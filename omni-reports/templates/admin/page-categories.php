<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="categories">
	<h1><?php esc_html_e( 'Categories Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- KPI Cards -->
	<div class="omni-kpi-grid" id="omni-cat-kpis">
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Revenue', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-cat-revenue">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Qty Sold', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-cat-qty">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Total Orders', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-cat-orders">—</span>
		</div>
		<div class="omni-kpi-card">
			<span class="omni-kpi-label"><?php esc_html_e( 'Top Category', 'omni-reports' ); ?></span>
			<span class="omni-kpi-value" id="kpi-cat-top" style="font-size:14px;word-break:break-word">—</span>
		</div>
	</div>

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
	var fmt = omniReports.formatCurrency;

	function load(from,to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_categories_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success)return;
			var data = r.data || [];

			// KPIs
			var totalRevenue = 0, totalQty = 0, totalOrders = 0, topCat = '—';
			data.forEach(function(d){
				totalRevenue += parseFloat(d.revenue) || 0;
				totalQty     += parseInt(d.qty_sold) || 0;
				totalOrders  += parseInt(d.orders) || 0;
			});
			if(data.length) topCat = data[0].category_name || '—';
			$('#kpi-cat-revenue').text(fmt(totalRevenue));
			$('#kpi-cat-qty').text(totalQty.toLocaleString());
			$('#kpi-cat-orders').text(totalOrders.toLocaleString());
			$('#kpi-cat-top').text(topCat);

			omniReports.renderPieChart('omni-cat-pie',data,'category_name','revenue');
			omniReports.renderBarChart('omni-cat-bar',data,'category_name','revenue','Revenue');

			// Custom table with formatted numbers
			if(!data.length){
				$('#omni-cat-table-wrap').html('<p class="omni-empty-state">No category data for this period.</p>');
				return;
			}
			var html = '<table class="omni-data-table"><thead><tr>' +
				'<th>Category</th>' +
				'<th class="num">Revenue</th>' +
				'<th class="num">Qty Sold</th>' +
				'<th class="num">Orders</th>' +
				'</tr></thead><tbody>';
			data.forEach(function(d){
				html += '<tr>' +
					'<td>' + $('<span>').text(d.category_name || '—').html() + '</td>' +
					'<td class="num">' + fmt(parseFloat(d.revenue) || 0) + '</td>' +
					'<td class="num">' + (parseInt(d.qty_sold) || 0).toLocaleString() + '</td>' +
					'<td class="num">' + (parseInt(d.orders) || 0).toLocaleString() + '</td>' +
					'</tr>';
			});
			html += '</tbody></table>';
			$('#omni-cat-table-wrap').html(html);
		});
	}
	$('#omni-export-csv').on('click',function(){omniReports.exportCsv('categories');});
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(),omniReports.currentTo());
});
</script>
