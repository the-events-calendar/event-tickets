<?php
/**
 * The template to render the list of map cards.
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 *
 * @var Map_Card[] $cards The map cards array.
 * @var string     $add_new_url The URL to add a new map.
 */

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;

if ( empty( $cards ) ) {
	$this->template( 'components/maps/empty', [ 'add_new_url' => $add_new_url ] );
	return;
}
?>
<div class="tec-tickets__seating-tab__cards">
<?php
foreach ( $cards as $card ) {
	$this->template( 'components/maps/card', [ 'card' => $card ] );
}
?>
</div>
