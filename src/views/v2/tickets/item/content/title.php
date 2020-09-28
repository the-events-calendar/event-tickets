<?php
/**
 * Block: Tickets
 * Content Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/content/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket Ticket Object.
 * @var WP_Post|int $post_id                  The post object or ID.
 * @var bool $is_mini                         True if it's in mini cart context.
 */

$no_description = ! $ticket->show_description() || empty( $ticket->description ) || $is_mini;

$title_classes = [
	'tribe-common-h7',
	'tribe-common-h6--min-medium',
	'tribe-tickets__item__content__title',
	'tribe-tickets--no-description' => $no_description,
];

$event_title_classes = [
	'tribe-common-b3',
	'tribe-tickets__item__content__subtitle',
];

?>
<div <?php tribe_classes( $title_classes ); ?> >
	<?php if ( $is_mini ) : ?>
		<div <?php tribe_classes( $event_title_classes ); ?> >
			<?php echo esc_html( get_the_title( $post_id ) ); ?>
		</div>
	<?php endif; ?>
	<?php echo esc_html( $ticket->name ); ?>
</div>
