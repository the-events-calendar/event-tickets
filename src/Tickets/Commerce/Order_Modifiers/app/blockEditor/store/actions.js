export const actions = {

	/**
	 * Set all the fees.
	 *
	 * @param {Fee[]} feesAvailable
	 * @param {Fee[]} feesAutomatic
	 * @return {{allFees, type: string}}
	 */
	setAllFees( feesAvailable, feesAutomatic ) {
		return {
			type: 'SET_ALL_FEES',
			feesAvailable,
			feesAutomatic,
		};
	},

	setTicketFees( clientId, feesSelected ) {
		return {
			type: 'SET_SELECTED_FEES',
			clientId,
			feesSelected
		};
	},

	// Add a fee to a ticket.
	addFeeToTicket( clientId, feeId ) {
		return {
			type: 'ADD_FEE_TO_TICKET',
			clientId,
			feeId,
		};
	},

	// Remove a fee from a ticket.
	removeFeeFromTicket( clientId, feeId ) {
		return {
			type: 'REMOVE_FEE_FROM_TICKET',
			clientId,
			feeId,
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

	setFeesByPostId( clientId ) {
		return {
			type: 'SET_SELECTED_FEES_BY_POST_ID',
			clientId,
		}
	},
};
