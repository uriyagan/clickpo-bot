<?php
/**
 * REST API: public chat endpoints.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_REST
 */
class ClickPo_Bot_REST {

	const NS = 'clickpo-bot/v1';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/session',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_session' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/history',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'history' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'session_uid' => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/message',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'message' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'session_uid' => array( 'required' => true, 'type' => 'string' ),
					'message'     => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Raw client IP.
	 *
	 * @return string
	 */
	private function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	/**
	 * Hash the client IP for privacy-friendly rate limiting.
	 *
	 * @return string
	 */
	private function ip_hash() {
		return hash( 'sha256', $this->client_ip() . wp_salt() );
	}

	/**
	 * Whether the client IP is on the admin blocklist.
	 *
	 * @return bool
	 */
	private function ip_blocked() {
		$raw = trim( (string) ClickPo_Bot_Settings::get( 'blocked_ips', '' ) );
		if ( '' === $raw ) {
			return false;
		}
		$list = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $raw ) ) );
		return in_array( $this->client_ip(), $list, true );
	}

	/**
	 * POST /session
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function create_session( WP_REST_Request $req ) {
		$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$uid = ClickPo_Bot_DB::create_session( $this->ip_hash(), $ua );
		if ( ! $uid ) {
			return new WP_REST_Response( array( 'error' => 'session_failed' ), 500 );
		}
		return new WP_REST_Response( array( 'session_uid' => $uid ), 200 );
	}

	/**
	 * GET /history — return saved messages for a session (to restore across pages).
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function history( WP_REST_Request $req ) {
		$uid     = sanitize_text_field( (string) $req->get_param( 'session_uid' ) );
		$session = ClickPo_Bot_DB::get_session( $uid );
		if ( ! $session ) {
			return new WP_REST_Response( array( 'error' => 'invalid_session' ), 404 );
		}
		$out = array();
		foreach ( ClickPo_Bot_DB::get_all_messages( $session->id ) as $m ) {
			if ( 'system' === $m->role ) {
				continue;
			}
			$out[] = array( 'role' => $m->role, 'content' => $m->content );
		}
		return new WP_REST_Response( array( 'messages' => $out ), 200 );
	}

	/**
	 * POST /message
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function message( WP_REST_Request $req ) {
		// Blocked IP.
		if ( $this->ip_blocked() ) {
			ClickPo_Bot_DB::log( 'blocked', 'blocked ip' );
			return new WP_REST_Response( array( 'reply' => __( 'מצטערים, לא ניתן להשתמש בצ׳אט כרגע.', 'clickpo-ai-chatbot' ) ), 200 );
		}

		// Honeypot: real users never fill this hidden field.
		if ( '' !== trim( (string) $req->get_param( 'hp' ) ) ) {
			ClickPo_Bot_DB::log( 'blocked', 'honeypot' );
			return new WP_REST_Response( array( 'reply' => __( 'מצטערים, משהו השתבש.', 'clickpo-ai-chatbot' ) ), 200 );
		}

		$uid     = sanitize_text_field( (string) $req->get_param( 'session_uid' ) );
		$message = trim( wp_strip_all_tags( (string) $req->get_param( 'message' ) ) );

		if ( '' === $message ) {
			return new WP_REST_Response( array( 'reply' => __( 'נא לכתוב הודעה.', 'clickpo-ai-chatbot' ) ), 200 );
		}

		$session = ClickPo_Bot_DB::get_session( $uid );
		if ( ! $session ) {
			return new WP_REST_Response( array( 'error' => 'invalid_session' ), 400 );
		}

		// Spam / abuse checks.
		$check = ClickPo_Bot_Spam::check( $message, $this->ip_hash(), $session->id );
		if ( true !== $check ) {
			return new WP_REST_Response( array( 'reply' => $check ), 200 );
		}

		// Save user message + load context history (excluding the one we just add).
		ClickPo_Bot_DB::add_message( $session->id, 'user', $message );
		$limit   = (int) ClickPo_Bot_Settings::get( 'context_messages', 10 );
		$history = ClickPo_Bot_DB::get_recent_messages( $session->id, $limit + 1 );
		// Drop the just-saved user message from history (it is sent separately).
		array_pop( $history );

		// Generate.
		$ai = ClickPo_Bot_AI::generate( $message, $history );

		if ( ! $ai['ok'] ) {
			ClickPo_Bot_DB::log( 'api_error', $ai['error'], $session->id );
			$fallback = ClickPo_Bot_Settings::get( 'error_message', __( 'מצטערים, אירעה תקלה זמנית. נסה שוב בעוד רגע.', 'clickpo-ai-chatbot' ) );
			if ( '' === trim( (string) $fallback ) ) {
				$fallback = __( 'מצטערים, אירעה תקלה זמנית. נסה שוב בעוד רגע.', 'clickpo-ai-chatbot' );
			}
			ClickPo_Bot_DB::add_message( $session->id, 'assistant', $fallback );
			return new WP_REST_Response( array( 'reply' => $fallback ), 200 );
		}

		$parsed = ClickPo_Bot_Recommender::parse( $ai['text'] );

		ClickPo_Bot_DB::add_message( $session->id, 'assistant', $parsed['reply'] );
		ClickPo_Bot_DB::touch_session( $session->id );

		$out = array( 'reply' => $parsed['reply'] );
		if ( $parsed['cta'] ) {
			$out['cta'] = $parsed['cta'];
		}
		if ( $parsed['recommendation'] ) {
			$out['recommendation'] = $parsed['recommendation'];
		}

		return new WP_REST_Response( $out, 200 );
	}
}
