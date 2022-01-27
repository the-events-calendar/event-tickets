<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since TBD
 *
 * @var Tribe__Template              $this              Template object.
 * @var array[]                      $sections          Array of section settings.
 */

if ( empty( $sections ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-section-menu">
	<?php 
	foreach ( $sections as $section ) {
		$this->template( 'section/link', $section );
	} 
	?>
</div>