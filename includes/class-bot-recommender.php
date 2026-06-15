<?php
/**
 * Package recommender — provides the packages block for the prompt and
 * resolves a recommendation tag emitted by the model into a CTA.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Recommender
 */
class ClickPo_Bot_Recommender {

	/**
	 * Build a text block describing the active packages for the prompt.
	 *
	 * @return string
	 */
	public static function packages_block() {
		$rows = ClickPo_Bot_DB::get_packages_list( true );
		if ( empty( $rows ) ) {
			return '';
		}
		$block = '';
		foreach ( $rows as $row ) {
			$block .= '### ' . $row->name . "\n";
			if ( $row->monthly_price ) {
				$block .= 'מחיר חודשי: ' . $row->monthly_price . "\n";
			}
			if ( $row->includes ) {
				$block .= 'כולל: ' . $row->includes . "\n";
			}
			$block .= "\n";
		}
		return trim( $block );
	}

	/**
	 * Extract a [[RECOMMEND: name]] tag from the model reply and resolve it
	 * to a package CTA. Returns the cleaned reply plus an optional cta.
	 *
	 * @param string $reply Raw model reply.
	 * @return array { 'reply' => string, 'cta' => array|null, 'recommendation' => array|null }
	 */
	public static function parse( $reply ) {
		$cta            = null;
		$recommendation = null;

		if ( preg_match( '/\[\[\s*RECOMMEND\s*:\s*(.+?)\s*\]\]/u', $reply, $m ) ) {
			$name  = trim( $m[1] );
			$reply = trim( str_replace( $m[0], '', $reply ) );

			$pkg = self::find_package_by_name( $name );
			if ( $pkg ) {
				$recommendation = array(
					'name'  => $pkg->name,
					'price' => $pkg->monthly_price,
				);
				if ( $pkg->signup_url ) {
					$cta = array(
						'label' => sprintf(
							/* translators: %s: plan name */
							__( 'הרשמה ל%s', 'clickpo-ai-chatbot' ),
							$pkg->name
						),
						'url'   => $pkg->signup_url,
					);
				}
			}
		}

		return array(
			'reply'          => $reply,
			'cta'            => $cta,
			'recommendation' => $recommendation,
		);
	}

	/**
	 * Find an active package by (case-insensitive) name match.
	 *
	 * @param string $name Package name from the model.
	 * @return object|null
	 */
	private static function find_package_by_name( $name ) {
		$name = mb_strtolower( trim( $name ) );
		foreach ( ClickPo_Bot_DB::get_packages_list( true ) as $row ) {
			if ( mb_strtolower( $row->name ) === $name ) {
				return $row;
			}
		}
		// Loose contains match as a fallback.
		foreach ( ClickPo_Bot_DB::get_packages_list( true ) as $row ) {
			if ( false !== mb_strpos( mb_strtolower( $row->name ), $name ) || false !== mb_strpos( $name, mb_strtolower( $row->name ) ) ) {
				return $row;
			}
		}
		return null;
	}
}
