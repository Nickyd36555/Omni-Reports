<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="orders">
	<h1><?php esc_html_e( 'Orders Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-two-col">
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Orders by Status', 'omni-reports' ); ?></h2>
			<canvas id="omni-status-pie" height="200"></canvas>
		</div>
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'Orders Over Time', 'omni-reports' ); ?></h2>
			<canvas id="omni-orders-line" height="200"></canvas>
		</div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Status Breakdown', 'omni-reports' ); ?></h2>
		<div id="omni-status-table-wrap"><p class="omni-loading"><span class="omni-spinner"></span>Loading…</p></div>
	</div>
</div>
<script>
jQuery(function($){
	var slug = 'orders';
	var col  = function(k){ return omniReports.colEnabled(slug, k); };
	var f    = omniReports.formatCurrency;

	var colDefs = [
		{ key:'status',         label:'Status',          field:'status',         fmt:null },
		{ key:'order_count',    label:'Orders',          field:'order_count',    fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
		{ key:'revenue',        label:'Revenue',         field:'revenue',        fmt:f },
		{ key:'percentage',     label:'% of Total',      field:'percentage',     fmt:function(v){return (parseFloat(v)||0).toFixed(1)+'%';} },
		{ key:'avg_order_value',label:'Avg Order Value', field:'avg_order_value',fmt:f },
		{ key:'tax',            label:'Tax',             field:'tax',            fmt:f },
		{ key:'shipping',       label:'Shipping',        field:'shipping',       fmt:f },
		{ key:'refunds',        label:'Refunds',         field:'refunds',        fmt:f },
	];

	function load(from, to){
		$('#omni-status-table-wrap').html('<p class="omni-loading"><span class="omni-spinner"></span>Loading…</p>');
		$.post(omniReports.ajaxUrl,{action:'omni_get_orders_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success) return;
			omniReports.renderPieChart('omni-status-pie', r.data.by_status, 'status', 'order_count');
			omniReports.renderLineChart('omni-orders-line', r.data.over_time, 'report_date', 'order_count', 'Orders');

			var rows = r.data.by_status || [];
			var activeCols = colDefs.filter(function(c){ return col(c.key); });
			if(!activeCols.length || !rows.length){
				$('#omni-status-table-wrap').html('<p class="omni-empty-state">No data or no columns enabled.</p>');
				return;
			}
			var html = '<table class="omni-data-table"><thead><tr>';
			activeCols.forEach(function(c){ html += '<th>'+c.label+'</th>'; });
			html += '</tr></thead><tbody>';
			rows.forEach(function(row){
				html += '<tr>';
				activeCols.forEach(function(c){
					var v = row[c.field] !== undefined ? row[c.field] : '—';
					html += '<td>'+(c.fmt ? c.fmt(v) : ($('<span>').text(v||'—').html()))+'</td>';
				});
				html += '</tr>';
			});
			html += '</tbody></table>';
			$('#omni-status-table-wrap').html(html);
		});
	}
	$('#omni-export-csv').on('click',function(){ omniReports.exportCsv('orders'); });
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
