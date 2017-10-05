<?php
/**
 * PayPal Tickets Success content
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/tpp-success.php
 *
 * @package TribeEventsCalendar
 * @version TBD
 *
 * @var string  $purchaser_name
 * @var string  $purchaser_email
 * @var bool    $order_is_valid Whether the current order is a valid one or not.
 * @var bool    $is_event Whether the post the tickets are associated with is an event or not.
 * @var array   $tickets {
 *      @type string $name     The ticket name
 *      @type int    $price    The ticket unit price
 *      @type int    $quantity The number of tickets of this type purchased by the user
 *      @type int    $subtotal The  ticket subtotal
 *      }
 * @var array   $order {
 *      @type int $quantity The total number or purchased tickets
 *      @type int $total    The  order subtotal
 *      }
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$view      = Tribe__Tickets__Tickets_View::instance();
$post_id   = get_the_ID();
$event     = get_post( $post_id );
$post_type = get_post_type_object( $event->post_type );

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type ? true : false;
?>

<div id="tribe-events-content" class="tribe-events-single tpp-success">
	<?php if ( ! $order_is_valid ) : ?>
		<div class="order-recap invalid">
			<p>
				<?php esc_html__( 'The order appears not be a valid one. Please contact the site owner.', 'event-tickets' ) ?>
			</p>
		</div>
	<?php else : ?>
		<div class="order-recap valid">
			<p>
				<?php esc_html_e( 'Thank you for your purchase! You will receive your receipt and tickets via email.', 'event-tickets' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Purchaser Name', 'event-tickets' ) ?>:</strong> <?php echo $purchaser_name ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Purchaser Email', 'event-tickets' ) ?>:</strong> <?php echo antispambot( $purchaser_email ) ?>
			</p>
		</div>
		<table class="tickets">
			<thead>
				<tr>
					<th><?php echo esc_html_x( 'Ticket', 'Success page tickets table header', 'event-tickets' ) ?></th>
					<th><?php echo  esc_html_x( 'Price', 'Success page tickets table header', 'event-tickets' ) ?></th>
					<th><?php echo esc_html_x( 'Quantity', 'Success page tickets table header', 'event-tickets' ) ?></th>
					<th><?php echo esc_html_x( 'Subtotal', 'Success page tickets table header', 'event-tickets' ) ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $tickets as $ticket ) : ?>
				<tr class="ticket">
					<td class="post-details">
						<div class="thumbnail">
							<?php the_post_thumbnail( 'thumbnail' ) ?>
						</div>
						<div class="ticket-name">
							<?php echo $ticket['name'] ?>
						</div>
						<div class="post-permalink">
							<a href="<?php the_permalink( $post_id ) ?>">
								<?php esc_html( the_title() ) ?>
							</a>
						</div>
						<?php if ( $is_event ) : ?>
							<span class="post-date"> - <?php echo tribe_get_start_date( $post_id, false ) ?></span>
						<?php endif; ?>
					</td>
					<td class="ticket-price">
						<div>
							<?php echo tribe_format_currency( $ticket['price'], $post_id ) ?>
						</div>
					</td>
					<td class="ticket-quantity">
						<div>
							<?php echo $ticket['quantity'] ?>
						</div>
					</td>
					<td class="ticket-subtotal">
						<div>
							<?php echo tribe_format_currency( $ticket['subtotal'], $post_id ) ?>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr class="order">
				<td class="empty"></td>
				<td class="title">
					<strong><?php esc_html_e( 'Order Total', 'event-tickets' ) ?></strong>
				</td>
				<td class="quantity">
					<div><?php echo $order['quantity'] ?></div>
				</td>
				<td class="total">
					<div><?php echo tribe_format_currency( $order['total'], $post_id ) ?></div>
				</td>
			</tr>
			</tbody>
		</table>
	<?php endif; ?>
</div><!-- #tribe-events-content -->
