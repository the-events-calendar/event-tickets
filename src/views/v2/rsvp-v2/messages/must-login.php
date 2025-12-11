<?php
/**
 * RSVP V2: Login Required Message
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/messages/must-login.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket     The RSVP ticket object.
 * @var int                           $post_id    The event post ID.
 * @var bool                          $must_login Whether the user has to login to RSVP or not.
 * @var string                        $login_url  The login URL.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

if ( ! $must_login ) {
	return;
}

?>
<div class="tribe-tickets__rsvp-v2-message tribe-tickets__rsvp-v2-message--must-login tribe-common-b3">
	<?php $this->template( 'v2/components/icons/error', [ 'classes' => [ 'tribe-tickets__rsvp-v2-message--must-login-icon' ] ] ); ?>

	<span class="tribe-tickets__rsvp-v2-message-text">
		<strong>
			<?php
			echo esc_html(
				sprintf(
					/* Translators: 1: RSVP label. */
					_x( 'You must be logged in to %1$s.', 'rsvp must login', 'event-tickets' ),
					tribe_get_rsvp_label_singular( 'rsvp_must_login' )
				)
			);
			?>

			<a
				href="<?php echo esc_url( $login_url . '?tribe-tickets__rsvp-v2-' . $ticket->ID ); ?>"
				class="tribe-tickets__rsvp-v2-message-link"
			>
				<?php esc_html_e( 'Log in here', 'event-tickets' ); ?>
			</a>
		</strong>
	</span>
</div>
