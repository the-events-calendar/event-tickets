<?php
/**
 * Help links for featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views $this   Template object.
 * @var array                        $links  Array of links.
 */

if ( empty( $links ) ) {
    return;
}
 
?>

<div class="tec-tickets__admin-settings-featured-links">
	<?php 
	foreach ( $links as $link ) {
		$this->template( 'link', [ 'link' => $link ] ); 
	}
	?>
</div>
