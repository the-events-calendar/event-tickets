/**
 * Internal dependencies
 */
import { types } from '@moderntribe/tickets/data/blocks/ticket';

//
// ─── TICKETS ACTIONS ────────────────────────────────────────────────────────────
//

export const setTicketsInitialState = ( props ) => ( {
	type: types.SET_TICKETS_INITIAL_STATE,
	payload: props,
} );

export const resetTicketsBlock = () => ( {
	type: types.RESET_TICKETS_BLOCK,
} );

export const setTicketsHeaderImage = ( payload ) => ( {
	type: types.SET_TICKETS_HEADER_IMAGE,
	payload,
} );

export const setTicketsIsSelected = ( isSelected ) => ( {
	type: types.SET_TICKETS_IS_SELECTED,
	payload: {
		isSelected,
	},
} );

export const setTicketsIsSettingsOpen = ( isSettingsOpen ) => ( {
	type: types.SET_TICKETS_IS_SETTINGS_OPEN,
	payload: {
		isSettingsOpen,
	},
} );

export const setTicketsIsSettingsLoading = ( isSettingsLoading ) => ( {
	type: types.SET_TICKETS_IS_SETTINGS_LOADING,
	payload: {
		isSettingsLoading,
	},
} );

export const openSettings = () => setTicketsIsSettingsOpen( true );
export const closeSettings = () => setTicketsIsSettingsOpen( false );

export const setTicketsProvider = ( provider ) => ( {
	type: types.SET_TICKETS_PROVIDER,
	payload: {
		provider,
	},
} );

export const setTicketsSharedCapacity = ( sharedCapacity ) => ( {
	type: types.SET_TICKETS_SHARED_CAPACITY,
	payload: {
		sharedCapacity,
	},
} );

export const setTicketsTempSharedCapacity = ( tempSharedCapacity ) => ( {
	type: types.SET_TICKETS_TEMP_SHARED_CAPACITY,
	payload: {
		tempSharedCapacity,
	},
} );

//
// ─── TICKETS SAGA ACTIONS ────────────────────────────────────────────────────────────
//

export const updateUneditableTickets = () => ( {
	type: types.UPDATE_UNEDITABLE_TICKETS,
} );

//
// ─── HEADER IMAGE SAGA ACTIONS ──────────────────────────────────────────────────
//

export const fetchTicketsHeaderImage = ( id ) => ( {
	type: types.FETCH_TICKETS_HEADER_IMAGE,
	payload: {
		id,
	},
} );

export const updateTicketsHeaderImage = ( image ) => ( {
	type: types.UPDATE_TICKETS_HEADER_IMAGE,
	payload: {
		image,
	},
} );

export const deleteTicketsHeaderImage = () => ( {
	type: types.DELETE_TICKETS_HEADER_IMAGE,
} );

//
// ─── TICKET DETAILS ACTIONS ─────────────────────────────────────────────────────
//

export const setTicketTitle = ( clientId, title ) => ( {
	type: types.SET_TICKET_TITLE,
	payload: {
		clientId,
		title,
	},
} );

export const setTicketDescription = ( clientId, description ) => ( {
	type: types.SET_TICKET_DESCRIPTION,
	payload: {
		clientId,
		description,
	},
} );

export const setTicketPrice = ( clientId, price ) => ( {
	type: types.SET_TICKET_PRICE,
	payload: {
		clientId,
		price,
	},
} );

export const setTicketOnSale = ( clientId, onSale ) => ( {
	type: types.SET_TICKET_ON_SALE,
	payload: {
		clientId,
		onSale,
	},
} );

export const setTicketSku = ( clientId, sku ) => ( {
	type: types.SET_TICKET_SKU,
	payload: {
		clientId,
		sku,
	},
} );

export const setTicketIACSetting = ( clientId, iac ) => ( {
	type: types.SET_TICKET_IAC_SETTING,
	payload: {
		clientId,
		iac,
	},
} );

export const setTicketStartDate = ( clientId, startDate ) => ( {
	type: types.SET_TICKET_START_DATE,
	payload: {
		clientId,
		startDate,
	},
} );

export const setTicketStartDateInput = ( clientId, startDateInput ) => ( {
	type: types.SET_TICKET_START_DATE_INPUT,
	payload: {
		clientId,
		startDateInput,
	},
} );

export const setTicketStartDateMoment = ( clientId, startDateMoment ) => ( {
	type: types.SET_TICKET_START_DATE_MOMENT,
	payload: {
		clientId,
		startDateMoment,
	},
} );

