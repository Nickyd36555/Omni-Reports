<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap omni-dash-v2" data-report="home">

	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- Row 1: Total Sales hero + payment methods -->
	<div class="omni-dash2-top">

		<!-- Total Sales hero -->
		<div class="omni-chart-card omni-dash2-hero">
			<div class="omni-dash2-hero-label"><?php esc_html_e( 'Total Sales', 'omni-reports' ); ?></div>
			<div class="omni-dash2-hero-value" id="dash-total-sales">—</div>
			<div class="omni-dash2-hero-sub">
				<span id="dash-total-orders">—</span> orders &nbsp;·&nbsp; avg <span id="dash-avg-order">—</span>
			</div>
		</div>

		<!-- Sales by payment method -->
		<div class="omni-chart-card omni-dash2-payment">
			<h2><?php esc_html_e( 'Sales by Payment Method', 'omni-reports' ); ?></h2>
			<div class="omni-dash2-payment-inner">
				<div class="omni-dash2-payment-chart-wrap">
					<canvas id="dash-payment-donut" width="160" height="160"></canvas>
				</div>
				<div id="dash-payment-list" class="omni-dash2-payment-list">
					<p class="omni-loading"><span class="omni-spinner"></span></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Row 2: Products sold by product -->
	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Products Sold', 'omni-reports' ); ?></h2>
		<div class="omni-dash2-products-inner">
			<div style="position:relative;height:240px;flex:1;min-width:260px;">
				<canvas id="dash-products-bar"></canvas>
			</div>
			<div id="dash-products-table" class="omni-dash2-products-table"></div>
		</div>
	</div>

	<!-- Row 3: Customer list -->
	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Customers', 'omni-reports' ); ?></h2>
		<div class="omni-dash2-cust-header">
			<div id="dash-cust-kpis" class="omni-dash2-cust-kpis">
				<div class="omni-dash2-cust-kpi"><span class="omni-kpi-label">New</span><span class="omni-kpi-value" id="dash-cust-new">—</span></div>
				<div class="omni-dash2-cust-kpi"><span class="omni-kpi-label">Returning</span><span class="omni-kpi-value" id="dash-cust-ret">—</span></div>
				<div class="omni-dash2-cust-kpi"><span class="omni-kpi-label">Unique</span><span class="omni-kpi-value" id="dash-cust-uniq">—</span></div>
			</div>
		</div>
		<div id="dash-customers-wrap">
			<p class="omni-loading"><span class="omni-spinner"></span><?php esc_html_e( 'Loading…', 'omni-reports' ); ?></p>
		</div>
	</div>

</div>

<style>
.omni-dash2-top {
	display: grid;
	grid-template-columns: 220px 1fr;
	gap: 14px;
	margin-bottom: 14px;
}
@media (max-width: 860px) { .omni-dash2-top { grid-template-columns: 1fr; } }

.omni-dash2-hero {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: flex-start;
	gap: 6px;
	padding: 24px 20px !important;
}
.omni-dash2-hero-label {
	font-size: 11px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .07em;
	color: var(--omni-muted);
}
.omni-dash2-hero-value {
	font-size: 38px;
	font-weight: 800;
	color: var(--omni-text);
	line-height: 1.1;
}
.omni-dash2-hero-sub { font-size: 12px; color: var(--omni-muted); }

.omni-dash2-payment h2 { margin-bottom: 10px; }
.omni-dash2-payment-inner { display: flex; align-items: flex-start; gap: 16px; }
.omni-dash2-payment-chart-wrap { flex-shrink: 0; }
.omni-dash2-payment-list { flex: 1; display: flex; flex-direction: column; gap: 8px; }

.omni-pay-row { display: flex; align-items: center; gap: 8px; font-size: 12px; }
.omni-pay-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.omni-pay-label { flex: 1; color: var(--omni-text); font-weight: 500; }
.omni-pay-amount { font-weight: 700; color: var(--omni-text); }
.omni-pay-count { color: var(--omni-muted); font-size: 11px; margin-left: 2px; }
.omni-pay-bar-wrap { width: 70px; background: var(--omni-border); border-radius: 4px; height: 5px; flex-shrink: 0; }
.omni-pay-bar { height: 5px; border-radius: 4px; }

