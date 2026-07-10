<?php
/**
 * Admin — single top-level menu, asset enqueuing, page routing.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_notices', [ $this, 'reorder_notices' ] );
	}

	public function register_menus() {
		$cap = 'manage_woocommerce';

		add_menu_page(
			__( 'Omni Reports', 'omni-reports' ),
			__( 'Omni Reports', 'omni-reports' ),
			$cap,
			'omni-reports',
			[ $this, 'render_page' ],
			'dashicons-chart-bar',
			56
		);

		// Dashboard (replaces default "Omni Reports" duplicate)
		add_submenu_page( 'omni-reports', __( 'Dashboard', 'omni-reports' ), __( 'Dashboard', 'omni-reports' ), $cap, 'omni-reports', [ $this, 'render_page' ] );

		// Sales Reports group
		add_submenu_page( 'omni-reports', '', '— Sales Reports', $cap, 'omni-reports-sales-group', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Sales Overview', 'omni-reports' ), __( 'Sales Overview', 'omni-reports' ), $cap, 'omni-reports-sales', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Revenue Trends', 'omni-reports' ), __( 'Revenue Trends', 'omni-reports' ), $cap, 'omni-reports-revenue', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Products', 'omni-reports' ), __( 'Products', 'omni-reports' ), $cap, 'omni-reports-products', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Categories', 'omni-reports' ), __( 'Categories', 'omni-reports' ), $cap, 'omni-reports-categories', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Customers', 'omni-reports' ), __( 'Customers', 'omni-reports' ), $cap, 'omni-reports-customers', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Orders', 'omni-reports' ), __( 'Orders', 'omni-reports' ), $cap, 'omni-reports-orders', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Coupons', 'omni-reports' ), __( 'Coupons', 'omni-reports' ), $cap, 'omni-reports-coupons', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Tax', 'omni-reports' ), __( 'Tax', 'omni-reports' ), $cap, 'omni-reports-tax', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Shipping', 'omni-reports' ), __( 'Shipping', 'omni-reports' ), $cap, 'omni-reports-shipping', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Refunds', 'omni-reports' ), __( 'Refunds', 'omni-reports' ), $cap, 'omni-reports-refunds', [ $this, 'render_page' ] );

		// Finance group
		add_submenu_page( 'omni-reports', '', '— Finance', $cap, 'omni-reports-finance-group', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Profit', 'omni-reports' ), __( 'Profit', 'omni-reports' ), $cap, 'omni-reports-profit', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Cost Manager', 'omni-reports' ), __( 'Cost Manager', 'omni-reports' ), $cap, 'omni-reports-costs', [ $this, 'render_page' ] );

		// Inventory group
		add_submenu_page( 'omni-reports', '', '— Inventory', $cap, 'omni-reports-inventory-group', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Stock Tracker', 'omni-reports' ), __( 'Stock Tracker', 'omni-reports' ), $cap, 'omni-reports-stock', [ $this, 'render_page' ] );

		// Tools group
		add_submenu_page( 'omni-reports', '', '— Tools', $cap, 'omni-reports-tools-group', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Report Builder', 'omni-reports' ), __( 'Report Builder', 'omni-reports' ), $cap, 'omni-reports-builder', [ $this, 'render_page' ] );
		add_submenu_page( 'omni-reports', __( 'Report Manager', 'omni-reports' ), __( 'Report Manager', 'omni-reports' ), $cap, 'omni-reports-reports', [ $this, 'render_page' ] );
	}

	public function enqueue_assets( $hook ) {
		// Check hook name OR the page query param (hidden subpages sometimes use different hooks).
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( strpos( $hook, 'omni-reports' ) === false && strpos( $page, 'omni-reports' ) === false ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
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

		wp_enqueue_script(
			'omni-reports-manager',
			OMNI_REPORTS_URL . 'assets/js/omni-reports-manager.js',
			[ 'omni-reports-admin' ],
			OMNI_REPORTS_VERSION,
			true
		);

		$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'omni-reports';

		wp_localize_script( 'omni-reports-admin', 'omniReports', [
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'omni_reports_nonce' ),
			'currency'        => get_woocommerce_currency_symbol(),
			'currentPage'     => $current_page,
			'adminUrl'        => admin_url( 'admin.php' ),
			'categories'      => Omni_Reports_Registry::categories(),
			'columnDefs'      => Omni_Reports_Columns::definitions(),
			'reportColumns'   => $this->get_all_report_columns(),
		] );

		if ( $current_page === 'omni-reports-builder' ) {
			wp_enqueue_script(
				'sortable-js',
				'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
				[],
				'1.15.2',
				true
			);
			wp_enqueue_script(
				'omni-reports-builder',
				OMNI_REPORTS_URL . 'assets/js/omni-reports-builder.js',
				[ 'omni-reports-admin', 'sortable-js' ],
				OMNI_REPORTS_VERSION,
				true
			);
		}

		// Load builder JS on all pages (activates only when #omni-dnd-builder exists).
		wp_enqueue_script(
			'omni-builder',
			OMNI_REPORTS_URL . 'assets/js/omni-builder.js',
			[ 'omni-reports-admin', 'jquery' ],
			OMNI_REPORTS_VERSION,
			true
		);
	}

	/**
	 * Admin notice: warn when products are below reorder point.
	 */
	public function reorder_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		$products     = Omni_Reports_Data_Store::get_stock_report();
		$needs_reorder = array_filter( $products, fn( $p ) => ! empty( $p->needs_reorder ) );
		if ( empty( $needs_reorder ) ) return;
		$count = count( $needs_reorder );
		$url   = admin_url( 'admin.php?page=omni-reports-stock' );
		echo '<div class="notice notice-warning is-dismissible"><p>';
		printf(
			'<strong>Omni Reports:</strong> %d %s below reorder point. <a href="%s">View Stock Tracker &rarr;</a>',
			esc_html( $count ),
			esc_html( $count === 1 ? 'product is' : 'products are' ),
			esc_url( $url )
		);
		echo '</p></div>';
	}

	/**
	 * Main page router — renders top nav + appropriate template.
	 */
	public function render_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'omni-reports';

		// Output stylesheet directly — guarantees it loads even if enqueue hook missed.
		$css_url = OMNI_REPORTS_URL . 'assets/css/omni-reports-admin.css?v=' . OMNI_REPORTS_VERSION;
		echo '<link rel="stylesheet" href="' . esc_url( $css_url ) . '" />';

		// Dashicons (usually already loaded in admin, but ensure it).
		echo '<link rel="stylesheet" href="' . esc_url( includes_url( 'css/dashicons.min.css' ) ) . '" />';

		$map = [
			'omni-reports'                 => 'page-home',
			'omni-reports-reports'         => 'page-dashboard',
			'omni-reports-sales'           => 'page-sales',
			'omni-reports-revenue'         => 'page-revenue',
			'omni-reports-products'        => 'page-products',
			'omni-reports-categories'      => 'page-categories',
			'omni-reports-customers'       => 'page-customers',
			'omni-reports-orders'          => 'page-orders',
			'omni-reports-coupons'         => 'page-coupons',
			'omni-reports-tax'             => 'page-tax',
			'omni-reports-shipping'        => 'page-shipping',
			'omni-reports-builder'         => 'page-builder',
			'omni-reports-profit'          => 'page-profit',
			'omni-reports-refunds'         => 'page-refunds',
			'omni-reports-costs'           => 'page-costs',
			'omni-reports-stock'           => 'page-stock',
			'omni-reports-sales-group'     => 'page-home',
			'omni-reports-finance-group'   => 'page-home',
			'omni-reports-inventory-group' => 'page-home',
			'omni-reports-tools-group'     => 'page-home',
		];

		$template = $map[ $page ] ?? 'page-dashboard';

		echo '<div class="omni-app">';
		$this->render_top_nav( $page );
		echo '<div class="omni-page">';
		$this->render( $template );
		echo '</div></div>';
	}

	private function render_top_nav( $current ) {
		$tabs = [
			'omni-reports'         => __( 'Dashboard', 'omni-reports' ),
			'omni-reports-reports' => __( 'Reports', 'omni-reports' ),
			'omni-reports-builder' => __( 'Report Builder', 'omni-reports' ),
			'omni-reports-costs'   => __( 'Cost Manager', 'omni-reports' ),
		];
		echo '<nav class="omni-top-nav">';
		echo '<a class="omni-top-nav-brand" href="' . esc_url( admin_url( 'admin.php?page=omni-reports' ) ) . '">';
		echo '<div class="omni-logo">O</div>';
		echo '<span>Omni Reports</span>';
		echo '</a>';
		foreach ( $tabs as $slug => $label ) {
			$active = ( $current === $slug ) ? ' active' : '';
			echo '<a class="omni-top-nav-tab' . esc_attr( $active ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
	}

	private function render_sub_nav( $current ) {
		$reports = Omni_Reports_Registry::get_visible();
		if ( empty( $reports ) ) return;

		echo '<nav class="omni-sub-nav">';
		foreach ( $reports as $r ) {
			$slug   = $r['page_slug'] ?? ( 'omni-reports-' . $r['slug'] );
			$active = ( $current === $slug ) ? ' active' : '';
			echo '<a class="omni-sub-nav-link' . esc_attr( $active ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '">' . esc_html( $r['name'] ) . '</a>';
		}
		echo '</nav>';
	}

	private function get_all_report_columns() {
		$out     = [];
		$reports = Omni_Reports_Registry::get_all();
		foreach ( $reports as $r ) {
			$slug = $r['slug'] ?? '';
			$out[ $slug ] = Omni_Reports_Columns::get_enabled( $slug, $r['columns'] ?? [] );
		}
		return $out;
	}

	private function render( $template ) {
		$path = OMNI_REPORTS_DIR . 'templates/admin/' . $template . '.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}