export const setTicketEndDate = ( clientId, endDate ) => ( {
	type: types.SET_TICKET_END_DATE,
	payload: {
		clientId,
		endDate,
	},
} );

export const setTicketEndDateInput = ( clientId, endDateInput ) => ( {
	type: types.SET_TICKET_END_DATE_INPUT,
	payload: {
		clientId,
		endDateInput,
	},
} );

export const setTicketEndDateMoment = ( clientId, endDateMoment ) => ( {
	type: types.SET_TICKET_END_DATE_MOMENT,
	payload: {
		clientId,
		endDateMoment,
	},
} );

export const setTicketStartTime = ( clientId, startTime ) => ( {
	type: types.SET_TICKET_START_TIME,
	payload: {
		clientId,
		startTime,
	},
} );

export const setTicketEndTime = ( clientId, endTime ) => ( {
	type: types.SET_TICKET_END_TIME,
	payload: {
		clientId,
		endTime,
	},
} );

export const setTicketStartTimeInput = ( clientId, startTimeInput ) => ( {
	type: types.SET_TICKET_START_TIME_INPUT,
	payload: {
		clientId,
		startTimeInput,
	},
} );

export const setTicketEndTimeInput = ( clientId, endTimeInput ) => ( {
	type: types.SET_TICKET_END_TIME_INPUT,
	payload: {
		clientId,
		endTimeInput,
	},
} );

export const setTicketCapacityType = ( clientId, capacityType ) => ( {
	type: types.SET_TICKET_CAPACITY_TYPE,
	payload: {
		clientId,
		capacityType,
	},
} );

export const setTicketCapacity = ( clientId, capacity ) => ( {
	type: types.SET_TICKET_CAPACITY,
	payload: {
		clientId,
		capacity,
	},
} );

export const setTicketType = ( clientId, type ) => ( {
	type: types.SET_TICKET_TYPE,
	payload: {
		clientId,
		type,
	},
} );

//
// ─── TICKET TEMP DETAILS ACTIONS ────────────────────────────────────────────────
//

export const setTicketTempTitle = ( clientId, title ) => ( {
	type: types.SET_TICKET_TEMP_TITLE,
	payload: {
		clientId,
		title,
	},
} );

export const setTicketTempDescription = ( clientId, description ) => ( {
	type: types.SET_TICKET_TEMP_DESCRIPTION,
	payload: {
		clientId,
		description,
	},
} );

export const setTicketTempPrice = ( clientId, price ) => ( {
	type: types.SET_TICKET_TEMP_PRICE,
	payload: {
		clientId,
		price,
	},
} );

/**
 * Set the sale price checked status for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {boolean} checked Whether the sale price is checked.
 * @returns {{payload: {clientId, checked}, type: string}} The action.
 */
export const setSalePriceChecked = ( clientId, checked ) => ( {
	type: types.SET_TICKET_SALE_PRICE_CHECK,
	payload: {
		clientId,
		checked,
	},
} );

/**
 * Set the sale price for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} salePrice The sale price.
 * @returns {{payload: {clientId, salePrice}, type: string}} The action.
 */
export const setSalePrice = ( clientId, salePrice ) => ( {
	type: types.SET_TICKET_SALE_PRICE,
	payload: {
		clientId,
		salePrice,
	},
} );

/**
 * Set the sale start date for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} startDate The start date.
 * @returns {{payload: {clientId, startDate}, type: string}} The action.
 */
export const setTicketSaleStartDate = ( clientId, startDate ) => ( {
	type: types.SET_TICKET_SALE_START_DATE,
	payload: {
		clientId,
		startDate,
	},
} );

/**
 * Set the sale start date input for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} startDateInput The start date input.
 * @returns {{payload: {clientId, startDateInput}, type: string}} The action.
 */
export const setTicketSaleStartDateInput = ( clientId, startDateInput ) => ( {
	type: types.SET_TICKET_SALE_START_DATE_INPUT,
	payload: {
		clientId,
		startDateInput,
	},
} );

/**
 * Set the sale start date moment for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {Object} startDateMoment The start date moment.
 * @returns {{payload: {clientId, startDateMoment}, type: string}} The action.
 */
export const setTicketSaleStartDateMoment = ( clientId, startDateMoment ) => ( {
	type: types.SET_TICKET_SALE_START_DATE_MOMENT,
	payload: {
		clientId,
		startDateMoment,
	},
} );

/**
 * Set the sale end date for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} endDate The end date.
 * @returns {{payload: {clientId, endDate}, type: string}} The action.
 */
export const setTicketSaleEndDate = ( clientId, endDate ) => ( {
	type: types.SET_TICKET_SALE_END_DATE,
	payload: {
		clientId,
		endDate,
	},
} );

