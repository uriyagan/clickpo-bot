<?php
/**
 * Conversations view — list saved chats + transcript viewer.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base = admin_url( 'admin.php?page=clickpo-bot-conversations' );

// Handle actions (delete / flag / unflag).
if ( isset( $_GET['do'], $_GET['id'] ) && check_admin_referer( 'clickpo_bot_conv_' . sanitize_key( $_GET['do'] ) . '_' . absint( $_GET['id'] ) ) ) {
	$id = absint( $_GET['id'] );
	switch ( sanitize_key( $_GET['do'] ) ) {
		case 'delete':
			ClickPo_Bot_DB::delete_session( $id );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Conversation deleted.', 'clickpo-ai-chatbot' ) . '</p></div>';
			break;
		case 'flag':
			ClickPo_Bot_DB::set_session_status( $id, 'flagged' );
			break;
		case 'unflag':
			ClickPo_Bot_DB::set_session_status( $id, 'open' );
			break;
	}
}

// Detail (transcript) view.
$view_id = isset( $_GET['action'], $_GET['id'] ) && 'view' === $_GET['action'] ? absint( $_GET['id'] ) : 0;

if ( $view_id ) {
	$session  = ClickPo_Bot_DB::get_session_by_id( $view_id );
	$messages = $session ? ClickPo_Bot_DB::get_all_messages( $view_id ) : array();
	?>
	<div class="wrap clickpo-bot">
		<h1>
			<?php esc_html_e( 'Conversation', 'clickpo-ai-chatbot' ); ?> #<?php echo esc_html( $view_id ); ?>
			<a href="<?php echo esc_url( $base ); ?>" class="page-title-action"><?php esc_html_e( '← Back to list', 'clickpo-ai-chatbot' ); ?></a>
		</h1>
		<?php if ( ! $session ) : ?>
			<p><?php esc_html_e( 'Not found.', 'clickpo-ai-chatbot' ); ?></p>
		<?php else : ?>
			<p class="description">
				<?php
				printf(
					/* translators: 1: start date, 2: last active date */
					esc_html__( 'Started: %1$s · Last active: %2$s', 'clickpo-ai-chatbot' ),
					esc_html( $session->created_at ),
					esc_html( $session->last_active_at )
				);
				?>
				· <?php echo esc_html( $session->status ); ?>
			</p>
			<div class="clickpo-bot-transcript" dir="rtl">
				<?php foreach ( $messages as $msg ) : ?>
					<div class="clickpo-bot-tr-msg clickpo-bot-tr-<?php echo esc_attr( $msg->role ); ?>">
						<div class="clickpo-bot-tr-role"><?php echo esc_html( 'user' === $msg->role ? __( 'משתמש', 'clickpo-ai-chatbot' ) : __( 'בוט', 'clickpo-ai-chatbot' ) ); ?></div>
						<div class="clickpo-bot-tr-text"><?php echo nl2br( esc_html( $msg->content ) ); ?></div>
						<div class="clickpo-bot-tr-time"><?php echo esc_html( $msg->created_at ); ?></div>
					</div>
				<?php endforeach; ?>
				<?php if ( empty( $messages ) ) : ?>
					<p><?php esc_html_e( 'No messages.', 'clickpo-ai-chatbot' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return;
}

// List view.
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$per_page = 25;
$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset   = ( $paged - 1 ) * $per_page;

$rows  = ClickPo_Bot_DB::get_sessions_list( $per_page, $offset, $search );
$total = ClickPo_Bot_DB::count_sessions();
$pages = max( 1, (int) ceil( $total / $per_page ) );
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'Conversations', 'clickpo-ai-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Saved chats, for your tracking.', 'clickpo-ai-chatbot' ); ?></p>

	<form method="get" style="margin:12px 0;">
		<input type="hidden" name="page" value="clickpo-bot-conversations" />
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search messages…', 'clickpo-ai-chatbot' ); ?>" />
		<?php submit_button( __( 'Search', 'clickpo-ai-chatbot' ), '', '', false ); ?>
		<?php if ( '' !== $search ) : ?>
			<a href="<?php echo esc_url( $base ); ?>" class="button"><?php esc_html_e( 'Clear', 'clickpo-ai-chatbot' ); ?></a>
		<?php endif; ?>
	</form>

	<table class="widefat striped">
		<thead>
			<tr>
				<th>#</th>
				<th><?php esc_html_e( 'Started', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Last active', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Messages', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Status', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'clickpo-ai-chatbot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No conversations yet.', 'clickpo-ai-chatbot' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$view_url   = add_query_arg( array( 'action' => 'view', 'id' => $row->id ), $base );
					$del_url    = wp_nonce_url( add_query_arg( array( 'do' => 'delete', 'id' => $row->id ), $base ), 'clickpo_bot_conv_delete_' . $row->id );
					$is_flagged = ( 'flagged' === $row->status );
					$flag_do    = $is_flagged ? 'unflag' : 'flag';
					$flag_url   = wp_nonce_url( add_query_arg( array( 'do' => $flag_do, 'id' => $row->id ), $base ), 'clickpo_bot_conv_' . $flag_do . '_' . $row->id );
					?>
					<tr<?php echo $is_flagged ? ' style="background:#fff4f4;"' : ''; ?>>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( $row->created_at ); ?></td>
						<td><?php echo esc_html( $row->last_active_at ); ?></td>
						<td><?php echo esc_html( (int) $row->msg_count ); ?></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td>
							<a href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View', 'clickpo-ai-chatbot' ); ?></a>
							|
							<a href="<?php echo esc_url( $flag_url ); ?>"><?php echo $is_flagged ? esc_html__( 'Unflag', 'clickpo-ai-chatbot' ) : esc_html__( 'Flag', 'clickpo-ai-chatbot' ); ?></a>
							|
							<a href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this conversation?', 'clickpo-ai-chatbot' ) ); ?>');" style="color:#b32d2e;"><?php esc_html_e( 'Delete', 'clickpo-ai-chatbot' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%', $base . ( '' !== $search ? '&s=' . rawurlencode( $search ) : '' ) ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $pages,
						'prev_text' => '‹',
						'next_text' => '›',
					)
				)
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
