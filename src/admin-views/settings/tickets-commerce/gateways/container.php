<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this              Template object.
 * @var Gateway[]                    $gateways          Array of gateway objects.
 * @var Manager                      $manager           Gateway Manager object.
 */

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways">
    <?php 
    foreach ($gateways as $gateway) {
        $this->template('item', [ 'gateway' => $gateway, 'manager' => $manager ]);
    } 
    ?>
</div>