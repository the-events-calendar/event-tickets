<?php
/**
 * Block: RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

$event_id         = $this->get( 'post_id' );
$rsvps            = $this->get( 'active_rsvps' );
$has_active_rsvps = $this->get( 'has_active_rsvps' );
$has_rsvps        = $this->get( 'has_rsvps' );
$all_past         = $this->get( 'all_past' );

// We don't display anything if there is no RSVP
if ( ! $has_rsvps ) {
	return false;
}

$html = $this->template( 'blocks/attendees/order-links', [], false );

if ( empty( $html ) ) {
	$html = $this->template( 'blocks/attendees/view-link', [], false );;
}

echo $html;
?>

<div class="tribe-block tribe-block__rsvp">
	<?php if ( $has_active_rsvps ) : ?>
		<?php foreach ( $rsvps as $rsvp ) : ?>
			<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $rsvp->ID ); ?>">
				<?php $this->template( 'blocks/rsvp/icon' ); ?>
				<?php $this->template( 'blocks/rsvp/content', array( 'ticket' => $rsvp ) ); ?>
				<?php $this->template( 'blocks/rsvp/loader' ); ?>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="tribe-block__rsvp__ticket tribe-block__rsvp__ticket--inactive">
			<?php $this->template( 'blocks/rsvp/icon' ); ?>
			<?php $this->template( 'blocks/rsvp/content-inactive', array( 'all_past' => $all_past ) ); ?>
		</div>
	<?php endif; ?>
</div>
