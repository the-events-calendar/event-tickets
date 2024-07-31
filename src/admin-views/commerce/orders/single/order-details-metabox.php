<?php
use Tribe__Date_Utils;

$ts = strtotime( $order->post_date_gmt );

$post_date  = Tribe__Date_Utils::reformat( $ts, Tribe__Date_Utils::DATEONLYFORMAT );
$post_date .= ' ' . esc_html__( 'at', 'event-ticktets' ) . ' ';
$post_date .= Tribe__Date_Utils::reformat( $ts, 'g:iA' );

?>
<h2><?php printf( esc_html__( 'Order #%d details', 'event-tickets' ), (int) $order->ID ); ?></h2>
<div class="tec-tickets-commerce-single-order tec-tickets-commerce-single-order--details">
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
			<a class="tribe-dashicons" href="javascript:void">
				<span class="dashicons dashicons-edit"></span>
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
		</div>
		<div class="tec-tickets-commerce-single-order--details--item--value">
			<?php
			$order->purchaser['full_name'] = trim( $order->purchaser['full_name'] );
			printf(
				'%1$s%2$s<a href="mailto:%3$s">%4$s</a>',
				esc_html( $order->purchaser['full_name'] ),
				$order->purchaser['full_name'] ? '<br/>' : '', // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped
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
			echo $single_page->get_gateway_label(); // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</div>
<?php
