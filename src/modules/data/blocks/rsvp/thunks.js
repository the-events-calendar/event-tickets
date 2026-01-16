/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { doAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as utils from '../../utils';
import { middlewares } from '@moderntribe/common/store';
import { globals, time, moment as momentUtil } from '@moderntribe/common/utils';
import { isV2Enabled, getV2Config } from '../rsvp-v2/config';

const {
	request: { actions: wpRequestActions },
} = middlewares;

/**
 * @todo: until we can abstract out wpRequest() better, these should remain as a thunk
 */
const METHODS = {
	DELETE: 'DELETE',
	GET: 'GET',
	POST: 'POST',
	PUT: 'PUT',
};

const TEC_EEA_HEADER = {
	'X-TEC-EEA':
		'I understand that this endpoint is experimental and may change in a future release without maintaining backward compatibility. I also understand that I am using this endpoint at my own risk, while support is not provided for it.',
};

const createOrUpdateRSVP = ( method ) => ( payload ) => ( dispatch ) => {
	const { title, description, capacity, notGoingResponses, startDateMoment, startTime, endDateMoment, endTime } =
		payload;

	const startMoment = startDateMoment
		.clone()
		.startOf( 'day' )
		.seconds( time.toSeconds( startTime, time.TIME_FORMAT_HH_MM_SS ) );

	const endMoment = endDateMoment
		.clone()
		.startOf( 'day' )
		.seconds( time.toSeconds( endTime, time.TIME_FORMAT_HH_MM_SS ) );

	let path = `${ utils.RSVP_POST_TYPE }`;
	const reqBody = {
		title,
		excerpt: description,
		meta: {
			[ utils.KEY_TICKET_CAPACITY ]: capacity,
			[ utils.KEY_TICKET_START_DATE ]: momentUtil.toDateTime( startMoment ),
			[ utils.KEY_TICKET_END_DATE ]: momentUtil.toDateTime( endMoment ),
			[ utils.KEY_TICKET_SHOW_NOT_GOING ]: notGoingResponses,
		},
	};

	if ( method === METHODS.POST ) {
		reqBody.status = 'publish';
		reqBody.meta[ utils.KEY_RSVP_FOR_EVENT ] = `${ payload.postId }`;
		/* This is hardcoded value until we can sort out BE */
		reqBody.meta[ utils.KEY_TICKET_SHOW_DESCRIPTION ] = 'yes';
		/* This is hardcoded value until we can sort out BE */
		reqBody.meta[ utils.KEY_PRICE ] = '0';
	} else if ( method === METHODS.PUT ) {
		path += `/${ payload.id }`;
	}

	const options = {
		path,
		params: {
			method,
			body: JSON.stringify( reqBody ),
		},
		actions: {
			start: () => dispatch( actions.setRSVPIsLoading( true ) ),
			success: ( { body } ) => {
				if ( method === METHODS.POST ) {
					dispatch( actions.createRSVP() );
					dispatch( actions.setRSVPId( body.id ) );
				}
				dispatch( actions.setRSVPDetails( payload ) );
				dispatch( actions.setRSVPHasChanges( false ) );
				dispatch( actions.setRSVPIsLoading( false ) );
			},
			error: () => dispatch( actions.setRSVPIsLoading( false ) ),
		},
	};

	dispatch( wpRequestActions.wpRequest( options ) );

	/**
	 * Fires after an RSVP is created or updated.
	 *
	 * @since 5.20.0
	 * @param {Object}  payload  The RSVP payload.
	 * @param {boolean} isCreate Whether the RSVP was created or not.
	 */
	doAction( 'tec.tickets.blocks.rsvp.createdOrUpdated', payload, method === METHODS.POST );
};

export const createRSVP = createOrUpdateRSVP( METHODS.POST );

export const updateRSVP = createOrUpdateRSVP( METHODS.PUT );

export const deleteRSVP = ( id ) => ( dispatch ) => {
	const path = `${ utils.RSVP_POST_TYPE }/${ id }`;
	const options = {
		path,
		params: {
			method: METHODS.DELETE,
		},
	};

	dispatch( wpRequestActions.wpRequest( options ) );

	/**
	 * Fires after an RSVP is deleted.
	 *
	 * @since 5.20.0
	 * @param {number} id The RSVP ID.
	 */
	doAction( 'tec.tickets.blocks.rsvp.deleted', id );
};

const getRSVPV1 = ( postId, page = 1 ) => ( dispatch ) => {
	const path = `${ utils.RSVP_POST_TYPE }?per_page=100&page=${ page }&context=edit`;

	const options = {
		path,
		params: {
			method: METHODS.GET,
		},
		actions: {
			start: () => dispatch( actions.setRSVPIsLoading( true ) ),
			success: ( { body, headers } ) => {
				const filteredRSVPs = body.filter(
					( rsvp ) => rsvp.meta[ utils.KEY_RSVP_FOR_EVENT ] == postId // eslint-disable-line eqeqeq
				);
				const totalPages = headers.get( 'x-wp-totalpages' );

				if ( filteredRSVPs.length ) {
					/**
					 * @todo We are currently only fetching the first RSVP.
					 *       If an event has more than 1 RSVP set up from
					 *       the classic editor, only one will be displayed.
					 *       The strategy to handle this is is being worked on.
					 */
					const datePickerFormat = globals.tecDateSettings().datepickerFormat;

					const rsvp = filteredRSVPs[ 0 ];
					const { meta = {} } = rsvp;
					const startMoment = momentUtil.toMoment( meta[ utils.KEY_TICKET_START_DATE ] );
					const endMoment = momentUtil.toMoment( meta[ utils.KEY_TICKET_END_DATE ] );
					const startDateInput = datePickerFormat
						? startMoment.format( momentUtil.toFormat( datePickerFormat ) )
						: momentUtil.toDate( startMoment );
					const endDateInput = datePickerFormat
						? endMoment.format( momentUtil.toFormat( datePickerFormat ) )
						: momentUtil.toDate( endMoment );
					const capacity =
						meta[ utils.KEY_TICKET_CAPACITY ] >= 0 ? meta[ utils.KEY_TICKET_CAPACITY ] : '';
					const notGoingResponses = meta[ utils.KEY_TICKET_SHOW_NOT_GOING ];

					dispatch( actions.createRSVP() );
					dispatch( actions.setRSVPId( rsvp.id ) );
					dispatch(
						actions.setRSVPGoingCount( parseInt( meta[ utils.KEY_TICKET_GOING_COUNT ], 10 ) || 0 )
					);
					dispatch(
						actions.setRSVPNotGoingCount(
							parseInt( meta[ utils.KEY_TICKET_NOT_GOING_COUNT ], 10 ) || 0
						)
					);
					dispatch(
						actions.setRSVPHasAttendeeInfoFields( meta[ utils.KEY_TICKET_HAS_ATTENDEE_INFO_FIELDS ] )
					);
					dispatch(
						actions.setRSVPDetails( {
							title: utils.normalizeTitle( rsvp.title ),
							description: utils.normalizeDescription( rsvp.excerpt ),
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
							tempTitle: utils.normalizeTitle( rsvp.title ),
							tempDescription: utils.normalizeDescription( rsvp.excerpt ),
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
					dispatch( actions.setRSVPIsLoading( false ) );
				} else if ( page < totalPages ) {
					dispatch( getRSVPV1( postId, page + 1 ) );
				} else {
					dispatch( actions.setRSVPIsLoading( false ) );
				}
			},
			error: () => dispatch( actions.setRSVPIsLoading( false ) ),
		},
	};

	dispatch( wpRequestActions.wpRequest( options ) );
};

const getRSVPV2 = ( postId ) => async ( dispatch ) => {
	const config = getV2Config();

	dispatch( actions.setRSVPIsLoading( true ) );

	try {
		const tickets = await apiFetch( {
			path: `${ config.ticketsEndpoint }?per_page=100&event=${ postId }&type=${ config.ticketType }`,
			method: 'GET',
			headers: TEC_EEA_HEADER,
		} );

		const rsvpTickets = Array.isArray( tickets ) ? tickets : [];

		if ( rsvpTickets.length > 0 ) {
			const rsvp = rsvpTickets[ 0 ];
			const datePickerFormat = globals.tecDateSettings().datepickerFormat;

			const title = utils.normalizeTitle( rsvp.title );
			const description = utils.normalizeDescription( rsvp.description, rsvp.excerpt );

			const startMoment = momentUtil.toMoment( rsvp.start_date );
			const endMoment = momentUtil.toMoment( rsvp.end_date );

			const startDateInput = datePickerFormat
				? startMoment.format( momentUtil.toFormat( datePickerFormat ) )
				: momentUtil.toDate( startMoment );
			const endDateInput = datePickerFormat
				? endMoment.format( momentUtil.toFormat( datePickerFormat ) )
				: momentUtil.toDate( endMoment );

			const capacity = rsvp.capacity >= 0 ? rsvp.capacity : ( rsvp.stock >= 0 ? rsvp.stock : '' );
			const notGoingResponses = rsvp.show_not_going || false;

			dispatch( actions.createRSVP() );
			dispatch( actions.setRSVPId( rsvp.id ) );
			dispatch( actions.setRSVPGoingCount( parseInt( rsvp.going_count || rsvp.sold || 0, 10 ) ) );
			dispatch( actions.setRSVPNotGoingCount( parseInt( rsvp.not_going_count || 0, 10 ) ) );
			dispatch( actions.setRSVPHasAttendeeInfoFields( rsvp.has_attendee_info_fields || false ) );

			dispatch(
				actions.setRSVPDetails( {
					title,
					description,
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
					tempTitle: title,
					tempDescription: description,
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

export const getRSVP = ( postId, page = 1 ) => ( dispatch ) => {
	if ( isV2Enabled() ) {
		// V2 filters by event and type at the API level, pagination not needed.
		dispatch( getRSVPV2( postId ) );
	} else {
		dispatch( getRSVPV1( postId, page ) );
	}
};
