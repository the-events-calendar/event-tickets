<?php
/**
 * Block: RSVP
 * Status Going
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/status/going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link http://m.tri.be/1amo
 *
 * @since 4.9.3
 * @version 4.10.4
 *
 */
$must_login = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();
$going = $must_login ? false : $this->get( 'going' );
?>
<span>
	<button
	class="tribe-block__rsvp__status-button tribe-block__rsvp__status-button--going<?php if ( 'yes' === $going ) { echo ' tribe-active'; }?>"
	<?php echo disabled( 'yes', $going, false ); ?>
	>
		<span><?php echo esc_html_x( 'Going', 'Label for the RSVP going button', 'event-tickets' ); ?></span>
		<?php $this->template( 'blocks/rsvp/status/going-icon' ); ?>
	</button>
</span>
