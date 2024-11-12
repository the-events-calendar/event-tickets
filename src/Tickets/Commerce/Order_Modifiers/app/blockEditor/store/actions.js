export default {
	// Actions that have to do with fees for a ticket.
	setTicketFees( clientId, feesSelected ) {
		return {
			type: 'SET_TICKET_FEES',
			clientId,
			feesSelected
		};
	},

	getTicketFees( clientId ) {
		return {
			type: 'GET_TICKET_FEES',
			clientId
		};
	},

	// Actions that have to do with managing fees separate from tickets.
	setAutomaticFees( feesAutomatic ) {
		return {
			type: 'SET_AUTOMATIC_FEES',
			feesAutomatic,
		};
	},

	getAutomaticFees() {
		return {
			type: 'GET_AUTOMATIC_FEES',
		};
	},

	setAvailableFees( feesAvailable ) {
		return {
			type: 'SET_AVAILABLE_FEES',
			feesAvailable,
		};
	},

	getAvailableFees() {
		return {
			type: 'GET_AVAILABLE_FEES',
		};
	},

	fetchFeesFromAPI() {
		return {
			type: 'FETCH_FEES_FROM_API',
		};
	},
};
