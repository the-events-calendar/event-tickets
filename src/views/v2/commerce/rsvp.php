<?php
/**
 * Block: RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template   $this
 * @var Tribe__Tickets__Ticket_Object|null $rsvp          The rsvp object or null.
 * @var array                              $active_rsvps  An array containing the active RSVPs.
 * @var string                             $block_html_id The unique HTML id for the block.
 */

defined( 'ABSPATH' ) || die();
// Enqueue assets.
wp_enqueue_style( 'event-tickets-rsvp' );
tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-rsvp-style' );
tribe_asset_enqueue_group( 'tec-tickets-commerce-rsvp' );
tribe_asset_enqueue( 'tribe-tickets-rsvp-style' );
tribe_asset_enqueue( 'tribe-tickets-forms-style' );
tribe_asset_enqueue( 'tribe-common-responsive' );

// Bail if there are no RSVP.
if ( empty( $rsvp ) ) {
	return;
}

// Bail if there are no active RSVP.
if ( empty( $active_rsvps ) ) {
	return;
}

?>

<div
	id="<?php echo esc_attr( $block_html_id ); ?>"
	class="tribe-common event-tickets"
>
	<div
		class="tribe-tickets__rsvp-wrapper"
		data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
	>
		<?php $this->template( 'v2/components/loader/loader' ); ?>
		<?php $this->template( 'v2/commerce/rsvp/content', [ 'rsvp' => $rsvp ] ); ?>

	</div>
</div>