.omni-dash2-products-inner { display: flex; gap: 20px; align-items: flex-start; }
.omni-dash2-products-table { flex: 1; min-width: 0; overflow-x: auto; }

.omni-dash2-cust-header { margin-bottom: 14px; }
.omni-dash2-cust-kpis { display: flex; gap: 24px; flex-wrap: wrap; }
.omni-dash2-cust-kpi { display: flex; flex-direction: column; gap: 2px; }
.omni-dash2-cust-kpi .omni-kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--omni-muted); }
.omni-dash2-cust-kpi .omni-kpi-value { font-size: 22px; font-weight: 800; color: var(--omni-text); }

.omni-badge-new {
	display: inline-block; padding: 2px 8px; border-radius: 20px;
	font-size: 10px; font-weight: 600;
	background: rgba(0,212,170,.12); color: #00A389; border: 1px solid rgba(0,212,170,.25);
}
.omni-badge-returning {
	display: inline-block; padding: 2px 8px; border-radius: 20px;
	font-size: 10px; font-weight: 600;
	background: rgba(99,102,241,.1); color: #4F46E5; border: 1px solid rgba(99,102,241,.2);
}
</style>

<script>
(function($){
	var fmt = omniReports.formatCurrency;
	var PAL = ['#6366F1','#00D4AA','#F59E0B','#EF4444','#10B981','#3B82F6','#EC4899','#8B5CF6','#F97316','#06B6D4'];
	var payChart = null, prodChart = null;

	function load(from, to) {
		loadSalesOverview(from, to);
		loadPaymentMethods(from, to);
		loadProducts(from, to);
		loadCustomers(from, to);
	}

	function loadSalesOverview(from, to) {
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_sales_overview', nonce: omniReports.nonce,
			date_from: from, date_to: to,
		}, function(r) {
			if (!r.success) return;
			var d = r.data || {};
			$('#dash-total-sales').text(fmt(d.revenue || 0));
			$('#dash-total-orders').text(parseInt(d.orders || 0).toLocaleString());
			$('#dash-avg-order').text(fmt(d.avg_order_value || 0));
		});
	}

	function loadPaymentMethods(from, to) {
		$('#dash-payment-list').html('<p class="omni-loading"><span class="omni-spinner"></span></p>');
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_payment_methods_report', nonce: omniReports.nonce,
			date_from: from, date_to: to,
		}, function(r) {
			if (!r.success) return;
			var rows = r.data || [];
			if (!rows.length) {
				$('#dash-payment-list').html('<p class="omni-empty-state" style="padding:16px 0">No payment data.</p>');
				return;
			}
			var total = rows.reduce(function(s, d){ return s + (parseFloat(d.total_sales)||0); }, 0);

			if (payChart) { payChart.destroy(); payChart = null; }
			var ctx = document.getElementById('dash-payment-donut');
			if (ctx) {
				payChart = new Chart(ctx, {
					type: 'doughnut',
					data: {
						labels: rows.map(function(d){ return d.payment_method_title || d.payment_method || 'Unknown'; }),
						datasets: [{ data: rows.map(function(d){ return parseFloat(d.total_sales)||0; }), backgroundColor: PAL, borderWidth: 2, borderColor: '#fff' }]
					},
					options: {
						responsive: false, cutout: '68%',
						plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ return ' ' + fmt(c.parsed); } } } }
					}
				});
			}

			var html = '';
			rows.forEach(function(d, i) {
				var amt   = parseFloat(d.total_sales) || 0;
				var pct   = total > 0 ? (amt / total * 100) : 0;
				var title = d.payment_method_title || d.payment_method || 'Unknown';
				html += '<div class="omni-pay-row">' +
					'<span class="omni-pay-dot" style="background:' + PAL[i % PAL.length] + '"></span>' +
					'<span class="omni-pay-label">' + $('<span>').text(title).html() + '</span>' +
					'<div class="omni-pay-bar-wrap"><div class="omni-pay-bar" style="width:' + pct.toFixed(1) + '%;background:' + PAL[i % PAL.length] + '"></div></div>' +
					'<span class="omni-pay-amount">' + fmt(amt) + '</span>' +
					'<span class="omni-pay-count">(' + parseInt(d.order_count || 0).toLocaleString() + ')</span>' +
					'</div>';
			});
			$('#dash-payment-list').html(html);
		});
	}

	function loadProducts(from, to) {
		$('#dash-products-table').html('<p class="omni-loading"><span class="omni-spinner"></span></p>');
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_products_report', nonce: omniReports.nonce,
			date_from: from, date_to: to, limit: 50,
		}, function(r) {
			if (!r.success) return;
			var rows = r.data || [];
			if (!rows.length) {
				$('#dash-products-table').html('<p class="omni-empty-state" style="padding:20px 0">No products sold.</p>');
				return;
			}

			var top = rows.slice(0, 12);
			if (prodChart) { prodChart.destroy(); prodChart = null; }
			var ctx2 = document.getElementById('dash-products-bar');
			if (ctx2) {
				prodChart = new Chart(ctx2, {
					type: 'bar',
					data: {
						labels: top.map(function(d){ return d.product_name || ''; }),
						datasets: [{ label: 'Qty Sold', data: top.map(function(d){ return parseInt(d.qty_sold)||0; }), backgroundColor: '#6366F1', borderRadius: 4 }]
					},
					options: {
						responsive: true, maintainAspectRatio: false,
						plugins: { legend: { display: false } },
						scales: {
							y: { beginAtZero: true, ticks: { font:{size:10} } },
							x: { ticks: { font:{size:9}, maxRotation: 45, maxTicksLimit: 12 }, grid: { display: false } }
						}
					}
				});
			}

			var html = '<table class="omni-data-table"><thead><tr>' +
				'<th>Product</th><th class="num">Qty Sold</th><th class="num">Revenue</th><th class="num">Orders</th>' +
				'</tr></thead><tbody>';
			rows.forEach(function(d) {
				html += '<tr>' +
					'<td><strong>' + $('<span>').text(d.product_name || '—').html() + '</strong></td>' +
					'<td class="num">' + (parseInt(d.qty_sold)||0).toLocaleString() + '</td>' +
					'<td class="num">' + fmt(parseFloat(d.revenue)||0) + '</td>' +
					'<td class="num">' + (parseInt(d.orders)||0).toLocaleString() + '</td>' +
					'</tr>';
			});
			html += '</tbody></table>';
			$('#dash-products-table').html(html);
		});
	}

	function loadCustomers(from, to) {
		$('#dash-customers-wrap').html('<p class="omni-loading"><span class="omni-spinner"></span>Loading…</p>');
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_customers_report', nonce: omniReports.nonce,
			date_from: from, date_to: to,
		}, function(r) {
			if (!r.success) return;
			var d = r.data || {};
			var s = d.summary || {};
			$('#dash-cust-new').text(parseInt(s.new_customers || 0).toLocaleString());
			$('#dash-cust-ret').text(parseInt(s.returning_customers || 0).toLocaleString());
			$('#dash-cust-uniq').text(parseInt(s.unique_customers || 0).toLocaleString());

			var customers = d.top_customers || [];
			if (!customers.length) {
				$('#dash-customers-wrap').html('<p class="omni-empty-state">No customer data for this period.</p>');
				return;
			}

			var html = '<table class="omni-data-table"><thead><tr>' +
				'<th>Name</th><th>Email</th><th>Type</th><th class="num">Orders</th><th class="num">Total Spend</th>' +
				'</tr></thead><tbody>';
			customers.forEach(function(c) {
				var name = (((c.first_name || '') + ' ' + (c.last_name || '')).trim()) || 'Guest';
				var isReturning = parseInt(c.order_count || 0) > 1;
				var badge = isReturning
					? '<span class="omni-badge-returning">Returning</span>'
					: '<span class="omni-badge-new">New</span>';
				html += '<tr>' +
					'<td><strong>' + $('<span>').text(name).html() + '</strong></td>' +
					'<td style="color:var(--omni-muted);font-size:12px">' + $('<span>').text(c.email || '').html() + '</td>' +
					'<td>' + badge + '</td>' +
					'<td class="num">' + parseInt(c.order_count || 0).toLocaleString() + '</td>' +
					'<td class="num"><strong>' + fmt(parseFloat(c.ltv) || 0) + '</strong></td>' +
					'</tr>';
			});
			html += '</tbody></table>';
			$('#dash-customers-wrap').html(html);
		});
	}

	omniReports.onDateChange(load);
	$(function(){ load(omniReports.currentFrom(), omniReports.currentTo()); });
})(jQuery);
</script>
