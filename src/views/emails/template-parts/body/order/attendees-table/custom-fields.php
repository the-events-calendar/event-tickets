<?php
/**
 * Event Tickets Emails: Order Attendee Info
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/attendee-info.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.11
 *
 * @since 5.5.11
 *
 * @var Tribe__Template $this          Current template object.
 * @var array           $order         [Global] The order object.
 * @var bool            $is_tec_active [Global] Whether `The Events Calendar` is active or not.
 */

// @todo @codingmusician: This needs to come from ET+
if ( empty( $attendee['custom_fields'] ) ) {
	return;
}

?>
<?php foreach ( $attendee['custom_fields'] as $custom_field ) : ?>
	<div><?php echo esc_html( $custom_field['label'] ); ?> - <?php echo esc_html( $custom_field['value'] ); ?></div>
<?php endforeach; ?>
