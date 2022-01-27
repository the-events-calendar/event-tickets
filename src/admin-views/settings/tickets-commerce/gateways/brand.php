<?php
/**
 * Template to display a featured gateway.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this              Template object.
 * @var Gateway_Abstract             $gateway           Gateway object.
 */

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;

if ( 
    empty( $gateway ) || 
    ! ( $gateway instanceof Abstract_Gateway ) || 
    ! $gateway::should_show() 
) {
    return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand">
    <div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-logo">
        <img 
            class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-logo-image" 
            src="<?php echo esc_attr( $gateway->get_logo_url() ); ?>" 
            alt="<?php echo esc_attr( $gateway->get_label() ); ?>" />
    </div>
    <div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-brand-subtitle">
        <?php echo $gateway->get_subtitle(); ?>
    </div>
</div>