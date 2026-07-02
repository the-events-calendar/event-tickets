/**
 * WordPress dependencies
 */
import { doAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import * as actions from '../rsvp-shared/actions';
import * as utils from '../../utils';
import { middlewares } from '@moderntribe/common/store';
import { globals, time, moment as momentUtil } from '@moderntribe/common/utils';
import { normalizeRSVPResponseFromV1Post } from '../rsvp-shared/utils/normalize-rsvp-response';
import { hydrateRsvpAttendanceCounts } from '../rsvp-shared/utils/hydrate-rsvp-attendance-counts';

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

const hydrateRSVPFromResponse = ( dispatch, normalized ) => {
	dispatch( actions.createRSVP() );
	dispatch( actions.setRSVPId( normalized.id ) );
	hydrateRsvpAttendanceCounts( dispatch, actions, {
		goingCount: normalized.goingCount,
		notGoingCount: normalized.notGoingCount,
	} );
	dispatch( actions.setRSVPHasAttendeeInfoFields( normalized.hasAttendeeInfoFields ) );

	if ( normalized.fieldLabels && normalized.fieldLabels.length ) {
		dispatch( actions.setRSVPAttendeeInfoFieldNames( normalized.fieldLabels ) );
	}

	dispatch( actions.setRSVPDetails( normalized.details ) );
	dispatch( actions.setRSVPTempDetails( normalized.tempDetails ) );
};

export const getRSVP = ( postId, page = 1 ) => ( dispatch ) => {
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
					const normalized = normalizeRSVPResponseFromV1Post( filteredRSVPs[ 0 ] );
					hydrateRSVPFromResponse( dispatch, normalized );
					dispatch( actions.setRSVPIsLoading( false ) );
				} else if ( page < totalPages ) {
					dispatch( getRSVP( postId, page + 1 ) );
				} else {
					dispatch( actions.setRSVPIsLoading( false ) );
				}
			},
			error: () => dispatch( actions.setRSVPIsLoading( false ) ),
		},
	};

	dispatch( wpRequestActions.wpRequest( options ) );
};
