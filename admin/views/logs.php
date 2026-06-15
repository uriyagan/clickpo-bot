<?php
/**
 * Security log view — blocked attempts, rate limits, API errors.
 *
 * @package ClickPo_Bot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base = admin_url( 'admin.php?page=clickpo-bot-logs' );

// Clear all.
if ( isset( $_POST['clickpo_bot_clear_logs_nonce'] ) && check_admin_referer( 'clickpo_bot_clear_logs', 'clickpo_bot_clear_logs_nonce' ) ) {
	ClickPo_Bot_DB::clear_logs();
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Log cleared.', 'clickpo-ai-chatbot' ) . '</p></div>';
}

$per_page = 50;
$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset   = ( $paged - 1 ) * $per_page;

$rows  = ClickPo_Bot_DB::get_logs_list( $per_page, $offset );
$total = ClickPo_Bot_DB::count_logs();
$pages = max( 1, (int) ceil( $total / $per_page ) );

$labels = array(
	'blocked'    => __( 'Blocked', 'clickpo-ai-chatbot' ),
	'rate_limit' => __( 'Rate limit', 'clickpo-ai-chatbot' ),
	'api_error'  => __( 'API error', 'clickpo-ai-chatbot' ),
	'flagged'    => __( 'Flagged', 'clickpo-ai-chatbot' ),
);
?>
<div class="wrap clickpo-bot">
	<h1><?php esc_html_e( 'Security Log', 'clickpo-ai-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Blocked attempts, rate-limit hits, and AI errors.', 'clickpo-ai-chatbot' ); ?></p>

	<?php if ( ! empty( $rows ) ) : ?>
		<form method="post" style="margin:10px 0;">
			<?php wp_nonce_field( 'clickpo_bot_clear_logs', 'clickpo_bot_clear_logs_nonce' ); ?>
			<button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Clear the entire log?', 'clickpo-ai-chatbot' ) ); ?>');"><?php esc_html_e( 'Clear log', 'clickpo-ai-chatbot' ); ?></button>
		</form>
	<?php endif; ?>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Time', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Type', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Detail', 'clickpo-ai-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Session', 'clickpo-ai-chatbot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No events logged.', 'clickpo-ai-chatbot' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->created_at ); ?></td>
						<td><?php echo esc_html( isset( $labels[ $row->type ] ) ? $labels[ $row->type ] : $row->type ); ?></td>
						<td><?php echo esc_html( $row->detail ); ?></td>
						<td>
							<?php if ( $row->session_id ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'clickpo-bot-conversations', 'action' => 'view', 'id' => (int) $row->session_id ), admin_url( 'admin.php' ) ) ); ?>">#<?php echo esc_html( (int) $row->session_id ); ?></a>
							<?php else : ?>
								—
							<?php endif; ?>
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
						'base'    => add_query_arg( 'paged', '%#%', $base ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				)
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
