<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap omni-reports-wrap" data-report="costs">
	<h1><?php esc_html_e( 'Cost Manager', 'omni-reports' ); ?></h1>

	<div class="omni-chart-card">
		<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
			<input type="text" id="omni-cost-search" class="omni-cost-search-input" placeholder="<?php esc_attr_e( 'Search products…', 'omni-reports' ); ?>" style="flex:1;min-width:200px;padding:8px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;outline:none;" />
			<label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:500;white-space:nowrap;">
				<input type="checkbox" id="omni-cost-no-cost-only" />
				<?php esc_html_e( 'Show products with no cost set', 'omni-reports' ); ?>
			</label>
			<button class="omni-btn omni-btn-secondary omni-btn-sm" id="omni-cost-search-btn"><?php esc_html_e( 'Search', 'omni-reports' ); ?></button>
		</div>

		<div id="omni-costs-table-wrap">
			<div class="omni-loading"><span class="omni-spinner"></span> <?php esc_html_e( 'Loading products…', 'omni-reports' ); ?></div>
		</div>

		<div id="omni-costs-pagination" style="display:flex;align-items:center;gap:8px;margin-top:16px;flex-wrap:wrap;"></div>

		<div style="display:flex;align-items:center;gap:10px;margin-top:16px;flex-wrap:wrap;">
			<button class="omni-btn omni-btn-primary" id="omni-save-costs"><?php esc_html_e( 'Save Changes', 'omni-reports' ); ?></button>
			<span id="omni-save-msg" style="font-size:13px;color:#00A389;display:none;"><?php esc_html_e( 'Saved!', 'omni-reports' ); ?></span>
			<div style="margin-left:auto;display:flex;align-items:center;gap:8px;">
				<label style="font-size:13px;font-weight:500;"><?php esc_html_e( 'Bulk Import CSV', 'omni-reports' ); ?></label>
				<input type="file" id="omni-csv-file" accept=".csv" style="font-size:12px;" />
				<button class="omni-btn omni-btn-secondary omni-btn-sm" id="omni-import-csv"><?php esc_html_e( 'Import', 'omni-reports' ); ?></button>
				<span id="omni-import-msg" style="font-size:13px;color:#00A389;display:none;"></span>
			</div>
		</div>

		<div style="margin-top:8px;">
			<p style="font-size:12px;color:#718096;margin:0;"><?php esc_html_e( 'CSV format: product_id, cost (header row required)', 'omni-reports' ); ?></p>
		</div>
	</div>
</div>
<script>
jQuery(function($){
	var currentPage = 1;
	var changedCosts = {};
	var f = omniReports.formatCurrency;

	function loadProducts(page){
		page = page || 1;
		currentPage = page;
		$('#omni-costs-table-wrap').html('<div class="omni-loading"><span class="omni-spinner"></span> Loading…</div>');
		$.post(omniReports.ajaxUrl,{
			action:'omni_get_product_costs',nonce:omniReports.nonce,
			paged:page,
			search:$('#omni-cost-search').val(),
			show_no_cost: $('#omni-cost-no-cost-only').is(':checked') ? 1 : 0
		},function(r){
			if(!r.success) return;
			renderTable(r.data.products);
			renderPagination(r.data.total_pages, r.data.page, r.data.total);
		});
	}

	function renderTable(products){
		var html = '<table class="omni-data-table omni-costs-table"><thead><tr><th>Product</th><th>SKU</th><th class="num">Retail Price</th><th class="num">Cost</th></tr></thead><tbody>';
		if(!products || !products.length){
			html += '<tr><td colspan="4"><div class="omni-empty-state">No products found.</div></td></tr>';
		} else {
			products.forEach(function(p){
				var costVal = (p.cost !== null && p.cost !== undefined) ? p.cost : '';
				html += '<tr>';
				html += '<td><a href="'+escHtml(p.edit_url||'#')+'" target="_blank">'+escHtml(p.name||'')+'</a></td>';
				html += '<td>'+escHtml(p.sku||'—')+'</td>';
				html += '<td class="num">'+f(p.price)+'</td>';
				html += '<td class="num"><input type="number" step="0.01" min="0" class="omni-cost-input" data-id="'+p.id+'" value="'+escHtml(''+costVal)+'" placeholder="0.00" style="width:90px;padding:5px 8px;border:1px solid #E2E8F0;border-radius:6px;font-size:13px;text-align:right;" /></td>';
				html += '</tr>';
			});
		}
		html += '</tbody></table>';
		$('#omni-costs-table-wrap').html(html);

		// Track changes.
		$(document).on('change','#omni-costs-table-wrap .omni-cost-input',function(){
			var id  = $(this).data('id');
			var val = $(this).val();
			changedCosts[id] = val;
		});
	}

	function renderPagination(totalPages, page, total){
		var html = '<span style="font-size:13px;color:#718096;">'+total+' products</span>';
		if(totalPages > 1){
			html += '<div style="display:flex;gap:4px;margin-left:auto;">';
			if(page > 1) html += '<button class="omni-btn omni-btn-secondary omni-btn-sm omni-cost-page" data-page="'+(page-1)+'">← Prev</button>';
			html += '<span style="font-size:13px;padding:6px 10px;">Page '+page+' of '+totalPages+'</span>';
			if(page < totalPages) html += '<button class="omni-btn omni-btn-secondary omni-btn-sm omni-cost-page" data-page="'+(page+1)+'">Next →</button>';
			html += '</div>';
		}
		$('#omni-costs-pagination').html(html);
	}

	function escHtml(s){ return $('<div>').text(s).html(); }

	$(document).on('click','.omni-cost-page',function(){
		loadProducts($(this).data('page'));
	});

	$('#omni-cost-search-btn').on('click',function(){ loadProducts(1); });
	$('#omni-cost-search').on('keypress',function(e){ if(e.which===13) loadProducts(1); });
	$('#omni-cost-no-cost-only').on('change',function(){ loadProducts(1); });

	$('#omni-save-costs').on('click',function(){
		// Collect all currently visible inputs too.
		$('#omni-costs-table-wrap .omni-cost-input').each(function(){
			changedCosts[$(this).data('id')] = $(this).val();
		});
		if(!Object.keys(changedCosts).length){
			alert('No changes to save.');
			return;
		}
		$.post(omniReports.ajaxUrl,{
			action:'omni_save_product_costs',nonce:omniReports.nonce,
			costs: JSON.stringify(changedCosts)
		},function(r){
			if(!r.success) return;
			changedCosts = {};
			$('#omni-save-msg').show().delay(2000).fadeOut();
		});
	});

	$('#omni-import-csv').on('click',function(){
		var file = $('#omni-csv-file')[0].files[0];
		if(!file){ alert('Please select a CSV file.'); return; }
		var fd = new FormData();
		fd.append('action','omni_import_costs_csv');
		fd.append('nonce',omniReports.nonce);
		fd.append('csv_file',file);
		$.ajax({ url:omniReports.ajaxUrl, type:'POST', data:fd, processData:false, contentType:false, success:function(r){
			if(!r.success) return;
			$('#omni-import-msg').text('Imported '+r.data.imported+' products!').show().delay(3000).fadeOut();
			loadProducts(currentPage);
		}});
	});

	loadProducts(1);
});
</script>
