<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="profit">
	<h1><?php esc_html_e( 'Profit', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid" id="omni-profit-kpis"></div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Profit Over Time', 'omni-reports' ); ?>
			<span class="omni-group-toggle">
				<button class="omni-group active" data-group="day"><?php esc_html_e( 'Day', 'omni-reports' ); ?></button>
				<button class="omni-group" data-group="week"><?php esc_html_e( 'Week', 'omni-reports' ); ?></button>
				<button class="omni-group" data-group="month"><?php esc_html_e( 'Month', 'omni-reports' ); ?></button>
				<button class="omni-group" data-group="year"><?php esc_html_e( 'Year', 'omni-reports' ); ?></button>
			</span>
		</h2>
		<div class="omni-dual-chart">
			<canvas id="omni-profit-chart" height="90"></canvas>
		</div>
	</div>

	<div class="omni-two-col" style="margin-bottom:20px">
		<div class="omni-chart-card" style="margin-bottom:0">
			<h2><?php esc_html_e( 'Cost Breakdown', 'omni-reports' ); ?></h2>
			<div class="omni-profit-breakdown" id="omni-cost-breakdown"></div>
		</div>
		<div class="omni-chart-card" style="margin-bottom:0">
			<h2><?php esc_html_e( 'Profit Summary', 'omni-reports' ); ?></h2>
			<canvas id="omni-profit-pie" height="160"></canvas>
		</div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Profit by Date', 'omni-reports' ); ?></h2>
		<div id="omni-profit-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	var currentGroup = 'day';
	var chartInst = null;
	var pieInst   = null;
	var f = omniReports.formatCurrency;
	var pct = function(v){ return (parseFloat(v)||0).toFixed(2)+'%'; };

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
			var pos = change >= 0;
			cls += pos ? ' omni-kpi-positive' : ' omni-kpi-negative';
			var arrow = pos ? '↑' : '↓';
			var changeCls = pos ? 'omni-change-up' : 'omni-change-down';
			badge = '<span class="omni-kpi-change '+changeCls+'">'+arrow+' '+Math.abs(change).toFixed(1)+'% vs prior period</span>';
		}
		return '<div class="'+cls+'"><span class="omni-kpi-label">'+label+'</span><span class="omni-kpi-value">'+value+'</span>'+badge+'</div>';
	}

	function renderKpis(data, comp){
		var t = data.totals;
		var c = comp ? comp.totals : null;
		var html = '';
		html += kpiCard('Gross Sales',       f(t.gross_sales),    c ? pctChange(t.gross_sales, c.gross_sales) : null);
		html += kpiCard('Gross Total Cost',  f(t.gross_cost),     c ? pctChange(t.gross_cost, c.gross_cost) : null);
		html += kpiCard('Gross Profit',      f(t.gross_profit),   c ? pctChange(t.gross_profit, c.gross_profit) : null);
		html += kpiCard('Profit Margin',     pct(t.profit_margin),c ? pctChange(t.profit_margin, c.profit_margin) : null);
		html += kpiCard('Orders',            (parseInt(t.orders)||0).toLocaleString(), c ? pctChange(t.orders, c.orders) : null);
		html += kpiCard('Avg Profit/Order',  f(t.avg_profit),     c ? pctChange(t.avg_profit, c.avg_profit) : null);
		$('#omni-profit-kpis').html(html);
	}

	function renderBreakdown(totals){
		var items = [
			{ label:'Product Cost',      value: f(totals.product_cost) },
			{ label:'Shipping Cost',     value: f(totals.shipping_total) },
			{ label:'Tax',               value: f(totals.tax_total) },
		];
		var html = items.map(function(i){
			return '<div class="omni-profit-breakdown-item"><span class="omni-profit-breakdown-label">'+i.label+'</span><span class="omni-profit-breakdown-value">'+i.value+'</span></div>';
		}).join('');
		$('#omni-cost-breakdown').html(html);
	}

	function renderChart(rows){
		var labels = rows.map(function(r){ return r.report_date; });
		var grossSales  = rows.map(function(r){ return parseFloat(r.gross_sales)||0; });
		var grossCost   = rows.map(function(r){ return parseFloat(r.gross_cost)||0; });
		var grossProfit = rows.map(function(r){ return parseFloat(r.gross_profit)||0; });
		var margin      = rows.map(function(r){ return parseFloat(r.profit_margin)||0; });
		var ctx = document.getElementById('omni-profit-chart').getContext('2d');
		if(chartInst) chartInst.destroy();
		chartInst = new Chart(ctx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{ label:'Gross Sales',   data:grossSales,  borderColor:'#F6AD55', backgroundColor:'rgba(246,173,85,0.15)', fill:true, tension:0.4, yAxisID:'y' },
					{ label:'Gross Cost',    data:grossCost,   borderColor:'#0099FF', backgroundColor:'rgba(0,153,255,0.10)', fill:true, tension:0.4, yAxisID:'y' },
					{ label:'Gross Profit',  data:grossProfit, borderColor:'#9F7AEA', backgroundColor:'rgba(159,122,234,0.15)', fill:true, tension:0.4, yAxisID:'y' },
					{ label:'Margin %',      data:margin,      borderColor:'#00D4AA', backgroundColor:'transparent', fill:false, tension:0.4, yAxisID:'y2', borderDash:[4,4] },
				]
			},
			options:{
				responsive:true,
				interaction:{ mode:'index', intersect:false },
				scales:{
					y:{ grid:{ color:'rgba(0,0,0,0.05)' }, ticks:{ callback:function(v){ return omniReports.currency+v; } } },
					y2:{ position:'right', grid:{ drawOnChartArea:false }, ticks:{ callback:function(v){ return v+'%'; } } }
				},
				plugins:{ legend:{ position:'top' }, tooltip:{ callbacks:{ label:function(ctx){ var v=ctx.parsed.y; return ctx.dataset.label+': '+(ctx.datasetIndex===3?v.toFixed(1)+'%':omniReports.currency+v.toFixed(2)); } } } }
			}
		});
	}

	function renderPie(totals){
		var ctx = document.getElementById('omni-profit-pie').getContext('2d');
		if(pieInst) pieInst.destroy();
		pieInst = new Chart(ctx, {
			type:'doughnut',
			data:{
				labels:['Gross Profit','Product Cost','Shipping','Tax'],
				datasets:[{ data:[
					Math.max(0, parseFloat(totals.gross_profit)||0),
					parseFloat(totals.product_cost)||0,
					parseFloat(totals.shipping_total)||0,
					parseFloat(totals.tax_total)||0
				], backgroundColor:['#9F7AEA','#0099FF','#F6AD55','#FC8181'] }]
			},
			options:{ responsive:true, plugins:{ legend:{ position:'right' } } }
		});
	}

	function renderTable(rows){
		var cols = ['Date','Orders','Gross Sales','Gross Cost','Gross Profit','Margin %'];
		var html = '<table class="omni-data-table"><thead><tr>'+cols.map(function(c){ return '<th>'+c+'</th>'; }).join('')+'</tr></thead><tbody>';
		rows.forEach(function(r){
			html += '<tr><td>'+r.report_date+'</td><td class="num">'+r.orders+'</td><td class="num">'+f(r.gross_sales)+'</td><td class="num">'+f(r.gross_cost)+'</td><td class="num">'+f(r.gross_profit)+'</td><td class="num">'+(parseFloat(r.profit_margin)||0).toFixed(1)+'%</td></tr>';
		});
		html += '</tbody></table>';
		$('#omni-profit-table-wrap').html(html);
	}

	function load(from, to){
		var compFrom = $('#omni-comp-from').val() || '';
		var compTo   = $('#omni-comp-to').val() || '';
		$.post(omniReports.ajaxUrl,{
			action:'omni_get_profit_report',nonce:omniReports.nonce,
			date_from:from,date_to:to,
			comp_from:compFrom,comp_to:compTo,
			group:currentGroup
		},function(r){
			if(!r.success) return;
			var data = r.data.current;
			var comp = r.data.comparison;
			renderKpis(data, comp);
			renderChart(data.rows);
			renderBreakdown(data.totals);
			renderPie(data.totals);
			renderTable(data.rows);
		});
	}

	$('.omni-group').on('click',function(){
		$('.omni-group').removeClass('active');
		$(this).addClass('active');
		currentGroup = $(this).data('group');
		load(omniReports.currentFrom(), omniReports.currentTo());
	});

	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
