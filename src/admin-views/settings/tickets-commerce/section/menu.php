<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this              Template object.
 * @var array[]                      $sections          Array of section settings.
 */

 if ( empty( $sections ) ) {
     return;
 }

?>
<div class="tec-tickets__admin-settings-tickets-commerce-section-menu">
    <?php 
    foreach ($sections as $section) {
        $this->template( 'link', $section );
    } 
    ?>
</div>