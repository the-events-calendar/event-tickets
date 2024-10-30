<?php
/**
 * Series pass header template for ticket form.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/series-pass/header.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var int $post_id  The post ID.
 * @var string $header The header text.
 */

if ( empty( $header ) ) {
	return;
}
?>
<h4 class="tribe-tickets__tickets-form__series-pass-header">
	<?php echo esc_html( $header ); ?>
</h4>