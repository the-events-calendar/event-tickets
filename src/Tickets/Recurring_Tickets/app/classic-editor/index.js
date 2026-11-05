/**
 * Recurring event tickets in the classic editor's tickets panel.
 *
 * Shows the button that adds a recurring event ticket while the event recurs. The tickets panel script keeps
 * standard tickets and RSVPs off a recurring event, as before.
 *
 * @since TBD
 */

const buttonSelector = '#recurring_ticket_form_toggle';
const recurrenceRuleSelector = '.tribe-event-recurrence-rule';
const standardTicketsSelector =
	'.tribe-tickets-editor-table-tickets-body [data-ticket-type="default"],' +
	' .tribe-tickets-editor-table-tickets-body [data-ticket-type="rsvp"]';

/**
 * Follows the recurrence and tickets events of the tickets panel script.
 *
 * @since TBD
 *
 * @param {jQuery}   $        jQuery.
 * @param {Document} document The document.
 *
 * @return {void}
 */
export function init( $, document ) {
	const $document = $( document );

	/*
	 * tickets.js says the event recurs when its rules are in sight, and a saved ticket refreshing the panel puts them
	 * out of sight for a moment: whether the event has a rule decides.
	 */
	$document.on( 'tribe-recurrence-active tribe-recurrence-inactive', () =>
		$( buttonSelector ).toggle( $( recurrenceRuleSelector ).length > 0 )
	);

	/*
	 * Without Flexible Tickets, the tickets panel script hides the recurrence rules whenever the tickets table shows.
	 * Recurring event tickets belong on a recurring event: only a standard ticket or an RSVP keeps them hidden.
	 */
	$document.on( 'tribe-tickets-active', () => {
		if (
			document.body.classList.contains( 'tec-no-tickets-on-recurring' ) &&
			! $( standardTicketsSelector ).length
		) {
			$document.trigger( 'tribe-tickets-inactive' );
		}
	} );
}

init( window.jQuery, document );
