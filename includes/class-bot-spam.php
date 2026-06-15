<?php
/**
 * Anti-spam / abuse checks.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Spam
 */
class ClickPo_Bot_Spam {

	/**
	 * Run all checks for an incoming message.
	 *
	 * @param string $message    User message.
	 * @param string $ip_hash    Hashed client IP.
	 * @param int    $session_id Session id (for logging).
	 * @return true|string True if allowed, or a user-facing refusal string.
	 */
	public static function check( $message, $ip_hash, $session_id = null ) {
		$s = ClickPo_Bot_Settings::all();

		// Length cap.
		if ( mb_strlen( $message ) > (int) $s['max_message_length'] ) {
			ClickPo_Bot_DB::log( 'blocked', 'message too long', $session_id );
			return __( 'ההודעה ארוכה מדי. נסה לקצר.', 'clickpo-ai-chatbot' );
		}

		// Blocklist keywords.
		$blocklist = array_filter( array_map( 'trim', explode( ',', (string) $s['blocklist'] ) ) );
		$lower     = mb_strtolower( $message );
		foreach ( $blocklist as $bad ) {
			$bad = mb_strtolower( $bad );
			if ( '' !== $bad && false !== mb_strpos( $lower, $bad ) ) {
				ClickPo_Bot_DB::log( 'blocked', 'blocklist hit: ' . $bad, $session_id );
				return __( 'מצטערים, לא ניתן לעבד את ההודעה הזו.', 'clickpo-ai-chatbot' );
			}
		}

		// Rate limit per IP (minute + hour) using transients.
		if ( ! self::rate_ok( 'min', $ip_hash, (int) $s['rate_per_minute'], MINUTE_IN_SECONDS ) ) {
			ClickPo_Bot_DB::log( 'rate_limit', 'per-minute limit', $session_id );
			return __( 'יותר מדי הודעות בזמן קצר. נסה שוב בעוד רגע.', 'clickpo-ai-chatbot' );
		}
		if ( ! self::rate_ok( 'hr', $ip_hash, (int) $s['rate_per_hour'], HOUR_IN_SECONDS ) ) {
			ClickPo_Bot_DB::log( 'rate_limit', 'per-hour limit', $session_id );
			return __( 'הגעת למגבלת ההודעות לשעה. נסה שוב מאוחר יותר.', 'clickpo-ai-chatbot' );
		}

		return true;
	}

	/**
	 * Increment + check a rate-limit bucket.
	 *
	 * @param string $scope   Bucket scope key.
	 * @param string $ip_hash Hashed IP.
	 * @param int    $limit   Max hits in the window.
	 * @param int    $window  Window length in seconds.
	 * @return bool True if still under the limit.
	 */
	private static function rate_ok( $scope, $ip_hash, $limit, $window ) {
		if ( $limit <= 0 ) {
			return true;
		}
		$key   = 'clickpo_bot_rl_' . $scope . '_' . $ip_hash;
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, $window );
		return true;
	}
}
