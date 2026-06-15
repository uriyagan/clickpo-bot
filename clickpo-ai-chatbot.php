<?php
/**
 * Plugin Name: ClickPo AI Chatbot
 * Plugin URI:  https://www.clickpo.io
 * Description: Hebrew (RTL) AI chatbot that answers visitors from an approved knowledge base, recommends the best plan, and saves conversations. Info-only, powered by Google Gemini.
 * Version:     0.9.0
 * Author:      ClickPo
 * Text Domain: clickpo-ai-chatbot
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'CLICKPO_BOT_VERSION', '0.9.0' );
define( 'CLICKPO_BOT_FILE', __FILE__ );
define( 'CLICKPO_BOT_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLICKPO_BOT_URL', plugin_dir_url( __FILE__ ) );
define( 'CLICKPO_BOT_BASENAME', plugin_basename( __FILE__ ) );

require_once CLICKPO_BOT_DIR . 'includes/class-bot-activator.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-settings.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-db.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-knowledge.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-recommender.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-spam.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-ai.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-rest.php';
require_once CLICKPO_BOT_DIR . 'includes/class-bot-core.php';
require_once CLICKPO_BOT_DIR . 'admin/class-bot-admin.php';
require_once CLICKPO_BOT_DIR . 'public/class-bot-widget.php';

register_activation_hook( __FILE__, array( 'ClickPo_Bot_Activator', 'activate' ) );

/**
 * Boot the plugin.
 */
function clickpo_bot() {
	return ClickPo_Bot_Core::instance();
}
add_action( 'plugins_loaded', 'clickpo_bot' );
