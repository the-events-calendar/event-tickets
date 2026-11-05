<?php
/**
 * The head of a recurring event's tickets in the classic editor's tickets panel: what they are, and the buttons that
 * open its Tickets by Date section and add a ticket.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/recurring-tickets/header.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $hidden  Whether the event does not recur yet; the head shows once it does.
 * @var bool $by_date Whether the event has a Tickets by Date section to open.
 */

defined( 'ABSPATH' ) || exit;

?>
<div
	id="tec-tickets-recurring-header"
	class="tec-tickets-recurring-header"
	<?php echo $hidden ? 'style="display: none"' : ''; ?>
>
	<div class="tec-tickets-recurring-header__text">
		<h3 class="tec-tickets-recurring-header__title">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: The plural label for tickets. */
					_x( 'Recurring Event %s', 'The title of the recurring event tickets in the tickets panel.', 'event-tickets' ),
					tribe_get_ticket_label_plural( 'recurring_tickets_header' )
				)
			);
			?>
		</h3>
		<p class="tec-tickets-recurring-header__description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: The lowercase plural label for tickets. */
					__( 'These %s apply to all dates in the recurring event', 'event-tickets' ),
					tribe_get_ticket_label_plural_lowercase( 'recurring_tickets_header' )
				)
			);
			?>
		</p>
	</div>
	<div class="tec-tickets-recurring-header__actions">
		<?php if ( $by_date ) : ?>
			<a href="#tec-tickets-by-date" class="button button-secondary tec-tickets-recurring-header__by-date">
				<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: The plural label for tickets. */
						_x( '%s by Date', 'The button that opens the Tickets by Date section.', 'event-tickets' ),
						tribe_get_ticket_label_plural( 'recurring_tickets_header' )
					)
				);
				?>
			</a>
		<?php endif; ?>
		<button
			type="button"
			id="recurring_ticket_form_toggle"
			class="button button-primary ticket_form_toggle"
			data-ticket-type="recurring"
		>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: The singular label for a ticket. */
					_x( 'Add a %s', 'The button that adds a recurring event ticket.', 'event-tickets' ),
					tribe_get_ticket_label_singular( 'recurring_tickets_header' )
				)
			);
			?>
		</button>
	</div>
</div>
