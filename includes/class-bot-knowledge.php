<?php
/**
 * Knowledge retrieval — builds the grounding block for the prompt.
 *
 * Phase 3 uses a simple strategy: include all active entries (optionally
 * keyword-ranked), capped to a character budget. RAG/embeddings can be
 * layered in later without changing the public API.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Knowledge
 */
class ClickPo_Bot_Knowledge {

	const CHAR_BUDGET = 12000;

	/**
	 * Build a text block of relevant knowledge for the given user message.
	 *
	 * @param string $message Latest user message (used for light ranking).
	 * @return string
	 */
	public static function context_block( $message ) {
		$rows = ClickPo_Bot_DB::get_knowledge_list( true );
		if ( empty( $rows ) ) {
			return '';
		}

		$ranked = self::rank( $rows, $message );

		$block  = '';
		$budget = self::CHAR_BUDGET;
		foreach ( $ranked as $row ) {
			$piece = '### ' . $row->title . "\n" . $row->content . "\n\n";
			if ( mb_strlen( $piece ) > $budget ) {
				break;
			}
			$block  .= $piece;
			$budget -= mb_strlen( $piece );
		}
		return trim( $block );
	}

	/**
	 * Light keyword ranking: entries whose keywords/title appear in the
	 * message float to the top. Stable for the rest.
	 *
	 * @param array  $rows    Knowledge rows.
	 * @param string $message User message.
	 * @return array
	 */
	private static function rank( $rows, $message ) {
		$msg = mb_strtolower( $message );

		$scored = array();
		foreach ( $rows as $i => $row ) {
			$score = 0;
			$terms = array_filter( array_map( 'trim', explode( ',', (string) $row->keywords ) ) );
			$terms[] = $row->title;
			foreach ( $terms as $term ) {
				$term = mb_strtolower( trim( $term ) );
				if ( '' !== $term && false !== mb_strpos( $msg, $term ) ) {
					$score++;
				}
			}
			$scored[] = array( 'score' => $score, 'i' => $i, 'row' => $row );
		}

		usort(
			$scored,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return $a['i'] - $b['i'];
				}
				return $b['score'] - $a['score'];
			}
		);

		return array_map(
			function ( $s ) {
				return $s['row'];
			},
			$scored
		);
	}
}
