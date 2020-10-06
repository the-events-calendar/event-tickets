<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var mixed                              $provider               The e-commerce provider.
 * @var array                              $providers              Array of providers, by event.
 * @var string                             $checkout_url           The checkout URL.
 * @var bool                               $is_meta_up_to_date     True if the meta is up to date.
 * @var bool                               $cart_has_required_meta True if the cart has required meta.
 * @var Tribe__Tickets__Commerce__Currency $currency               The tribe commerce currency object.
 * @var mixed                              $currency_config        Currency configuration for default provider.
 * @var bool                               $is_modal               True if it's in the modal context.
 * @var int                                $non_meta_count         Number of tickets without meta fields.
 */

$provider_class = $this->get_form_class( $provider );
$all_tickets    = [];
$classes        = [
	'tribe-common',
	'event-tickets',
	'tribe-tickets__registration',
];
?>
<div
	<?php tribe_classes( $classes ); ?>
	data-provider="<?php echo esc_attr( $provider ); ?>"
>
	<?php
	/**
	 * Before the output, whether $events is empty.
	 *
	 * @since 4.11.0
	 *
	 * @param string $provider       The 'provider' $_REQUEST var.
	 * @param string $provider_class The class string or empty string if ticket provider is not found.
	 * @param array  $events         The array of events, which might be empty.
	 */
	do_action( 'tribe_tickets_registration_content_before_all_events', $provider, $provider_class, $events );
	?>

	<div class="tribe-common-h8 tribe-common-h--alt tribe-tickets__registration__actions">
		<?php $this->template( 'v2/attendee-registration/button/back-to-cart' ); ?>
	</div>

	<?php $this->template( 'v2/attendee-registration/content/title' ); ?>
	<form
		method="post"
		id="tribe-tickets__registration__form"
		action="<?php echo esc_url( $checkout_url ); ?>"
		data-provider="<?php echo esc_attr( $provider ); ?>"
		novalidate
	>
	<div class="tribe-tickets__registration__grid">

		<?php

		$this->template( 'v2/attendee-registration/content/notice' );

		$context = [
			'events'      => $events,
			'provider'    => $provider,
			'provider_id' => $this->get( 'provider_id' ),
		];

		$this->template( 'v2/attendee-registration/mini-cart', $context );
		?>
		<div class="tribe-tickets__registration__content">
			<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />
			<input type="hidden" name="tribe_tickets_ar" value="1" />
			<input type="hidden" name="tribe_tickets_ar_page" value="1" />
			<input type="hidden" name="tribe_tickets_ar_data" value="" id="tribe_tickets_ar_data" />
			<input type="hidden" name="tribe_tickets_provider" value="<?php echo esc_attr( $provider ); ?>" />

			<?php foreach ( $events as $post_id => $tickets ) : ?>

				<?php
				$this->template(
					'v2/attendee-registration/content/event',
					[
						'post_id'  => $post_id,
						'tickets'  => $tickets,
						'provider' => $provider,
					]
				);
				?>

			<?php endforeach; ?>
		</div>
	</div>

	<?php $this->template( 'v2/attendee-registration/footer' ); ?>

	</form>
</div>
<?php

$this->template( 'v2/components/loader/loader' );
