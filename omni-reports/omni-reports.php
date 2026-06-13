<?php
/**
 * Plugin Name: Omni Reports
 * Plugin URI:  https://github.com/Nickyd36555/Omni-Reports
 * Description: Comprehensive WooCommerce reporting with 9 standard reports and a custom report builder.
 * Version:     1.0.0
 * Author:      Omni Reports
 * Author URI:  https://github.com/Nickyd36555/Omni-Reports
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: omni-reports
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

define( 'OMNI_REPORTS_VERSION', '1.0.0' );
define( 'OMNI_REPORTS_FILE', __FILE__ );
define( 'OMNI_REPORTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMNI_REPORTS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check WooCommerce is active before loading plugin.
 */
function omni_reports_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Omni Reports requires WooCommerce to be installed and active.', 'omni-reports' ) .
				'</p></div>';
		} );
		return false;
	}
	return true;
}

/**
 * Initialize the plugin.
 */
function omni_reports_init() {
	if ( ! omni_reports_check_woocommerce() ) {
		return;
	}

	require_once OMNI_REPORTS_DIR . 'includes/class-data-store.php';
	require_once OMNI_REPORTS_DIR . 'includes/class-exporter.php';
	require_once OMNI_REPORTS_DIR . 'includes/class-report-builder.php';
	require_once OMNI_REPORTS_DIR . 'includes/class-ajax.php';
	require_once OMNI_REPORTS_DIR . 'includes/class-admin.php';
	require_once OMNI_REPORTS_DIR . 'includes/class-updater.php';
	require_once OMNI_REPORTS_DIR . 'includes/class-omni-reports.php';

	Omni_Reports::instance();
}
add_action( 'plugins_loaded', 'omni_reports_init' );

/**
 * Plugin activation hook.
 */
function omni_reports_activate() {
	// Nothing to create — we rely on WooCommerce tables.
	update_option( 'omni_reports_version', OMNI_REPORTS_VERSION );
}
register_activation_hook( __FILE__, 'omni_reports_activate' );
