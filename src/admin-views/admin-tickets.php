<?php
/**
 * All Tickets template.
 *
 * @since 5.14.0
 *
 * @version 5.14.0
 *
 * @var \Tribe__Template  $this           Current template object.
 * @var bool              $tickets_exist  Whether tickets exist.
 */

$wrapper_classes = [
	'wrap',
	'tec-tickets__admin-tickets',
];
?>
<div <?php tribe_classes( $wrapper_classes ); ?>>
	<?php
		$this->template( 'admin-tickets/tickets' );
		$this->template( 'admin-tickets/no-tickets' );
	?>
</div>
