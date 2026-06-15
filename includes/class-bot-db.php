<?php
/**
 * Database access layer.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_DB
 */
class ClickPo_Bot_DB {

	/**
	 * Fully-qualified table name.
	 *
	 * @param string $name Short table name (sessions|messages|knowledge|packages|logs).
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'clickpo_bot_' . $name;
	}

	/* ------------------------------------------------------------------ Sessions */

	/**
	 * Create a new chat session.
	 *
	 * @param string $ip_hash    Hashed IP.
	 * @param string $user_agent UA string.
	 * @return string|false Session UID on success.
	 */
	public static function create_session( $ip_hash = '', $user_agent = '' ) {
		global $wpdb;
		$uid = wp_generate_uuid4();
		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert(
			self::table( 'sessions' ),
			array(
				'session_uid'    => $uid,
				'created_at'     => $now,
				'last_active_at' => $now,
				'ip_hash'        => $ip_hash,
				'user_agent'     => substr( $user_agent, 0, 255 ),
				'status'         => 'open',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return $ok ? $uid : false;
	}

	/**
	 * Get a session row by its public UID.
	 *
	 * @param string $uid Session UID.
	 * @return object|null
	 */
	public static function get_session( $uid ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table( 'sessions' ) . ' WHERE session_uid = %s', $uid )
		);
	}

	/**
	 * Touch last_active_at.
	 *
	 * @param int $session_id Session id.
	 */
	public static function touch_session( $session_id ) {
		global $wpdb;
		$wpdb->update(
			self::table( 'sessions' ),
			array( 'last_active_at' => current_time( 'mysql' ) ),
			array( 'id' => $session_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/* ------------------------------------------------------------------ Messages */

	/**
	 * Insert a message.
	 *
	 * @param int    $session_id Session id.
	 * @param string $role       user|assistant|system.
	 * @param string $content    Message text.
	 * @param int    $tokens     Optional token count.
	 * @return int Inserted id.
	 */
	public static function add_message( $session_id, $role, $content, $tokens = 0 ) {
		global $wpdb;
		$wpdb->insert(
			self::table( 'messages' ),
			array(
				'session_id' => $session_id,
				'role'       => $role,
				'content'    => $content,
				'tokens'     => $tokens,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get the latest N messages for a session, oldest-first.
	 *
	 * @param int $session_id Session id.
	 * @param int $limit      Max messages.
	 * @return array
	 */
	public static function get_recent_messages( $session_id, $limit = 10 ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT role, content FROM ' . self::table( 'messages' ) . ' WHERE session_id = %d ORDER BY id DESC LIMIT %d',
				$session_id,
				$limit
			)
		);
		return array_reverse( $rows ? $rows : array() );
	}

	/* ------------------------------------------------------------------ Knowledge */

	/**
	 * Get knowledge entries.
	 *
	 * @param bool $only_active Return only active rows.
	 * @return array
	 */
	public static function get_knowledge_list( $only_active = false ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . self::table( 'knowledge' );
		if ( $only_active ) {
			$sql .= ' WHERE is_active = 1';
		}
		$sql .= ' ORDER BY updated_at DESC';
		return $wpdb->get_results( $sql );
	}

	/**
	 * Get one knowledge entry.
	 *
	 * @param int $id Row id.
	 * @return object|null
	 */
	public static function get_knowledge( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'knowledge' ) . ' WHERE id = %d', $id ) );
	}

	/**
	 * Insert or update a knowledge entry.
	 *
	 * @param array $data Fields: id?, title, content, category, keywords, is_active.
	 * @return int Row id.
	 */
	public static function save_knowledge( $data ) {
		global $wpdb;
		$row = array(
			'title'      => isset( $data['title'] ) ? $data['title'] : '',
			'content'    => isset( $data['content'] ) ? $data['content'] : '',
			'category'   => isset( $data['category'] ) ? $data['category'] : '',
			'keywords'   => isset( $data['keywords'] ) ? $data['keywords'] : '',
			'is_active'  => ! empty( $data['is_active'] ) ? 1 : 0,
			'updated_at' => current_time( 'mysql' ),
		);
		$fmt = array( '%s', '%s', '%s', '%s', '%d', '%s' );

		if ( ! empty( $data['id'] ) ) {
			$wpdb->update( self::table( 'knowledge' ), $row, array( 'id' => (int) $data['id'] ), $fmt, array( '%d' ) );
			return (int) $data['id'];
		}
		$wpdb->insert( self::table( 'knowledge' ), $row, $fmt );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete a knowledge entry.
	 *
	 * @param int $id Row id.
	 */
	public static function delete_knowledge( $id ) {
		global $wpdb;
		$wpdb->delete( self::table( 'knowledge' ), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/* ------------------------------------------------------------------ Packages */

	/**
	 * Get packages.
	 *
	 * @param bool $only_active Return only active rows.
	 * @return array
	 */
	public static function get_packages_list( $only_active = false ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . self::table( 'packages' );
		if ( $only_active ) {
			$sql .= ' WHERE is_active = 1';
		}
		$sql .= ' ORDER BY sort_order ASC, id ASC';
		return $wpdb->get_results( $sql );
	}

	/**
	 * Get one package.
	 *
	 * @param int $id Row id.
	 * @return object|null
	 */
	public static function get_package( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'packages' ) . ' WHERE id = %d', $id ) );
	}

	/**
	 * Insert or update a package.
	 *
	 * @param array $data Fields: id?, name, monthly_price, includes, signup_url, sort_order, is_active.
	 * @return int Row id.
	 */
	public static function save_package( $data ) {
		global $wpdb;
		$row = array(
			'name'          => isset( $data['name'] ) ? $data['name'] : '',
			'monthly_price' => isset( $data['monthly_price'] ) ? $data['monthly_price'] : '',
			'includes'      => isset( $data['includes'] ) ? $data['includes'] : '',
			'signup_url'    => isset( $data['signup_url'] ) ? $data['signup_url'] : '',
			'sort_order'    => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0,
			'is_active'     => ! empty( $data['is_active'] ) ? 1 : 0,
			'updated_at'    => current_time( 'mysql' ),
		);
		$fmt = array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' );

		if ( ! empty( $data['id'] ) ) {
			$wpdb->update( self::table( 'packages' ), $row, array( 'id' => (int) $data['id'] ), $fmt, array( '%d' ) );
			return (int) $data['id'];
		}
		$wpdb->insert( self::table( 'packages' ), $row, $fmt );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete a package.
	 *
	 * @param int $id Row id.
	 */
	public static function delete_package( $id ) {
		global $wpdb;
		$wpdb->delete( self::table( 'packages' ), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/* ------------------------------------------------------------------ Conversations (admin) */

	/**
	 * Get sessions with message counts, newest first.
	 *
	 * @param int    $limit  Page size.
	 * @param int    $offset Offset.
	 * @param string $search Optional search across message content.
	 * @return array
	 */
	public static function get_sessions_list( $limit = 25, $offset = 0, $search = '' ) {
		global $wpdb;
		$s = self::table( 'sessions' );
		$m = self::table( 'messages' );

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT s.*, COUNT(msg.id) AS msg_count
					 FROM $s s
					 INNER JOIN $m msg ON msg.session_id = s.id
					 WHERE s.id IN ( SELECT DISTINCT session_id FROM $m WHERE content LIKE %s )
					 GROUP BY s.id
					 ORDER BY s.last_active_at DESC
					 LIMIT %d OFFSET %d",
					$like,
					$limit,
					$offset
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, COUNT(msg.id) AS msg_count
				 FROM $s s
				 LEFT JOIN $m msg ON msg.session_id = s.id
				 GROUP BY s.id
				 ORDER BY s.last_active_at DESC
				 LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
	}

	/**
	 * Count total sessions (for pagination).
	 *
	 * @return int
	 */
	public static function count_sessions() {
		return self::count( 'sessions' );
	}

	/**
	 * Get a session row by numeric id.
	 *
	 * @param int $id Session id.
	 * @return object|null
	 */
	public static function get_session_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'sessions' ) . ' WHERE id = %d', $id ) );
	}

