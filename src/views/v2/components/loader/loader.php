<?php
/**
 * View: Loader
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/components/loader/loader.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.0.4 Update template to use icons from Tribe Common.
 *
 * @version 5.0.4
 */

$classes = $this->get( 'classes' ) ?: [];

$spinner_classes = [
	'tribe-tickets-loader__dots',
	'tribe-common-c-loader',
	'tribe-common-a11y-hidden',
];

if ( ! empty( $classes ) ) {
	$spinner_classes = array_merge( $spinner_classes, (array) $classes );
}

?>
<div <?php tribe_classes( $spinner_classes ); ?>>
	<?php $this->template( 'v2/components/icons/dot', [ 'classes' => [ 'tribe-common-c-loader__dot', 'tribe-common-c-loader__dot--first' ] ] ); ?>
	<?php $this->template( 'v2/components/icons/dot', [ 'classes' => [ 'tribe-common-c-loader__dot', 'tribe-common-c-loader__dot--second' ] ] ); ?>
	<?php $this->template( 'v2/components/icons/dot', [ 'classes' => [ 'tribe-common-c-loader__dot', 'tribe-common-c-loader__dot--third' ] ] ); ?>
</div>
