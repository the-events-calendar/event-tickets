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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version TBD
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
		<span><?php esc_html_e( 'Going', 'event-tickets' ); ?></span>
		<?php $this->template( 'blocks/rsvp/status/going-icon' ); ?>
	</button>
</span>
