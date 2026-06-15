<?php
/**
 * Admin menu and pages.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Admin
 */
class ClickPo_Bot_Admin {

	const CAP  = 'manage_options';
	const SLUG = 'clickpo-bot';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the admin menu + submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'ClickPo Chatbot', 'clickpo-ai-chatbot' ),
			__( 'ClickPo Chatbot', 'clickpo-ai-chatbot' ),
			self::CAP,
			self::SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			58
		);

		$pages = array(
			''              => array( __( 'Dashboard', 'clickpo-ai-chatbot' ), 'render_dashboard' ),
			'-knowledge'    => array( __( 'Knowledge Base', 'clickpo-ai-chatbot' ), 'render_knowledge' ),
			'-packages'     => array( __( 'Packages', 'clickpo-ai-chatbot' ), 'render_packages' ),
			'-conversations'=> array( __( 'Conversations', 'clickpo-ai-chatbot' ), 'render_conversations' ),
			'-logs'         => array( __( 'Security Log', 'clickpo-ai-chatbot' ), 'render_logs' ),
			'-settings'     => array( __( 'Settings', 'clickpo-ai-chatbot' ), 'render_settings' ),
			'-appearance'   => array( __( 'Appearance', 'clickpo-ai-chatbot' ), 'render_appearance' ),
		);

		foreach ( $pages as $suffix => $cfg ) {
			add_submenu_page(
				self::SLUG,
				$cfg[0],
				$cfg[0],
				self::CAP,
				'' === $suffix ? self::SLUG : self::SLUG . $suffix,
				array( $this, $cfg[1] )
			);
		}
	}

	/**
	 * Enqueue admin CSS/JS only on our pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'clickpo-bot-admin', CLICKPO_BOT_URL . 'admin/assets/admin.css', array(), CLICKPO_BOT_VERSION );

		// The appearance page uses the WP media library for the custom icon upload.
		if ( false !== strpos( $hook, 'appearance' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script( 'clickpo-bot-admin', CLICKPO_BOT_URL . 'admin/assets/admin.js', array( 'jquery' ), CLICKPO_BOT_VERSION, true );
	}

	/* ------------------------------------------------------------------ Renderers */

	/**
	 * Include a view file.
	 *
	 * @param string $view View base name.
	 */
	private function view( $view ) {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$file = CLICKPO_BOT_DIR . 'admin/views/' . $view . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	public function render_dashboard() {
		$this->view( 'dashboard' );
	}
	public function render_knowledge() {
		$this->view( 'knowledge' );
	}
	public function render_packages() {
		$this->view( 'packages' );
	}
	public function render_conversations() {
		$this->view( 'conversations' );
	}
	public function render_logs() {
		$this->view( 'logs' );
	}
	public function render_settings() {
		$this->view( 'settings' );
	}
	public function render_appearance() {
		$this->view( 'appearance' );
	}
}
