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
	}

	public function register_menus() {
		$cap = 'manage_woocommerce';

		// Single top-level page — all navigation handled inside the plugin.
		add_menu_page(
			__( 'Omni Reports', 'omni-reports' ),
			__( 'Omni Reports', 'omni-reports' ),
			$cap,
			'omni-reports',
			[ $this, 'render_page' ],
			'dashicons-chart-bar',
			56
		);

		// Hidden subpages for standard reports (accessible via URL, not sidebar).
		$subpages = [
			'omni-reports-sales'      => [ $this, 'render_page' ],
			'omni-reports-revenue'    => [ $this, 'render_page' ],
			'omni-reports-products'   => [ $this, 'render_page' ],
			'omni-reports-categories' => [ $this, 'render_page' ],
			'omni-reports-customers'  => [ $this, 'render_page' ],
			'omni-reports-orders'     => [ $this, 'render_page' ],
			'omni-reports-coupons'    => [ $this, 'render_page' ],
			'omni-reports-tax'        => [ $this, 'render_page' ],
			'omni-reports-shipping'   => [ $this, 'render_page' ],
			'omni-reports-builder'    => [ $this, 'render_page' ],
		];

		foreach ( $subpages as $slug => $cb ) {
			add_submenu_page( null, '', '', $cap, $slug, $cb );
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

		wp_enqueue_script(
			'omni-reports-manager',
			OMNI_REPORTS_URL . 'assets/js/omni-reports-manager.js',
			[ 'omni-reports-admin' ],
			OMNI_REPORTS_VERSION,
			true
		);

		$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'omni-reports';

		wp_localize_script( 'omni-reports-admin', 'omniReports', [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'omni_reports_nonce' ),
			'currency'    => get_woocommerce_currency_symbol(),
			'currentPage' => $current_page,
			'adminUrl'    => admin_url( 'admin.php' ),
			'categories'  => Omni_Reports_Registry::categories(),
		] );

		if ( $current_page === 'omni-reports-builder' ) {
			wp_enqueue_script(
				'omni-reports-builder',
				OMNI_REPORTS_URL . 'assets/js/omni-reports-builder.js',
				[ 'omni-reports-admin' ],
				OMNI_REPORTS_VERSION,
				true
			);
		}
	}

	/**
	 * Main page router — renders top nav + appropriate template.
	 */
	public function render_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'omni-reports';

		$map = [
			'omni-reports'            => 'page-dashboard',
			'omni-reports-sales'      => 'page-sales',
			'omni-reports-revenue'    => 'page-revenue',
			'omni-reports-products'   => 'page-products',
			'omni-reports-categories' => 'page-categories',
			'omni-reports-customers'  => 'page-customers',
			'omni-reports-orders'     => 'page-orders',
			'omni-reports-coupons'    => 'page-coupons',
			'omni-reports-tax'        => 'page-tax',
			'omni-reports-shipping'   => 'page-shipping',
			'omni-reports-builder'    => 'page-builder',
		];

		$template = $map[ $page ] ?? 'page-dashboard';

		echo '<div class="omni-app">';
		$this->render_top_nav( $page );
		$this->render_sub_nav( $page );
		echo '<div class="omni-page">';
		$this->render( $template );
		echo '</div></div>';
	}

	private function render_top_nav( $current ) {
		$tabs = [
			'omni-reports'         => __( 'Sales Reports', 'omni-reports' ),
			'omni-reports-builder' => __( 'Report Builder', 'omni-reports' ),
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

	private function render( $template ) {
		$path = OMNI_REPORTS_DIR . 'templates/admin/' . $template . '.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}
