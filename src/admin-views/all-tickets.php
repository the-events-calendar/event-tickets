<?php
/**
 * All Tickets template.
 *
 * @since  TBD
 *
 * @var \Tribe__Template          $this      Current template object.
 */

$wrapper_classes = [
	'wrap',
	'tec-tickets__admin-all-tickets',
];
?>
<div <?php tribe_classes( $wrapper_classes ); ?>>
	<?php
		$this->template( 'all-tickets/tickets' );
	?>
</div>
