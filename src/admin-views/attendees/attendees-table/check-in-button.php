<?php
/**
 * Check In and Un Check In button for Attendee Table.
 *
 * @since 5.1.3
 *
 * @var array $item Current row item data.
 * @var Tribe__Tickets__Attendees_Table $attendee_table Attendees Table Object.
 * @var string $provider Commerce Provider Class name.
 * @var bool $disable_checkin Whether check-in is disabled.
 */

$disabled_class = $disable_checkin ? 'button-disabled' : '';
?>

<button
	data-attendee-id="<?php echo esc_attr( $item['attendee_id'] ); ?>"
	data-provider="<?php echo esc_attr( $provider ); ?>"
	data-event-id="<?php echo $attendee_table->event ? esc_attr( $attendee_table->event->ID ) : ''; ?>"
	class="components-button is-primary tickets_checkin tec-tickets__admin-table-attendees-check-in-button <?php echo esc_attr( $disabled_class ); ?>"
	title="<?php esc_attr_e( 'Check In attendee', 'event-tickets' ); ?>"
	<?php disabled( $disable_checkin ); ?> >
		<?php esc_html_e( 'Check In', 'event-tickets' ); ?>
</button>

<span class="delete">
	<button
		data-attendee-id="<?php echo esc_attr( $item['attendee_id'] ); ?>"
		data-provider="<?php echo esc_attr( $provider ); ?>"
		title="<?php esc_attr_e( 'Undo Check In', 'event-tickets' ); ?>"
		data-event-id="<?php echo $attendee_table->event ? esc_attr( $attendee_table->event->ID ) : ''; ?>"
		class="components-button is-secondary tickets_uncheckin tec-tickets__admin-table-attendees-undo-check-in-button">
			<?php esc_html_e( 'Undo Check In', 'event-tickets' ); ?>
	</button>
</span>
