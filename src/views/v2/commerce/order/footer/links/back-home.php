<?php
/**
 * Tickets Commerce: Success Order Page Footer Links > Back home.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/footer/links/back-home.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

?>
<a
	class="tribe-common-anchor-alt tribe-tickets__commerce-order-footer-link tribe-tickets__commerce-order-footer-link--back-home"
	href="<?php echo esc_url( home_url() ); ?>">
	<?php esc_html_e( 'back home', 'event-tickets' ); ?>
</a>
