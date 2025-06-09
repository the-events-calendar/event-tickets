<?php
/**
 * Attendees table template.
 *
 * @since 5.5.9
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

$attendees->attendees_table->prepare_items();

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

	<?php $this->template( 'attendees/attendees-table/form/fields' ); ?>

	<?php $attendees->attendees_table->search_box( __( 'Search attendees', 'event-tickets' ), 'attendees-search' ); ?>
	<?php $attendees->attendees_table->display(); ?>
</form>
