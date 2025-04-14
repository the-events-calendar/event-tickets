/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as utils from '../../utils';
import { middlewares } from '@moderntribe/common/store';
import { globals, time, moment as momentUtil } from '@moderntribe/common/utils';
import { doAction } from '@wordpress/hooks';

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

export const getRSVP =
	( postId, page = 1 ) =>
	( dispatch ) => {
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
						/* If RSVP for event exists in fetched data */
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
								title: rsvp.title.raw,
								description: rsvp.excerpt.raw,
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
								tempTitle: rsvp.title.raw,
								tempDescription: rsvp.excerpt.raw,
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
						/* If there are more pages */
						dispatch( getRSVP( postId, page + 1 ) );
					} else {
						/* Did not find RSVP */
						dispatch( actions.setRSVPIsLoading( false ) );
					}
				},
				error: () => dispatch( actions.setRSVPIsLoading( false ) ),
			},
		};

		dispatch( wpRequestActions.wpRequest( options ) );
	};
