<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="products">
	<h1><?php esc_html_e( 'Products Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Top Products by Revenue', 'omni-reports' ); ?></h2>
		<canvas id="omni-products-chart" height="100"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Product Data', 'omni-reports' ); ?></h2>
		<div id="omni-products-table-wrap"><p class="omni-loading"><span class="omni-spinner"></span>Loading…</p></div>
	</div>
</div>
<script>
jQuery(function($){
	var slug = 'products';
	var col  = function(k){ return omniReports.colEnabled(slug, k); };
	var f    = omniReports.formatCurrency;

	var colDefs = [
		{ key:'product_name', label:'Product',         field:'product_name', fmt:null },
		{ key:'sku',          label:'SKU',              field:'sku',          fmt:null },
		{ key:'revenue',      label:'Revenue',          field:'revenue',      fmt:f },
		{ key:'qty_sold',     label:'Qty Sold',         field:'qty_sold',     fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
		{ key:'orders',       label:'Orders',           field:'orders',       fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
		{ key:'avg_price',    label:'Avg Price',        field:'avg_price',    fmt:f },
		{ key:'net_revenue',  label:'Net Revenue',      field:'net_revenue',  fmt:f },
		{ key:'refunds',      label:'Refunded Qty',     field:'refunds',      fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
	];

	function load(from, to){
		$('#omni-products-table-wrap').html('<p class="omni-loading"><span class="omni-spinner"></span>Loading…</p>');
		$.post(omniReports.ajaxUrl,{action:'omni_get_products_report',nonce:omniReports.nonce,date_from:from,date_to:to,limit:50},function(r){
			if(!r.success) return;
			var rows = r.data || [];
			omniReports.renderBarChart('omni-products-chart', rows.slice(0,15), 'product_name', 'revenue', 'Revenue');

			var activeCols = colDefs.filter(function(c){ return col(c.key); });
			if(!activeCols.length || !rows.length){
				$('#omni-products-table-wrap').html('<p class="omni-empty-state">No data or no columns enabled.</p>');
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
			$('#omni-products-table-wrap').html(html);
		});
	}
	$('#omni-export-csv').on('click',function(){ omniReports.exportCsv('products'); });
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
