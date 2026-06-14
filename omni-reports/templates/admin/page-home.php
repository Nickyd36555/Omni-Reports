<?php
/**
 * Dashboard Home page — KPIs, charts, top lists.
 *
 * @package OmniReports
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="omni-page-inner">

	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- KPI Cards -->
	<div class="omni-kpi-grid" id="omni-home-kpis">
		<?php
		$kpis = [
			[ 'id' => 'revenue',      'label' => 'Total Revenue',    'icon' => 'dashicons-chart-line',  'format' => 'currency' ],
			[ 'id' => 'orders',       'label' => 'Orders',           'icon' => 'dashicons-list-view',   'format' => 'number' ],
			[ 'id' => 'aov',          'label' => 'Avg Order Value',  'icon' => 'dashicons-cart',        'format' => 'currency' ],
			[ 'id' => 'gross_profit', 'label' => 'Gross Profit',     'icon' => 'dashicons-chart-area',  'format' => 'currency' ],
			[ 'id' => 'refund_rate',  'label' => 'Refund Rate',      'icon' => 'dashicons-undo',        'format' => 'percent' ],
			[ 'id' => 'new_customers','label' => 'New Customers',    'icon' => 'dashicons-groups',      'format' => 'number' ],
		];
		foreach ( $kpis as $k ) :
		?>
		<div class="omni-kpi-card omni-skeleton-wrap" id="omni-kpi-<?php echo esc_attr( $k['id'] ); ?>">
			<div class="omni-kpi-icon"><span class="dashicons <?php echo esc_attr( $k['icon'] ); ?>"></span></div>
			<div class="omni-kpi-body">
				<div class="omni-kpi-label"><?php echo esc_html( $k['label'] ); ?></div>
				<div class="omni-kpi-value omni-skeleton">—</div>
				<div class="omni-kpi-change omni-skeleton-inline">&nbsp;</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Main chart + Quick Stats sidebar -->
	<div class="omni-home-main-row">
		<div class="omni-home-chart-wrap omni-card">
			<div class="omni-card-header">
				<span class="omni-card-title">Revenue &amp; Net Profit</span>
				<span class="omni-period-badge" id="omni-home-period-badge">Last 30 Days</span>
			</div>
			<div class="omni-home-chart-container">
				<canvas id="omni-home-main-chart" height="280"></canvas>
				<div class="omni-skeleton omni-skeleton-chart" id="omni-home-chart-skeleton"></div>
			</div>
		</div>

		<div class="omni-home-sidebar">
			<!-- Quick Stats -->
			<div class="omni-card omni-quick-stats-card">
				<div class="omni-card-header"><span class="omni-card-title">Quick Stats</span></div>
				<ul class="omni-quick-stats-list" id="omni-quick-stats">
					<li class="omni-qs-item">
						<span class="omni-qs-label">Conversion Rate</span>
						<span class="omni-qs-value omni-muted">—</span>
					</li>
					<li class="omni-qs-item">
						<span class="omni-qs-label">Top Category</span>
						<span class="omni-qs-value" id="omni-qs-top-cat"><span class="omni-skeleton omni-skeleton-text"></span></span>
					</li>
					<li class="omni-qs-item">
						<span class="omni-qs-label">Top Product</span>
						<span class="omni-qs-value" id="omni-qs-top-prod"><span class="omni-skeleton omni-skeleton-text"></span></span>
					</li>
				</ul>
			</div>

			<!-- Weekly Strip -->
			<div class="omni-card omni-weekly-card">
				<div class="omni-card-header"><span class="omni-card-title">Last 7 Days</span></div>
				<div id="omni-weekly-bars" class="omni-weekly-bars">
					<?php for ( $i = 0; $i < 7; $i++ ) : ?>
					<div class="omni-weekly-bar-wrap">
						<div class="omni-weekly-bar omni-skeleton" style="height:40px;"></div>
						<div class="omni-weekly-label omni-skeleton-text">&nbsp;</div>
					</div>
					<?php endfor; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Profit Breakdown -->
	<div class="omni-profit-breakdown-row omni-card" id="omni-profit-breakdown">
		<div class="omni-card-header"><span class="omni-card-title">Profit Breakdown</span></div>
		<div class="omni-profit-breakdown">
			<?php
			$pb = [
				[ 'id' => 'gross_sales',    'label' => 'Gross Sales' ],
				[ 'id' => 'product_cost',   'label' => 'Product Cost' ],
				[ 'id' => 'gross_profit2',  'label' => 'Gross Profit' ],
				[ 'id' => 'net_margin',     'label' => 'Net Margin %' ],
			];
			foreach ( $pb as $item ) :
			?>
			<div class="omni-profit-breakdown-item">
				<span class="omni-profit-breakdown-label"><?php echo esc_html( $item['label'] ); ?></span>
				<span class="omni-profit-breakdown-value omni-skeleton" id="omni-pb-<?php echo esc_attr( $item['id'] ); ?>">—</span>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Bottom two-column -->
	<div class="omni-home-bottom-row">
		<!-- Top Products donut + table -->
		<div class="omni-card omni-home-bottom-card">
			<div class="omni-card-header"><span class="omni-card-title">Top Products by Revenue</span></div>
			<div class="omni-home-donut-wrap">
				<canvas id="omni-home-products-donut" width="200" height="200"></canvas>
				<div class="omni-skeleton omni-skeleton-donut" id="omni-products-donut-skeleton"></div>
			</div>
			<div id="omni-top-products-table" class="omni-home-mini-table">
				<div class="omni-skeleton omni-skeleton-table"></div>
			</div>
		</div>

		<!-- Order status bar + top customers -->
		<div class="omni-card omni-home-bottom-card">
			<div class="omni-card-header"><span class="omni-card-title">Order Status &amp; Top Customers</span></div>
			<div class="omni-home-status-chart-wrap">
				<canvas id="omni-home-status-chart" height="160"></canvas>
				<div class="omni-skeleton omni-skeleton-chart omni-skeleton-short" id="omni-status-chart-skeleton"></div>
			</div>
			<div id="omni-top-customers-table" class="omni-home-mini-table">
				<div class="omni-skeleton omni-skeleton-table"></div>
			</div>
		</div>
	</div>

</div>

<script>
(function($) {
	var mainChart = null, donutChart = null, statusChart = null;

	function fmt(val, type) {
		if (type === 'currency') return omniReports.formatCurrency(val);
		if (type === 'percent')  return parseFloat(val).toFixed(2) + '%';
		return parseInt(val).toLocaleString();
	}

	function pctChange(curr, prev) {
		if (!prev || parseFloat(prev) === 0) return null;
		return ((parseFloat(curr) - parseFloat(prev)) / Math.abs(parseFloat(prev))) * 100;
	}

	function renderKpi(id, value, compValue, format) {
		var $card = $('#omni-kpi-' + id);
		$card.find('.omni-kpi-value').removeClass('omni-skeleton').text(fmt(value, format));
		var pct = pctChange(value, compValue);
		var $change = $card.find('.omni-kpi-change').removeClass('omni-skeleton-inline');
		if (pct === null) { $change.text(''); return; }
		var up = pct >= 0;
		var arrow = up ? '↑' : '↓';
		var cls = up ? 'omni-change-up' : 'omni-change-down';
		$change.html('<span class="' + cls + '">' + arrow + ' ' + Math.abs(pct).toFixed(1) + '% vs prior period</span>');
		$card.addClass(up ? 'omni-kpi-positive' : 'omni-kpi-negative');
	}

	function renderMainChart(data) {
		$('#omni-home-chart-skeleton').hide();
		var ctx = document.getElementById('omni-home-main-chart').getContext('2d');
		if (mainChart) mainChart.destroy();
		var labels = data.labels || [];
		var ds = data.datasets || [];
		var barDs   = ds.find(function(d){ return d.type === 'bar'; }) || {};
		var lineDs  = ds.find(function(d){ return d.type === 'line' && !d.dashed; }) || {};
		var priorDs = ds.find(function(d){ return d.dashed; }) || {};

		mainChart = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [
					{
						label: barDs.label || 'Revenue',
						data: barDs.data || [],
						backgroundColor: 'rgba(0,212,170,0.35)',
						borderColor: '#00D4AA',
						borderWidth: 1,
						order: 2,
						yAxisID: 'y',
					},
					{
						label: lineDs.label || 'Net Profit',
						data: lineDs.data || [],
						type: 'line',
						borderColor: '#0099FF',
						backgroundColor: 'transparent',
						pointRadius: 3,
						tension: 0.3,
						order: 1,
						yAxisID: 'y',
					},
					{
						label: priorDs.label || 'Prior Revenue',
						data: priorDs.data || [],
						type: 'line',
						borderColor: 'rgba(113,128,150,0.55)',
						borderDash: [6, 4],
						backgroundColor: 'transparent',
						pointRadius: 0,
						tension: 0.3,
						order: 3,
						yAxisID: 'y',
					}
				]
			},
			options: {
				responsive: true,
				interaction: { mode: 'index', intersect: false },
				plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
				scales: {
					y: { beginAtZero: true, ticks: { font: { size: 11 } }, grid: { color: '#E2E8F0' } },
					x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } }
				}
			}
		});
	}

	function renderWeekly(data) {
		if (!data || !data.length) return;
		var max = Math.max.apply(null, data.map(function(d){ return d.revenue || 0; }));
		var $wrap = $('#omni-weekly-bars').empty();
		data.forEach(function(d) {
			var pct = max > 0 ? Math.round((d.revenue / max) * 80) + 10 : 10;
			var label = d.date ? d.date.slice(5) : '';
			$wrap.append(
				'<div class="omni-weekly-bar-wrap" title="' + omniReports.formatCurrency(d.revenue) + '">' +
				'<div class="omni-weekly-bar" style="height:' + pct + 'px;"></div>' +
				'<div class="omni-weekly-label">' + label + '</div>' +
				'</div>'
			);
		});
	}

	function renderTopProductsDonut(products) {
		$('#omni-products-donut-skeleton').hide();
		var top = products.slice(0, 8);
		var ctx = document.getElementById('omni-home-products-donut').getContext('2d');
		if (donutChart) donutChart.destroy();
		var colors = ['#00D4AA','#0099FF','#9F7AEA','#F6AD55','#FC8181','#68D391','#63B3ED','#F687B3'];
		donutChart = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: top.map(function(p){ return p.product_name || ''; }),
				datasets: [{ data: top.map(function(p){ return parseFloat(p.revenue)||0; }), backgroundColor: colors, borderWidth: 2 }]
			},
			options: { responsive: false, plugins: { legend: { display: false } }, cutout: '65%' }
		});
		// Table
		var $t = $('#omni-top-products-table').empty();
		var html = '<table class="omni-data-table omni-mini-table"><thead><tr><th>Product</th><th>Revenue</th><th>Qty</th></tr></thead><tbody>';
		top.forEach(function(p) {
			html += '<tr><td>' + ($('<div>').text(p.product_name || '').html()) + '</td>' +
			        '<td>' + omniReports.formatCurrency(p.revenue) + '</td>' +
			        '<td>' + parseInt(p.qty_sold || 0).toLocaleString() + '</td></tr>';
		});
		html += '</tbody></table>';
		$t.html(html);
	}

	function renderOrderStatus(statusData) {
		$('#omni-status-chart-skeleton').hide();
		var ctx = document.getElementById('omni-home-status-chart').getContext('2d');
		if (statusChart) statusChart.destroy();
		var labels = statusData.map(function(s){ return s.status || ''; });
		var counts = statusData.map(function(s){ return parseInt(s.order_count)||0; });
		statusChart = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{ label: 'Orders', data: counts, backgroundColor: '#0099FF', borderRadius: 4 }]
			},
			options: {
				responsive: true,
				indexAxis: 'y',
				plugins: { legend: { display: false } },
				scales: { x: { beginAtZero: true, ticks: { font: { size: 10 } } }, y: { ticks: { font: { size: 10 } } } }
			}
		});
	}

	function renderTopCustomers(customers) {
		var $t = $('#omni-top-customers-table').empty();
		if (!customers || !customers.length) { $t.html('<div class="omni-empty-state">No customers in period.</div>'); return; }
		var top = customers.slice(0, 8);
		var html = '<table class="omni-data-table omni-mini-table"><thead><tr><th>Customer</th><th>LTV</th><th>Orders</th></tr></thead><tbody>';
		top.forEach(function(c) {
			var name = (c.first_name || '') + ' ' + (c.last_name || '');
			html += '<tr><td>' + ($('<div>').text(name.trim() || c.email || '').html()) + '</td>' +
			        '<td>' + omniReports.formatCurrency(c.ltv) + '</td>' +
			        '<td>' + parseInt(c.order_count || 0).toLocaleString() + '</td></tr>';
		});
		html += '</tbody></table>';
		$t.html(html);
	}

	function loadDashboard() {
		// Show all skeletons
		$('.omni-kpi-value').addClass('omni-skeleton').text('—');
		$('.omni-kpi-change').addClass('omni-skeleton-inline').text('');
		$('#omni-home-chart-skeleton').show();
		$('#omni-products-donut-skeleton').show();
		$('#omni-status-chart-skeleton').show();

		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_dashboard_home',
			nonce:  omniReports.nonce,
			date_from: omniReports.currentFrom ? omniReports.currentFrom() : '',
			date_to:   omniReports.currentTo   ? omniReports.currentTo()   : '',
		}, function(res) {
			if (!res.success) return;
			var d = res.data;
			var s = d.summary || {};
			var c = d.comp_summary || {};

			renderKpi('revenue',       s.revenue          || 0, c.revenue          || 0, 'currency');
			renderKpi('orders',        s.orders           || 0, c.orders           || 0, 'number');
			renderKpi('aov',           s.avg_order_value  || 0, c.avg_order_value  || 0, 'currency');
			renderKpi('gross_profit',  s.gross_profit     || 0, c.gross_profit     || 0, 'currency');
			renderKpi('refund_rate',   s.refund_rate      || 0, c.refund_rate      || 0, 'percent');
			renderKpi('new_customers', s.new_customers    || 0, c.new_customers    || 0, 'number');

			if (d.revenue_chart) renderMainChart(d.revenue_chart);
			if (d.weekly)        renderWeekly(d.weekly);

			// Quick stats
			$('#omni-qs-top-cat').text(d.quick_stats && d.quick_stats.top_category ? d.quick_stats.top_category : '—');
			$('#omni-qs-top-prod').text(d.quick_stats && d.quick_stats.top_product ? d.quick_stats.top_product : '—');

			// Profit breakdown
			$('#omni-pb-gross_sales').removeClass('omni-skeleton').text(omniReports.formatCurrency(s.revenue || 0));
			$('#omni-pb-product_cost').removeClass('omni-skeleton').text(omniReports.formatCurrency((parseFloat(s.revenue||0) - parseFloat(s.gross_profit||0)).toFixed(2)));
			$('#omni-pb-gross_profit2').removeClass('omni-skeleton').text(omniReports.formatCurrency(s.gross_profit || 0));
			var netMargin = s.revenue && parseFloat(s.revenue) > 0
				? ((parseFloat(s.gross_profit||0) / parseFloat(s.revenue)) * 100).toFixed(1) + '%'
				: '0%';
			$('#omni-pb-net_margin').removeClass('omni-skeleton').text(netMargin);

			if (d.top_products)           renderTopProductsDonut(d.top_products);
			if (d.order_status_breakdown) renderOrderStatus(d.order_status_breakdown);
			if (d.top_customers)          renderTopCustomers(d.top_customers);
		});
	}

	$(document).ready(function() {
		loadDashboard();
		if (omniReports.onDateChange) {
			omniReports.onDateChange(loadDashboard);
		}
	});
})(jQuery);
</script>
