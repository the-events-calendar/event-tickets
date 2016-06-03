<?php
/**
 * List of RSVP Orders
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders-rsvp.php
 *
 * @package TribeEventsCalendar
 * @version 4.2
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$view      = Tribe__Tickets__Tickets_View::instance();
$post_id   = get_the_ID();
$post      = get_post( $post_id );
$post_type = get_post_type_object( $post->post_type );
$user_id   = get_current_user_id();
$user_info = get_userdata( $user_id );
$attendees = $view->get_event_rsvp_attendees( $post_id, $user_id );


if ( ! $view->has_rsvp_attendees( $post_id, $user_id ) ) {
	return;
}
$first_attendee = reset( $attendees );
?>
<div class="tribe-rsvp">
	<h2><?php printf( esc_html__( 'My RSVPs for This %s', 'event-tickets' ), $post_type->labels->singular_name ); ?></h2>
	<div class="user-details">
		<div class="tribe-tickets attendees-list-optout">
			<input <?php echo $view->get_restriction_attr( $post_id, esc_attr( $first_attendee['product_id'] ) ); ?> type="checkbox" name="attendee[<?php echo esc_attr( $first_attendee['order_id'] ); ?>][optout]" id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>" <?php checked( true, esc_attr( $first_attendee['optout'] ) ) ?>>
			<label for="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets' ); ?></label>
		</div>
		<p class="reserved-by">
			<?php printf( esc_html__( 'Reserved by %s', 'event-tickets' ), esc_html( $first_attendee['purchaser_name'] ) ); ?>
			<?php printf( esc_html__( ' on %s', 'event-tickets' ), date_i18n( 'F j, Y', strtotime( esc_attr( $first_attendee['purchase_time'] ) ) ) ); ?>
		</p>
	</div>
		<ul class="tribe-rsvp-list">
		<?php foreach ( $attendees as $i => $attendee ): ?>
			<?php $key = $attendee['order_id']; ?>
			<li class="tribe-item<?php echo $view->is_rsvp_restricted( $post_id, $attendee['product_id'] ) ? 'tribe-disabled' : ''; ?>" <?php echo $view->get_restriction_attr( $post_id, $attendee['product_id'] ); ?> id="attendee-<?php echo $attendee['order_id']; ?>">
				<p class="list-attendee"><?php printf( esc_html__( 'Attendee %d', 'event-tickets' ), $i + 1 ); ?></p>
				<div class="tribe-answer">
					<!-- Wrapping <label> around both the text and the <select> will implicitly associate the text with the label. -->
					<!-- See https://www.w3.org/WAI/tutorials/forms/labels/#associating-labels-implicitly -->
					<label>
						<?php esc_html_e( 'RSVP: ', 'event-tickets' ); ?>
						<?php $view->render_rsvp_selector( "attendee[{$key}][order_status]", $attendee['order_status'], $post_id, $attendee['product_id'] ); ?>
					</label>
				</div>
				<div class="attendee-meta-row">
					<?php
					$meta_fields = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $attendee['product_id'] );
					$meta_data = get_post_meta( $attendee['attendee_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );
					?>
					<?php
					foreach ( $meta_fields as $field ) {
						if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
							$values = array();
							foreach ( $field->extra['options'] as $option ) {
								$key = $field->slug . '_' . sanitize_title( $option );

								if ( isset( $meta_data[ $key ] ) ) {
									$values[] = $meta_data[ $key ];
								}
							}
							$value = implode( ', ', $values );
						} elseif ( isset( $meta_data[ $field->slug ] ) ) {
							$value = $meta_data[ $field->slug ];
						} else {
							continue;
						}
						if ( '' === trim( $value ) ) {
							$value = '&nbsp;';
						}
					if ( '' != $value ) { ?>
						<a class="attendee-meta toggle show"><?php esc_html_e( 'Toggle attendee info', 'event-tickets-plus' ); ?></a>
						<div class="attendee-meta-details">
							<span class="event-tickets-meta-label <?php echo esc_attr( $field->slug ); ?>"><?php echo esc_html( $field->label ); ?>&nbsp;</span>
							<span class="event-tickets-meta-data <?php echo esc_attr( $field->slug ); ?>"><?php echo $value ? esc_html( $value ) : '&nbsp;'; ?></span>
						</div>
						<?php
						}
					}
					?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>