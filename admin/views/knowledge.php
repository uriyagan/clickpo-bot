<?php
/**
 * Knowledge base view — list + add/edit/delete.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base = admin_url( 'admin.php?page=clickpo-bot-knowledge' );

// Handle save.
if ( isset( $_POST['clickpo_bot_kb_nonce'] ) && check_admin_referer( 'clickpo_bot_save_kb', 'clickpo_bot_kb_nonce' ) ) {
	ClickPo_Bot_DB::save_knowledge(
		array(
			'id'        => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'title'     => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'content'   => isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '',
			'category'  => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '',
			'keywords'  => isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '',
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
		)
	);
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

// Handle delete.
if ( isset( $_GET['action'], $_GET['id'] ) && 'delete' === $_GET['action'] && check_admin_referer( 'clickpo_bot_delete_kb_' . absint( $_GET['id'] ) ) ) {
	ClickPo_Bot_DB::delete_knowledge( absint( $_GET['id'] ) );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Deleted.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

// Edit target.
$editing = null;
if ( isset( $_GET['action'], $_GET['id'] ) && 'edit' === $_GET['action'] ) {
	$editing = ClickPo_Bot_DB::get_knowledge( absint( $_GET['id'] ) );
}

$list = ClickPo_Bot_DB::get_knowledge_list();
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'Knowledge Base', 'clickpo-ai-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'The approved facts the bot is allowed to use. It will not invent anything outside this list.', 'clickpo-ai-chatbot' ); ?></p>

	<div class="clickpo-bot-grid">
		<div class="clickpo-bot-col-form">
			<h2><?php echo $editing ? esc_html__( 'Edit entry', 'clickpo-ai-chatbot' ) : esc_html__( 'Add entry', 'clickpo-ai-chatbot' ); ?></h2>
			<form method="post" action="<?php echo esc_url( $base ); ?>">
				<?php wp_nonce_field( 'clickpo_bot_save_kb', 'clickpo_bot_kb_nonce' ); ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $editing ? $editing->id : 0 ); ?>" />
				<p>
					<label><strong><?php esc_html_e( 'Title', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="text" name="title" class="widefat" value="<?php echo esc_attr( $editing ? $editing->title : '' ); ?>" required />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Content (the answer)', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<textarea name="content" rows="6" class="widefat" required><?php echo esc_textarea( $editing ? $editing->content : '' ); ?></textarea>
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Category', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="text" name="category" class="widefat" value="<?php echo esc_attr( $editing ? $editing->category : '' ); ?>" />
				</p>
				<p>
					<label><strong><?php esc_html_e( 'Keywords (comma-separated)', 'clickpo-ai-chatbot' ); ?></strong></label><br />
					<input type="text" name="keywords" class="widefat" value="<?php echo esc_attr( $editing ? $editing->keywords : '' ); ?>" />
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
			<h2><?php esc_html_e( 'Entries', 'clickpo-ai-chatbot' ); ?> (<?php echo esc_html( count( $list ) ); ?>)</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'clickpo-ai-chatbot' ); ?></th>
						<th><?php esc_html_e( 'Category', 'clickpo-ai-chatbot' ); ?></th>
						<th><?php esc_html_e( 'Active', 'clickpo-ai-chatbot' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'clickpo-ai-chatbot' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $list ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No entries yet.', 'clickpo-ai-chatbot' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $list as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row->title ); ?></strong></td>
								<td><?php echo esc_html( $row->category ); ?></td>
								<td><?php echo $row->is_active ? '✓' : '—'; ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $row->id ), $base ) ); ?>"><?php esc_html_e( 'Edit', 'clickpo-ai-chatbot' ); ?></a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $row->id ), $base ), 'clickpo_bot_delete_kb_' . $row->id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this entry?', 'clickpo-ai-chatbot' ) ); ?>');" style="color:#b32d2e;"><?php esc_html_e( 'Delete', 'clickpo-ai-chatbot' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
