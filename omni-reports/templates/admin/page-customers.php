<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="customers">
	<h1><?php esc_html_e( 'Customers Report', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid">
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Unique Customers', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-unique">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'New Customers', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-new">—</span></div>
		<div class="omni-kpi-card"><span class="omni-kpi-label"><?php esc_html_e( 'Returning Customers', 'omni-reports' ); ?></span><span class="omni-kpi-value" id="kpi-returning">—</span></div>
	</div>

	<div class="omni-two-col">
		<div class="omni-chart-card">
			<h2><?php esc_html_e( 'New vs Returning', 'omni-reports' ); ?></h2>
			<canvas id="omni-customer-pie" height="200"></canvas>
		</div>
		<div class="omni-chart-card" id="omni-geo-card">
			<h2><?php esc_html_e( 'Revenue by Country', 'omni-reports' ); ?></h2>
			<canvas id="omni-geo-bar" height="200"></canvas>
		</div>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Top Customers by Lifetime Value', 'omni-reports' ); ?></h2>
		<div id="omni-customers-table-wrap"><p class="omni-loading"><span class="omni-spinner"></span>Loading…</p></div>
	</div>
</div>
<script>
jQuery(function($){
	var slug = 'customers';
	var col  = function(k){ return omniReports.colEnabled(slug, k); };
	var f    = omniReports.formatCurrency;

	var colDefs = [
		{ key:'customer_name', label:'Name',        field:'customer_name', fmt:null },
		{ key:'email',         label:'Email',        field:'email',         fmt:null },
		{ key:'total_spend',   label:'Total Spend',  field:'total_spend',   fmt:f },
		{ key:'order_count',   label:'Orders',       field:'order_count',   fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
		{ key:'avg_order',     label:'Avg Order',    field:'avg_order_value',fmt:f },
		{ key:'country',       label:'Country',      field:'country',       fmt:null },
		{ key:'first_order',   label:'First Order',  field:'date_last_active',fmt:null },
		{ key:'last_order',    label:'Last Order',   field:'date_registered',fmt:null },
	];

	// Hide geo chart if country col not enabled.
	if(!col('country')) $('#omni-geo-card').hide();

	function load(from, to){
		$('#omni-customers-table-wrap').html('<p class="omni-loading"><span class="omni-spinner"></span>Loading…</p>');
		$.post(omniReports.ajaxUrl,{action:'omni_get_customers_report',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success) return;
			var d = r.data, s = d.summary;
			$('#kpi-unique').text((parseInt(s.unique_customers)||0).toLocaleString());
			$('#kpi-new').text((parseInt(s.new_customers)||0).toLocaleString());
			$('#kpi-returning').text((parseInt(s.returning_customers)||0).toLocaleString());

			if(col('new_returning')){
				omniReports.renderPieChart('omni-customer-pie',[
					{label:'New',value:s.new_customers},
					{label:'Returning',value:s.returning_customers}
				],'label','value');
			}
			if(col('country')){
				omniReports.renderBarChart('omni-geo-bar', d.geography, 'country', 'revenue', 'Revenue');
			}

			var rows = d.top_customers || [];
			var activeCols = colDefs.filter(function(c){ return col(c.key); });
			if(!activeCols.length || !rows.length){
				$('#omni-customers-table-wrap').html('<p class="omni-empty-state">No data or no columns enabled.</p>');
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
			$('#omni-customers-table-wrap').html(html);
		});
	}
	$('#omni-export-csv').on('click',function(){ omniReports.exportCsv('customers'); });
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
