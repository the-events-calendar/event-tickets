/**
 * Internal dependencies
 */
import { types } from '@moderntribe/tickets/data/blocks/ticket';

export const setHeader = ( header ) => ( {
	type: types.SET_TICKET_HEADER,
	payload: {
		header,
	},
} );

export const setTotalSharedCapacity = ( sharedCapacity ) => ( {
	type: types.SET_TICKET_TOTAL_SHARED_CAPACITY,
	payload: {
		sharedCapacity,
	},
} );

export const setSettingsOpen = ( isSettingsOpen ) => ( {
	type: types.SET_TICKET_SETTINGS_OPEN,
	payload: {
		isSettingsOpen,
	},
} );

export const openSettings = () => setSettingsOpen( true );
export const closeSettings = () => setSettingsOpen( false );

export const setParentBlockSelected = ( selected ) => ( {
	type: types.SET_PARENT_BLOCK_SELECTED,
	payload: {
		selected,
	},
} );

export const setChildBlockSelected = ( selected ) => ( {
	type: types.SET_CHILD_BLOCK_SELECTED,
	payload: {
		selected,
	},
} );

export const setActiveChildBlockId = ( activeChildBlockId ) => ( {
	type: types.SET_ACTIVE_CHILD_BLOCK_ID,
	payload: {
		activeChildBlockId,
	},
} );

export const setTempSharedCapacity = ( sharedCapacity ) => ( {
	type: types.SET_TICKET_TMP_TICKET_SHARED_CAPACITY,
	payload: {
		sharedCapacity,
	},
} );

export const setInitialState = ( props ) => ( {
	type: types.SET_INITIAL_STATE,
	payload: props,
} );

export const setProvider = ( provider ) => ( {
	type: types.SET_PROVIDER,
	payload: {
		provider,
	},
} );

// individual ticket actions
export const registerTicketBlock = ( blockId ) => ( {
	type: types.SET_TICKET_BLOCK_ID,
	payload: {
		blockId,
	},
} );

export const requestRemovalOfTicketBlock = ( blockId ) => ( {
	type: types.REQUEST_REMOVAL_OF_TICKET_BLOCK,
	payload: {
		blockId,
	},
} );

export const removeTicketBlock = ( blockId ) => ( {
	type: types.REMOVE_TICKET_BLOCK,
	payload: {
		blockId,
	},
} );

export const setTitle = ( blockId, title ) => ( {
	type: types.SET_TICKET_TITLE,
	payload: {
		blockId,
		title,
	},
} );

export const setDescription = ( blockId, description ) => ( {
	type: types.SET_TICKET_DESCRIPTION,
	payload: {
		blockId,
		description,
	},
} );

export const setPrice = ( blockId, price ) => ( {
	type: types.SET_TICKET_PRICE,
	payload: {
		blockId,
		price,
	},
} );

export const setSKU = ( blockId, SKU ) => ( {
	type: types.SET_TICKET_SKU,
	payload: {
		blockId,
		SKU,
	},
} );

export const setStartDate = ( blockId, startDate ) => ( {
	type: types.SET_TICKET_START_DATE,
	payload: {
		blockId,
		startDate,
	},
} );

export const setStartTime = ( blockId, startTime ) => ( {
	type: types.SET_TICKET_START_TIME,
	payload: {
		blockId,
		startTime,
	},
} );

export const setEndDate = ( blockId, endDate ) => ( {
	type: types.SET_TICKET_END_DATE,
	payload: {
		blockId,
		endDate,
	},
} );

export const setEndTime = ( blockId, endTime ) => ( {
	type: types.SET_TICKET_END_TIME,
	payload: {
		blockId,
		endTime,
	},
} );

export const setCapacityType = ( blockId, capacityType ) => ( {
	type: types.SET_TICKET_CAPACITY_TYPE,
	payload: {
		blockId,
		capacityType,
	},
} );

export const setCapacity = ( blockId, capacity ) => ( {
	type: types.SET_TICKET_CAPACITY,
	payload: {
		blockId,
		capacity,
	},
} );

export const createNewTicket = ( blockId ) => ( {
	type: types.SET_CREATE_NEW_TICKET,
	payload: {
		blockId,
	},
} );

export const updateTicket = ( blockId ) => ( {
	type: types.SET_UPDATE_TICKET,
	payload: {
		blockId,
	},
} );

export const setTicketIsEditing = ( blockId, isEditing ) => ( {
	type: types.SET_TICKET_IS_EDITING,
	payload: {
		blockId,
		isEditing,
	},
} );

export const setTicketId = ( blockId, ticketId ) => ( {
	type: types.SET_TICKET_ID,
	payload: {
		blockId,
		ticketId,
	},
} );

export const setTicketProvider = ( blockId, provider ) => ( {
	type: types.SET_TICKET_PROVIDER,
	payload: {
		blockId,
		provider,
	},
} );

export const setTicketDateIsPristine = ( blockId, dateIsPristine ) => ( {
	type: types.SET_TICKET_DATE_PRISTINE,
	payload: {
		blockId,
		dateIsPristine,
	},
} );

export const setParentBlockIsLoading = ( isParentBlockLoading ) => ( {
	type: types.SET_PARENT_BLOCK_LOADING,
	payload: {
		isParentBlockLoading,
	},
} );

export const setTicketStartDateMoment = ( blockId, startDateMoment ) => ( {
	type: types.SET_TICKET_START_DATE_MOMENT,
	payload: {
		blockId,
		startDateMoment,
	},
} );

export const setTicketEndDateMoment = ( blockId, endDateMoment ) => ( {
	type: types.SET_TICKET_END_DATE_MOMENT,
	payload: {
		blockId,
		endDateMoment,
	},
} );

export const setTicketIsLoading = ( blockId, isLoading ) => ( {
	type: types.SET_TICKET_IS_LOADING,
	payload: {
		blockId,
		isLoading,
	},
} );

export const setTicketHasBeenCreated = ( blockId, hasBeenCreated ) => ( {
	type: types.SET_TICKET_HAS_BEEN_CREATED,
	payload: {
		blockId,
		hasBeenCreated,
	},
} );

export const setTicketInitialState = ( props ) => ( {
	type: types.SET_TICKET_INITIAL_STATE,
	payload: props,
} );

export const fetchTicketDetails = ( blockId, ticketId ) => ( {
	type: types.FETCH_TICKET_DETAILS,
	payload: {
		blockId,
		ticketId,
	},
} );

export const cancelTicketEdit = ( blockId ) => ( {
	type: types.CANCEL_EDIT_OF_TICKET,
	payload: {
		blockId,
	},
} );

export const setTicketSold = ( blockId, sold ) => ( {
	type: types.SET_TICKET_SOLD,
	payload: {
		blockId,
		sold,
	},
} );

export const setTicketCurrency = ( blockId, currencySymbol ) => ( {
	type: types.SET_TICKET_CURRENCY,
	payload: {
		blockId,
		currencySymbol,
	},
} );

export const setTicketAvailable = ( blockId, available ) => ( {
	type: types.SET_TICKET_AVAILABLE,
	payload: {
		blockId,
		available,
	},
} );

export const setRegularTicketValue = ( blockId, capacity ) => ( {
	type: types.SET_TICKET_REGULAR_CAPACITY,
	payload: {
		blockId,
		capacity,
	}
} );