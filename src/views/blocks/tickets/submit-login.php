<?php
/**
 * Block: Tickets
 * Submit Login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-login.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 *
 * @version TBD
 *
 */

?>
<a class="tribe-common-c-btn tribe-common-c-btn--small" href="<?php echo esc_url( Tribe__Tickets__Tickets::get_login_url() ); ?>">
	<?php echo esc_html_x( 'Log in to purchase', 'login required before purchase', 'event-tickets' ); ?>
</a>
