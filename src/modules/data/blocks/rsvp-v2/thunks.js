/**
 * V2 RSVP Thunks
 *
 * API calls for the V2 RSVP implementation using the TEC REST V1 endpoints.
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { doAction } from '@wordpress/hooks';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import * as actions from '../rsvp-shared/actions';
import * as selectors from '../rsvp-shared/selectors';
import { normalizeRSVPResponseFromV2Ticket } from '../rsvp-shared/utils/normalize-rsvp-response';
import {
	getAttendanceCountsFromV2Ticket,
	hydrateRsvpAttendanceCounts,
} from '../rsvp-shared/utils/hydrate-rsvp-attendance-counts';
import { selectLatestRsvpTicket } from '../rsvp-shared/utils/select-latest-rsvp-ticket';
import { getV2Config } from './config';
import { buildPersistPayload } from './build-persist-payload';

/**
 * Prevents overlapping create/update/delete requests.
 *
 * @type {boolean}
 */
let persistLocked = false;

/**
 * Experimental endpoint acknowledgement header.
 * Required for TEC REST V1 experimental endpoints.
 */
const TEC_EEA_HEADER = {
	'X-TEC-EEA':
		'I understand that this endpoint is experimental and may change in a future release without maintaining backward compatibility. I also understand that I am using this endpoint at my own risk, while support is not provided for it.',
};

/**
 * Fetches a single ticket from the TEC REST API.
 *
 * @param {number} ticketId Ticket ID.
 * @return {Promise<Object|null>} Ticket REST response.
 */
const fetchV2Ticket = async ( ticketId ) => {
	const config = getV2Config();

	return apiFetch( {
		path: `${ config.ticketsEndpoint }/${ ticketId }`,
		method: 'GET',
		headers: TEC_EEA_HEADER,
	} );
};

/**
 * Hydrates attendance counts and refreshes from the single-ticket endpoint when needed.
 *
 * @param {Function} dispatch Redux dispatch.
 * @param {Object}   ticket   Ticket REST response.
 * @return {Promise<void>}
 */
const hydrateAttendanceCountsFromTicket = async ( dispatch, ticket ) => {
	const counts = getAttendanceCountsFromV2Ticket( ticket );

	hydrateRsvpAttendanceCounts( dispatch, actions, counts );

	if ( counts.goingCount === undefined || counts.inventory === undefined ) {
		await dispatch( refreshRSVPAttendanceCounts() );
	}
};

/**
 * Format a moment object and time string to a datetime string (YYYY-MM-DD HH:MM:SS).
 *
 * @param {Object} momentObj The moment object.
 * @param {string} timeStr   The time string (HH:MM or HH:MM:SS).
 * @return {string} The formatted datetime string.
 */
const formatDateTime = ( momentObj, timeStr ) => {
	if ( ! momentObj || ! momentObj.isValid?.() ) {
		return '';
	}
	const date = momentObj.format( 'YYYY-MM-DD' );
	let time = timeStr || '00:00:00';
	// Ensure time has seconds.
	if ( /^\d{2}:\d{2}$/.test( time ) ) {
		time = `${ time }:00`;
	}
	return `${ date } ${ time }`;
};

/**
 * Create a new RSVP using TEC REST V1 endpoint.
 *
 * @param {Object} payload The RSVP data payload.
 * @return {Function} Redux thunk function.
 */
