<?php
/**
 * Tickets Commerce: Checkout Page Header title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/header/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.1.10
 *
 * @version 5.1.10
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var array[]          $sections              [Global] Which events we have tickets for.
 * @var bool             $must_login            [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url             [Global] The site's login URL.
 * @var string           $registration_url      [Global] The site's registration URL.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways              [Global] An array with the gateways.
 */

$the_title = sprintf(
	// Translators: %1$s: Plural `Tickets` label.
	__( 'Purchase %1$s', 'event-tickets' ),
	tribe_get_ticket_label_plural( 'tickets_commerce_checkout_title' ) // phpcs:ignore
);
?>
<h3 class="tribe-common-h2 tribe-tickets__commerce-checkout-header-title">
	<?php echo esc_html( $the_title ); ?>
</h3>
