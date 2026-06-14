<?php
/**
 * Dashboard Home page — Metorik-style layout.
 *
 * @package OmniReports
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="omni-page-inner">
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<!-- Main 2-col: chart + in-this-period -->
	<div class="omni-dash-main-row">
		<!-- Chart card -->
		<div class="omni-card omni-dash-chart-card">
			<div class="omni-dash-chart-header">
				<span class="omni-card-title">Revenue Overview</span>
				<div class="omni-dash-chart-controls">
					<div class="omni-group-toggle">
						<button class="omni-group-btn active" data-group="day">Day</button>
						<button class="omni-group-btn" data-group="week">Week</button>
						<button class="omni-group-btn" data-group="month">Month</button>
					</div>
				</div>
			</div>
			<div class="omni-dash-chart-container">
				<canvas id="omni-dash-main-chart"></canvas>
			</div>
		</div>

		<!-- In This Period sidebar -->
		<div class="omni-card omni-dash-period-card">
			<div class="omni-card-header">
				<span class="omni-period-label">IN THIS PERIOD</span>
			</div>
			<div class="omni-period-grid" id="omni-period-grid">
				<!-- populated by JS -->
			</div>
		</div>
	</div>

	<!-- Bottom strip 1: 6 financial KPIs -->
	<div class="omni-dash-strip" id="omni-dash-strip-1">
		<!-- populated by JS -->
	</div>

	<!-- Bottom strip 2: weekly stats -->
	<div class="omni-dash-weekly-strip" id="omni-dash-weekly-strip">
		<!-- populated by JS -->
	</div>

	<!-- New vs Returning placeholder -->
	<div class="omni-card omni-dash-bottom-row" style="padding:20px;margin-top:14px;">
		<div class="omni-card-header" style="padding:0 0 12px;"><span class="omni-card-title">New vs. Returning Customers</span></div>
		<div class="omni-home-bottom-row">
			<div class="omni-home-bottom-card" style="padding:0;">
				<canvas id="omni-home-products-donut" width="150" height="150"></canvas>
				<div id="omni-top-products-table" class="omni-home-mini-table" style="margin-top:8px;"></div>
			</div>
			<div class="omni-home-bottom-card" style="padding:0;">
				<canvas id="omni-home-status-chart" height="130"></canvas>
				<div id="omni-top-customers-table" class="omni-home-mini-table" style="margin-top:8px;"></div>
			</div>
		</div>
	</div>
</div>

<script>
(function($){
	var mainChart = null, donutChart = null, statusChart = null;
	var f = omniReports.formatCurrency;
	var currentGroup = 'day';

	function pct(curr, prev) {
		curr = parseFloat(curr) || 0;
		prev = parseFloat(prev) || 0;
		if (!prev) return null;
		return ((curr - prev) / Math.abs(prev)) * 100;
	}

	function changeBadge(curr, prev) {
		var p = pct(curr, prev);
		if (p === null) return '<span class="omni-muted-text">—</span>';
		var up = p >= 0;
		var cls = up ? 'omni-chg-up' : 'omni-chg-down';
		var arrow = up ? '↑' : '↓';
		return '<span class="' + cls + '">' + arrow + ' ' + Math.abs(p).toFixed(1) + '%</span>';
	}

	function renderPeriodGrid(s, c) {
		var metrics = [
			{label:'Net Sales',       curr: s.net_revenue||s.revenue||0,  prev: c.net_revenue||c.revenue||0,  fmt:'currency'},
			{label:'Gross Sales',     curr: s.revenue||0,                  prev: c.revenue||0,                  fmt:'currency'},
			{label:'Orders',          curr: s.orders||0,                   prev: c.orders||0,                   fmt:'number'},
			{label:'Items',           curr: s.items_sold||0,               prev: c.items_sold||0,               fmt:'number'},
			{label:'Avg Order Net',   curr: s.avg_order_value||0,          prev: c.avg_order_value||0,          fmt:'currency'},
			{label:'Avg Order Gross', curr: s.revenue&&s.orders ? s.revenue/s.orders : 0, prev: c.revenue&&c.orders ? c.revenue/c.orders : 0, fmt:'currency'},
			{label:'Refund Rate',     curr: s.refund_rate||0,              prev: c.refund_rate||0,              fmt:'percent'},
		];
		var html = '';
		metrics.forEach(function(m) {
			var val = m.fmt === 'currency' ? f(m.curr) : m.fmt === 'percent' ? parseFloat(m.curr).toFixed(2)+'%' : parseInt(m.curr).toLocaleString();
			html += '<div class="omni-period-metric">' +
				'<div class="omni-period-metric-val">' + val + ' ' + changeBadge(m.curr, m.prev) + '</div>' +
				'<div class="omni-period-metric-label">' + m.label + '</div>' +
				'</div>';
		});
		$('#omni-period-grid').html(html);
	}

	function renderStrip1(s, c) {
		var items = [
			{label:'Gross Sales', curr:s.revenue||0,   prev:c.revenue||0,   fmt:'currency'},
			{label:'Refunds',     curr:s.refunds||0,   prev:c.refunds||0,   fmt:'currency'},
			{label:'Discounts',   curr:s.discounts||0, prev:c.discounts||0, fmt:'currency'},
			{label:'Taxes',       curr:s.tax||0,       prev:c.tax||0,       fmt:'currency'},
			{label:'Shipping',    curr:s.shipping||0,  prev:c.shipping||0,  fmt:'currency'},
			{label:'Fees',        curr:s.fees||0,      prev:c.fees||0,      fmt:'currency'},
		];
		var html = '';
		items.forEach(function(m) {
			var val = f(m.curr);
			html += '<div class="omni-strip-card">' +
				'<div class="omni-strip-val">' + val + ' ' + changeBadge(m.curr, m.prev) + '</div>' +
				'<div class="omni-strip-label">' + m.label + '</div>' +
				'</div>';
		});
		$('#omni-dash-strip-1').html(html);
	}

	function renderWeeklyStrip(ws, pws) {
		ws  = ws  || {};
		pws = pws || {};
		var items = [
			{label:'Weekly Net',    curr:ws.weekly_net||0,    prev:pws.weekly_net||0,    fmt:'currency'},
			{label:'Weekly Gross',  curr:ws.weekly_gross||0,  prev:pws.weekly_gross||0,  fmt:'currency'},
			{label:'Weekly Orders', curr:ws.weekly_orders||0, prev:pws.weekly_orders||0, fmt:'number'},
			{label:'Weekly Items',  curr:ws.weekly_items||0,  prev:pws.weekly_items||0,  fmt:'number'},
		];
		var html = '';
		items.forEach(function(m) {
			var val = m.fmt === 'currency' ? f(m.curr) : parseInt(m.curr).toLocaleString();
			html += '<div class="omni-weekly-stat">' +
				'<span class="omni-weekly-stat-val">' + val + '</span>' +
				'<span class="omni-weekly-stat-label">' + m.label + '</span>' +
				changeBadge(m.curr, m.prev) +
				'</div>';
		});
		$('#omni-dash-weekly-strip').html(html);
	}

	function renderMainChart(chartData) {
		var ctx = document.getElementById('omni-dash-main-chart');
		if (!ctx || !chartData) return;
		if (mainChart) { mainChart.destroy(); mainChart = null; }

		var labels = chartData.labels || [];
		var ds = chartData.datasets || [];
		var barDs   = ds.find(function(d){ return d.type==='bar' && !d.dashed; }) || {};
		var lineDs  = ds.find(function(d){ return d.type==='line' && !d.dashed; }) || {};
		var priorDs = ds.find(function(d){ return d.dashed; }) || {};

		mainChart = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [
					{
						label: 'Gross Sales',
						data: barDs.data || [],
						backgroundColor: 'rgba(99,102,241,0.18)',
						borderColor: 'rgba(99,102,241,0.4)',
						borderWidth: 1,
						borderRadius: 3,
						order: 2,
					},
					{
						label: 'Net Sales',
						data: lineDs.data || [],
						type: 'line',
						borderColor: '#6366F1',
						backgroundColor: 'rgba(99,102,241,0.08)',
						pointRadius: labels.length > 60 ? 0 : 3,
						pointHoverRadius: 5,
						tension: 0.3,
						fill: true,
						order: 1,
					},
					{
						label: 'Prior Gross',
						data: priorDs.data || [],
						type: 'line',
						borderColor: 'rgba(156,163,175,0.5)',
						borderDash: [5,4],
						backgroundColor: 'transparent',
						pointRadius: 0,
						tension: 0.3,
						order: 3,
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: {
					legend: { display: true, position: 'top', labels: { boxWidth: 10, font: {size:10} } },
					tooltip: {
						callbacks: {
							label: function(ctx) {
								var v = ctx.parsed.y;
								if (ctx.dataset.label && ctx.dataset.label.includes('Orders')) return ctx.dataset.label + ': ' + parseInt(v).toLocaleString();
								return ctx.dataset.label + ': ' + omniReports.formatCurrency(v);
							}
						}
					}
				},
				scales: {
					y: { beginAtZero: true, ticks: { font:{size:10}, callback: function(v){ return '$'+v.toLocaleString(); } }, grid: {color:'#F0F4F8'} },
					x: { ticks: { font:{size:9}, maxRotation:45, maxTicksLimit:20 }, grid:{display:false} }
				}
			}
		});
	}

	function renderBottomCharts(d) {
		// Top products donut
		if (d.top_products && d.top_products.length) {
			var top = d.top_products.slice(0,8);
			var ctx2 = document.getElementById('omni-home-products-donut');
			if (donutChart) { donutChart.destroy(); donutChart = null; }
			var colors = ['#6366F1','#00D4AA','#F59E0B','#EF4444','#10B981','#3B82F6','#EC4899','#8B5CF6'];
			donutChart = new Chart(ctx2, {
				type:'doughnut',
				data:{ labels:top.map(function(p){return p.product_name||'';}), datasets:[{data:top.map(function(p){return parseFloat(p.revenue)||0;}), backgroundColor:colors, borderWidth:2}]},
				options:{ responsive:false, plugins:{legend:{display:false}}, cutout:'65%' }
			});
			var html='<table class="omni-data-table omni-mini-table"><thead><tr><th>Product</th><th>Revenue</th><th>Qty</th></tr></thead><tbody>';
			top.forEach(function(p){ html+='<tr><td>'+($('<span>').text(p.product_name||'').html())+'</td><td>'+f(p.revenue)+'</td><td>'+(parseInt(p.qty_sold||0)).toLocaleString()+'</td></tr>'; });
			html+='</tbody></table>';
			$('#omni-top-products-table').html(html);
		}
		// Status chart
		if (d.order_status_breakdown && d.order_status_breakdown.length) {
			var ctx3 = document.getElementById('omni-home-status-chart');
			if (statusChart) { statusChart.destroy(); statusChart = null; }
			statusChart = new Chart(ctx3, {
				type:'bar',
				data:{ labels:d.order_status_breakdown.map(function(s){return s.status||'';}), datasets:[{label:'Orders',data:d.order_status_breakdown.map(function(s){return parseInt(s.order_count)||0;}),backgroundColor:'#6366F1',borderRadius:4}]},
				options:{ responsive:true, indexAxis:'y', plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true,ticks:{font:{size:10}}},y:{ticks:{font:{size:10}}}} }
			});
		}
		// Top customers
		if (d.top_customers && d.top_customers.length) {
			var top2 = d.top_customers.slice(0,6);
			var html2='<table class="omni-data-table omni-mini-table"><thead><tr><th>Customer</th><th>LTV</th><th>Orders</th></tr></thead><tbody>';
			top2.forEach(function(c){ var nm=((c.first_name||'')+' '+(c.last_name||'')).trim()||c.email||'Guest'; html2+='<tr><td>'+($('<span>').text(nm).html())+'</td><td>'+f(c.ltv)+'</td><td>'+(parseInt(c.order_count||0)).toLocaleString()+'</td></tr>'; });
			html2+='</tbody></table>';
			$('#omni-top-customers-table').html(html2);
		}
	}

	function load(from, to) {
		$.post(omniReports.ajaxUrl, {
			action: 'omni_get_dashboard_home',
			nonce: omniReports.nonce,
			date_from: from,
			date_to: to,
		}, function(res) {
			if (!res.success) return;
			var d = res.data;
			var s = d.summary || {};
			var c = d.comp_summary || {};
			renderPeriodGrid(s, c);
			renderStrip1(s, c);
			renderWeeklyStrip(d.weekly_summary, d.comp_weekly_summary);
			if (d.revenue_chart) renderMainChart(d.revenue_chart);
			renderBottomCharts(d);
		});
	}

	// Group toggle
	$(document).on('click', '.omni-group-btn', function() {
		$('.omni-group-btn').removeClass('active');
		$(this).addClass('active');
		currentGroup = $(this).data('group');
		load(omniReports.currentFrom ? omniReports.currentFrom() : '', omniReports.currentTo ? omniReports.currentTo() : '');
	});

	$(document).ready(function() {
		load(omniReports.currentFrom ? omniReports.currentFrom() : '', omniReports.currentTo ? omniReports.currentTo() : '');
		if (omniReports.onDateChange) omniReports.onDateChange(function(from, to){ load(from, to); });
	});
})(jQuery);
</script>