	/**
	 * Get all messages for a session, oldest first.
	 *
	 * @param int $session_id Session id.
	 * @return array
	 */
	public static function get_all_messages( $session_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT role, content, created_at FROM ' . self::table( 'messages' ) . ' WHERE session_id = %d ORDER BY id ASC',
				$session_id
			)
		);
	}

	/**
	 * Delete a session and all its messages.
	 *
	 * @param int $id Session id.
	 */
	public static function delete_session( $id ) {
		global $wpdb;
		$id = (int) $id;
		$wpdb->delete( self::table( 'messages' ), array( 'session_id' => $id ), array( '%d' ) );
		$wpdb->delete( self::table( 'sessions' ), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Update a session status (open|closed|flagged).
	 *
	 * @param int    $id     Session id.
	 * @param string $status New status.
	 */
	public static function set_session_status( $id, $status ) {
		global $wpdb;
		$wpdb->update(
			self::table( 'sessions' ),
			array( 'status' => $status ),
			array( 'id' => (int) $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/* ------------------------------------------------------------------ Logs */

	/**
	 * Write a log/security event.
	 *
	 * @param string   $type       Event type.
	 * @param string   $detail     Detail text.
	 * @param int|null $session_id Optional session id.
	 */
	public static function log( $type, $detail = '', $session_id = null ) {
		global $wpdb;
		$wpdb->insert(
			self::table( 'logs' ),
			array(
				'type'       => $type,
				'session_id' => $session_id,
				'detail'     => $detail,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Get log rows, newest first.
	 *
	 * @param int $limit  Page size.
	 * @param int $offset Offset.
	 * @return array
	 */
	public static function get_logs_list( $limit = 50, $offset = 0 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table( 'logs' ) . ' ORDER BY id DESC LIMIT %d OFFSET %d',
				$limit,
				$offset
			)
		);
	}

	/**
	 * Count log rows, optionally limited to given types.
	 *
	 * @param array $types Optional list of types to count.
	 * @return int
	 */
	public static function count_logs( $types = array() ) {
		global $wpdb;
		if ( empty( $types ) ) {
			return self::count( 'logs' );
		}
		$place = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table( 'logs' ) . " WHERE type IN ($place)", $types ) );
	}

	/**
	 * Delete all log rows.
	 */
	public static function clear_logs() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::table( 'logs' ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/* ------------------------------------------------------------------ Counts (dashboard) */

	/**
	 * Count rows in a plugin table.
	 *
	 * @param string $name Short table name.
	 * @return int
	 */
	public static function count( $name ) {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table( $name ) );
	}
}
