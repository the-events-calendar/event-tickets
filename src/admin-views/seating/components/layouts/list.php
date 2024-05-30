<?php
/**
 * The template to render the list of Layout cards.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Layout_Card[] $cards The Layout cards array.
 */

use TEC\Tickets\Seating\Admin\Tabs\Layout_Card;

if ( empty( $cards ) ) {
	$this->template( 'components/layouts/empty' );
	return;
}
?>
<div class="tec-tickets__seating-tab__cards">
<?php
foreach ( $cards as $card ) {
	$this->template( 'components/layouts/card', [ 'card' => $card ] );
}
?>
</div>