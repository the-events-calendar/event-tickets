export const actions = {
	setUsingAssignedSeating(isUsingAssignedSeating) {
		return {
			type: 'SET_USING_ASSIGNED_SEATING',
			isUsingAssignedSeating,
		};
	},
	setLayout(layoutId) {
		return {
			type: 'SET_LAYOUT',
			layoutId,
		};
	},
	setEventCapacity(eventCapacity) {
		return {
			type: 'SET_EVENT_CAPACITY',
			eventCapacity,
		};
	},
	setSeatTypesForLayout(layoutId, seatTypes) {
		return {
			type: 'SET_SEAT_TYPES_FOR_LAYOUT',
			layoutId,
			seatTypes,
		};
	},
	setTicketSeatType(clientId, seatTypeId) {
		return {
			type: 'SET_TICKET_SEAT_TYPE',
			clientId,
			seatTypeId,
		};
	},
	setTicketSeatTypeByPostId(clientId) {
		return {
			type: 'SET_TICKET_SEAT_TYPE_BY_POST_ID',
			clientId,
		};
	},
	fetchSeatTypesForLayout(layoutId) {
		return {
			type: 'FETCH_SEAT_TYPES_FOR_LAYOUT',
			layoutId,
		};
	},
	setIsLayoutLocked(isLayoutLocked) {
		return {
			type: 'LOCK_LAYOUT',
			isLayoutLocked,
		};
	},
};
