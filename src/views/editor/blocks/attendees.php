<?php
/**
 * This template renders the attendees
 *
 * @version TBD
 *
 */
$title     = $this->attr( 'title' );
$attendees = $this->get( 'attendees', array() );
$classes   = array( 'tribe-block', 'tribe-block__attendees' );
?>
<div
	id="tribe-block__attendees"
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

	<?php $this->template( 'editor/blocks/attendees/title', array( 'title' => $title ) ); ?>

	<?php $this->template( 'editor/blocks/attendees/description', array( 'attendees' => $attendees ) ); ?>

	<?php foreach ( $attendees as $key => $attendee ) : ?>

		<?php $this->template( 'editor/blocks/attendees/gravatar', array( 'attendee' => $attendee ) ); ?>

	<?php endforeach; ?>

</div>

