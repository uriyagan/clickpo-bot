<?php
/**
 * Dashboard view.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sessions  = ClickPo_Bot_DB::count( 'sessions' );
$messages  = ClickPo_Bot_DB::count( 'messages' );
$knowledge = ClickPo_Bot_DB::count( 'knowledge' );
$packages  = ClickPo_Bot_DB::count( 'packages' );
$blocked   = ClickPo_Bot_DB::count_logs( array( 'blocked', 'rate_limit' ) );
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'ClickPo Chatbot', 'clickpo-ai-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Hebrew AI chatbot — info only, powered by Gemini.', 'clickpo-ai-chatbot' ); ?></p>

	<div class="clickpo-bot-cards">
		<div class="clickpo-bot-card">
			<span class="num"><?php echo esc_html( number_format_i18n( $sessions ) ); ?></span>
			<span class="label"><?php esc_html_e( 'Conversations', 'clickpo-ai-chatbot' ); ?></span>
		</div>
		<div class="clickpo-bot-card">
			<span class="num"><?php echo esc_html( number_format_i18n( $messages ) ); ?></span>
			<span class="label"><?php esc_html_e( 'Messages', 'clickpo-ai-chatbot' ); ?></span>
		</div>
		<div class="clickpo-bot-card">
			<span class="num"><?php echo esc_html( number_format_i18n( $knowledge ) ); ?></span>
			<span class="label"><?php esc_html_e( 'Knowledge entries', 'clickpo-ai-chatbot' ); ?></span>
		</div>
		<div class="clickpo-bot-card">
			<span class="num"><?php echo esc_html( number_format_i18n( $packages ) ); ?></span>
			<span class="label"><?php esc_html_e( 'Packages', 'clickpo-ai-chatbot' ); ?></span>
		</div>
		<div class="clickpo-bot-card">
			<span class="num"><?php echo esc_html( number_format_i18n( $blocked ) ); ?></span>
			<span class="label"><?php esc_html_e( 'Blocked attempts', 'clickpo-ai-chatbot' ); ?></span>
		</div>
	</div>

	<?php if ( ! ClickPo_Bot_Settings::get( 'api_key' ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php
				printf(
					/* translators: %s: settings page link */
					esc_html__( 'No Gemini API key set yet. Add it in %s to activate the bot.', 'clickpo-ai-chatbot' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=clickpo-bot-settings' ) ) . '">' . esc_html__( 'Settings', 'clickpo-ai-chatbot' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
