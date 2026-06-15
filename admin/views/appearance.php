<?php
/**
 * Appearance view — widget branding.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_POST['clickpo_bot_appearance_nonce'] ) && check_admin_referer( 'clickpo_bot_save_appearance', 'clickpo_bot_appearance_nonce' ) ) {
	ClickPo_Bot_Settings::update(
		array(
			'bot_name'          => isset( $_POST['bot_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bot_name'] ) ) : 'ClickPo',
			'header_title'      => isset( $_POST['header_title'] ) ? sanitize_text_field( wp_unslash( $_POST['header_title'] ) ) : '',
			'header_subtitle'   => isset( $_POST['header_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['header_subtitle'] ) ) : '',
			'welcome_message'   => isset( $_POST['welcome_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['welcome_message'] ) ) : '',
			'error_message'     => isset( $_POST['error_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['error_message'] ) ) : '',
			'suggested_questions' => isset( $_POST['suggested_questions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['suggested_questions'] ) ) : '',
			'primary_color'     => isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : '#4f46e5',
			'launcher_icon'     => ( isset( $_POST['launcher_icon'] ) && in_array( $_POST['launcher_icon'], array( 'default', 'custom', 'image' ), true ) ) ? sanitize_key( $_POST['launcher_icon'] ) : 'custom',
			'launcher_icon_url' => isset( $_POST['launcher_icon_url'] ) ? esc_url_raw( wp_unslash( $_POST['launcher_icon_url'] ) ) : '',
			'launcher_position' => ( isset( $_POST['launcher_position'] ) && 'right' === $_POST['launcher_position'] ) ? 'right' : 'left',
		)
	);
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Appearance saved.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

$s = ClickPo_Bot_Settings::all();
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'Appearance', 'clickpo-ai-chatbot' ); ?></h1>
	<form method="post" action="">
		<?php wp_nonce_field( 'clickpo_bot_save_appearance', 'clickpo_bot_appearance_nonce' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bot_name"><?php esc_html_e( 'Bot name', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="bot_name" id="bot_name" type="text" class="regular-text" value="<?php echo esc_attr( $s['bot_name'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="header_title"><?php esc_html_e( 'Header title', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="header_title" id="header_title" type="text" class="regular-text" value="<?php echo esc_attr( $s['header_title'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="header_subtitle"><?php esc_html_e( 'Header subtitle', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="header_subtitle" id="header_subtitle" type="text" class="regular-text" value="<?php echo esc_attr( $s['header_subtitle'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="welcome_message"><?php esc_html_e( 'Welcome message', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><textarea name="welcome_message" id="welcome_message" rows="2" class="large-text"><?php echo esc_textarea( $s['welcome_message'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="error_message"><?php esc_html_e( 'Error message', 'clickpo-ai-chatbot' ); ?></label></th>
				<td>
					<textarea name="error_message" id="error_message" rows="2" class="large-text"><?php echo esc_textarea( $s['error_message'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Shown when the AI service is temporarily unavailable.', 'clickpo-ai-chatbot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="suggested_questions"><?php esc_html_e( 'Suggested questions', 'clickpo-ai-chatbot' ); ?></label></th>
				<td>
					<textarea name="suggested_questions" id="suggested_questions" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'מה המחירים?&#10;איך זה עובד?&#10;יש תקופת ניסיון?', 'clickpo-ai-chatbot' ); ?>"><?php echo esc_textarea( $s['suggested_questions'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One per line. Shown as clickable buttons under the welcome message.', 'clickpo-ai-chatbot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="primary_color"><?php esc_html_e( 'Primary color', 'clickpo-ai-chatbot' ); ?></label></th>
				<td><input name="primary_color" id="primary_color" type="color" value="<?php echo esc_attr( $s['primary_color'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Launcher icon', 'clickpo-ai-chatbot' ); ?></th>
				<td>
					<label style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
						<input type="radio" name="launcher_icon" value="custom" <?php checked( $s['launcher_icon'], 'custom' ); ?> />
						<img src="<?php echo esc_url( CLICKPO_BOT_URL . 'public/assets/chat.svg' ); ?>" alt="" width="28" height="28" />
						<?php esc_html_e( 'Default bubble (pink)', 'clickpo-ai-chatbot' ); ?>
					</label>
					<label style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
						<input type="radio" name="launcher_icon" value="default" <?php checked( $s['launcher_icon'], 'default' ); ?> />
						<?php esc_html_e( 'Solid color circle', 'clickpo-ai-chatbot' ); ?>
					</label>
					<label style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
						<input type="radio" name="launcher_icon" id="clickpo-bot-icon-image-radio" value="image" <?php checked( $s['launcher_icon'], 'image' ); ?> />
						<?php esc_html_e( 'My own image (upload)', 'clickpo-ai-chatbot' ); ?>
					</label>

					<div style="display:flex;align-items:center;gap:10px;">
						<span class="clickpo-bot-icon-thumb" style="display:inline-flex;width:48px;height:48px;border:1px solid #dcdcde;border-radius:8px;align-items:center;justify-content:center;overflow:hidden;background:#fafafa;">
							<img id="clickpo-bot-icon-preview"
								src="<?php echo esc_url( $s['launcher_icon_url'] ); ?>"
								alt=""
								style="max-width:100%;max-height:100%;<?php echo $s['launcher_icon_url'] ? '' : 'display:none;'; ?>" />
						</span>
						<button type="button" class="button" id="clickpo-bot-upload-icon"><?php esc_html_e( 'Upload / choose image', 'clickpo-ai-chatbot' ); ?></button>
						<button type="button" class="button-link" id="clickpo-bot-remove-icon" style="color:#b32d2e;<?php echo $s['launcher_icon_url'] ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'clickpo-ai-chatbot' ); ?></button>
						<input type="hidden" name="launcher_icon_url" id="clickpo_bot_launcher_icon_url" value="<?php echo esc_attr( $s['launcher_icon_url'] ); ?>" />
					</div>
					<p class="description"><?php esc_html_e( 'PNG/SVG recommended, square, ~120×120px. Selecting an image switches the launcher to “My own image”.', 'clickpo-ai-chatbot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Launcher position', 'clickpo-ai-chatbot' ); ?></th>
				<td>
					<label><input type="radio" name="launcher_position" value="left" <?php checked( $s['launcher_position'], 'left' ); ?> /> <?php esc_html_e( 'Bottom-left (RTL)', 'clickpo-ai-chatbot' ); ?></label>
					&nbsp;&nbsp;
					<label><input type="radio" name="launcher_position" value="right" <?php checked( $s['launcher_position'], 'right' ); ?> /> <?php esc_html_e( 'Bottom-right', 'clickpo-ai-chatbot' ); ?></label>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save appearance', 'clickpo-ai-chatbot' ) ); ?>
	</form>
</div>
