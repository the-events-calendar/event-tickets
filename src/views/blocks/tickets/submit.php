<?php
/**
 * Block: Tickets
 * Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 *
 * @version TBD
 *
 */
$provider = $this->get( 'provider' );
$must_login = ! is_user_logged_in() && $provider->login_required();
$event_id = $this->get( 'event_id' );
$event = get_post( $event_id );
$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type;
?>

<?php if ( $is_event_page ) : ?>
	<?php if ( $must_login ) : ?>
		<?php $this->template( 'blocks/tickets/submit-login' ); ?>
	<?php elseif ( $is_event_page && Tribe__Settings_Manager::get_option( 'ticket-attendee-modal' ) ) : ?>
		<?php $this->template( 'blocks/tickets/submit-button-modal' ); ?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/submit-button' ); ?>
	<?php endif; ?>
<?php endif; ?>
