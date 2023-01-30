<?php
/**
 * Attendees table template.
 *
 * @since  TBD
 *
 * @var Tribe_Template            $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

$attendees->attendees_table->prepare_items();
$event        = $attendees->attendees_table->event;
$form_classes = [
	'topics-filter',
	'event-tickets__attendees-admin-form',
];

?>
<form
	id="event-tickets__attendees-admin-form"
	<?php tribe_classes( $form_classes ); ?>
	method="post"
>
	<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>" value="<?php echo esc_attr( tribe_get_request_var( 'page', '' ) ); ?>" />

	<?php if ( ! empty( $event_id ) ) : ?>
	<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>" id="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
	<?php endif; ?>

	<?php if ( ! empty( $event ) ) : ?>
	<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>" value="<?php echo esc_attr( $event->post_type ); ?>" />
	<?php endif; ?>

	<?php $attendees->attendees_table->search_box( __( 'Search attendees', 'event-tickets' ), 'attendees-search' ); ?>
	<?php $attendees->attendees_table->display(); ?>
</form>
