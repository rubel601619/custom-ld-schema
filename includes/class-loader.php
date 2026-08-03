<?php
/**
 * Loader class that boots every plugin component.
 *
 * @package Custom_LD_Schema
 */

defined( 'ABSPATH' ) || exit;

class LD_Schema_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var LD_Schema_Loader|null
	 */
	private static $instance = null;

	/**
	 * Settings component.
	 *
	 * @var LD_Schema_Settings|null
	 */
	public $settings;

	/**
	 * Meta box component.
	 *
	 * @var LD_Schema_Metabox|null
	 */
	public $metabox;

	/**
	 * Front-end output component.
	 *
	 * @var LD_Schema_Output|null
	 */
	public $output;

	/**
	 * Admin component.
	 *
	 * @var LD_Schema_Admin|null
	 */
	public $admin;

	/**
	 * Return the singleton instance.
	 *
	 * @return LD_Schema_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load dependencies and wire hooks.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->bootstrap();
	}

	/**
	 * Require all class files.
	 */
	private function load_dependencies() {
		require_once LD_SCHEMA_PATH . 'includes/class-settings.php';
		require_once LD_SCHEMA_PATH . 'includes/class-metabox.php';
		require_once LD_SCHEMA_PATH . 'includes/class-schema-output.php';
		require_once LD_SCHEMA_PATH . 'includes/class-admin.php';
	}

	/**
	 * Instantiate components and initialize their hooks.
	 */
	private function bootstrap() {
		$this->settings = new LD_Schema_Settings();
		$this->metabox  = new LD_Schema_Metabox();
		$this->output   = new LD_Schema_Output();
		$this->admin    = new LD_Schema_Admin();

		$this->settings->init();
		$this->metabox->init();
		$this->output->init();
		$this->admin->init();
	}
}
