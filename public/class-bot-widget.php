<?php
/**
 * Frontend floating chat widget.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClickPo_Bot_Widget
 */
class ClickPo_Bot_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * Whether the widget should load.
	 *
	 * Requires an API key. While visibility is set to 'admins' (testing mode),
	 * only logged-in users who can manage options will see the widget.
	 *
	 * @return bool
	 */
	private function enabled() {
		if ( ! ClickPo_Bot_Settings::get( 'api_key' ) ) {
			return false;
		}
		if ( 'everyone' !== ClickPo_Bot_Settings::get( 'visibility', 'admins' ) ) {
			return current_user_can( 'manage_options' );
		}
		return true;
	}

	/**
	 * Enqueue widget CSS/JS and pass config.
	 */
	public function enqueue_assets() {
		if ( ! $this->enabled() ) {
			return;
		}
		$s = ClickPo_Bot_Settings::all();

		wp_enqueue_style( 'clickpo-bot-widget', CLICKPO_BOT_URL . 'public/assets/widget.css', array(), CLICKPO_BOT_VERSION );
		wp_enqueue_script( 'clickpo-bot-widget', CLICKPO_BOT_URL . 'public/assets/widget.js', array(), CLICKPO_BOT_VERSION, true );

		wp_localize_script(
			'clickpo-bot-widget',
			'ClickPoBot',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'clickpo-bot/v1' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'welcome'  => $s['welcome_message'],
				'title'    => $s['header_title'],
				'botName'  => $s['bot_name'],
				'suggestions' => array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s['suggested_questions'] ) ) ) ),
				'color'    => $s['primary_color'],
				'position' => $s['launcher_position'],
				'i18n'     => array(
					'placeholder' => __( 'מקלידים פה...', 'clickpo-ai-chatbot' ),
					'send'        => __( 'שליחה', 'clickpo-ai-chatbot' ),
					'typing'      => __( 'מקליד', 'clickpo-ai-chatbot' ),
					'clearConfirm'=> __( 'למחוק את השיחה ולהתחיל מחדש?', 'clickpo-ai-chatbot' ),
					'error'       => __( 'מצטערים, משהו השתבש. נסה שוב.', 'clickpo-ai-chatbot' ),
				),
			)
		);
	}

	/**
	 * Output the widget container.
	 */
	public function render() {
		if ( ! $this->enabled() ) {
			return;
		}
		$s   = ClickPo_Bot_Settings::all();
		$pos = 'right' === $s['launcher_position'] ? 'right' : 'left';
		?>
		<div id="clickpo-bot-root" class="clickpo-bot-root clickpo-bot-pos-<?php echo esc_attr( $pos ); ?>" dir="rtl" style="--cpb-color: <?php echo esc_attr( $s['primary_color'] ); ?>;">
			<?php
			$icon_mode = $s['launcher_icon'];
			$icon_src  = '';
			if ( 'image' === $icon_mode && ! empty( $s['launcher_icon_url'] ) ) {
				$icon_src = $s['launcher_icon_url'];
			} elseif ( 'custom' === $icon_mode ) {
				$icon_src = CLICKPO_BOT_URL . 'public/assets/chat.svg';
			}
			$use_img = ( '' !== $icon_src );
			?>
			<button id="clickpo-bot-launcher" class="clickpo-bot-launcher<?php echo $use_img ? ' clickpo-bot-launcher--img' : ''; ?>" aria-label="<?php esc_attr_e( 'פתח צ׳אט', 'clickpo-ai-chatbot' ); ?>">
				<?php if ( $use_img ) : ?>
					<img src="<?php echo esc_url( $icon_src ); ?>" alt="" width="60" height="60" />
				<?php else : ?>
					<svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M12 3C6.5 3 2 6.6 2 11c0 2.2 1.1 4.2 3 5.6V21l3.9-2.1c1 .3 2 .5 3.1.5 5.5 0 10-3.6 10-8s-4.5-8-10-8z"/></svg>
				<?php endif; ?>
			</button>

			<div id="clickpo-bot-panel" class="clickpo-bot-panel" hidden>
				<div class="clickpo-bot-header">
					<span class="clickpo-bot-title">
						<span class="clickpo-bot-title-main">
							<?php echo esc_html( $s['header_title'] ); ?>
							<?php if ( 'everyone' !== $s['visibility'] ) : ?>
								<span class="clickpo-bot-testing"><?php esc_html_e( 'מצב בדיקה', 'clickpo-ai-chatbot' ); ?></span>
							<?php endif; ?>
						</span>
						<?php if ( ! empty( $s['header_subtitle'] ) ) : ?>
							<span class="clickpo-bot-subtitle"><?php echo esc_html( $s['header_subtitle'] ); ?></span>
						<?php endif; ?>
					</span>
					<div class="clickpo-bot-actions">
						<button id="clickpo-bot-clear" class="clickpo-bot-iconbtn" aria-label="<?php esc_attr_e( 'מחיקת השיחה', 'clickpo-ai-chatbot' ); ?>" title="<?php esc_attr_e( 'מחיקת השיחה', 'clickpo-ai-chatbot' ); ?>">
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
						</button>
						<button id="clickpo-bot-close" class="clickpo-bot-close" aria-label="<?php esc_attr_e( 'סגור', 'clickpo-ai-chatbot' ); ?>">&times;</button>
					</div>
				</div>
				<div id="clickpo-bot-messages" class="clickpo-bot-messages" aria-live="polite"></div>
				<form id="clickpo-bot-form" class="clickpo-bot-form">
					<input id="clickpo-bot-hp" class="clickpo-bot-hp" type="text" name="hp" tabindex="-1" autocomplete="off" aria-hidden="true" />
					<input id="clickpo-bot-input" class="clickpo-bot-input" type="text" autocomplete="off" />
					<button type="submit" class="clickpo-bot-submit"></button>
				</form>
			</div>
		</div>
		<?php
	}
}
