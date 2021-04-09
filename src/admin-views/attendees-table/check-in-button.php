<?php
/**
 * Check In and Un Check In button for Attendee Table.
 *
 * @since TBD
 *
 * @var array $item Current row item data.
 * @var Tribe__Tickets__Attendees_Table $attendee_table Attendees Table Object.
 * @var string $provider Commerce Provider Class name.
 * @var string $disabled_class Class for Disabled button.
 */

$event_provider = $attendee_table->event ? 'data-provider="' . $attendee_table->event->ID . '"' : '';
$disabled_attr  = ! empty( $disabled_class ) ? 'disabled' : '';
?>

<button
	data-attendee-id="<?php echo esc_attr( $item['attendee_id'] ); ?>"
	data-provider="<?php echo esc_attr( $provider ); ?>"
	class="button-primary tickets_checkin <?php echo esc_attr( $disabled_class ); ?>"
	<?php echo esc_attr( $event_provider ); ?>
	<?php echo esc_attr( $disabled_attr ); ?> >
		<?php esc_html_e( 'Check In', 'event-tickets' ) ?>
</button>

<span class="delete">
	<button
		data-attendee-id="<?php echo esc_attr( $item['attendee_id'] ); ?>"
		data-provider="<?php echo esc_attr( $provider ); ?>"
		class="button-secondary tickets_uncheckin" >
			<?php esc_html_e( 'Undo Check In', 'event-tickets' ) ?>
	</button>
</span>
