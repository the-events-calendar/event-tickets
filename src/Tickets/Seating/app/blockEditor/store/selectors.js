import { getTicketIdFromCommonStore } from './common-store-bridge';
import { currentProviderSupportsSeating } from './compatibility';

export const selectors = {
	isUsingAssignedSeating(state) {
		return state.isUsingAssignedSeating && currentProviderSupportsSeating();
	},
	getLayouts(state) {
		return state.layouts;
	},
	getLayoutSeats(state, layoutId) {
		return (
			state.layouts.find((layout) => layout.id === layoutId)?.seats || 0
		);
	},
	getLayoutsInOptionFormat(state) {
		return state.layouts.map((layout) => ({
			label: layout.name,
			value: layout.id,
		}));
	},
	getSeatTypesForLayout(state, layoutId, onlyValue = false) {
		const layoutSeatTypes = state.seatTypesByLayoutId?.[layoutId] || null;

		if (!layoutSeatTypes) {
			return [];
		}

		if (onlyValue) {
			return layoutSeatTypes;
		}

		return Object.values(layoutSeatTypes).map(function (seatType) {
			return {
				label: `${seatType.name} (${seatType.seats})`,
				value: seatType.id,
			};
		});
	},
	getCurrentLayoutId(state) {
		if (!state.isUsingAssignedSeating) {
			return null;
		}

		return state?.currentLayoutId || null;
	},
	getSeatTypeSeats(state, seatTypeId) {
		if (!state.isUsingAssignedSeating) {
			return null;
		}

		return (
			state?.seatTypesByLayoutId?.[state.currentLayoutId]?.[seatTypeId]
				?.seats || 0
		);
	},
	getTicketSeatType(state, clientId) {
		if (!state.isUsingAssignedSeating) {
			return null;
		}

		const ticketPostId = getTicketIdFromCommonStore(clientId);

		return (
			state?.seatTypesByPostId?.[ticketPostId] ||
			state?.seatTypesByClientId?.[clientId] ||
			null
		);
	},
	isLayoutLocked(state) {
		return state?.isLayoutLocked || false;
	},
	getAllSeatTypes(state) {
		return state?.seatTypes || [];
	},
	getEventCapacity(state) {
		return state?.eventCapacity || 0;
	},
	getSeatTypesByPostID(state) {
		if (!state.isUsingAssignedSeating) {
			return null;
		}

		return state?.seatTypesByPostId || [];
	},
	getSeatTypesByClientID(state) {
		if (!state.isUsingAssignedSeating) {
			return null;
		}

		return state?.seatTypesByClientId || [];
	},
	isServiceStatusOk(state) {
		return state?.serviceStatus?.ok === true;
	},
	getServiceStatus(state) {
		return state?.serviceStatus?.status;
	},
	getServiceConnectUrl(state) {
		return state?.serviceStatus?.connectUrl;
	},
};
