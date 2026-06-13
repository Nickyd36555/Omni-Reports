<?php
/**
 * Core plugin class.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Omni_Reports
 *
 * Bootstraps all sub-systems via singleton.
 */
class Omni_Reports {

	/** @var Omni_Reports|null */
	private static $instance = null;

	/** @var Omni_Reports_Admin */
	public $admin;

	/** @var Omni_Reports_Ajax */
	public $ajax;

	/**
	 * Get singleton instance.
	 *
	 * @return Omni_Reports
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->admin = new Omni_Reports_Admin();
		$this->ajax  = new Omni_Reports_Ajax();
		new Omni_Reports_Updater();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	public function __wakeup() {}
}
