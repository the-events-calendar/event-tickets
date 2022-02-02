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

if ( ! (  $gateway instanceof Abstract_Gateway  ) ) {
    return;
}

if ( ! $gateway::should_show() ) {
    return;
}

$key         = $gateway->get_key();
$enabled     = $manager->is_gateway_enabled( $gateway );
$button_text = sprintf(
    // Translators: %s: Name of payment gateway.
    __( 'Connect to %s', 'event-tickets' ),
    $gateway->get_label()
);

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button">
    <a 
        class="tec-tickets__admin-settings-tickets-commerce-gateways-item-button-link" 
        href="<?php echo esc_url( $gateway->get_settings_url() ); ?>"
    >
        <?php echo esc_html( $button_text ); ?>
    </a>
</div>