/**
 * Set the sale end date input for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} endDateInput The end date input.
 * @returns {{payload: {clientId, endDateInput}, type: string}} The action.
 */
export const setTicketSaleEndDateInput = ( clientId, endDateInput ) => ( {
	type: types.SET_TICKET_SALE_END_DATE_INPUT,
	payload: {
		clientId,
		endDateInput,
	},
} );

/**
 * Set the sale end date moment for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {Object} endDateMoment The end date moment.
 * @returns {{payload: {clientId, endDateMoment}, type: string}} The action.
 */
export const setTicketSaleEndDateMoment = ( clientId, endDateMoment ) => ( {
	type: types.SET_TICKET_SALE_END_DATE_MOMENT,
	payload: {
		clientId,
		endDateMoment,
	},
} );

/**
 * Set the Temp Sale Price Checked status for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {boolean} checked Whether the sale price is checked.
 * @returns {{payload: {clientId, checked}, type: string}} The action.
 */
export const setTempSalePriceChecked = ( clientId, checked ) => ( {
	type: types.SET_TICKET_TEMP_SALE_PRICE_CHECK,
	payload: {
		clientId,
		checked,
	},
} );

/**
 * Set the Temp Sale Price for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} salePrice The sale price.
 * @returns {{payload: {clientId, salePrice}, type: string}} The action.
 */
export const setTempSalePrice = ( clientId, salePrice ) => ( {
	type: types.SET_TICKET_TEMP_SALE_PRICE,
	payload: {
		clientId,
		salePrice,
	},
} );

/**
 * Set the Temp Sale Start Date for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} startDate The start date.
 * @returns {{payload: {clientId, startDate}, type: string}} The action.
 */
export const setTicketTempSaleStartDate = ( clientId, startDate ) => ( {
	type: types.SET_TICKET_TEMP_SALE_START_DATE,
	payload: {
		clientId,
		startDate,
	},
} );

/**
 * Set the Temp Sale Start Date input for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} startDateInput The start date input.
 * @returns {{payload: {clientId, startDateInput}, type: string}} The action.
 */
export const setTicketTempSaleStartDateInput = ( clientId, startDateInput ) => ( {
	type: types.SET_TICKET_TEMP_SALE_START_DATE_INPUT,
	payload: {
		clientId,
		startDateInput,
	},
} );

/**
 * Set the Temp Sale Start Date moment for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {Object} startDateMoment The start date moment.
 * @returns {{payload: {clientId, startDateMoment}, type: string}} The action.
 */
export const setTicketTempSaleStartDateMoment = ( clientId, startDateMoment ) => ( {
	type: types.SET_TICKET_TEMP_SALE_START_DATE_MOMENT,
	payload: {
		clientId,
		startDateMoment,
	},
} );

/**
 * Set the Temp Sale End Date for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} endDate The end date.
 * @returns {{payload: {clientId, endDate}, type: string}} The action.
 */
export const setTicketTempSaleEndDate = ( clientId, endDate ) => ( {
	type: types.SET_TICKET_TEMP_SALE_END_DATE,
	payload: {
		clientId,
		endDate,
	},
} );

/**
 * Set the Temp Sale End Date input for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} endDateInput The end date input.
 * @returns {{payload: {clientId, endDateInput}, type: string}} The action.
 */
export const setTicketTempSaleEndDateInput = ( clientId, endDateInput ) => ( {
	type: types.SET_TICKET_TEMP_SALE_END_DATE_INPUT,
	payload: {
		clientId,
		endDateInput,
	},
} );

/**
 * Set the Temp Sale End Date moment for a ticket.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {Object} endDateMoment The end date moment.
 * @returns {{payload: {clientId, endDateMoment}, type: string}} The action.
 */
export const setTicketTempSaleEndDateMoment = ( clientId, endDateMoment ) => ( {
	type: types.SET_TICKET_TEMP_SALE_END_DATE_MOMENT,
	payload: {
		clientId,
		endDateMoment,
	},
} );

export const setTicketTempSku = ( clientId, sku ) => ( {
	type: types.SET_TICKET_TEMP_SKU,
	payload: {
		clientId,
		sku,
	},
} );

export const setTicketTempIACSetting = ( clientId, iac ) => ( {
	type: types.SET_TICKET_TEMP_IAC_SETTING,
	payload: {
		clientId,
		iac,
	},
} );

export const setTicketTempStartDate = ( clientId, startDate ) => ( {
	type: types.SET_TICKET_TEMP_START_DATE,
	payload: {
		clientId,
		startDate,
	},
} );

