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

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-logo">
		<img
			class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-logo-image"
			src="<?php echo esc_url( $gateway->get_logo_url() ); ?>"
			alt="<?php echo esc_attr( $gateway::get_label() ); ?>" />
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-subtitle">
		<?php echo $gateway->get_subtitle(); ?>
	</div>
</div>
