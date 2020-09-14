<?php
/**
 * Attendee registration
 * Content > Event
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/event.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var int                             $post_id            The event/post ID.
 * @var Tribe__Tickets__Ticket_Object[] $tickets            List of tickets for the particular event.
 * @var string                          $provider           The provider.
 * @var bool                            $is_meta_up_to_date True if the meta is up to date.
 */

$providers      = wp_list_pluck( $tickets, 'provider' );
$providers_arr  = array_unique( wp_list_pluck( $providers, 'attendee_object' ) );
$provider_class = $this->get_form_class( $provider );

if (
	empty( $provider_class )
	&& ! empty( $providers_arr[ $post_id ] )
) :
	$provider_class = 'tribe-tickets__item__attendee__fields__form--' . $providers_arr[ $post_id ];
endif;

$has_tpp = Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT === $provider || in_array( Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT, $providers_arr, true );

$classes = [
	'tribe-tickets__item__attendee__fields__form',
	'tribe-validation',
	$provider_class,
]

?>
<div
	class="tribe-tickets__registration__event"
	data-event-id="<?php echo esc_attr( $post_id ); ?>"
	data-is-meta-up-to-date="<?php echo absint( $is_meta_up_to_date ); ?>"
>
	<?php $this->template( 'v2/attendee-registration/content/event/summary', [ 'post_id' => $post_id, 'tickets' => $tickets ] ); ?>

	<div class="tribe-tickets__item__attendee__fields">

		<?php $this->template( 'v2/attendee-registration/content/attendees/error', [ 'post_id' => $post_id, 'tickets' => $tickets ] ); ?>

		<div
			<?php tribe_classes( $classes ); ?>
			name="event<?php echo esc_attr( $post_id ); ?>"
		>
			<?php
			foreach ( $tickets as $ticket ) :
				$all_tickets[] = $ticket;

				// Only include tickets with meta.
				if ( ! $ticket->has_meta_enabled() ) {
					continue;
				}
				?>
					<div
						class="tribe-tickets__item__attendee__fields__container"
						data-ticket-id="<?php echo esc_attr( $ticket['id'] ); ?>"
					>
						<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading">
							<?php echo esc_html( get_the_title( $ticket['id'] ) ); ?>
						</h3>
					</div>
			<?php endforeach; ?>

			<?php if ( $has_tpp ) : ?>
				<button type="submit"><?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?></button>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php $this->template( 'v2/attendee-registration/content/attendees/content', [ 'post_id' => $post_id, 'tickets' => $tickets, 'provider' => $providers[0] ] ); ?>