export const setTicketTempStartDateInput = ( clientId, startDateInput ) => ( {
	type: types.SET_TICKET_TEMP_START_DATE_INPUT,
	payload: {
		clientId,
		startDateInput,
	},
} );

export const setTicketTempStartDateMoment = ( clientId, startDateMoment ) => ( {
	type: types.SET_TICKET_TEMP_START_DATE_MOMENT,
	payload: {
		clientId,
		startDateMoment,
	},
} );

export const setTicketTempEndDate = ( clientId, endDate ) => ( {
	type: types.SET_TICKET_TEMP_END_DATE,
	payload: {
		clientId,
		endDate,
	},
} );

export const setTicketTempEndDateInput = ( clientId, endDateInput ) => ( {
	type: types.SET_TICKET_TEMP_END_DATE_INPUT,
	payload: {
		clientId,
		endDateInput,
	},
} );

export const setTicketTempEndDateMoment = ( clientId, endDateMoment ) => ( {
	type: types.SET_TICKET_TEMP_END_DATE_MOMENT,
	payload: {
		clientId,
		endDateMoment,
	},
} );

export const setTicketTempStartTime = ( clientId, startTime ) => ( {
	type: types.SET_TICKET_TEMP_START_TIME,
	payload: {
		clientId,
		startTime,
	},
} );

export const setTicketTempEndTime = ( clientId, endTime ) => ( {
	type: types.SET_TICKET_TEMP_END_TIME,
	payload: {
		clientId,
		endTime,
	},
} );

export const setTicketTempStartTimeInput = ( clientId, startTimeInput ) => ( {
	type: types.SET_TICKET_TEMP_START_TIME_INPUT,
	payload: {
		clientId,
		startTimeInput,
	},
} );

export const setTicketTempEndTimeInput = ( clientId, endTimeInput ) => ( {
	type: types.SET_TICKET_TEMP_END_TIME_INPUT,
	payload: {
		clientId,
		endTimeInput,
	},
} );

export const setTicketTempCapacityType = ( clientId, capacityType ) => ( {
	type: types.SET_TICKET_TEMP_CAPACITY_TYPE,
	payload: {
		clientId,
		capacityType,
	},
} );

export const setTicketTempCapacity = ( clientId, capacity ) => ( {
	type: types.SET_TICKET_TEMP_CAPACITY,
	payload: {
		clientId,
		capacity,
	},
} );

//
// ─── TICKET ACTIONS ─────────────────────────────────────────────────────────────
//

export const registerTicketBlock = ( clientId ) => ( {
	type: types.REGISTER_TICKET_BLOCK,
	payload: {
		clientId,
	},
} );

export const removeTicketBlock = ( clientId ) => ( {
	type: types.REMOVE_TICKET_BLOCK,
	payload: {
		clientId,
	},
} );

export const removeTicketBlocks = () => ( {
	type: types.REMOVE_TICKET_BLOCKS,
} );

export const setTicketSold = ( clientId, sold ) => ( {
	type: types.SET_TICKET_SOLD,
	payload: {
		clientId,
		sold,
	},
} );

export const setTicketAvailable = ( clientId, available ) => ( {
	type: types.SET_TICKET_AVAILABLE,
	payload: {
		clientId,
		available,
	},
} );

export const setTicketId = ( clientId, ticketId ) => ( {
	type: types.SET_TICKET_ID,
	payload: {
		clientId,
		ticketId,
	},
} );

export const setTicketCurrencySymbol = ( clientId, currencySymbol ) => ( {
	type: types.SET_TICKET_CURRENCY_SYMBOL,
	payload: {
		clientId,
		currencySymbol,
	},
} );

export const setTicketCurrencyPosition = ( clientId, currencyPosition ) => ( {
	type: types.SET_TICKET_CURRENCY_POSITION,
	payload: {
		clientId,
		currencyPosition,
	},
} );

export const setTicketProvider = ( clientId, provider ) => ( {
	type: types.SET_TICKET_PROVIDER,
	payload: {
		clientId,
		provider,
	},
} );

export const setTicketHasAttendeeInfoFields = ( clientId, hasAttendeeInfoFields ) => ( {
	type: types.SET_TICKET_HAS_ATTENDEE_INFO_FIELDS,
	payload: {
		clientId,
		hasAttendeeInfoFields,
	},
} );

export const setTicketAttendeeInfoFields = ( clientId, attendeeInfoFields ) => ( {
	type: types.SET_TICKET_ATTENDEE_INFO_FIELDS,
	payload: {
		clientId,
		attendeeInfoFields,
	},
} );

