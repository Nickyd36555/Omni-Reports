<?php
/**
 * AJAX handlers.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Ajax {

	/** @var Omni_Reports_Data_Store */
	private $ds;

	/** @var Omni_Reports_Report_Builder */
	private $builder;

	public function __construct() {
		$this->ds      = new Omni_Reports_Data_Store();
		$this->builder = new Omni_Reports_Report_Builder();

		$actions = [
			'omni_get_sales_overview',
			'omni_get_sales_over_time',
			'omni_get_revenue_trends',
			'omni_get_products_report',
			'omni_get_categories_report',
			'omni_get_customers_report',
			'omni_get_orders_report',
			'omni_get_coupons_report',
			'omni_get_tax_report',
			'omni_get_shipping_report',
			'omni_get_dashboard',
			'omni_run_builder',
			'omni_save_report',
			'omni_delete_report',
			'omni_get_saved_reports',
			'omni_export_csv',
			'omni_get_profit_report',
			'omni_get_refunds_report',
			'omni_get_product_costs',
			'omni_save_product_costs',
			'omni_import_costs_csv',
			// Registry / Report Manager
			'omni_get_report_registry',
			'omni_save_report_meta',
			'omni_delete_report_meta',
			'omni_reorder_reports',
			'omni_install_defaults',
			// Dashboard Home
			'omni_get_dashboard_home',
			// Stock Tracker
			'omni_get_stock_report',
			'omni_save_reorder_point',
			'omni_export_stock_csv',
			// Builder layout
			'omni_save_builder_report',
			'omni_load_builder_report',
		];

		foreach ( $actions as $action ) {
			add_action( 'wp_ajax_' . $action, [ $this, 'handle_' . $action ] );
		}
	}

	private function verify_nonce() {
		if ( ! check_ajax_referer( 'omni_reports_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce.' ], 403 );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
		}
	}

	private function dates() {
		$from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
		$to   = isset( $_POST['date_to'] )   ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) )   : '';
		return $this->ds->sanitize_dates( $from, $to );
	}

	public function handle_omni_get_sales_overview() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		wp_send_json_success( $this->ds->get_sales_overview( $from, $to ) );
	}

	public function handle_omni_get_sales_over_time() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$group = isset( $_POST['group'] ) ? sanitize_key( $_POST['group'] ) : 'day';
		wp_send_json_success( $this->ds->get_sales_over_time( $from, $to, $group ) );
	}

	public function handle_omni_get_revenue_trends() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$group = isset( $_POST['group'] ) ? sanitize_key( $_POST['group'] ) : 'day';
		wp_send_json_success( $this->ds->get_revenue_trends( $from, $to, $group ) );
	}

	public function handle_omni_get_products_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50;
		wp_send_json_success( $this->ds->get_products_report( $from, $to, $limit ) );
	}

	public function handle_omni_get_categories_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		wp_send_json_success( $this->ds->get_categories_report( $from, $to ) );
	}

	public function handle_omni_get_customers_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		wp_send_json_success( $this->ds->get_customers_report( $from, $to ) );
	}

	public function handle_omni_get_orders_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		wp_send_json_success( $this->ds->get_orders_report( $from, $to ) );
	}

	public function handle_omni_get_coupons_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		wp_send_json_success( $this->ds->get_coupons_report( $from, $to, $search ) );
	}

	public function handle_omni_get_tax_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		wp_send_json_success( $this->ds->get_tax_report( $from, $to ) );
	}

	public function handle_omni_get_shipping_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		wp_send_json_success( $this->ds->get_shipping_report( $from, $to ) );
	}

	public function handle_omni_get_dashboard() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$data = $this->ds->get_dashboard_summary( $from, $to );
		$data['analytics_tables_exist'] = $this->ds->analytics_tables_exist();
		wp_send_json_success( $data );
	}

	public function handle_omni_run_builder() {
		$this->verify_nonce();

		$config = [];
		if ( isset( $_POST['config'] ) ) {
			// config is JSON-encoded string
			$raw = wp_unslash( $_POST['config'] );
			$config = json_decode( $raw, true );
			if ( ! is_array( $config ) ) {
				$config = [];
			}
		}

		$results = $this->builder->run( $config );
		wp_send_json_success( $results );
	}

	public function handle_omni_save_report() {
		$this->verify_nonce();
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : 'My Report';
		$config = [];
		if ( isset( $_POST['config'] ) ) {
			$config = json_decode( wp_unslash( $_POST['config'] ), true ) ?: [];
		}
		$id = $this->builder->save_report( $name, $config );
		wp_send_json_success( [ 'id' => $id ] );
	}

	public function handle_omni_delete_report() {
		$this->verify_nonce();
		$id = isset( $_POST['report_id'] ) ? sanitize_key( wp_unslash( $_POST['report_id'] ) ) : '';
		if ( $id ) {
			$this->builder->delete_report( $id );
		}
		wp_send_json_success();
	}

	public function handle_omni_get_saved_reports() {
		$this->verify_nonce();
		$reports = $this->builder->get_saved_reports();
		wp_send_json_success( array_values( $reports ) );
	}

	public function handle_omni_export_csv() {
		$this->verify_nonce();

		$report_type = isset( $_POST['report_type'] ) ? sanitize_key( wp_unslash( $_POST['report_type'] ) ) : 'sales';
		[ $from, $to ] = $this->dates();

		$rows     = [];
		$filename = 'omni-' . $report_type;

		switch ( $report_type ) {
			case 'sales':
				$rows = $this->ds->get_sales_over_time( $from, $to );
				break;
			case 'revenue':
				$group = isset( $_POST['group'] ) ? sanitize_key( $_POST['group'] ) : 'day';
				$rows  = $this->ds->get_revenue_trends( $from, $to, $group );
				break;
			case 'products':
				$rows = $this->ds->get_products_report( $from, $to );
				break;
			case 'categories':
				$rows = $this->ds->get_categories_report( $from, $to );
				break;
			case 'customers':
				$data = $this->ds->get_customers_report( $from, $to );
				$rows = $data['top_customers'] ?? [];
				break;
			case 'orders':
				$data = $this->ds->get_orders_report( $from, $to );
				$rows = $data['by_status'] ?? [];
				break;
			case 'coupons':
				$rows = $this->ds->get_coupons_report( $from, $to );
				break;
			case 'tax':
				$data = $this->ds->get_tax_report( $from, $to );
				$rows = $data['by_rate'] ?? [];
				break;
			case 'shipping':
				$data = $this->ds->get_shipping_report( $from, $to );
				$rows = $data['by_method'] ?? [];
				break;
			case 'builder':
				$config = [];
				if ( isset( $_POST['config'] ) ) {
					$config = json_decode( wp_unslash( $_POST['config'] ), true ) ?: [];
				}
				$rows = $this->builder->run( $config );
				break;
		}

		Omni_Reports_Exporter::output_csv( $filename . '-' . $from . '-to-' . $to, (array) $rows );
	}

	public function handle_omni_get_profit_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$comp_from = isset( $_POST['comp_from'] ) ? sanitize_text_field( wp_unslash( $_POST['comp_from'] ) ) : '';
		$comp_to   = isset( $_POST['comp_to'] )   ? sanitize_text_field( wp_unslash( $_POST['comp_to'] ) )   : '';
		$group     = isset( $_POST['group'] ) ? sanitize_key( $_POST['group'] ) : 'day';
		$data      = $this->ds->get_profit_report( $from, $to, $group );
		$comp_data = ( $comp_from && $comp_to ) ? $this->ds->get_profit_report( $comp_from, $comp_to . ' 23:59:59', $group ) : null;
		wp_send_json_success( [ 'current' => $data, 'comparison' => $comp_data ] );
	}

	public function handle_omni_get_refunds_report() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$comp_from = isset( $_POST['comp_from'] ) ? sanitize_text_field( wp_unslash( $_POST['comp_from'] ) ) : '';
		$comp_to   = isset( $_POST['comp_to'] )   ? sanitize_text_field( wp_unslash( $_POST['comp_to'] ) )   : '';
		$data      = $this->ds->get_refunds_report( $from, $to );
		$comp_data = ( $comp_from && $comp_to ) ? $this->ds->get_refunds_report( $comp_from, $comp_to . ' 23:59:59' ) : null;
		wp_send_json_success( [ 'current' => $data, 'comparison' => $comp_data ] );
	}

	public function handle_omni_get_product_costs() {
		$this->verify_nonce();
		$page         = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
		$per_page     = 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$show_no_cost = ! empty( $_POST['show_no_cost'] );

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];
		if ( $search ) {
			$args['s'] = $search;
		}

		$query    = new WP_Query( $args );
		$saved    = get_option( 'omni_product_costs', [] );
		$products = [];

		foreach ( $query->posts as $post ) {
			$product = wc_get_product( $post->ID );
			if ( ! $product ) continue;
			$cost = isset( $saved[ $post->ID ] ) ? floatval( $saved[ $post->ID ] ) : null;
			if ( $show_no_cost && $cost !== null ) continue;
			$products[] = [
				'id'       => $post->ID,
				'name'     => $product->get_name(),
				'sku'      => $product->get_sku(),
				'price'    => $product->get_price(),
				'cost'     => $cost,
				'edit_url' => get_edit_post_link( $post->ID, 'raw' ),
			];
		}

		wp_send_json_success( [
			'products'    => $products,
			'total'       => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'page'        => $page,
		] );
	}

	public function handle_omni_save_product_costs() {
		$this->verify_nonce();
		$raw   = isset( $_POST['costs'] ) ? wp_unslash( $_POST['costs'] ) : '{}';
		$costs = json_decode( $raw, true );
		if ( ! is_array( $costs ) ) {
			wp_send_json_error( [ 'message' => 'Invalid cost data.' ] );
		}
		$saved = get_option( 'omni_product_costs', [] );
		foreach ( $costs as $product_id => $cost ) {
			$product_id          = absint( $product_id );
			$saved[ $product_id ] = round( floatval( $cost ), 4 );
		}
		update_option( 'omni_product_costs', $saved );
		wp_send_json_success( [ 'saved' => count( $costs ) ] );
	}

	public function handle_omni_import_costs_csv() {
		$this->verify_nonce();
		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_send_json_error( [ 'message' => 'No file uploaded.' ] );
		}
		$file    = $_FILES['csv_file']['tmp_name'];
		$handle  = fopen( $file, 'r' );
		if ( ! $handle ) {
			wp_send_json_error( [ 'message' => 'Could not read file.' ] );
		}
		$saved   = get_option( 'omni_product_costs', [] );
		$count   = 0;
		$headers = null;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( $headers === null ) {
				$headers = array_map( 'strtolower', array_map( 'trim', $row ) );
				continue;
			}
			$id_idx   = array_search( 'product_id', $headers, true );
			$cost_idx = array_search( 'cost', $headers, true );
			if ( $id_idx === false || $cost_idx === false ) continue;
			$pid = absint( $row[ $id_idx ] );
			if ( ! $pid ) continue;
			$saved[ $pid ] = round( floatval( $row[ $cost_idx ] ), 4 );
			$count++;
		}
		fclose( $handle );
		update_option( 'omni_product_costs', $saved );
		wp_send_json_success( [ 'imported' => $count ] );
	}

	/* ── Registry / Report Manager handlers ── */

	public function handle_omni_get_report_registry() {
		$this->verify_nonce();
		wp_send_json_success( array_values( Omni_Reports_Registry::get_all() ) );
	}

	public function handle_omni_save_report_meta() {
		$this->verify_nonce();

		$raw = isset( $_POST['report'] ) ? wp_unslash( $_POST['report'] ) : '{}';
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['name'] ) ) {
			wp_send_json_error( [ 'message' => 'Invalid report data.' ] );
		}

		$id = ! empty( $data['id'] ) ? sanitize_key( $data['id'] ) : 'rpt_' . uniqid();

		$report = [
			'id'          => $id,
			'name'        => sanitize_text_field( $data['name'] ),
			'slug'        => sanitize_title( $data['slug'] ?? $data['name'] ),
			'category'    => sanitize_key( $data['category'] ?? 'other' ),
			'version'     => sanitize_text_field( $data['version'] ?? '1.0' ),
			'icon'        => sanitize_text_field( $data['icon'] ?? 'dashicons-chart-bar' ),
			'color'       => sanitize_key( $data['color'] ?? 'teal' ),
			'menu_order'  => absint( $data['menu_order'] ?? 99 ),
			'visible'     => ! empty( $data['visible'] ),
			'type'        => sanitize_key( $data['type'] ?? 'custom' ),
			'required'    => ! empty( $data['required'] ),
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'csv_export'  => ! empty( $data['csv_export'] ),
			'printable'   => ! empty( $data['printable'] ),
			'page_slug'   => sanitize_key( $data['page_slug'] ?? '' ),
			'columns'     => array_map( 'sanitize_key', (array) ( $data['columns'] ?? [] ) ),
		];

		Omni_Reports_Registry::save( $report );
		wp_send_json_success( $report );
	}

	public function handle_omni_delete_report_meta() {
		$this->verify_nonce();
		$id = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Missing report ID.' ] );
		}
		Omni_Reports_Registry::delete( $id );
		wp_send_json_success();
	}

	public function handle_omni_reorder_reports() {
		$this->verify_nonce();

		$raw    = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : '[]';
		$order  = json_decode( $raw, true );
		if ( ! is_array( $order ) ) {
			wp_send_json_error( [ 'message' => 'Invalid order data.' ] );
		}

		$reports = Omni_Reports_Registry::get_all();
		$index   = [];
		foreach ( $reports as $r ) {
			$index[ $r['id'] ] = $r;
		}

		$new_reports = [];
		foreach ( $order as $i => $id ) {
			$id = sanitize_key( $id );
			if ( isset( $index[ $id ] ) ) {
				$index[ $id ]['menu_order'] = ( $i + 1 ) * 10;
				$new_reports[] = $index[ $id ];
				unset( $index[ $id ] );
			}
		}
		// Append any reports not in the order list.
		foreach ( $index as $r ) {
			$new_reports[] = $r;
		}

		update_option( Omni_Reports_Registry::OPTION, $new_reports );
		wp_send_json_success();
	}

	public function handle_omni_install_defaults() {
		$this->verify_nonce();
		Omni_Reports_Registry::install_defaults();
		wp_send_json_success( array_values( Omni_Reports_Registry::get_all() ) );
	}

	/* ── Dashboard Home ── */

	public function handle_omni_get_dashboard_home() {
		$this->verify_nonce();
		[ $from, $to ] = $this->dates();
		$data = $this->ds->get_dashboard_home( $from, $to );
		wp_send_json_success( $data );
	}

	/* ── Stock Tracker ── */

	public function handle_omni_get_stock_report() {
		$this->verify_nonce();
		$products = Omni_Reports_Data_Store::get_stock_report();
		wp_send_json_success( array_values( $products ) );
	}

	public function handle_omni_save_reorder_point() {
		$this->verify_nonce();
		$product_id    = absint( $_POST['product_id'] ?? 0 );
		$reorder_point = absint( $_POST['reorder_point'] ?? 5 );
		if ( ! $product_id ) {
			wp_send_json_error( [ 'message' => 'Invalid product ID.' ] );
		}
		$points               = get_option( 'omni_reorder_points', [] );
		$points[ $product_id ] = $reorder_point;
		update_option( 'omni_reorder_points', $points );
		wp_send_json_success( [ 'product_id' => $product_id, 'reorder_point' => $reorder_point ] );
	}

	public function handle_omni_export_stock_csv() {
		$this->verify_nonce();
		$products = Omni_Reports_Data_Store::get_stock_report();
		$rows     = [];
		foreach ( $products as $p ) {
			$rows[] = [
				'name'          => $p->name,
				'sku'           => $p->sku,
				'stock_qty'     => $p->stock_qty,
				'stock_status'  => $p->stock_status,
				'stock_value'   => $p->stock_value,
				'reorder_point' => $p->reorder_point,
				'needs_reorder' => $p->needs_reorder ? 'Yes' : 'No',
			];
		}
		Omni_Reports_Exporter::output_csv( 'omni-stock-tracker-' . gmdate( 'Y-m-d' ), $rows );
	}

	/* ── Drag-and-Drop Builder layout persistence ── */

	public function handle_omni_save_builder_report() {
		$this->verify_nonce();
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? 'My Report' ) );
		$layout = [];
		if ( ! empty( $_POST['layout'] ) ) {
			$layout = json_decode( wp_unslash( $_POST['layout'] ), true );
			if ( ! is_array( $layout ) ) $layout = [];
		}
		$id = ! empty( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : 'bldr_' . uniqid();

		$report = [
			'id'     => $id,
			'name'   => $name,
			'type'   => 'custom',
			'layout' => $layout,
		];
		Omni_Reports_Registry::save( array_merge( $report, [
			'slug'       => sanitize_title( $name ),
			'category'   => 'other',
			'version'    => '1.0',
			'icon'       => 'dashicons-layout',
			'color'      => 'teal',
			'menu_order' => 200,
			'visible'    => false,
			'required'   => false,
			'description'=> '',
			'page_slug'  => '',
			'csv_export' => false,
			'printable'  => false,
		] ) );
		wp_send_json_success( $report );
	}

	public function handle_omni_load_builder_report() {
		$this->verify_nonce();
		$id      = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$reports = Omni_Reports_Registry::get_all();
		foreach ( $reports as $r ) {
			if ( $r['id'] === $id ) {
				wp_send_json_success( $r );
			}
		}
		wp_send_json_error( [ 'message' => 'Report not found.' ] );
	}
}
