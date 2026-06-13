<?php
/**
 * Admin — menus, pages, asset enqueuing.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function register_menus() {
		$cap = 'manage_woocommerce';

		add_menu_page(
			__( 'Omni Reports', 'omni-reports' ),
			__( 'Omni Reports', 'omni-reports' ),
			$cap,
			'omni-reports',
			[ $this, 'page_dashboard' ],
			'dashicons-chart-bar',
			56
		);

		$pages = [
			'omni-reports'           => __( 'Dashboard', 'omni-reports' ),
			'omni-reports-sales'     => __( 'Sales', 'omni-reports' ),
			'omni-reports-revenue'   => __( 'Revenue Trends', 'omni-reports' ),
			'omni-reports-products'  => __( 'Products', 'omni-reports' ),
			'omni-reports-categories'=> __( 'Categories', 'omni-reports' ),
			'omni-reports-customers' => __( 'Customers', 'omni-reports' ),
			'omni-reports-orders'    => __( 'Orders', 'omni-reports' ),
			'omni-reports-coupons'   => __( 'Coupons', 'omni-reports' ),
			'omni-reports-tax'       => __( 'Tax', 'omni-reports' ),
			'omni-reports-shipping'  => __( 'Shipping', 'omni-reports' ),
			'omni-reports-builder'   => __( 'Report Builder', 'omni-reports' ),
		];

		$callbacks = [
			'omni-reports'           => 'page_dashboard',
			'omni-reports-sales'     => 'page_sales',
			'omni-reports-revenue'   => 'page_revenue',
			'omni-reports-products'  => 'page_products',
			'omni-reports-categories'=> 'page_categories',
			'omni-reports-customers' => 'page_customers',
			'omni-reports-orders'    => 'page_orders',
			'omni-reports-coupons'   => 'page_coupons',
			'omni-reports-tax'       => 'page_tax',
			'omni-reports-shipping'  => 'page_shipping',
			'omni-reports-builder'   => 'page_builder',
		];

		foreach ( $pages as $slug => $title ) {
			$cb = $callbacks[ $slug ];
			add_submenu_page( 'omni-reports', $title, $title, $cap, $slug, [ $this, $cb ] );
		}
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'omni-reports' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'omni-reports-admin',
			OMNI_REPORTS_URL . 'assets/css/omni-reports-admin.css',
			[],
			OMNI_REPORTS_VERSION
		);

		wp_enqueue_script(
			'chart-js',
			'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
			[],
			'4.4.1',
			true
		);

		wp_enqueue_script(
			'omni-reports-admin',
			OMNI_REPORTS_URL . 'assets/js/omni-reports-admin.js',
			[ 'jquery', 'chart-js' ],
			OMNI_REPORTS_VERSION,
			true
		);

		wp_localize_script( 'omni-reports-admin', 'omniReports', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'omni_reports_nonce' ),
			'currency'  => get_woocommerce_currency_symbol(),
		] );

		if ( strpos( $hook, 'omni-reports-builder' ) !== false ) {
			wp_enqueue_script(
				'omni-reports-builder',
				OMNI_REPORTS_URL . 'assets/js/omni-reports-builder.js',
				[ 'omni-reports-admin' ],
				OMNI_REPORTS_VERSION,
				true
			);
		}
	}

	private function render( $template ) {
		$path = OMNI_REPORTS_DIR . 'templates/admin/' . $template . '.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}

	public function page_dashboard()   { $this->render( 'page-dashboard' ); }
	public function page_sales()       { $this->render( 'page-sales' ); }
	public function page_revenue()     { $this->render( 'page-revenue' ); }
	public function page_products()    { $this->render( 'page-products' ); }
	public function page_categories()  { $this->render( 'page-categories' ); }
	public function page_customers()   { $this->render( 'page-customers' ); }
	public function page_orders()      { $this->render( 'page-orders' ); }
	public function page_coupons()     { $this->render( 'page-coupons' ); }
	public function page_tax()         { $this->render( 'page-tax' ); }
	public function page_shipping()    { $this->render( 'page-shipping' ); }
	public function page_builder()     { $this->render( 'page-builder' ); }
}
