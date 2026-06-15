<?php
/**
 * Packages view — list + add/edit/delete.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base = admin_url( 'admin.php?page=clickpo-bot-packages' );

// Handle save.
if ( isset( $_POST['clickpo_bot_pkg_nonce'] ) && check_admin_referer( 'clickpo_bot_save_pkg', 'clickpo_bot_pkg_nonce' ) ) {
	ClickPo_Bot_DB::save_package(
		array(
			'id'            => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'name'          => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'monthly_price' => isset( $_POST['monthly_price'] ) ? sanitize_text_field( wp_unslash( $_POST['monthly_price'] ) ) : '',
			'includes'      => isset( $_POST['includes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['includes'] ) ) : '',
			'signup_url'    => isset( $_POST['signup_url'] ) ? esc_url_raw( wp_unslash( $_POST['signup_url'] ) ) : '',
			'sort_order'    => isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0,
			'is_active'     => isset( $_POST['is_active'] ) ? 1 : 0,
		)
	);
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

// Handle delete.
if ( isset( $_GET['action'], $_GET['id'] ) && 'delete' === $_GET['action'] && check_admin_referer( 'clickpo_bot_delete_pkg_' . absint( $_GET['id'] ) ) ) {
	ClickPo_Bot_DB::delete_package( absint( $_GET['id'] ) );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Deleted.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

// Edit target.
$editing = null;
if ( isset( $_GET['action'], $_GET['id'] ) && 'edit' === $_GET['action'] ) {
	$editing = ClickPo_Bot_DB::get_package( absint( $_GET['id'] ) );
}

$list = ClickPo_Bot_DB::get_packages_list();
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'Packages', 'clickpo-ai-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Your plans. The bot recommends the best match and links to its signup page.', 'clickpo-ai-chatbot' ); ?></p>

	<div class="clickpo-bot-grid">
		<div class="clickpo-bot-col-form">
			<h2><?php echo $editing ? esc_html__( 'Edit package', 'clickpo-ai-chatbot' ) : esc_html__( 'Add package', 'clickpo-ai-chatbot' ); ?></h2>
			<form method="post" action="<?php echo esc_url( $base ); ?>">
				<?php wp_nonce_field( 'clickpo_bot_save_pkg', 'clickpo_bot_pkg_nonce' ); ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $editing ? $editing->id : 0 ); ?>" />
				<p>
					<label><strong><?php esc_html_e( 'Name', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="text" name="name" class="widefat" value="<?php echo esc_attr( $editing ? $editing->name : '' ); ?>" required />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Monthly price', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="text" name="monthly_price" class="widefat" placeholder="₪99 / חודש" value="<?php echo esc_attr( $editing ? $editing->monthly_price : '' ); ?>" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'What it includes', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<textarea name="includes" rows="5" class="widefat"><?php echo esc_textarea( $editing ? $editing->includes : '' ); ?></textarea>
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Signup link', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="url" name="signup_url" class="widefat" placeholder="https://" value="<?php echo esc_attr( $editing ? $editing->signup_url : '' ); ?>" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Sort order', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="number" name="sort_order" value="<?php echo esc_attr( $editing ? $editing->sort_order : 0 ); ?>" />
				</p>
				<p>
					<label><input type="checkbox" name="is_active" value="1" <?php checked( $editing ? $editing->is_active : 1, 1 ); ?> /> <?php esc_html_e( 'Active', 'clickpo-ai-chatbot' ); ?></label>
				</p>
				<p>
					<?php submit_button( $editing ? __( 'Update', 'clickpo-ai-chatbot' ) : __( 'Add', 'clickpo-ai-chatbot' ), 'primary', 'submit', false ); ?>
					<?php if ( $editing ) : ?>
						<a href="<?php echo esc_url( $base ); ?>" class="button"><?php esc_html_e( 'Cancel', 'clickpo-ai-chatbot' ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<div class="clickpo-bot-col-list">
			<h2><?php esc_html_e( 'Plans', 'clickpo-ai-chatbot' ); ?> (<?php echo esc_html( count( $list ) ); ?>)</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'clickpo-ai-chatbot' ); ?></th>
						<th><?php esc_html_e( 'Price', 'clickpo-ai-chatbot' ); ?></th>
						<th><?php esc_html_e( 'Active', 'clickpo-ai-chatbot' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'clickpo-ai-chatbot' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $list ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No packages yet.', 'clickpo-ai-chatbot' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $list as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row->name ); ?></strong></td>
								<td><?php echo esc_html( $row->monthly_price ); ?></td>
								<td><?php echo $row->is_active ? '✓' : '—'; ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $row->id ), $base ) ); ?>"><?php esc_html_e( 'Edit', 'clickpo-ai-chatbot' ); ?></a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $row->id ), $base ), 'clickpo_bot_delete_pkg_' . $row->id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this package?', 'clickpo-ai-chatbot' ) ); ?>');" style="color:#b32d2e;"><?php esc_html_e( 'Delete', 'clickpo-ai-chatbot' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
