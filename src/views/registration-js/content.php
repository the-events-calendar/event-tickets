<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/content.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */

/** @var Tribe__Tickets__Editor__Template $template */
$template = tribe( 'tickets.editor.template' );

$provider = $this->get( 'provider' ) ?: tribe_get_request_var( 'provider' );

if ( empty( $provider ) ) {
	$event_keys    = array_keys( $events );
	$event_key     = array_shift( $event_keys );
	$provider_name = Tribe__Tickets__Tickets::get_event_ticket_provider( $event_key );
	$provider_obj  = new $provider_name;
	$provider      = $provider_obj->attendee_object;
} elseif ( is_string( $provider ) ) {
	$provider_obj = tribe( 'tickets.attendee_registration.view' )->get_cart_provider( $provider );
	$provider      = $provider_obj->attendee_object;
}

$non_meta_count = 0;
$provider_class = $this->get_form_class( $provider );
$all_tickets    = [];
$classes        = [
	'tribe-common',
	'tribe-tickets__registration',
];
?>
<div <?php tribe_classes( $classes ); ?> data-provider="<?php echo esc_attr( $provider ); ?>">
	<div class="tribe-common-h8 tribe-common-h--alt tribe-tickets__registration__actions">
		<?php $this->template( 'registration/button-cart', array( 'provider' => $provider ) ); ?>
	</div>
	<h1 class="tribe-common-h2 tribe-common-h1--min-medium tribe-common-h--alt tribe-tickets__registration__page-title"><?php esc_html_e( 'Attendee Registration', 'event-tickets' ); ?></h1>


	<div class="tribe-tickets__registration__grid">
		<?php
		$this->template(
			'components/notice',
			[
				'id' => 'tribe-tickets__notice__attendee-modal',
				'notice_classes' => [
					'tribe-tickets__notice--error',
					'tribe-tickets__validation-notice',
				],
				'title' => __( 'Whoops', 'event-tickets' ),
				'content' => sprintf(
					esc_html_x(
						'You have %s ticket(s) with a field that requires information.',
						'Note about missing required fields, %s is the html-wrapped number of tickets.',
						'event-tickets'
					),
					'<span class="tribe-tickets__notice--error__count">1</span>'
			)
			]
		);

		$args = [
			'cart_url'            => $template->get( 'cart_url' ),
			'events'              => $events,
			'has_tickets_on_sale' => $template->get( 'has_tickets_on_sale' ),
			'is_sale_past'        => $template->get( 'is_sale_past' ),
			'post_id'             => $template->get( 'post_id' ),
			'provider_id'         => $template->get( 'provider_id' ),
			'provider'            => $provider,
			'tickets_on_sale'     => $template->get( 'tickets_on_sale' ),
			'tickets'             => $all_tickets,
			'tickets'             => $template->get( 'tickets', [] ),
		];

		$template->template( 'registration-js/mini-cart', $args );
		?>
		<div class="tribe-tickets__registration__content">
			<?php foreach ( $events as $event_id => $tickets ) : ?>
				<?php
					$provider_name = Tribe__Tickets__Tickets::get_event_ticket_provider( $event_id );
					$provider_obj  = new $provider_name; ?>
				<?php if ( $provider !== $provider_obj->attendee_object ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<?php
					$provider_class = $provider_class;
					$providers = wp_list_pluck( $tickets, 'provider' );
					$providers_arr = array_unique( wp_list_pluck( $providers, 'attendee_object' ) );

					if ( empty( $provider_class ) && ! empty( $providers_arr[ $event_id ] ) ) :
						$provider_class = 'tribe-tickets__item__attendee__fields__form--' . $providers_arr[ $event_id ];
					endif;

					$has_tpp = Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === $provider || in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers_arr, true );
				?>
				<div
					class="tribe-tickets__registration__event"
					data-event-id="<?php echo esc_attr( $event_id ); ?>"
					data-is-meta-up-to-date="<?php echo absint( $is_meta_up_to_date ); ?>"
				>
					<?php $this->template( 'registration/summary/content', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>

					<div class="tribe-tickets__item__attendee__fields">

						<?php $this->template( 'registration-js/attendees/error', array( 'event_id' => $event_id, 'tickets' => $tickets ) ); ?>

						<form
							method="post"
							class="tribe-tickets__item__attendee__fields__form <?php echo sanitize_html_class( $provider_class ); ?> tribe-validation"
							name="event<?php echo esc_attr( $event_id ); ?>"
							novalidate
						>
							<input type="hidden" name="tribe_tickets_saving_attendees" value="1" />

							<?php
							foreach ( $tickets as $ticket ) :
								$all_tickets[] = $ticket;
								// Only include tickets with meta
								$has_meta = get_post_meta( $ticket['id'], '_tribe_tickets_meta_enabled', true );

								if ( empty( $has_meta ) || ! tribe_is_truthy( $has_meta ) ) {
									$non_meta_count++;
									continue;
								}
								?>
									<div class="tribe-tickets__item__attendee__fields__container" data-ticket-id="<?php echo esc_attr( $ticket['id'] ); ?>">
										<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading">
											<?php echo esc_html( get_the_title( $ticket['id'] ) ); ?>
										</h3>
									</div>
							<?php endforeach; ?>

							<?php if ( $has_tpp ) : ?>
								<button type="submit"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
							<?php endif; ?>
						</form>
					</div>
				</div>
				<?php $template->template( 'registration-js/attendees/content', array( 'event_id' => $event_id, 'tickets' => $tickets, 'provider' => $providers[0] ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="tribe-tickets__registration__footer">
		<?php
		$notice_classes = [
			'tribe-tickets__notice--non-ar',
		];

		if ( ! empty( $non_meta_count ) ) {
			$notice_classes[] = 'tribe-common-a11y-hidden';
		}

		tribe( 'tickets.editor.template' )->template(
			'components/notice',
			[
				'notice_classes'  => $notice_classes,
				'content' => sprintf(
					esc_html_x(
						'There are %s other tickets in your cart that do not require attendee information.',
						'Note that there are more tickets in the cart, %s is the html-wrapped number.',
						'event-tickets'
					),
					'<span id="tribe-tickets__non-ar-count">' . absint( $non_meta_count ) . '</span>'
				)
			]
		); ?>
		<?php $this->template( 'blocks/tickets/registration/attendee/submit' ); ?>
	</div>
</div>
