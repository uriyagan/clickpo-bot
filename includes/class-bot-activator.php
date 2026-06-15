<?php
/**
 * Activation: create database tables and default settings.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Activator
 */
class ClickPo_Bot_Activator {

	/**
	 * Run on activation.
	 */
	public static function activate() {
		self::create_tables();
		self::seed_settings();
		update_option( 'clickpo_bot_db_version', CLICKPO_BOT_VERSION );
	}

	/**
	 * Create all plugin tables via dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix . 'clickpo_bot_';

		$sessions = "CREATE TABLE {$p}sessions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_uid CHAR(36) NOT NULL,
			created_at DATETIME NOT NULL,
			last_active_at DATETIME NOT NULL,
			ip_hash CHAR(64) DEFAULT '',
			user_agent VARCHAR(255) DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			PRIMARY KEY  (id),
			UNIQUE KEY session_uid (session_uid),
			KEY status (status)
		) $charset;";

		$messages = "CREATE TABLE {$p}messages (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id BIGINT UNSIGNED NOT NULL,
			role VARCHAR(10) NOT NULL,
			content LONGTEXT NOT NULL,
			tokens INT DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY session_id (session_id)
		) $charset;";

		$knowledge = "CREATE TABLE {$p}knowledge (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL DEFAULT '',
			content LONGTEXT NOT NULL,
			category VARCHAR(100) DEFAULT '',
			keywords TEXT,
			embedding LONGTEXT,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY is_active (is_active)
		) $charset;";

		$packages = "CREATE TABLE {$p}packages (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL DEFAULT '',
			monthly_price VARCHAR(50) DEFAULT '',
			includes LONGTEXT,
			signup_url VARCHAR(255) DEFAULT '',
			sort_order INT NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY is_active (is_active)
		) $charset;";

		$logs = "CREATE TABLE {$p}logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			type VARCHAR(30) NOT NULL DEFAULT '',
			session_id BIGINT UNSIGNED DEFAULT NULL,
			detail TEXT,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY type (type)
		) $charset;";

		dbDelta( $sessions );
		dbDelta( $messages );
		dbDelta( $knowledge );
		dbDelta( $packages );
		dbDelta( $logs );
	}

	/**
	 * Insert default settings if none exist.
	 */
	public static function seed_settings() {
		if ( false === get_option( 'clickpo_bot_settings', false ) ) {
			add_option( 'clickpo_bot_settings', ClickPo_Bot_Settings::defaults() );
		}
	}
}
