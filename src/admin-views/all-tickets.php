<?php
/**
 * All Tickets template.
 *
 * @since  TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template  $this           Current template object.
 * @var bool              $tickets_exist  Whether tickets exist.
 */

$wrapper_classes = [
	'wrap',
	'tec-tickets__admin-all-tickets'            => $tickets_exist,
	'tec-tickets__admin-all-tickets-no-tickets' => ! $tickets_exist,
];
?>
<div <?php tribe_classes( $wrapper_classes ); ?>>
	<?php
		$this->template( 'all-tickets/tickets' );
		$this->template( 'all-tickets/tickets' );
	?>
</div>
