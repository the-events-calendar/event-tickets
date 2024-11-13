export const actions = {
	// Actions that have to do with fees for a ticket.
	setTicketFees( clientId, feesSelected ) {
		return {
			type: 'SET_TICKET_FEES',
			clientId,
			feesSelected
		};
	},

	// Actions that have to do with managing fees separate from tickets.
	setAutomaticFees( feesAutomatic ) {
		return {
			type: 'SET_AUTOMATIC_FEES',
			feesAutomatic,
		};
	},

	setAvailableFees( feesAvailable ) {
		return {
			type: 'SET_AVAILABLE_FEES',
			feesAvailable,
		};
	},

	fetchFeesFromAPI() {
		return {
			type: 'FETCH_FEES_FROM_API',
		};
	},
};
