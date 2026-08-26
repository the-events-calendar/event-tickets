<?php
/**
 * Block: RSVP
 * Inactive Content
 *
 * Rendered when the RSVP exists but its window is closed or has yet to open,
 * mirroring the legacy `blocks/rsvp/content-inactive.php`.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/content-inactive.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The RSVP ticket object.
 *
 * @since TBD
 *
 * @version TBD
 */

defined( 'ABSPATH' ) || die();

$message = $rsvp->date_is_later()
	? sprintf( _x( '%s are no longer available', 'RSVP block inactive content in the past', 'event-tickets' ), tribe_get_rsvp_label_plural( 'block_inactive_content_past' ) )
	: sprintf( _x( '%s are not yet available', 'RSVP block inactive content', 'event-tickets' ), tribe_get_rsvp_label_plural( 'block_inactive_content' ) );
?>
<div
	class="tribe-common event-tickets"
>
	<div
		class="tribe-tickets__rsvp-wrapper"
		data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
		data-iac="<?php echo esc_attr( $rsvp->iac ); ?>"
	>
		<div class="tribe-tickets__rsvp-message tribe-tickets__rsvp-message--inactive tribe-common-b3">
			<span class="tribe-tickets__rsvp-message-text">
				<?php echo esc_html( $message ); ?>
			</span>
		</div>
	</div>
</div>
