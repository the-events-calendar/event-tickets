<?php
/**
 * Block: RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.12.3
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 * @var WP_Post|int                      $post_id      The post object or ID.
 * @var boolean                          $has_rsvps    True if there are RSVPs.
 * @var array                            $active_rsvps An array containing the active RSVPs.
 */

// We don't display anything if there is no RSVP.
if ( ! $has_rsvps ) {
	return false;
}

/**
 * A flag we can set via filter, e.g. at the end of this method, to ensure this template only shows once.
 *
 * @since 4.5.6
 *
 * @param boolean $already_rendered Whether the order link template has already been rendered.
 *
 * @see Tribe__Tickets__Tickets_View::inject_link_template()
 */
$already_rendered = apply_filters( 'tribe_tickets_order_link_template_already_rendered', false );

// Output order links / view link if we haven't already (for RSVPs).
// @todo @juanfra: componetize this.
if ( ! $already_rendered ) {
	$html = $this->template( 'blocks/attendees/order-links', [], false );

	if ( empty( $html ) ) {
		$html = $this->template( 'blocks/attendees/view-link', [], false );
	}

	echo $html;

	add_filter( 'tribe_tickets_order_link_template_already_rendered', '__return_true' );
}

if ( empty( $active_rsvps ) ) {
	return;
}

?>

<div class="tribe-common event-tickets">
	<?php foreach ( $active_rsvps as $rsvp ) : ?>
		<div
			class="tribe-tickets__rsvp-wrapper"
			data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
		>
			<?php $this->template( 'v2/components/loader/loader' ); ?>
			<?php $this->template( 'v2/rsvp/content', [ 'rsvp' => $rsvp ] ); ?>

		</div>
	<?php endforeach; ?>
</div>
