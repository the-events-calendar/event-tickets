<?php
/**
 * Conditional Warnings on Ticket Features
 *
 * Class Tribe__Tickets_Plus__Commerce__Warnings
 *
 * @since TBD
 *
 */
class Tribe__Tickets__Commerce__Warnings {

	public function hook() {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'recurring_event_warning' ] );
		add_action( 'tribe_events_tickets_after_new_ticket_panel', [ $this, 'recurring_event_warning_msg' ] );
	}

	/**
	 * Create Recurring Event Warning Label
	 *
	 * @since 4.6
	 *
	 */
	public function recurring_event_warning() {
		if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
			return;
		}
		if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
			return;
		}
		?>
		<div
			class="tribe-warning-toggle tribe-dependent"
			data-depends="#tribe-recurrence-active"
			data-condition-is-checked
		>
			<label for="tribe-tickets-warning">
			<span
				class="event-warnings tribe-dependent"
				data-depends="#tribe-tickets-warning"
				data-condition-is-not-checked
			>
				<span class="dashicons dashicons-warning"></span><?php esc_html_e( 'Warning', 'event-tickets-plus' ); ?>
			</span>
				<span
					class="event-warnings-close tribe-dependent"
					data-depends="#tribe-tickets-warning"
					data-condition-is-checked
				>
				<span class="dashicons dashicons-no"></span><?php esc_html_e( 'Hide Warning', 'event-tickets-plus' ); ?>
			</span>
			</label>
			<input id="tribe-tickets-warning" type="checkbox">
		</div>
		<?php

	}

	/**
	 * Create Recurring Event Warning Message Label
	 *
	 * @since 4.6
	 *
	 */
	public function recurring_event_warning_msg() {
		if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
			return;
		}
		if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
			return;
		}

		$warning = __( 'This is a recurring event. If you add tickets they will only show up on the next upcoming event in the recurrence pattern. The same ticket form will appear across all events in the series. Please configure your events accordingly.', 'event-tickets-plus' );
		?>
		<div
			class="recurring-warning tribe-dependent"
			data-depends="#tribe-tickets-warning"
			data-condition-is-checked
		>
			<span class="dashicons dashicons-warning"></span>
			<div class="recurring-warning-msg"><?php echo esc_attr( $warning ); ?></div>
		</div>
		<?php
	}
}