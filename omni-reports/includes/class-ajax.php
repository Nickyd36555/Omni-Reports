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
		wp_send_json_success( $this->ds->get_coupons_report( $from, $to ) );
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
		wp_send_json_success( $this->ds->get_dashboard_summary( $from, $to ) );
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
}