export const setTicketIsLoading = ( clientId, isLoading ) => ( {
	type: types.SET_TICKET_IS_LOADING,
	payload: {
		clientId,
		isLoading,
	},
} );

export const setTicketIsModalOpen = ( clientId, isModalOpen ) => ( {
	type: types.SET_TICKET_IS_MODAL_OPEN,
	payload: {
		clientId,
		isModalOpen,
	},
} );

export const setTicketHasBeenCreated = ( clientId, hasBeenCreated ) => ( {
	type: types.SET_TICKET_HAS_BEEN_CREATED,
	payload: {
		clientId,
		hasBeenCreated,
	},
} );

export const setTicketHasChanges = ( clientId, hasChanges ) => ( {
	type: types.SET_TICKET_HAS_CHANGES,
	payload: {
		clientId,
		hasChanges,
	},
} );

export const setTicketHasDurationError = ( clientId, hasDurationError ) => ( {
	type: types.SET_TICKET_HAS_DURATION_ERROR,
	payload: {
		clientId,
		hasDurationError,
	},
} );

export const setTicketIsSelected = ( clientId, isSelected ) => ( {
	type: types.SET_TICKET_IS_SELECTED,
	payload: {
		clientId,
		isSelected,
	},
} );

export const setUneditableTickets = ( uneditableTickets ) => ( {
	type: types.SET_UNEDITABLE_TICKETS,
	payload: {
		uneditableTickets,
	},
} );

export const setUneditableTicketsLoading = ( loading ) => ( {
	type: types.SET_UNEDITABLE_TICKETS_LOADING,
	loading,
} );

//
// ─── TICKET SAGA ACTIONS ────────────────────────────────────────────────────────
//

export const setTicketDetails = ( clientId, details ) => ( {
	type: types.SET_TICKET_DETAILS,
	payload: {
		clientId,
		details,
	},
} );

export const setTicketTempDetails = ( clientId, tempDetails ) => ( {
	type: types.SET_TICKET_TEMP_DETAILS,
	payload: {
		clientId,
		tempDetails,
	},
} );

export const handleTicketStartDate = ( clientId, date, dayPickerInput ) => ( {
	type: types.HANDLE_TICKET_START_DATE,
	payload: {
		clientId,
		date,
		dayPickerInput,
	},
} );

export const handleTicketEndDate = ( clientId, date, dayPickerInput ) => ( {
	type: types.HANDLE_TICKET_END_DATE,
	payload: {
		clientId,
		date,
		dayPickerInput,
	},
} );

/**
 * Process the ticket sale start date.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} date The date.
 * @param {string} dayPickerInput The day picker input.
 * @returns {{payload: {date, dayPickerInput, clientId}, type: string}} The action.
 */
export const processTicketSaleStartDate = ( clientId, date, dayPickerInput ) => ( {
	type: types.HANDLE_TICKET_SALE_START_DATE,
	payload: {
		clientId,
		date,
		dayPickerInput,
	},
} );

/**
 * Process the ticket sale end date.
 *
 * @since 5.9.0
 * @param {string} clientId The client ID of the ticket.
 * @param {string} date The date.
 * @param {string} dayPickerInput The day picker input.
 * @returns {{payload: {date, dayPickerInput, clientId}, type: string}} The action.
 */
export const processTicketSaleEndDate = ( clientId, date, dayPickerInput ) => ( {
	type: types.HANDLE_TICKET_SALE_END_DATE,
	payload: {
		clientId,
		date,
		dayPickerInput,
	},
} );

export const handleTicketStartTime = ( clientId, seconds ) => ( {
	type: types.HANDLE_TICKET_START_TIME,
	payload: {
		clientId,
		seconds,
	},
} );

export const handleTicketEndTime = ( clientId, seconds ) => ( {
	type: types.HANDLE_TICKET_END_TIME,
	payload: {
		clientId,
		seconds,
	},
} );

export const fetchTicket = ( clientId, ticketId ) => ( {
	type: types.FETCH_TICKET,
	payload: {
		clientId,
		ticketId,
	},
} );

export const createNewTicket = ( clientId ) => ( {
	type: types.CREATE_NEW_TICKET,
	payload: {
		clientId,
	},
} );

export const updateTicket = ( clientId ) => ( {
	type: types.UPDATE_TICKET,
	payload: {
		clientId,
	},
} );

export const deleteTicket = ( clientId, askForDeletion ) => ( {
	type: types.DELETE_TICKET,
	payload: {
		clientId,
		askForDeletion,
	},
} );

export const setTicketInitialState = ( props ) => ( {
	type: types.SET_TICKET_INITIAL_STATE,
	payload: props,
} );