export const createRSVP = ( payload ) => async ( dispatch, getState ) => {
	if ( persistLocked || selectors.getRSVPIsLoading( getState() ) ) {
		return;
	}

	const config = getV2Config();
	const { capacity, notGoingResponses, startDateMoment, startTime, endDateMoment, endTime, postId } = payload;

	persistLocked = true;
	dispatch( actions.setRSVPIsLoading( true ) );

	try {
		// Build request data.
		const hasCapacity = false !== capacity && null !== capacity && parseInt( capacity, 10 ) >= 0;
		const data = {
			event: postId,
			type: config.ticketType,
			title: 'RSVP',
			description: '',
			price: 0,
			start_date: formatDateTime( startDateMoment, startTime ),
			end_date: formatDateTime( endDateMoment, endTime ),
			show_description: false,
			show_not_going: notGoingResponses ? true : false,
			// Use 'unlimited' stock mode when no capacity, 'own' when capacity is set.
			stock_mode: hasCapacity ? 'own' : 'unlimited',
		};

		// Only include capacity if it's a positive number.
		if ( hasCapacity ) {
			data.capacity = parseInt( capacity, 10 );
		}

		// POST /tec/v1/tickets
		const response = await apiFetch( {
			path: config.ticketsEndpoint,
			method: 'POST',
			headers: TEC_EEA_HEADER,
			data,
		} );

		if ( response && response.id ) {
			dispatch( actions.createRSVP() );
			dispatch( actions.setRSVPId( response.id ) );
			dispatch( actions.setRSVPDetails( { ...payload, title: 'RSVP', description: '' } ) );
			await hydrateAttendanceCountsFromTicket( dispatch, response );
			dispatch( actions.setRSVPHasChanges( false ) );
		}

		/**
		 * Fires after an RSVP is created.
		 *
		 * @since TBD
		 * @param {Object}  payload  The RSVP payload.
		 * @param {boolean} isCreate Whether the RSVP was created (true) or updated (false).
		 */
		doAction( 'tec.tickets.blocks.rsvp.createdOrUpdated', payload, true );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error creating V2 RSVP:', error );
	} finally {
		persistLocked = false;
		dispatch( actions.setRSVPIsLoading( false ) );
	}
};

/**
 * Update an existing RSVP using TEC REST V1 endpoint.
 *
 * @param {Object} payload The RSVP data payload.
 * @return {Function} Redux thunk function.
 */
export const updateRSVP = ( payload ) => async ( dispatch, getState ) => {
	if ( persistLocked || selectors.getRSVPIsLoading( getState() ) ) {
		return;
	}

	const config = getV2Config();
	const { id, capacity, notGoingResponses, startDateMoment, startTime, endDateMoment, endTime } = payload;

	persistLocked = true;
	dispatch( actions.setRSVPIsLoading( true ) );

	try {
		// Build request data.
		const hasCapacity = false !== capacity && null !== capacity && parseInt( capacity, 10 ) >= 0;
		const data = {
			type: config.ticketType,
			title: 'RSVP',
			description: '',
			price: 0,
			start_date: formatDateTime( startDateMoment, startTime ),
			end_date: formatDateTime( endDateMoment, endTime ),
			show_description: false,
			show_not_going: notGoingResponses ? true : false,
			// Use 'unlimited' stock mode when no capacity, 'own' when capacity is set.
			stock_mode: hasCapacity ? 'own' : 'unlimited',
		};

		// Only include capacity if it's a positive number.
		if ( hasCapacity ) {
			data.capacity = parseInt( capacity, 10 );
		}

		// PUT /tec/v1/tickets/{id}
		const response = await apiFetch( {
			path: `${ config.ticketsEndpoint }/${ id }`,
			method: 'PUT',
			headers: TEC_EEA_HEADER,
			data,
		} );

		dispatch( actions.setRSVPDetails( { ...payload, title: 'RSVP', description: '' } ) );
		dispatch(
			actions.setRSVPTempDetails( {
				tempCapacity: capacity,
				tempNotGoingResponses: notGoingResponses,
				tempStartDate: payload.startDate,
				tempStartDateInput: payload.startDateInput,
				tempStartDateMoment: startDateMoment,
				tempEndDate: payload.endDate,
				tempEndDateInput: payload.endDateInput,
				tempEndDateMoment: endDateMoment,
				tempStartTime: startTime,
				tempEndTime: endTime,
				tempStartTimeInput: payload.startTimeInput,
				tempEndTimeInput: payload.endTimeInput,
			} )
		);
		dispatch( actions.setRSVPHasChanges( false ) );

		if ( response ) {
			await hydrateAttendanceCountsFromTicket( dispatch, response );
		}

		/**
		 * Fires after an RSVP is updated.
		 *
		 * @since TBD
		 * @param {Object}  payload  The RSVP payload.
		 * @param {boolean} isCreate Whether the RSVP was created (true) or updated (false).
		 */
		doAction( 'tec.tickets.blocks.rsvp.createdOrUpdated', payload, false );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error updating V2 RSVP:', error );
	} finally {
		persistLocked = false;
		dispatch( actions.setRSVPIsLoading( false ) );
	}
};

