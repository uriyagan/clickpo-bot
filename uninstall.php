<?php
/**
 * Uninstall cleanup. Only removes data if the user opted in via a constant.
 * By default it leaves data intact (safe).
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Safety: keep data unless explicitly told to purge.
if ( ! defined( 'CLICKPO_BOT_PURGE_ON_UNINSTALL' ) || ! CLICKPO_BOT_PURGE_ON_UNINSTALL ) {
	return;
}

global $wpdb;
$p = $wpdb->prefix . 'clickpo_bot_';
foreach ( array( 'sessions', 'messages', 'knowledge', 'packages', 'logs' ) as $t ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $p . $t ); // phpcs:ignore WordPress.DB.PreparedSQL
}
delete_option( 'clickpo_bot_settings' );
delete_option( 'clickpo_bot_db_version' );
