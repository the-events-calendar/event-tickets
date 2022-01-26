<?php
/**
 * Template to display a featured gateway.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this              Template object.
 * @var Gateway_Abstract             $gateway           Gateway object.
 * @var Manager                      $manager           Gateway Manager object.
 */

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;

if( 
    empty( $gateway ) || 
    ! ( $gateway instanceof Abstract_Gateway ) || 
    ! $gateway::should_show() 
) {
    return;
}

$key = $gateway->get_key();
$enabled = $manager->is_gateway_enabled( $gateway );

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item">
    <?php $this->template( 'toggle', ['checked' => $enabled] ); ?>
    <?php $this->template( 'brand' ); ?>
    <?php $this->template( 'button' ); ?>
</div>