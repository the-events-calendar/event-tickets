<?php
/**
 * Template to display a featured gateway.
 *
 * @since TBD
 *
 * @var Tribe__Template  $this              Template object.
 * @var Gateway_Abstract $gateway           Gateway object.
 * @var Manager          $manager           Gateway Manager object.
 */

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Manager;

if ( empty( $gateway ) ) {
    return;
}

if ( ! (  $gateway instanceof Abstract_Gateway  ) ) {
    return;
}

if ( ! $gateway::should_show() ) {
    return;
}

$key     = $gateway->get_key();
$enabled = $manager->is_gateway_enabled( $gateway );

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item">
    <?php $this->template( 'gateways/toggle', ['checked' => $enabled] ); ?>
    <?php $this->template( 'gateways/brand' ); ?>
    <?php $this->template( 'gateways/button' ); ?>
</div>