<?php
/**
 * Event Tickets Emails: Order Attendee Info
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/attendee-info.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template  $this  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

// @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.
if ( empty( $attendee['custom_fields'] ) ) {
	return;
}

$fields = [];
foreach ( $attendee['custom_fields'] as $custom_field ) {
	$fields[] = $custom_field['label'] . ' - ' . $custom_field['value'];
}

echo implode( '<br>', $fields );