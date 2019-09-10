<?php
/**
 * Block: Tickets
 * Content Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/content-title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$ticket = $this->get( 'ticket' );
$classes = [
	'tribe-common-h7',
	'tribe-common-h6--min-medium',
	'tribe-block__tickets__item__content__title',
];

if ( ! $ticket->show_description() || empty( $ticket->description ) ) {
	$classes[] = 'tribe-block__tickets--no-description';
}
?>
<div
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
>
	<?php echo $ticket->name; ?>
</div>
