<?php
/**
 * Omni Reports Uninstall
 *
 * Runs when the plugin is deleted via the WordPress admin.
 *
 * @package OmniReports
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete plugin options.
delete_option( 'omni_reports_version' );
delete_option( 'omni_reports_saved_reports' );