/**
 * Creates or updates the RSVP based on current editor state.
 *
 * @param {Object} overrides Optional field overrides for the payload.
 * @return {Function} Redux thunk function.
 */
export const persistRSVP = ( overrides = {} ) => async ( dispatch, getState ) => {
	const state = getState();

	if ( persistLocked || selectors.getRSVPIsLoading( state ) ) {
		return;
	}

	if ( selectors.getRSVPHasDurationError( state ) ) {
		return;
	}

	const payload = buildPersistPayload( state, overrides );

	if ( selectors.getRSVPCreated( state ) ) {
		if ( ! payload.id ) {
			return;
		}

		return dispatch( updateRSVP( payload ) );
	}

	return dispatch(
		createRSVP( {
			...payload,
			postId: select( 'core/editor' ).getCurrentPostId(),
		} )
	);
};

/**
 * Delete an RSVP using TEC REST V1 endpoint.
 *
 * @param {number} id The RSVP ID.
 * @return {Function} Redux thunk function.
 */
export const deleteRSVP = ( id ) => async () => {
	const config = getV2Config();

	try {
		// DELETE /tec/v1/tickets/{id}
		await apiFetch( {
			path: `${ config.ticketsEndpoint }/${ id }`,
			method: 'DELETE',
			headers: TEC_EEA_HEADER,
		} );

		/**
		 * Fires after an RSVP is deleted.
		 *
		 * @since TBD
		 * @param {number} id The RSVP ID.
		 */
		doAction( 'tec.tickets.blocks.rsvp.deleted', id );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error deleting V2 RSVP:', error );
	}
};

/**
 * Get existing RSVP for a post using TEC REST V1 endpoint.
 *
 * @param {number} postId The post ID (event).
 * @return {Function} Redux thunk function.
 */
export const getRSVP = ( postId ) => async ( dispatch ) => {
	const config = getV2Config();

	dispatch( actions.setRSVPIsLoading( true ) );

	try {
		// GET /tec/v1/tickets?event={postId}
		const tickets = await apiFetch( {
			path: `${ config.ticketsEndpoint }?event=${ postId }&type=${ config.ticketType }&orderby=id&order=desc`,
			method: 'GET',
			headers: TEC_EEA_HEADER,
		} );

		const listTicket = selectLatestRsvpTicket( tickets, config.ticketType );

		if ( listTicket ) {
			const normalized = normalizeRSVPResponseFromV2Ticket( listTicket, {
				title: 'RSVP',
				description: '',
			} );

			dispatch( actions.createRSVP() );
			dispatch( actions.setRSVPId( normalized.id ) );
			dispatch( actions.setRSVPHasAttendeeInfoFields( normalized.hasAttendeeInfoFields ) );
			dispatch( actions.setRSVPDetails( normalized.details ) );
			dispatch( actions.setRSVPTempDetails( normalized.tempDetails ) );

			let countsTicket = listTicket;

			try {
				const detailedTicket = await fetchV2Ticket( normalized.id );

				if ( detailedTicket ) {
					countsTicket = detailedTicket;
				}
			} catch ( fetchError ) {
				// eslint-disable-next-line no-console
				console.error( 'Error fetching V2 RSVP ticket details:', fetchError );
			}

			hydrateRsvpAttendanceCounts(
				dispatch,
				actions,
				getAttendanceCountsFromV2Ticket( countsTicket )
			);
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error fetching V2 RSVP:', error );
	} finally {
		dispatch( actions.setRSVPIsLoading( false ) );
	}
};

/**
 * Refresh RSVP attendance counts from the TEC REST ticket endpoint.
 *
 * @return {Function} Redux thunk function.
 */
export const refreshRSVPAttendanceCounts = () => async ( dispatch, getState ) => {
	const ticketId = selectors.getRSVPId( getState() );

	if ( ! ticketId ) {
		return;
	}

	try {
		const ticket = await fetchV2Ticket( ticketId );

		if ( ticket ) {
			hydrateRsvpAttendanceCounts( dispatch, actions, getAttendanceCountsFromV2Ticket( ticket ) );
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error refreshing V2 RSVP attendance counts:', error );
	}
};
