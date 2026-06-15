<?php
/**
 * Core bootstrap.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Core
 */
class ClickPo_Bot_Core {

	/**
	 * Singleton instance.
	 *
	 * @var ClickPo_Bot_Core|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return ClickPo_Bot_Core
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: wire up subsystems.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			new ClickPo_Bot_Admin();
		} else {
			new ClickPo_Bot_Widget();
		}

		// REST API (chat endpoints).
		add_action( 'rest_api_init', array( new ClickPo_Bot_REST(), 'register_routes' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'clickpo-ai-chatbot', false, dirname( CLICKPO_BOT_BASENAME ) . '/languages' );
	}
}
