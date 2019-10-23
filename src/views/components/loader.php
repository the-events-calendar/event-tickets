<?php
/**
 * View: Loader
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/components/loader.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */
if ( empty( $text ) ) {
	$text = $this->get( 'text' ) ?: __( 'Loading...', 'event-tickets' );
}

if ( empty( $loader_classes ) ) {
	$loader_classes = $this->get( 'classes' ) ?: [];
}

$spinner_classes = [
	'tribe-tickets-loader__dots',
	'tribe-common-c-loader',
	'tribe-common-a11y-hidden',
];

if ( ! empty( $loader_classes ) ) {
	$spinner_classes = array_merge( $spinner_classes, (array) $loader_classes );
}

?>
<div class="tribe-common">
	<div <?php tribe_classes( $spinner_classes ); ?> >
		<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--first"></div>
		<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--second"></div>
		<div class="tribe-common-c-loader__dot tribe-common-c-loader__dot--third"></div>
	</div>
</div>
