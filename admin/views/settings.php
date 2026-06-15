<?php
/**
 * Settings view — AI/model + spam config.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle save.
if ( isset( $_POST['clickpo_bot_settings_nonce'] ) && check_admin_referer( 'clickpo_bot_save_settings', 'clickpo_bot_settings_nonce' ) ) {
	$incoming = array(
		'visibility'         => ( isset( $_POST['visibility'] ) && 'everyone' === $_POST['visibility'] ) ? 'everyone' : 'admins',
		'api_key'            => isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '',
		'model'              => isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'gemini-2.5-flash',
		'context_messages'   => isset( $_POST['context_messages'] ) ? absint( $_POST['context_messages'] ) : 10,
		'temperature'        => isset( $_POST['temperature'] ) ? floatval( $_POST['temperature'] ) : 0.4,
		'system_extra'       => isset( $_POST['system_extra'] ) ? sanitize_textarea_field( wp_unslash( $_POST['system_extra'] ) ) : '',
		'rate_per_minute'    => isset( $_POST['rate_per_minute'] ) ? absint( $_POST['rate_per_minute'] ) : 8,
		'rate_per_hour'      => isset( $_POST['rate_per_hour'] ) ? absint( $_POST['rate_per_hour'] ) : 60,
		'max_message_length' => isset( $_POST['max_message_length'] ) ? absint( $_POST['max_message_length'] ) : 1000,
		'blocklist'          => isset( $_POST['blocklist'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blocklist'] ) ) : '',
		'blocked_ips'        => isset( $_POST['blocked_ips'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blocked_ips'] ) ) : '',
	);
	ClickPo_Bot_Settings::update( $incoming );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

$s = ClickPo_Bot_Settings::all();
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'Chatbot Settings', 'clickpo-ai-chatbot' ); ?></h1>
	<form method="post" action="">
		<?php wp_nonce_field( 'clickpo_bot_save_settings', 'clickpo_bot_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Visibility', 'clickpo-ai-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Who can see the chatbot', 'clickpo-ai-chatbot' ); ?></th>
				<td>
					<label style="display:block;margin-bottom:6px;">
						<input type="radio" name="visibility" value="admins" <?php checked( $s['visibility'], 'admins' ); ?> />
						<?php esc_html_e( 'Admins only (testing) — visible only to logged-in administrators', 'clickpo-ai-chatbot' ); ?>
					</label>
					<label style="display:block;">
						<input type="radio" name="visibility" value="everyone" <?php checked( $s['visibility'], 'everyone' ); ?> />
						<?php esc_html_e( 'Everyone (live) — visible to all visitors', 'clickpo-ai-chatbot' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Keep on “Admins only” while testing. Switch to “Everyone” to launch.', 'clickpo-ai-chatbot' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'AI (Google Gemini)', 'clickpo-ai-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="api_key"><?php esc_html_e( 'Gemini API Key', 'clickpo-ai-chatbot' ); ?></label></th>
				<td>
					<input name="api_key" id="api_key" type="password" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $s['api_key'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Stored server-side only. Never exposed to visitors.', 'clickpo-ai-chatbot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="model"><?php esc_html_e( 'Model', 'clickpo-ai-chatbot' ); ?></label></th>
				<td>
					<select name="model" id="model">
						<?php
						$models = array( 'gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash' );
						foreach ( $models as $m ) {
							printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $m ), selected( $s['model'], $m, false ) );
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="context_messages"><?php esc_html_e( 'Context messages', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="context_messages" id="context_messages" type="number" min="2" max="40" value="<?php echo esc_attr( $s['context_messages'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="temperature"><?php esc_html_e( 'Temperature', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="temperature" id="temperature" type="number" step="0.1" min="0" max="1" value="<?php echo esc_attr( $s['temperature'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="system_extra"><?php esc_html_e( 'Extra system instructions', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><textarea name="system_extra" id="system_extra" rows="4" class="large-text"><?php echo esc_textarea( $s['system_extra'] ); ?></textarea></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Spam protection', 'clickpo-ai-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="rate_per_minute"><?php esc_html_e( 'Max messages / minute', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="rate_per_minute" id="rate_per_minute" type="number" min="1" value="<?php echo esc_attr( $s['rate_per_minute'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="rate_per_hour"><?php esc_html_e( 'Max messages / hour', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="rate_per_hour" id="rate_per_hour" type="number" min="1" value="<?php echo esc_attr( $s['rate_per_hour'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="max_message_length"><?php esc_html_e( 'Max message length', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="max_message_length" id="max_message_length" type="number" min="50" value="<?php echo esc_attr( $s['max_message_length'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="blocklist"><?php esc_html_e( 'Blocked keywords', 'clickpo-ai-chatbot' ); ?></label></th>
				<td>
					<textarea name="blocklist" id="blocklist" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'comma,separated,words', 'clickpo-ai-chatbot' ); ?>"><?php echo esc_textarea( $s['blocklist'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="blocked_ips"><?php esc_html_e( 'Blocked IP addresses', 'clickpo-ai-chatbot' ); ?></label></th>
				<td>
					<textarea name="blocked_ips" id="blocked_ips" rows="3" class="large-text" placeholder="1.2.3.4&#10;5.6.7.8"><?php echo esc_textarea( $s['blocked_ips'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One IP per line. These visitors cannot use the chatbot.', 'clickpo-ai-chatbot' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'clickpo-ai-chatbot' ) ); ?>
	</form>
</div>
