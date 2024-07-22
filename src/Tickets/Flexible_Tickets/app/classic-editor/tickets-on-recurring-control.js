import { onReady } from './utils.js';

// The selectors that will be used to interact with the DOM.
const recurrenceRowSelector = '.recurrence-row';
const newRecurrenceRowSelector =
	'.recurrence-row.tribe-datetime-block:not(.tribe-recurrence-exclusion-row)';
const existingRecurrenceRowSelector =
	'.recurrence-row.tribe-recurrence-description,' +
	' .recurrence-row.tribe-recurrence-exclusion-row';
const recurrenceNotSupportedRowSelector =
	'.recurrence-row.tec-events-pro-recurrence-not-supported';
const recurrenceControls = '.recurrence-container';
const recurrenceRule = '.recurrence-container .tribe-event-recurrence-rule';
const ticketTablesSelector = '.tribe-tickets-editor-table-tickets-body';
const rsvpTicketsSelector = ticketTablesSelector + ' [data-ticket-type="rsvp"]';
const defaultTicketsSelector =
	ticketTablesSelector + ' [data-ticket-type="default"]';
const ticketsMetaboxId = 'tribetickets';
const ticketWarningSelector =
	'.tec_ticket-panel__recurring-unsupported-warning';
const ticketControlsSelector =
	'#ticket_form_toggle, #rsvp_form_toggle, #settings_form_toggle, .tec_ticket-panel__helper_text__wrap';
const ticketEditPanelActiveSelector = '#tribe_panel_edit[aria-hidden="false"]';

// Init the control state from the localized data.
let state = {
	hasRecurrenceRules: window?.TECFtEditorData?.event?.isRecurring || false,
	hasOwnTickets: window?.TECFtEditorData?.event?.hasOwnTickets || false,
};

// Clone and keep track of the previous state.
let prevState = Object.assign({}, state);

/**
 * Update the state and call the callback if the state has changed.
 *
 * @since 5.8.0
 *
 * @param {Object} newState The updates to the state.
 */
function updateState(newState) {
	prevState = Object.assign({}, state);
	state = Object.assign({}, state, newState);

	if (
		prevState.hasRecurrenceRules === state.hasRecurrenceRules &&
		prevState.hasOwnTickets === state.hasOwnTickets
	) {
		// No changes, do nothing.
		return;
	}

	handleControls(state);
}

/**
 * Hide the recurrence controls.
 *
 * The method will take care of hiding the recurrence controls and showing the recurrence not supported message.
 *
 * @since 5.8.0
 */
function hideRecurrenceControls() {
	document.querySelectorAll(recurrenceRowSelector).forEach((el) => {
		el.style.display = 'none';
	});

	document
		.querySelectorAll(recurrenceNotSupportedRowSelector)
		.forEach((el) => {
			el.style.display = 'contents';
			el.style.visibility = 'visible';
		});
}

/**
 * Show the recurrence controls.
 *
 * The method will take care of showing the recurrence controls and hiding the recurrence not supported message.
 * If the Events has not recurrence rules, the method will show just the button to add recurrence rules.
 *
 * @since 5.8.0
 */
function showRecurrenceControls() {
	if (state.hasRecurrenceRules) {
		document.querySelectorAll(recurrenceRowSelector).forEach((el) => {
			el.style.display = '';
		});
	} else {
		document
			.querySelectorAll(existingRecurrenceRowSelector)
			.forEach((el) => {
				el.style.display = 'none';
			});

		document.querySelectorAll(newRecurrenceRowSelector).forEach((el) => {
			el.style.display = '';
		});
	}

	document
		.querySelectorAll(recurrenceNotSupportedRowSelector)
		.forEach((el) => {
			el.style.display = 'none';
		});
}

/**
 * Show the ticket controls.
 *
 * The method will take care of showing the ticket controls and hiding the ticket warning.
 *
 * @since 5.8.0
 */
function showTicketControls() {
	document.querySelectorAll(ticketWarningSelector).forEach((el) => {
		el.style.display = 'none';
	});
	document.querySelectorAll(ticketControlsSelector).forEach((el) => {
		el.style.display = '';
	});
}

/**
 * Hide the ticket controls.
 *
 * The method will take care of hiding the ticket controls and showing the ticket warning.
 *
 * @since 5.8.0
 */
function hideTicketControls() {
	document.querySelectorAll(ticketWarningSelector).forEach((el) => {
		el.style.display = '';
	});
	document.querySelectorAll(ticketControlsSelector).forEach((el) => {
		el.style.display = 'none';
	});
}

/**
 * Handle the controls visibility based on the state.
 *
 * @since 5.8.0
 *
 * @param {Object} newState The new state to hide/show controls based on.
 */
function handleControls(newState) {
	if (!newState.hasRecurrenceRules && !newState.hasOwnTickets) {
		// The potential state where both recurrence rules and tickets are still possible.
		showRecurrenceControls();
		showTicketControls();
		return;
	}

	if (newState.hasOwnTickets && newState.hasRecurrenceRules) {
		// This newState should not exist; we'll be conservative and hide everything.
		hideRecurrenceControls();
		hideTicketControls();
		return;
	}

	if (newState.hasOwnTickets) {
		// If an event has own tickets, it cannot have recurrence rules.
		hideRecurrenceControls();
		showTicketControls();
		return;
	}

	// Finally, if an event has recurrence rules, it cannot have own tickets.
	showRecurrenceControls();
	hideTicketControls();
}

// Initialize the controls visibility based on the initial state.
onReady(() => handleControls(state));
const recurrenceControlsElement = document.querySelector(recurrenceControls);

if (recurrenceControlsElement) {
	// Set up a mutation observer to detect when the recurrence rule is added or removed from the recurrence container.
	const recurrenceControlsObserver = new MutationObserver(() => {
		const recurrenceRulesCount =
			document.querySelectorAll(recurrenceRule).length;
		updateState({ hasRecurrenceRules: recurrenceRulesCount > 0 });
	});

	recurrenceControlsObserver.observe(recurrenceControlsElement, {
		childList: true,
	});
}

const ticketsMetaboxElement = document.getElementById(ticketsMetaboxId);

if (ticketsMetaboxElement) {
	/*
	 * Set up a mutation observer to detect when tickets or RSVPs are added or removed from the tickets metabox.
	 * Also: detect when the user is editing or creating a ticket.
	 */
	const ticketsObserver = new MutationObserver(() => {
		// Run the DOM queries only if required.
		const hasOwnTickets =
			document.querySelectorAll(rsvpTicketsSelector).length || // Has RSVP tickets or...
			document.querySelectorAll(defaultTicketsSelector).length || // ...has default tickets or...
			document.querySelectorAll(ticketEditPanelActiveSelector).length; // ...is editing a ticket.
		updateState({ hasOwnTickets });
	});

	ticketsObserver.observe(ticketsMetaboxElement, {
		childList: true,
		subtree: true,
		attributes: true,
	});
}
