<?php
/**
 * Block: Tickets
 * Footer Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/footer/total.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var WP_Post|int                        $post_id             The post object or ID.
 * @var Tribe__Tickets__Tickets            $provider            The tickets provider class.
 * @var string                             $provider_id         The tickets provider class name.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets             List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale     List of tickets on sale.
 * @var Tribe__Tickets__Commerce__Currency $currency            The Currency instance.
 * @var boolean                            $is_mini             Context of template.
 */

?>
<div class="tribe-common-b2 tribe-tickets__tickets-footer-total">
	<span class="tribe-tickets__tickets-footer-total-label">
		<?php echo esc_html_x( 'Total:', 'Total selected tickets price.', 'event-tickets' ); ?>
	</span>
	<span class="tribe-tickets__tickets-footer-total-wrap">
		<?php echo $currency->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ); ?>
	</span>
</div>
