<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="refunds">
	<h1><?php esc_html_e( 'Refunds', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid" id="omni-refunds-kpis"></div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Refunds Over Time', 'omni-reports' ); ?></h2>
		<canvas id="omni-refunds-chart" height="80"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Top Refunded Products', 'omni-reports' ); ?></h2>
		<div id="omni-refunds-products-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	var chartInst = null;
	var f = omniReports.formatCurrency;

	function pctChange(curr, prev){
		curr = parseFloat(curr) || 0;
		prev = parseFloat(prev) || 0;
		if(!prev) return null;
		return ((curr - prev) / Math.abs(prev)) * 100;
	}

	function kpiCard(label, value, change){
		var cls = 'omni-kpi-card';
		var badge = '';
		if(change !== null && change !== undefined){
			// For refunds, lower is better
			var pos = change <= 0;
			cls += pos ? ' omni-kpi-positive' : ' omni-kpi-negative';
			var arrow = change >= 0 ? '↑' : '↓';
			var changeCls = pos ? 'omni-change-up' : 'omni-change-down';
			badge = '<span class="omni-kpi-change '+changeCls+'">'+arrow+' '+Math.abs(change).toFixed(1)+'% vs prior period</span>';
		}
		return '<div class="'+cls+'"><span class="omni-kpi-label">'+label+'</span><span class="omni-kpi-value">'+value+'</span>'+badge+'</div>';
	}

	function renderKpis(data, comp){
		var s = data.summary || {};
		var c = comp ? (comp.summary || {}) : null;
		var html = '';
		html += kpiCard('Total Refunded',  f(s.total_refunded||0),   c ? pctChange(s.total_refunded, c.total_refunded) : null);
		html += kpiCard('Refund Count',    (parseInt(s.refund_count||0)).toLocaleString(), c ? pctChange(s.refund_count, c.refund_count) : null);
		html += kpiCard('Avg Refund',      f(s.avg_refund||0),        c ? pctChange(s.avg_refund, c.avg_refund) : null);
		html += kpiCard('Refund Rate',     (parseFloat(s.refund_rate||0)).toFixed(2)+'%', c ? pctChange(s.refund_rate, c.refund_rate) : null);
		$('#omni-refunds-kpis').html(html);
	}

	function renderChart(rows){
		var labels   = rows.map(function(r){ return r.report_date; });
		var refunded = rows.map(function(r){ return parseFloat(r.refunded)||0; });
		var ctx = document.getElementById('omni-refunds-chart').getContext('2d');
		if(chartInst) chartInst.destroy();
		chartInst = new Chart(ctx,{
			type:'line',
			data:{
				labels:labels,
				datasets:[{ label:'Refunded', data:refunded, borderColor:'#FC8181', backgroundColor:'rgba(252,129,129,0.15)', fill:true, tension:0.4 }]
			},
			options:{
				responsive:true,
				scales:{ y:{ grid:{ color:'rgba(0,0,0,0.05)' }, ticks:{ callback:function(v){ return omniReports.currency+v; } } } },
				plugins:{ tooltip:{ callbacks:{ label:function(ctx){ return 'Refunded: '+omniReports.currency+ctx.parsed.y.toFixed(2); } } } }
			}
		});
	}

	function renderProducts(products){
		var html = '<table class="omni-data-table"><thead><tr><th>Product</th><th class="num">Refund Count</th><th class="num">Refund Amount</th></tr></thead><tbody>';
		if(!products || !products.length){
			html += '<tr><td colspan="3"><div class="omni-empty-state">No refunded products in this period.</div></td></tr>';
		} else {
			products.forEach(function(p){
				html += '<tr><td>'+escHtml(p.product_name||'—')+'</td><td class="num">'+p.refund_count+'</td><td class="num">'+f(p.refund_amount)+'</td></tr>';
			});
		}
		html += '</tbody></table>';
		$('#omni-refunds-products-wrap').html(html);
	}

	function escHtml(s){ return $('<div>').text(s).html(); }

	function load(from, to){
		var compFrom = $('#omni-comp-from').val() || '';
		var compTo   = $('#omni-comp-to').val() || '';
		$.post(omniReports.ajaxUrl,{
			action:'omni_get_refunds_report',nonce:omniReports.nonce,
			date_from:from,date_to:to,
			comp_from:compFrom,comp_to:compTo
		},function(r){
			if(!r.success) return;
			var data = r.data.current;
			var comp = r.data.comparison;
			renderKpis(data, comp);
			renderChart(data.over_time || []);
			renderProducts(data.top_products || []);
		});
	}

	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
