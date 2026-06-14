<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="sales-overview">
	<h1><?php esc_html_e( 'Sales Overview', 'omni-reports' ); ?></h1>
	<?php include __DIR__ . '/partials/date-filter.php'; ?>

	<div class="omni-kpi-grid" id="omni-sales-kpis"></div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Sales Over Time', 'omni-reports' ); ?>
			<span class="omni-group-toggle">
				<button class="omni-group active" data-group="day"><?php esc_html_e( 'Day', 'omni-reports' ); ?></button>
				<button class="omni-group" data-group="week"><?php esc_html_e( 'Week', 'omni-reports' ); ?></button>
				<button class="omni-group" data-group="month"><?php esc_html_e( 'Month', 'omni-reports' ); ?></button>
			</span>
		</h2>
		<canvas id="omni-sales-chart" height="80"></canvas>
	</div>

	<div class="omni-chart-card">
		<h2><?php esc_html_e( 'Data Table', 'omni-reports' ); ?></h2>
		<div id="omni-sales-table-wrap"></div>
	</div>
</div>
<script>
jQuery(function($){
	var slug = 'sales-overview';
	var col  = function(k){ return omniReports.colEnabled(slug, k); };
	var currentGroup = 'day';
	var f = omniReports.formatCurrency;

	var allKpis = [
		{ key:'gross_revenue',  label:'Gross Revenue',   field:'revenue',        fmt:f },
		{ key:'net_revenue',    label:'Net Revenue',      field:'net_revenue',    fmt:f },
		{ key:'orders',         label:'Orders',           field:'orders',         fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
		{ key:'avg_order_value',label:'Avg Order Value',  field:'avg_order_value',fmt:f },
		{ key:'refunds',        label:'Refunds',          field:'refunds',        fmt:f },
		{ key:'tax',            label:'Tax Collected',    field:'tax',            fmt:f },
		{ key:'shipping',       label:'Shipping Revenue', field:'shipping',       fmt:f },
		{ key:'num_items_sold', label:'Items Sold',       field:'num_items_sold', fmt:function(v){return (parseInt(v)||0).toLocaleString();} },
	];

	function renderKpis(data){
		var html = '';
		allKpis.forEach(function(k){
			if(!col(k.key)) return;
			var val = data[k.field] !== undefined ? data[k.field] : '—';
			html += '<div class="omni-kpi-card"><span class="omni-kpi-label">'+k.label+'</span><span class="omni-kpi-value">'+k.fmt(val)+'</span></div>';
		});
		$('#omni-sales-kpis').html(html || '<p style="color:#A0AEC0;padding:12px">No columns enabled. Open Edit Report → Modify to enable columns.</p>');
	}

	function load(from, to){
		$.post(omniReports.ajaxUrl,{action:'omni_get_sales_overview',nonce:omniReports.nonce,date_from:from,date_to:to},function(r){
			if(!r.success) return;
			renderKpis(r.data);
		});
		$.post(omniReports.ajaxUrl,{action:'omni_get_sales_over_time',nonce:omniReports.nonce,date_from:from,date_to:to,group:currentGroup},function(r){
			if(!r.success) return;
			omniReports.renderLineChart('omni-sales-chart', r.data, 'report_date', 'revenue', 'Revenue');
			omniReports.renderTable('omni-sales-table-wrap', r.data);
		});
	}

	$('.omni-group').on('click',function(){
		$('.omni-group').removeClass('active');
		$(this).addClass('active');
		currentGroup = $(this).data('group');
		load(omniReports.currentFrom(), omniReports.currentTo());
	});
	$('#omni-export-csv').on('click',function(){ omniReports.exportCsv('sales', currentGroup); });
	omniReports.onDateChange(load);
	load(omniReports.currentFrom(), omniReports.currentTo());
});
</script>
