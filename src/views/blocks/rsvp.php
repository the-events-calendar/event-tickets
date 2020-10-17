<?php
/**
 * Block: RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.10.8 Updated loading logic for including a renamed template.
 * @since   4.11.0 Added tribe_tickets_order_link_template_already_rendered hook usage to template to prevent duplicate links.
 * @since   TBD Removed duplicated variables.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this                Template object.
 * @var int                              $post_id             [Global] The current Post ID to which RSVPs are attached.
 * @var array                            $attributes          [Global] RSVP attributes (could be empty).
 * @var Tribe__Tickets__Ticket_Object[]  $active_rsvps        [Global] List of RSVPs.
 * @var bool                             $all_past            [Global] True if RSVPs availability dates are all in the past.
 * @var bool                             $has_rsvps           [Global] True if the event has any RSVPs.
 * @var bool                             $has_active_rsvps    [Global] True if the event has any RSVPs available.
 * @var bool                             $must_login          [Global] True if only logged-in users may obtain RSVPs.
 * @var string                           $login_url           [Global] The site's login URL.
 * @var int                              $threshold           [Global] The count at which "number of tickets left" message appears.
 * @var null|string                      $step                [Global] The point we're at in the loading process.
 * @var bool                             $opt_in_checked      [Global] Whether appearing in Attendee List was checked.
 * @var string                           $opt_in_attendee_ids [Global] The list of attendee IDs to send in the form submission.
 * @var string                           $opt_in_nonce        [Global] The nonce for opt-in AJAX requests.
 * @var bool                             $doing_shortcode     [Global] True if detected within context of shortcode output.
 * @var bool                             $block_html_id       [Global] The RSVP block HTML ID. $doing_shortcode may alter it.
 */

$event_id = $post_id;
$rsvps    = $active_rsvps;

// We don't display anything if there is no RSVP
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
if ( ! $already_rendered ) {
	$html = $this->template( 'blocks/attendees/order-links', [], false );

	if ( empty( $html ) ) {
		$html = $this->template( 'blocks/attendees/view-link', [], false );;
	}

	echo $html;

	add_filter( 'tribe_tickets_order_link_template_already_rendered', '__return_true' );
}
?>

<div class="tribe-block tribe-block__rsvp">
	<?php if ( $has_active_rsvps ) : ?>
		<?php foreach ( $rsvps as $rsvp ) : ?>
			<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $rsvp->ID ); ?>">
				<?php $this->template( 'blocks/rsvp/icon' ); ?>
				<?php $this->template( 'blocks/rsvp/content', [ 'ticket' => $rsvp ] ); ?>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="tribe-block__rsvp__ticket tribe-block__rsvp__ticket--inactive">
			<?php $this->template( 'blocks/rsvp/icon' ); ?>
			<?php $this->template( 'blocks/rsvp/content-inactive', [ 'all_past' => $all_past ] ); ?>
		</div>
	<?php endif; ?>
	<?php
		ob_start();
		/**
		 * Allows filtering of extra classes used on the rsvp-block loader.
		 *
		 * @since  4.11.1
		 *
		 * @param  array $classes The array of classes that will be filtered.
		 */
		$loader_classes = apply_filters( 'tribe_rsvp_block_loader_classes', [ 'tribe-block__rsvp__loading' ] );
		include Tribe__Tickets__Templates::get_template_hierarchy( 'components/loader.php' );
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	?>
</div>
