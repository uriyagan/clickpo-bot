<?php
/**
 * Settings store (single wp_options array).
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Settings
 */
class ClickPo_Bot_Settings {

	const OPTION = 'clickpo_bot_settings';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// AI / Gemini. API key is stored here (server-side only, never sent to the client).
			'api_key'            => '',
			'model'              => 'gemini-2.5-flash',
			'context_messages'   => 10,
			'temperature'        => 0.4,
			// Visibility: 'admins' = only logged-in admins see the widget (testing),
			// 'everyone' = public. Defaults to admins-only until released.
			'visibility'         => 'admins',
			// Behavior.
			'welcome_message'    => 'שלום! איך אפשר לעזור לך היום?',
			'error_message'      => 'מצטערים, אירעה תקלה זמנית. נסה שוב בעוד רגע.',
			'bot_name'           => 'ClickPo',
			'system_extra'       => '',
			// Appearance.
			'primary_color'      => '#4f46e5',
			'launcher_icon'      => 'custom', // 'default' = built-in bubble; 'custom' = bundled chat.svg; 'image' = uploaded.
			'launcher_icon_url'  => '',       // URL of an uploaded custom icon image.
			'launcher_position'  => 'left', // left for RTL by default.
			'header_title'       => 'צ׳אט עם ClickPo',
			'header_subtitle'    => 'הבוט שלנו עונה מהר ובכל שעה',
			'suggested_questions'=> '',
			// Spam.
			'rate_per_minute'    => 8,
			'rate_per_hour'      => 60,
			'max_message_length' => 1000,
			'blocklist'          => '',
			'blocked_ips'        => '',
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : $default;
	}

	/**
	 * Save a partial settings array (merged over existing).
	 *
	 * @param array $values Values to update.
	 */
	public static function update( array $values ) {
		$current = self::all();
		update_option( self::OPTION, array_merge( $current, $values ) );
	}
}
