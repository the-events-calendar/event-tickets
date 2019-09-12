<?php
/**
 * Block: Attendees List
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/attendees.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$title     = $this->attr( 'title' );
$attendees = $this->get( 'attendees', null );
$classes   = array( 'tribe-block', 'tribe-block__attendees' );

if ( ! is_array( $attendees ) ) {
	return;
}
?>
<div
	id="tribe-block__attendees"
	<?php tribe_classes( $classes ); ?>

	<?php $this->template( 'blocks/attendees/title', array( 'title' => $title ) ); ?>

	<?php $this->template( 'blocks/attendees/description', array( 'attendees' => $attendees ) ); ?>

	<?php foreach ( $attendees as $key => $attendee ) : ?>

		<?php $this->template( 'blocks/attendees/gravatar', array( 'attendee' => $attendee ) ); ?>

	<?php endforeach; ?>

</div>

