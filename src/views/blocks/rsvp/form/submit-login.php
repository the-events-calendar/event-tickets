<?php
/**
 * Block: RSVP
 * Form Submit Login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/submit-login.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @version 4.9.4
 *
 * @since 4.9.3
 * @since 4.10.8 Fixed text domain for string.
 *
 */
$event_id  = $this->get( 'event_id' );
$ticket_id = $this->get( 'ticket_id' );
$going     = $this->get( 'going' );
// Note: the anchor tag is urlencoded here ('%23tribe-block__rsvp__ticket-') so it passes through the login redirect
?>
<a href="<?php echo esc_url( Tribe__Tickets__Tickets::get_login_url( $event_id ) . '?going=' . $going . '%23tribe-block__rsvp__ticket-' . $ticket_id ); ?>">
	<?php esc_html_e( 'Log in to RSVP', 'event-tickets' ); ?>
</a>
