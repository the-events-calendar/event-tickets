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

/**
 * Internal dependencies
 */
import * as actions from '../rsvp/actions';
import { getV2Config } from './config';
import { globals, moment as momentUtil } from '@moderntribe/common/utils';

/**
 * Experimental endpoint acknowledgement header.
 * Required for TEC REST V1 experimental endpoints.
 */
const TEC_EEA_HEADER = {
	'X-TEC-EEA':
		'I understand that this endpoint is experimental and may change in a future release without maintaining backward compatibility. I also understand that I am using this endpoint at my own risk, while support is not provided for it.',
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
export const createRSVP = ( payload ) => async ( dispatch ) => {
	const config = getV2Config();
	const {
		title,
		description,
		capacity,
		notGoingResponses,
		startDateMoment,
		startTime,
		endDateMoment,
		endTime,
		postId,
	} = payload;

	dispatch( actions.setRSVPIsLoading( true ) );

	try {
		// Build request data.
		const hasCapacity = capacity && parseInt( capacity, 10 ) > 0;
		const data = {
			event: postId,
			type: config.ticketType,
			title,
			description,
			price: 0,
			start_date: formatDateTime( startDateMoment, startTime ),
			end_date: formatDateTime( endDateMoment, endTime ),
			show_description: true,
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
			dispatch( actions.setRSVPDetails( payload ) );
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
		dispatch( actions.setRSVPIsLoading( false ) );
	}
};

/**
 * Update an existing RSVP using TEC REST V1 endpoint.
 *
 * @param {Object} payload The RSVP data payload.
 * @return {Function} Redux thunk function.
 */
export const updateRSVP = ( payload ) => async ( dispatch ) => {
	const config = getV2Config();
	const {
		id,
		title,
		description,
		capacity,
		notGoingResponses,
		startDateMoment,
		startTime,
		endDateMoment,
		endTime,
	} = payload;

	dispatch( actions.setRSVPIsLoading( true ) );

	try {
		// Build request data.
		const hasCapacity = capacity && parseInt( capacity, 10 ) > 0;
		const data = {
			type: config.ticketType,
			title,
			description,
			price: 0,
			start_date: formatDateTime( startDateMoment, startTime ),
			end_date: formatDateTime( endDateMoment, endTime ),
			show_not_going: notGoingResponses ? true : false,
			// Use 'unlimited' stock mode when no capacity, 'own' when capacity is set.
			stock_mode: hasCapacity ? 'own' : 'unlimited',
		};

		// Only include capacity if it's a positive number.
		if ( hasCapacity ) {
			data.capacity = parseInt( capacity, 10 );
		}

		// POST /tec/v1/tickets/{id}
		await apiFetch( {
			path: `${ config.ticketsEndpoint }/${ id }`,
			method: 'POST',
			headers: TEC_EEA_HEADER,
			data,
		} );

		dispatch( actions.setRSVPDetails( payload ) );
		dispatch( actions.setRSVPHasChanges( false ) );

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
		dispatch( actions.setRSVPIsLoading( false ) );
	}
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
			path: `${ config.ticketsEndpoint }?event=${ postId }`,
			method: 'GET',
			headers: TEC_EEA_HEADER,
		} );

		// Filter for tc-rsvp type tickets.
		const rsvpTickets = Array.isArray( tickets )
			? tickets.filter( ( ticket ) => ticket.type === config.ticketType )
			: [];

		if ( rsvpTickets.length > 0 ) {
			const rsvp = rsvpTickets[ 0 ];
			const datePickerFormat = globals.tecDateSettings().datepickerFormat;

			// Parse dates from the response.
			const startMoment = momentUtil.toMoment( rsvp.start_date );
			const endMoment = momentUtil.toMoment( rsvp.end_date );

			const startDateInput = datePickerFormat
				? startMoment.format( momentUtil.toFormat( datePickerFormat ) )
				: momentUtil.toDate( startMoment );
			const endDateInput = datePickerFormat
				? endMoment.format( momentUtil.toFormat( datePickerFormat ) )
				: momentUtil.toDate( endMoment );

			// TEC REST V1 returns 'stock' for capacity, or -1 for unlimited.
			const capacity = rsvp.capacity >= 0 ? rsvp.capacity : ( rsvp.stock >= 0 ? rsvp.stock : '' );
			const notGoingResponses = rsvp.show_not_going || false;

			dispatch( actions.createRSVP() );
			dispatch( actions.setRSVPId( rsvp.id ) );
			dispatch( actions.setRSVPGoingCount( parseInt( rsvp.going_count || rsvp.sold || 0, 10 ) ) );
			dispatch( actions.setRSVPNotGoingCount( parseInt( rsvp.not_going_count || 0, 10 ) ) );
			dispatch( actions.setRSVPHasAttendeeInfoFields( rsvp.has_attendee_info_fields || false ) );

			dispatch(
				actions.setRSVPDetails( {
					title: rsvp.title,
					description: rsvp.description || rsvp.excerpt || '',
					capacity,
					notGoingResponses,
					startDate: momentUtil.toDate( startMoment ),
					startDateInput,
					startDateMoment: startMoment.clone().startOf( 'day' ),
					endDate: momentUtil.toDate( endMoment ),
					endDateInput,
					endDateMoment: endMoment.clone().seconds( 0 ),
					startTime: momentUtil.toDatabaseTime( startMoment ),
					endTime: momentUtil.toDatabaseTime( endMoment ),
					startTimeInput: momentUtil.toTime( startMoment ),
					endTimeInput: momentUtil.toTime( endMoment ),
				} )
			);

			dispatch(
				actions.setRSVPTempDetails( {
					tempTitle: rsvp.title,
					tempDescription: rsvp.description || rsvp.excerpt || '',
					tempCapacity: capacity,
					tempNotGoingResponses: notGoingResponses,
					tempStartDate: momentUtil.toDate( startMoment ),
					tempStartDateInput: startDateInput,
					tempStartDateMoment: startMoment.clone().startOf( 'day' ),
					tempEndDate: momentUtil.toDate( endMoment ),
					tempEndDateInput: endDateInput,
					tempEndDateMoment: endMoment.clone().seconds( 0 ),
					tempStartTime: momentUtil.toDatabaseTime( startMoment ),
					tempEndTime: momentUtil.toDatabaseTime( endMoment ),
					tempStartTimeInput: momentUtil.toTime( startMoment ),
					tempEndTimeInput: momentUtil.toTime( endMoment ),
				} )
			);
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Error fetching V2 RSVP:', error );
	} finally {
		dispatch( actions.setRSVPIsLoading( false ) );
	}
};
