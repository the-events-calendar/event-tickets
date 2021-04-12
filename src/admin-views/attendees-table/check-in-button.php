<?php
/**
 * Check In and Un Check In button for Attendee Table.
 *
 * @since TBD
 *
 * @var array $item Current row item data.
 * @var Tribe__Tickets__Attendees_Table $attendee_table Attendees Table Object.
 * @var string $provider Commerce Provider Class name.
 * @var bool $disable_checkin Whether check-in is disabled.
 */

$event_provider = $attendee_table->event ? 'data-provider="' . $attendee_table->event->ID . '"' : '';
$disabled_class = $disable_checkin ? 'button-disabled' : '';
?>

<button
	data-attendee-id="<?php echo esc_attr( $item['attendee_id'] ); ?>"
	data-provider="<?php echo esc_attr( $provider ); ?>"
	class="button-primary tickets_checkin <?php echo esc_attr( $disabled_class ); ?>"
	<?php echo $event_provider; ?>
	<?php disabled( $disable_checkin ); ?> >
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
