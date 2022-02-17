<?php
/**
 * Template to display a featured gateway.
 *
 * @since TBD
 *
 * @var Tribe__Template  $this              Template object.
 * @var Gateway_Abstract $gateway           Gateway object.
 */

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;

if ( empty( $gateway ) ) {
	return;
}

if ( ! ( $gateway instanceof Abstract_Gateway ) ) {
	return;
}

if ( ! $gateway::should_show() ) {
	return;
}

$classes = [
	'tec-tickets__admin-settings-tickets-commerce-gateways-item-status',
	'tec-tickets__admin-settings-tickets-commerce-gateways-item-status--enabled' => $gateway->is_enabled() && $gateway->is_connected(),
];

?>
<div <?php tribe_classes( $classes ); ?>>
	<?php echo $gateway->get_status_text(); // phpcs:ignore ?>
</div>
