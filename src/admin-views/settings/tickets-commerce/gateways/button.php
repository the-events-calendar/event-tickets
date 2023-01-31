<?php
/**
 * Template to display a featured gateway.
 *
 * @since 5.3.0
 *
 * @var Tribe__Template  $this    Template object.
 * @var Gateway_Abstract $gateway Gateway object.
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
	'tec-tickets__admin-settings-tickets-commerce-gateways-item-button-link',
	'tec-tickets__admin-settings-tickets-commerce-gateways-item-button-link--active' => $gateway->is_active(),
];

$button_text = sprintf(
	// Translators: %s: Name of payment gateway.
	__( 'Connect to %s', 'event-tickets' ),
	$gateway->get_label()
);

if ( $gateway->is_active() ) {
	$button_text = sprintf(
		// Translators: %s: Name of payment gateway.
		__( 'Edit %s Connection', 'event-tickets' ),
		$gateway->get_label()
	);
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button">
	<a
		<?php tribe_classes( $classes ); ?>
		href="<?php echo esc_url( $gateway->get_settings_url() ); ?>"
	>
		<?php echo esc_html( $button_text ); ?>
	</a>
</div>
