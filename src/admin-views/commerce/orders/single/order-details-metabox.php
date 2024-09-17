<?php
/**
 * Single order - Details metabox.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var WP_Post                                         $order             The current post object.
 * @var \TEC\Tickets\Commerce\Admin\Singular_Order_Page $single_page       The orders table output.
 */

$ts = strtotime( $order->post_date_gmt );

$post_date  = Tribe__Date_Utils::reformat( $ts, Tribe__Date_Utils::DATEONLYFORMAT );
$post_date .= ' ' . esc_html_x( 'at', 'It\'s usage is to separate date from time. For example May 2nd 2024 <b>at</b> 11:35 AM.', 'event-tickets' ) . ' ';
$post_date .= Tribe__Date_Utils::reformat( $ts, 'g:i A' );

?>
<h2>
	<?php
	// translators: %d is the order ID.
	printf( esc_html__( 'Order #%d details', 'event-tickets' ), (int) $order->ID );
	?>
</h2>
<div class="tec-tickets-commerce-single-order--details purchaser-details">
	<div class="tec-tickets-commerce-single-order--details--item">
		<div class="tec-tickets-commerce-single-order--details--item--label">
			<?php esc_html_e( 'Date of purchase', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets-commerce-single-order--details--item--value">
			<?php echo esc_html( $post_date ); ?>
		</div>
	</div>
	<div class="tec-tickets-commerce-single-order--details--item">
		<div class="tec-tickets-commerce-single-order--details--item--label">
			<?php esc_html_e( 'Purchaser', 'event-tickets' ); ?>
			<a data-content="dialog-content-edit-purchaser-modal" data-js="trigger-dialog-edit-purchaser-modal" class="tribe-dashicons">
				<span class="dashicons dashicons-edit"></span>
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
		</div>
		<div class="tec-tickets-commerce-single-order--details--item--value">
			<?php
			$name = $order->purchaser['first_name'] . ' ' . $order->purchaser['last_name'];
			printf(
				'<span class="purchaser-name">%1$s</span>%2$s<span class="purchaser-email"><a href="mailto:%3$s">%4$s</a></span>',
				esc_html( $name ),
				$name ? '<br/>' : '', // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped
				esc_attr( $order->purchaser['email'] ),
				esc_html( $order->purchaser['email'] ),
			);
			?>
		</div>
	</div>
	<div class="tec-tickets-commerce-single-order--details--item">
		<div class="tec-tickets-commerce-single-order--details--item--label">
			<?php esc_html_e( 'Payment', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets-commerce-single-order--details--item--value">
			<?php
			echo $single_page->get_gateway_label( $order ); // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</div>
