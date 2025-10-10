/* eslint-disable camelcase */

/**
 * External Dependencies
 */
import {
	all,
	call,
	cancel,
	fork,
	put,
	select,
	take,
	takeEvery,
} from 'redux-saga/effects';
import { includes } from 'lodash';

/**
 * Wordpress dependencies
 */
import { dispatch as wpDispatch, select as wpSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { applyFilters, doAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import * as constants from './constants';
import * as types from './types';
import * as actions from './actions';
import * as selectors from './selectors';
import { DEFAULT_STATE } from './reducer';
import { DEFAULT_STATE as TICKET_HEADER_IMAGE_DEFAULT_STATE } from './reducers/header-image';
import { DEFAULT_STATE as TICKET_DEFAULT_STATE } from './reducers/tickets/ticket';
import * as rsvpActions from '../../blocks/rsvp/actions';
import { DEFAULT_STATE as RSVP_HEADER_IMAGE_DEFAULT_STATE } from '../rsvp/reducers/header-image';
import * as utils from '../../utils';
import {
	api,
	globals,
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';
import { plugins } from '@moderntribe/common/data';
import { MOVE_TICKET_SUCCESS } from '../../shared/move/types';
import * as moveSelectors from '../../shared/move/selectors';
import {
	createDates,
	createWPEditorNotSavingChannel,
	createWPEditorSavingChannel,
	hasPostTypeChannel,
	isTribeEventPostType,
} from '../../shared/sagas';
import { isTicketEditableFromPost } from './utils';

const { UNLIMITED, SHARED, TICKET_TYPES, PROVIDER_CLASS_TO_PROVIDER_MAPPING } =
	constants;
const { restNonce, tecDateSettings } = globals;
const { wpREST } = api;

export function* createMissingTicketBlocks( tickets ) {
	const { insertBlock, updateBlockListSettings } = yield call(
		wpDispatch,
		'core/block-editor'
	);
	const { getBlockCount, getBlocks } = yield call(
		wpSelect,
		'core/block-editor'
	);
	const ticketsBlocks = yield call(
		[ getBlocks(), 'filter' ],
		( block ) => block.name === 'tribe/tickets'
	);

	ticketsBlocks.forEach( ( { clientId } ) => {
		// Since we're not using the store provided by WordPress, we need to update the block list
		// settings for the Tickets block here to allow the tickets-item block to be inserted.
		// If the WP store did not initialize yet when the `insertBlock` function is called, the
		// block will not be inserted and there will be a silent failure.
		updateBlockListSettings( clientId, {
			allowedBlocks: [ 'tribe/tickets-item' ],
		} );
		tickets.forEach( ( ticketId ) => {
			const attributes = {
				hasBeenCreated: true,
				ticketId,
			};
			const nextChildPosition = getBlockCount( clientId );
			const block = createBlock( 'tribe/tickets-item', attributes );
			insertBlock( block, nextChildPosition, clientId, false );
		} );
	} );
}

export function formatTicketFromRestToAttributeFormat( ticket ) {
	const capacity = ticket?.capacity_details?.max || 0;
	const available = ticket?.capacity_details?.available || 0;
	const capacityType =
		ticket?.capacity_details?.global_stock_mode || constants.UNLIMITED;
	const sold = ticket?.capacity_details?.sold || 0;
	const isShared =
		capacityType === constants.SHARED ||
		capacityType === constants.CAPPED ||
		capacityType === constants.GLOBAL;

	return {
		id: ticket.id,
		type: ticket.type,
		title: ticket.title,
		description: ticket.description,
		capacityType,
		price: ticket?.cost || '0.00',
		capacity,
		available,
		sharedCapacity: capacity,
		sold,
		shareSold: sold,
		isShared,
		currencyDecimalPoint:
			ticket?.cost_details?.currency_decimal_separator || '.',
		currencyNumberOfDecimals:
			ticket?.cost_details?.currency_decimal_numbers || 2,
		currencyPosition: ticket?.cost_details?.currency_position || 'prefix',
		currencySymbol: ticket?.cost_details.currency_symbol || '$',
		currencyThousandsSep:
			ticket?.cost_details?.currency_thousand_separator || ',',
	};
}

export function* updateUneditableTickets() {
	yield put( actions.setUneditableTicketsLoading( true ) );

	const post = yield call( () => wpSelect( 'core/editor' ).getCurrentPost() );

	if ( ! post?.id ) {
		return;
	}

	// Get **all** the tickets, not just the uneditable ones. Filtering will take care of removing the editable ones.
	const { response, data = { tickets: [] } } = yield call( wpREST, {
		namespace: 'tribe/tickets/v1',
		path: `tickets/?include_post=${ post.id }&per_page=30`,
		initParams: {
			method: 'GET',
		},
	} );

	if ( response?.status !== 200 || ! Array.isArray( data?.tickets ) ) {
		// Something went wrong, bail out.
		return null;
	}

	const restFormatUneditableTickets = data.tickets
		// Remove the editable tickets.
		.filter(
			( ticket ) =>
				! isTicketEditableFromPost( ticket.id, ticket.type, post )
		);

	const uneditableTickets = [];

	if ( restFormatUneditableTickets.length >= 1 ) {
		for ( const ticket of restFormatUneditableTickets ) {
			const formattedUneditableTicket =
				yield formatTicketFromRestToAttributeFormat( ticket );
			uneditableTickets.push( formattedUneditableTicket );
		}
	}

	/**
	 * Fires after the uneditable tickets have been updated from the backend.
	 *
	 * @since 5.8.0
	 * @param {Object[]} uneditableTickets The uneditable tickets just fetched from the backend.
	 */
	doAction(
		'tec.tickets.blocks.uneditableTicketsUpdated',
		uneditableTickets
	);

	yield put( actions.setUneditableTickets( uneditableTickets ) );
	yield put( actions.setUneditableTicketsLoading( false ) );
}

export function* setTicketsInitialState( action ) {
	const { get } = action.payload;

	const currentPost = yield wpSelect( 'core/editor' ).getCurrentPost();

	const header = parseInt(
		get( 'header', TICKET_HEADER_IMAGE_DEFAULT_STATE.id ),
		10
	);
	const sharedCapacity = get( 'sharedCapacity' );
	// Shape: [ {id: int, type: string}, ... ].
	const allTickets = JSON.parse( get( 'tickets', '[]' ) );

	const { editableTickets, uneditableTickets } = allTickets.reduce(
		( acc, ticket ) => {
			if (
				isTicketEditableFromPost( ticket.id, ticket.type, currentPost )
			) {
				acc.editableTickets.push( ticket );
			} else {
				acc.uneditableTickets.push( ticket );
			}
			return acc;
		},
		{ editableTickets: [], uneditableTickets: [] }
	);

	// Get only the IDs of the tickets that are not in the block list already.
	const ticketsInBlock = yield select( selectors.getTicketsIdsInBlocks );
	const ticketsDiff = editableTickets.filter(
		( item ) => ! includes( ticketsInBlock, item.id )
	);

	if ( ticketsDiff.length >= 1 ) {
		yield call(
			createMissingTicketBlocks,
			ticketsDiff.map( ( ticket ) => ticket.id )
		);
	}

	if ( uneditableTickets.length >= 1 ) {
		yield put( actions.setUneditableTickets( uneditableTickets ) );
	}

	// Meta value is '0' however fields use empty string as default
	if ( sharedCapacity !== '0' ) {
		yield all( [
			put( actions.setTicketsSharedCapacity( sharedCapacity ) ),
			put( actions.setTicketsTempSharedCapacity( sharedCapacity ) ),
		] );
	}

	if ( ! isNaN( header ) && header !== 0 ) {
		yield put( actions.fetchTicketsHeaderImage( header ) );
	}

	let provider = get( 'provider', DEFAULT_STATE.provider );
	if ( provider === constants.RSVP_CLASS || ! provider ) {
		const defaultProvider = yield select(
			selectors.getDefaultTicketProvider
		);
		provider =
			defaultProvider === constants.RSVP_CLASS ? '' : defaultProvider;
	}
	yield put( actions.setTicketsProvider( provider ) );
}

export function* resetTicketsBlock() {
	const hasCreatedTickets = yield select( selectors.hasCreatedTickets );
	yield all( [
		put( actions.removeTicketBlocks() ),
		put( actions.setTicketsIsSettingsOpen( false ) ),
	] );

	if ( ! hasCreatedTickets ) {
		const currentMeta = yield call(
			[ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ],
			'meta'
		);
		const newMeta = {
			...currentMeta,
			[ utils.KEY_TICKET_CAPACITY ]: '',
		};
		yield call( [ wpDispatch( 'core/editor' ), 'editPost' ], {
			meta: newMeta,
		} );
		yield all( [
			put( actions.setTicketsSharedCapacity( '' ) ),
			put( actions.setTicketsTempSharedCapacity( '' ) ),
		] );
	}
}

export function* setTicketInitialState( action ) {
	const { clientId, get } = action.payload;
	const ticketId = get( 'ticketId', TICKET_DEFAULT_STATE.ticketId );
	const hasBeenCreated = get(
		'hasBeenCreated',
		TICKET_DEFAULT_STATE.hasBeenCreated
	);

	const datePickerFormat = tecDateSettings().datepickerFormat;
	const publishDate = yield call(
		[ wpSelect( 'core/editor' ), 'getEditedPostAttribute' ],
		'date'
	);
	const startMoment = yield call( momentUtil.toMoment, publishDate );
	const startDate = yield call( momentUtil.toDatabaseDate, startMoment );
	const startDateInput = yield datePickerFormat
		? call( momentUtil.toDate, startMoment, datePickerFormat )
		: call( momentUtil.toDate, startMoment );
	const startTime = yield call( momentUtil.toDatabaseTime, startMoment );
	const startTimeInput = yield call( momentUtil.toTime, startMoment );

	yield all( [
		put( actions.setTicketStartDate( clientId, startDate ) ),
		put( actions.setTicketStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketStartDateMoment( clientId, startMoment ) ),
		put( actions.setTicketStartTime( clientId, startTime ) ),
		put( actions.setTicketStartTimeInput( clientId, startTimeInput ) ),
		put( actions.setTicketTempStartDate( clientId, startDate ) ),
		put( actions.setTicketTempStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketTempStartDateMoment( clientId, startMoment ) ),
		put( actions.setTicketTempStartTime( clientId, startTime ) ),
		put( actions.setTicketTempStartTimeInput( clientId, startTimeInput ) ),
		put( actions.setTicketHasBeenCreated( clientId, hasBeenCreated ) ),
	] );

	const isEvent = yield call( isTribeEventPostType );

	// Only run this on events post type.
	if ( isEvent && window.tec.events ) {
		// This try-catch may be redundant given the above if statement.
		try {
			// NOTE: This requires TEC to be installed, if not installed, do not set an end date
			// Ticket purchase window should end when event starts
			const eventStart = yield select(
				window.tec.events.app.main.data.blocks.datetime.selectors
					.getStart
			);
			const endMoment = yield call( momentUtil.toMoment, eventStart );
			const endDate = yield call( momentUtil.toDatabaseDate, endMoment );
			const endDateInput = yield datePickerFormat
				? call( momentUtil.toDate, endMoment, datePickerFormat )
				: call( momentUtil.toDate, endMoment );
			const endTime = yield call( momentUtil.toDatabaseTime, endMoment );
			const endTimeInput = yield call( momentUtil.toTime, endMoment );

			yield all( [
				put( actions.setTicketEndDate( clientId, endDate ) ),
				put( actions.setTicketEndDateInput( clientId, endDateInput ) ),
				put( actions.setTicketEndDateMoment( clientId, endMoment ) ),
				put( actions.setTicketEndTime( clientId, endTime ) ),
				put( actions.setTicketEndTimeInput( clientId, endTimeInput ) ),
				put( actions.setTicketTempEndDate( clientId, endDate ) ),
				put(
					actions.setTicketTempEndDateInput( clientId, endDateInput )
				),
				put(
					actions.setTicketTempEndDateMoment( clientId, endMoment )
				),
				put( actions.setTicketTempEndTime( clientId, endTime ) ),
				put(
					actions.setTicketTempEndTimeInput( clientId, endTimeInput )
				),
			] );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( err );
			// ¯\_(ツ)_/¯
		}
	}

	const hasTicketsPlus = yield select(
		plugins.selectors.hasPlugin,
		plugins.constants.TICKETS_PLUS
	);
	if ( hasTicketsPlus ) {
		yield all( [
			put(
				actions.setTicketCapacityType(
					clientId,
					constants.TICKET_TYPES[ constants.SHARED ]
				)
			),
			put(
				actions.setTicketTempCapacityType(
					clientId,
					constants.TICKET_TYPES[ constants.SHARED ]
				)
			),
		] );
	}

	const sharedCapacity = yield select( selectors.getTicketsSharedCapacity );
	if ( sharedCapacity ) {
		yield all( [
			put( actions.setTicketCapacity( clientId, sharedCapacity ) ),
			put( actions.setTicketTempCapacity( clientId, sharedCapacity ) ),
		] );
	}

	if ( ticketId !== 0 ) {
		yield all( [
			put( actions.setTicketId( clientId, ticketId ) ),
			call( fetchTicket, { payload: { clientId, ticketId } } ),
		] );
	}

	yield call( handleTicketDurationError, clientId );
	yield fork( saveTicketWithPostSave, clientId );
}

export function* setBodyDetails( clientId ) {
	let body = new FormData();
	const props = { clientId };
	const rootClientId = yield call(
		[ wpSelect( 'core/block-editor' ), 'getBlockRootClientId' ],
		clientId
	);
	const ticketProvider = yield select( selectors.getTicketProvider, props );
	const ticketsProvider = yield select( selectors.getTicketsProvider );

	body.append(
		'post_id',
		yield call( [ wpSelect( 'core/editor' ), 'getCurrentPostId' ] )
	);
	body.append( 'provider', ticketProvider || ticketsProvider );
	body.append( 'name', yield select( selectors.getTicketTempTitle, props ) );
	body.append(
		'description',
		yield select( selectors.getTicketTempDescription, props )
	);
	body.append( 'price', yield select( selectors.getTicketTempPrice, props ) );
	body.append(
		'start_date',
		yield select( selectors.getTicketTempStartDate, props )
	);
	body.append(
		'start_time',
		yield select( selectors.getTicketTempStartTime, props )
	);
	body.append(
		'end_date',
		yield select( selectors.getTicketTempEndDate, props )
	);
	body.append(
		'end_time',
		yield select( selectors.getTicketTempEndTime, props )
	);
	body.append( 'sku', yield select( selectors.getTicketTempSku, props ) );
	body.append(
		'iac',
		yield select( selectors.getTicketTempIACSetting, props )
	);
	body.append(
		'menu_order',
		yield call(
			[ wpSelect( 'core/block-editor' ), 'getBlockIndex' ],
			clientId,
			rootClientId
		)
	);

	const capacityType = yield select(
		selectors.getTicketTempCapacityType,
		props
	);
	const capacity = yield select( selectors.getTicketTempCapacity, props );

	const isUnlimited = capacityType === TICKET_TYPES[ UNLIMITED ];
	body.append( 'ticket[mode]', isUnlimited ? '' : capacityType );
	body.append( 'ticket[capacity]', isUnlimited ? '' : capacity );

	if ( capacityType === TICKET_TYPES[ SHARED ] ) {
		body.append(
			'ticket[event_capacity]',
			yield select( selectors.getTicketsTempSharedCapacity )
		);
	}

	const showSalePrice = yield select( selectors.showSalePrice, props );

	if ( showSalePrice ) {
		body.append(
			'ticket[sale_price][checked]',
			yield select( selectors.getTempSalePriceChecked, props )
		);
		body.append(
			'ticket[sale_price][price]',
			yield select( selectors.getTempSalePrice, props )
		);
		body.append(
			'ticket[sale_price][start_date]',
			yield select( selectors.getTicketTempSaleStartDate, props )
		);
		body.append(
			'ticket[sale_price][end_date]',
			yield select( selectors.getTicketTempSaleEndDate, props )
		);
	}

	/**
	 * Fires after the body details have been set and before the request is sent.
	 * The action will fire both when a ticket is being created and when an existing ticket is being updated.
	 *
	 * @since 5.16.0
	 * @param {Object} body     The body of the request.
	 * @param {string} clientId The client ID of the ticket block that is being created or updated.
	 */
	body = applyFilters( 'tec.tickets.blocks.setBodyDetails', body, clientId );

	return body;
}

export function* removeTicketBlock( clientId ) {
	const { removeBlock } = wpDispatch( 'core/editor' );

	yield all( [
		put( actions.removeTicketBlock( clientId ) ),
		call( removeBlock, clientId ),
	] );
}

export function* fetchTicket( action ) {
	const { ticketId, clientId } = action.payload;

	if ( ticketId === 0 ) {
		return;
	}

	yield put( actions.setTicketIsLoading( clientId, true ) );

	try {
		const { response, data: ticket } = yield call( wpREST, {
			path: `tickets/${ ticketId }`,
			namespace: 'tribe/tickets/v1',
		} );

		const { status = '', provider } = ticket;

		if (
			response.status === 404 ||
			status === 'trash' ||
			provider === constants.RSVP
		) {
			yield call( removeTicketBlock, clientId );
			return;
		}

		if ( response.ok ) {
			/* eslint-disable camelcase */

			const {
				totals = {},
				available_from,
				available_until,
				cost_details,
				title,
				description,
				sku,
				iac,
				capacity_type,
				capacity,
				supports_attendee_information,
				attendee_information_fields,
				type,
				sale_price_data,
				on_sale,
			} = ticket;
			/* eslint-enable camelcase */

			const datePickerFormat = tecDateSettings().datepickerFormat;

			const startMoment = yield call(
				momentUtil.toMoment,
				available_from
			);
			const startDate = yield call(
				momentUtil.toDatabaseDate,
				startMoment
			);
			const startDateInput = yield datePickerFormat
				? call( momentUtil.toDate, startMoment, datePickerFormat )
				: call( momentUtil.toDate, startMoment );
			const startTime = yield call(
				momentUtil.toDatabaseTime,
				startMoment
			);
			const startTimeInput = yield call( momentUtil.toTime, startMoment );

			let endMoment = yield call( momentUtil.toMoment, available_until );
			let endDate = yield call( momentUtil.toDatabaseDate, endMoment );
			let endDateInput = yield datePickerFormat
				? call( momentUtil.toDate, endMoment, datePickerFormat )
				: call( momentUtil.toDate, endMoment );
			let endTime = yield call( momentUtil.toDatabaseTime, endMoment );
			let endTimeInput = yield call( momentUtil.toTime, endMoment );

			if ( available_until ) {
				// eslint-disable-line camelcase
				endMoment = yield call( momentUtil.toMoment, available_until );
				endDate = yield call( momentUtil.toDatabaseDate, endMoment );
				endDateInput = yield datePickerFormat
					? call( momentUtil.toDate, endMoment, datePickerFormat )
					: call( momentUtil.toDate, endMoment );
				endTime = yield call( momentUtil.toDatabaseTime, endMoment );
				endTimeInput = yield call( momentUtil.toTime, endMoment );
			}

			const salePriceChecked = sale_price_data?.enabled || false;
			const salePrice = sale_price_data?.sale_price || '';

			const saleStartDateMoment = yield call(
				momentUtil.toMoment,
				sale_price_data?.start_date || ''
			);
			const saleStartDate = yield call(
				momentUtil.toDatabaseDate,
				saleStartDateMoment
			);
			const saleStartDateInput = yield call(
				momentUtil.toDate,
				saleStartDateMoment
			);

			const saleEndDateMoment = yield call(
				momentUtil.toMoment,
				sale_price_data?.end_date || ''
			);
			const saleEndDate = yield call(
				momentUtil.toDatabaseDate,
				saleEndDateMoment
			);
			const saleEndDateInput = yield call(
				momentUtil.toDate,
				saleEndDateMoment
			);

			const details = {
				attendeeInfoFields: attendee_information_fields,
				title,
				description,
				price: cost_details.values[ 0 ],
				on_sale,
				sku,
				iac,
				startDate,
				startDateInput,
				startDateMoment: startMoment,
				endDate,
				endDateInput,
				endDateMoment: endMoment,
				startTime,
				endTime,
				startTimeInput,
				endTimeInput,
				capacityType: capacity_type,
				capacity,
				type,
				salePriceChecked,
				salePrice,
				saleStartDate,
				saleStartDateInput,
				saleStartDateMoment,
				saleEndDate,
				saleEndDateInput,
				saleEndDateMoment,
			};

			yield all( [
				put( actions.setTicketDetails( clientId, details ) ),
				put( actions.setTicketTempDetails( clientId, details ) ),
				put( actions.setTicketSold( clientId, totals.sold ) ),
				put( actions.setTicketAvailable( clientId, totals.stock ) ),
				put(
					actions.setTicketCurrencySymbol(
						clientId,
						cost_details.currency_symbol
					)
				),
				put(
					actions.setTicketCurrencyPosition(
						clientId,
						cost_details.currency_position
					)
				),
				put( actions.setTicketProvider( clientId, provider ) ),
				put(
					actions.setTicketHasAttendeeInfoFields(
						clientId,
						supports_attendee_information
					)
				),
				put( actions.setTicketHasBeenCreated( clientId, true ) ),
			] );

			/**
			 * Fires after the ticket has been fetched.
			 *
			 * @since 5.18.0
			 * @param {string} clientId The ticket's client ID.
			 * @param {Object} ticket   The ticket object.
			 * @param {Object} details  The ticket details.
			 */
			yield doAction(
				'tec.tickets.blocks.fetchTicket',
				clientId,
				ticket,
				details
			);
		}
	} catch ( e ) {
		// eslint-disable-next-line no-console
		console.error( e );
		/**
		 * @todo handle error scenario
		 */
	}

	yield put( actions.setTicketIsLoading( clientId, false ) );
}

export function* createNewTicket( action ) {
	const { clientId } = action.payload;
	const props = { clientId };

	const { add_ticket_nonce = '' } = restNonce(); // eslint-disable-line camelcase
	const body = yield call( setBodyDetails, clientId );
	body.append( 'add_ticket_nonce', add_ticket_nonce );

	try {
		yield put( actions.setTicketIsLoading( clientId, true ) );
		const { response, data: ticket } = yield call( wpREST, {
			path: 'tickets/',
			namespace: 'tribe/tickets/v1',
			initParams: {
				method: 'POST',
				body,
			},
		} );

		if ( response.ok ) {
			const sharedCapacity = yield select(
				selectors.getTicketsSharedCapacity
			);
			const tempSharedCapacity = yield select(
				selectors.getTicketsTempSharedCapacity
			);
			if (
				sharedCapacity === '' &&
				! isNaN( tempSharedCapacity ) &&
				tempSharedCapacity > 0
			) {
				yield put(
					actions.setTicketsSharedCapacity( tempSharedCapacity )
				);
			}
			const available =
				ticket.capacity_details.available === -1
					? 0
					: ticket.capacity_details.available;

			const { sale_price_data } = ticket; // eslint-disable-line camelcase
			const salePriceChecked = sale_price_data?.enabled || false;
			const salePrice = sale_price_data?.sale_price || '';

			const [
				title,
				description,
				price,
				sku,
				iac,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
				saleStartDate,
				saleStartDateInput,
				saleStartDateMoment,
				saleEndDate,
				saleEndDateInput,
				saleEndDateMoment,
			] = yield all( [
				select( selectors.getTicketTempTitle, props ),
				select( selectors.getTicketTempDescription, props ),
				select( selectors.getTicketTempPrice, props ),
				select( selectors.getTicketTempSku, props ),
				select( selectors.getTicketTempIACSetting, props ),
				select( selectors.getTicketTempStartDate, props ),
				select( selectors.getTicketTempStartDateInput, props ),
				select( selectors.getTicketTempStartDateMoment, props ),
				select( selectors.getTicketTempEndDate, props ),
				select( selectors.getTicketTempEndDateInput, props ),
				select( selectors.getTicketTempEndDateMoment, props ),
				select( selectors.getTicketTempStartTime, props ),
				select( selectors.getTicketTempEndTime, props ),
				select( selectors.getTicketTempStartTimeInput, props ),
				select( selectors.getTicketTempEndTimeInput, props ),
				select( selectors.getTicketTempCapacityType, props ),
				select( selectors.getTicketTempCapacity, props ),
				select( selectors.getTicketTempSaleStartDate, props ),
				select( selectors.getTicketTempSaleStartDateInput, props ),
				select( selectors.getTicketTempSaleStartDateMoment, props ),
				select( selectors.getTicketTempSaleEndDate, props ),
				select( selectors.getTicketTempSaleEndDateInput, props ),
				select( selectors.getTicketTempSaleEndDateMoment, props ),
			] );

			const ticketDetails = {
				title,
				description,
				price,
				sku,
				iac,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
				salePriceChecked,
				salePrice,
				saleStartDate,
				saleStartDateInput,
				saleStartDateMoment,
				saleEndDate,
				saleEndDateInput,
				saleEndDateMoment,
			};

			yield all( [
				put( actions.setTicketDetails( clientId, ticketDetails ) ),
				put(
					actions.setTempSalePriceChecked(
						clientId,
						salePriceChecked
					)
				),
				put( actions.setTempSalePrice( clientId, salePrice ) ),
				put( actions.setTicketId( clientId, ticket.id ) ),
				put( actions.setTicketHasBeenCreated( clientId, true ) ),
				put( actions.setTicketAvailable( clientId, available ) ),
				put(
					actions.setTicketProvider(
						clientId,
						PROVIDER_CLASS_TO_PROVIDER_MAPPING[
							ticket.provider_class
						]
					)
				),
				put( actions.setTicketHasChanges( clientId, false ) ),
			] );

			/**
			 * Fires after the ticket has been created.
			 *
			 * @since 5.16.0
			 * @since 5.20.0 The `ticketId` and `ticketDetails` parameters were added.
			 * @param {string} clientId      The ticket's client ID.
			 * @param {number} ticketId      The ticket's ID.
			 * @param {Object} ticketDetails The ticket details.
			 */
			doAction(
				'tec.tickets.blocks.ticketCreated',
				clientId,
				ticket.id,
				ticketDetails
			);

			yield fork( saveTicketWithPostSave, clientId );
		}
	} catch ( e ) {
		// eslint-disable-next-line no-console
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( clientId, false ) );
	}
}

export function* updateTicket( action ) {
	const { clientId } = action.payload;
	const props = { clientId };

	const { edit_ticket_nonce = '' } = restNonce(); // eslint-disable-line camelcase
	const body = yield call( setBodyDetails, clientId );
	body.append( 'edit_ticket_nonce', edit_ticket_nonce );

	const ticketId = yield select( selectors.getTicketId, props );

	try {
		const data = [];
		for ( const [ key, value ] of body.entries() ) {
			data.push(
				`${ encodeURIComponent( key ) }=${ encodeURIComponent(
					value
				) }`
			);
		}

		yield put( actions.setTicketIsLoading( clientId, true ) );
		const { response, data: ticket } = yield call( wpREST, {
			path: `tickets/${ ticketId }`,
			namespace: 'tribe/tickets/v1',
			headers: {
				'Content-Type':
					'application/x-www-form-urlencoded;charset=UTF-8',
			},
			initParams: {
				method: 'PUT',
				body: data.join( '&' ),
			},
		} );

		if ( response.ok ) {
			const { capacity_details, sale_price_data, on_sale } = ticket; // eslint-disable-line camelcase, max-len
			const available =
				capacity_details.available === -1
					? 0
					: capacity_details.available;

			const salePriceChecked = sale_price_data?.enabled || false;
			const salePrice = sale_price_data?.sale_price || '';

			const datePickerFormat = tecDateSettings().datepickerFormat;
			const sale_start_date = sale_price_data?.start_date || ''; // eslint-disable-line camelcase
			const saleStartDateMoment = yield call(
				momentUtil.toMoment,
				sale_start_date
			);
			const saleStartDate = yield call(
				momentUtil.toDatabaseDate,
				saleStartDateMoment
			);
			const saleStartDateInput = yield datePickerFormat
				? call(
						momentUtil.toDate,
						saleStartDateMoment,
						datePickerFormat
				  )
				: call( momentUtil.toDate, saleStartDateMoment );

			const sale_end_date = sale_price_data?.end_date || ''; // eslint-disable-line camelcase
			const saleEndDateMoment = yield call(
				momentUtil.toMoment,
				sale_end_date
			);
			const saleEndDate = yield call(
				momentUtil.toDatabaseDate,
				saleEndDateMoment
			);
			const saleEndDateInput = yield datePickerFormat
				? call( momentUtil.toDate, saleEndDateMoment, datePickerFormat )
				: call( momentUtil.toDate, saleEndDateMoment );

			const [
				title,
				description,
				price,
				sku,
				iac,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
			] = yield all( [
				select( selectors.getTicketTempTitle, props ),
				select( selectors.getTicketTempDescription, props ),
				select( selectors.getTicketTempPrice, props ),
				select( selectors.getTicketTempSku, props ),
				select( selectors.getTicketTempIACSetting, props ),
				select( selectors.getTicketTempStartDate, props ),
				select( selectors.getTicketTempStartDateInput, props ),
				select( selectors.getTicketTempStartDateMoment, props ),
				select( selectors.getTicketTempEndDate, props ),
				select( selectors.getTicketTempEndDateInput, props ),
				select( selectors.getTicketTempEndDateMoment, props ),
				select( selectors.getTicketTempStartTime, props ),
				select( selectors.getTicketTempEndTime, props ),
				select( selectors.getTicketTempStartTimeInput, props ),
				select( selectors.getTicketTempEndTimeInput, props ),
				select( selectors.getTicketTempCapacityType, props ),
				select( selectors.getTicketTempCapacity, props ),
			] );

			const ticketDetails = {
				title,
				description,
				price,
				on_sale,
				sku,
				iac,
				startDate,
				startDateInput,
				startDateMoment,
				endDate,
				endDateInput,
				endDateMoment,
				startTime,
				endTime,
				startTimeInput,
				endTimeInput,
				capacityType,
				capacity,
				salePriceChecked,
				salePrice,
				saleStartDate,
				saleStartDateInput,
				saleStartDateMoment,
				saleEndDate,
				saleEndDateInput,
				saleEndDateMoment,
			};

			yield all( [
				put( actions.setTicketDetails( clientId, ticketDetails ) ),
				put( actions.setTicketSold( clientId, capacity_details.sold ) ),
				put( actions.setTicketAvailable( clientId, available ) ),
				put( actions.setTicketHasChanges( clientId, false ) ),
				put( actions.setTempSalePrice( clientId, salePrice ) ),
				put(
					actions.setTempSalePriceChecked(
						clientId,
						salePriceChecked
					)
				),
				put(
					actions.setTicketTempSaleStartDate(
						clientId,
						saleStartDate
					)
				),
				put(
					actions.setTicketTempSaleStartDateInput(
						clientId,
						saleStartDateInput
					)
				),
				put(
					actions.setTicketTempSaleStartDateMoment(
						clientId,
						saleStartDateMoment
					)
				),
				put(
					actions.setTicketTempSaleEndDate( clientId, saleEndDate )
				),
				put(
					actions.setTicketTempSaleEndDateInput(
						clientId,
						saleEndDateInput
					)
				),
				put(
					actions.setTicketTempSaleEndDateMoment(
						clientId,
						saleEndDateMoment
					)
				),
			] );

			/**
			 * Fires after the ticket has been updated.
			 *
			 * @since 5.16.0
			 * @since 5.20.0 The `ticketId and `ticketDetails` parameters were added
			 * @param {string} clientId      The ticket's client ID.
			 * @param {number} ticketId      The ticket's ID.
			 * @param {Object} ticketDetails The ticket details.
			 */
			doAction(
				'tec.tickets.blocks.ticketUpdated',
				clientId,
				ticketId,
				ticketDetails
			);
		}
	} catch ( e ) {
		// eslint-disable-next-line no-console
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketIsLoading( clientId, false ) );
	}
}

export function* deleteTicket( action ) {
	const { clientId, askForDeletion = true } = action.payload;
	const props = { clientId };

	let shouldDelete = false;

	if ( askForDeletion ) {
		shouldDelete = yield call(
			[ window, 'confirm' ],
			__(
				'Are you sure you want to delete this ticket? It cannot be undone.',
				'event-tickets'
			)
		);
	} else {
		shouldDelete = true;
	}

	if ( shouldDelete ) {
		const ticketId = yield select( selectors.getTicketId, props );
		const hasBeenCreated = yield select(
			selectors.getTicketHasBeenCreated,
			props
		);

		yield put( actions.setTicketIsSelected( clientId, false ) );
		yield put( actions.removeTicketBlock( clientId ) );
		yield call( [
			wpDispatch( 'core/block-editor' ),
			'clearSelectedBlock',
		] );
		yield call(
			[ wpDispatch( 'core/block-editor' ), 'removeBlocks' ],
			[ clientId ]
		);

		if ( hasBeenCreated ) {
			const { remove_ticket_nonce = '' } = restNonce(); // eslint-disable-line camelcase
			const postId = yield call( [
				wpSelect( 'core/editor' ),
				'getCurrentPostId',
			] );

			/**
			 * Encode params to be passed into the DELETE request as PHP doesn’t transform the request body
			 * of a DELETE request into a super global.
			 */
			const body = [
				`${ encodeURIComponent( 'post_id' ) }=${ encodeURIComponent(
					postId
				) }`,
				`${ encodeURIComponent(
					'remove_ticket_nonce'
				) }=${ encodeURIComponent( remove_ticket_nonce ) }`, // eslint-disable-line max-len
			];

			try {
				yield call( wpREST, {
					path: `tickets/${ ticketId }`,
					namespace: 'tribe/tickets/v1',
					headers: {
						'Content-Type':
							'application/x-www-form-urlencoded;charset=UTF-8',
					},
					initParams: {
						method: 'DELETE',
						body: body.join( '&' ),
					},
				} );

				/**
				 * Fires after the ticket has been deleted.
				 *
				 * @since 5.20.0
				 * @param {string} clientId The ticket's client ID.
				 * @param {number} ticketId The ticket's ID.
				 */
				doAction(
					'tec.tickets.blocks.ticketDeleted',
					clientId,
					ticketId
				);
			} catch ( e ) {
				/**
				 * @todo handle error on removal
				 */
			}
		}
	}
}

export function* fetchTicketsHeaderImage( action ) {
	const { id } = action.payload;
	yield put( actions.setTicketsIsSettingsLoading( true ) );

	try {
		const { response, data: media } = yield call( wpREST, {
			path: `media/${ id }`,
		} );

		if ( response.ok ) {
			const headerImage = {
				id: media.id,
				alt: media.alt_text,
				src: media.media_details.sizes.medium.source_url,
			};
			yield put( actions.setTicketsHeaderImage( headerImage ) );
		}
	} catch ( e ) {
		// eslint-disable-next-line no-console
		console.error( e );
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		yield put( actions.setTicketsIsSettingsLoading( false ) );
	}
}

export function* updateTicketsHeaderImage( action ) {
	const { image } = action.payload;
	const postId = yield call( [
		wpSelect( 'core/editor' ),
		'getCurrentPostId',
	] );
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: `${ image.id }`,
		},
	};

	try {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setTicketsIsSettingsLoading( true ) );
		yield put( rsvpActions.setRSVPIsSettingsLoading( true ) );

		const slug = wpSelect( 'core/editor' ).getCurrentPostType();
		const postType = wpSelect( 'core' ).getPostType( slug );
		const restBase = postType.rest_base;

		const { response } = yield call( wpREST, {
			path: `${ restBase }/${ postId }`,
			headers: {
				'Content-Type': 'application/json',
			},
			initParams: {
				method: 'PUT',
				body: JSON.stringify( body ),
			},
		} );

		if ( response.ok ) {
			const headerImage = {
				id: image.id,
				alt: image.alt,
				src: image.sizes.medium.url,
			};
			/**
			 * @todo: until rsvp and tickets header image can be separated, they need to be linked
			 */
			yield put( actions.setTicketsHeaderImage( headerImage ) );
			yield put( rsvpActions.setRSVPHeaderImage( headerImage ) );
		}
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setTicketsIsSettingsLoading( false ) );
		yield put( rsvpActions.setRSVPIsSettingsLoading( false ) );
	}
}

export function* deleteTicketsHeaderImage() {
	const postId = yield call( [
		wpSelect( 'core/editor' ),
		'getCurrentPostId',
	] );
	const body = {
		meta: {
			[ utils.KEY_TICKET_HEADER ]: null,
		},
	};

	try {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setTicketsIsSettingsLoading( true ) );
		yield put( rsvpActions.setRSVPIsSettingsLoading( true ) );

		const slug = wpSelect( 'core/editor' ).getCurrentPostType();
		const postType = wpSelect( 'core' ).getPostType( slug );
		const restBase = postType.rest_base;

		const { response } = yield call( api.wpREST, {
			path: `${ restBase }/${ postId }`,
			headers: {
				'Content-Type': 'application/json',
			},
			initParams: {
				method: 'PUT',
				body: JSON.stringify( body ),
			},
		} );

		if ( response.ok ) {
			/**
			 * @todo: until rsvp and tickets header image can be separated, they need to be linked
			 */
			yield put(
				actions.setTicketsHeaderImage(
					TICKET_HEADER_IMAGE_DEFAULT_STATE
				)
			);
			yield put(
				rsvpActions.setRSVPHeaderImage(
					RSVP_HEADER_IMAGE_DEFAULT_STATE
				)
			);
		}
	} catch ( e ) {
		/**
		 * @todo: handle error scenario
		 */
	} finally {
		/**
		 * @todo: until rsvp and tickets header image can be separated, they need to be linked
		 */
		yield put( actions.setTicketsIsSettingsLoading( false ) );
		yield put( rsvpActions.setRSVPIsSettingsLoading( false ) );
	}
}

export function* setTicketDetails( action ) {
	const { clientId, details } = action.payload;
	const {
		attendeeInfoFields,
		title,
		description,
		price,
		on_sale, // eslint-disable-line camelcase
		sku,
		iac,
		startDate,
		startDateInput,
		startDateMoment,
		endDate,
		endDateInput,
		endDateMoment,
		startTime,
		endTime,
		startTimeInput,
		endTimeInput,
		capacityType,
		capacity,
		type,
		salePriceChecked,
		salePrice,
		saleStartDate,
		saleStartDateInput,
		saleStartDateMoment,
		saleEndDate,
		saleEndDateInput,
		saleEndDateMoment,
	} = details;

	yield all( [
		put(
			actions.setTicketAttendeeInfoFields( clientId, attendeeInfoFields )
		),
		put( actions.setTicketTitle( clientId, title ) ),
		put( actions.setTicketDescription( clientId, description ) ),
		put( actions.setTicketPrice( clientId, price ) ),
		put( actions.setTicketOnSale( clientId, on_sale ) ),
		put( actions.setTicketSku( clientId, sku ) ),
		put( actions.setTicketIACSetting( clientId, iac ) ),
		put( actions.setTicketStartDate( clientId, startDate ) ),
		put( actions.setTicketStartDateInput( clientId, startDateInput ) ),
		put( actions.setTicketStartDateMoment( clientId, startDateMoment ) ),
		put( actions.setTicketEndDate( clientId, endDate ) ),
		put( actions.setTicketEndDateInput( clientId, endDateInput ) ),
		put( actions.setTicketEndDateMoment( clientId, endDateMoment ) ),
		put( actions.setTicketStartTime( clientId, startTime ) ),
		put( actions.setTicketEndTime( clientId, endTime ) ),
		put( actions.setTicketStartTimeInput( clientId, startTimeInput ) ),
		put( actions.setTicketEndTimeInput( clientId, endTimeInput ) ),
		put( actions.setTicketCapacityType( clientId, capacityType ) ),
		put( actions.setTicketCapacity( clientId, capacity ) ),
		put( actions.setTicketType( clientId, type ) ),
		put( actions.setSalePriceChecked( clientId, salePriceChecked ) ),
		put( actions.setSalePrice( clientId, salePrice ) ),
		put( actions.setTicketSaleStartDate( clientId, saleStartDate ) ),
		put(
			actions.setTicketSaleStartDateInput( clientId, saleStartDateInput )
		),
		put(
			actions.setTicketSaleStartDateMoment(
				clientId,
				saleStartDateMoment
			)
		),
		put( actions.setTicketSaleEndDate( clientId, saleEndDate ) ),
		put( actions.setTicketSaleEndDateInput( clientId, saleEndDateInput ) ),
		put(
			actions.setTicketSaleEndDateMoment( clientId, saleEndDateMoment )
		),
	] );
}

export function* setTicketTempDetails( action ) {
	const { clientId, tempDetails } = action.payload;
	const {
		title,
		description,
		price,
		sku,
		iac,
		startDate,
		startDateInput,
		startDateMoment,
		endDate,
		endDateInput,
		endDateMoment,
		startTime,
		endTime,
		startTimeInput,
		endTimeInput,
		capacityType,
		capacity,
		salePriceChecked,
		salePrice,
		saleStartDate,
		saleStartDateInput,
		saleStartDateMoment,
		saleEndDate,
		saleEndDateInput,
		saleEndDateMoment,
	} = tempDetails;

	yield all( [
		put( actions.setTicketTempTitle( clientId, title ) ),
		put( actions.setTicketTempDescription( clientId, description ) ),
		put( actions.setTicketTempPrice( clientId, price ) ),
		put( actions.setTicketTempSku( clientId, sku ) ),
		put( actions.setTicketTempIACSetting( clientId, iac ) ),
		put( actions.setTicketTempStartDate( clientId, startDate ) ),
		put( actions.setTicketTempStartDateInput( clientId, startDateInput ) ),
		put(
			actions.setTicketTempStartDateMoment( clientId, startDateMoment )
		),
		put( actions.setTicketTempEndDate( clientId, endDate ) ),
		put( actions.setTicketTempEndDateInput( clientId, endDateInput ) ),
		put( actions.setTicketTempEndDateMoment( clientId, endDateMoment ) ),
		put( actions.setTicketTempStartTime( clientId, startTime ) ),
		put( actions.setTicketTempEndTime( clientId, endTime ) ),
		put( actions.setTicketTempStartTimeInput( clientId, startTimeInput ) ),
		put( actions.setTicketTempEndTimeInput( clientId, endTimeInput ) ),
		put( actions.setTicketTempCapacityType( clientId, capacityType ) ),
		put( actions.setTicketTempCapacity( clientId, capacity ) ),
		put( actions.setTempSalePriceChecked( clientId, salePriceChecked ) ),
		put( actions.setTempSalePrice( clientId, salePrice ) ),
		put( actions.setTicketTempSaleStartDate( clientId, saleStartDate ) ),
		put(
			actions.setTicketTempSaleStartDateInput(
				clientId,
				saleStartDateInput
			)
		),
		put(
			actions.setTicketTempSaleStartDateMoment(
				clientId,
				saleStartDateMoment
			)
		),
		put( actions.setTicketTempSaleEndDate( clientId, saleEndDate ) ),
		put(
			actions.setTicketTempSaleEndDateInput( clientId, saleEndDateInput )
		),
		put(
			actions.setTicketTempSaleEndDateMoment(
				clientId,
				saleEndDateMoment
			)
		),
	] );
}

/**
 * Allows the Ticket to be saved at the same time a post is being saved.
 * Avoids the user having to open up the Ticket block, and then click update again there,
 * when changing the event start date.
 *
 * @param {string} clientId Client ID of ticket block
 * @export
 * @yield
 */
export function* saveTicketWithPostSave( clientId ) {
	let savingChannel, notSavingChannel;
	try {
		// Do nothing when not already created
		if ( yield select( selectors.getTicketHasBeenCreated, { clientId } ) ) {
			// Create channels for use
			savingChannel = yield call( createWPEditorSavingChannel );
			notSavingChannel = yield call( createWPEditorNotSavingChannel );

			while ( true ) {
				// Wait for channel to save
				yield take( savingChannel );

				// Update when saving
				yield call( updateTicket, { payload: { clientId } } );

				// Wait for channel to finish saving
				yield take( notSavingChannel );
			}
		}
	} catch ( error ) {
		console.error( error );
	} finally {
		// Close save channel if exists
		if ( savingChannel ) {
			yield call( [ savingChannel, 'close' ] );
		}
		// Close not saving channel if exists
		if ( notSavingChannel ) {
			yield call( [ notSavingChannel, 'close' ] );
		}
	}
}

/**
 * Will sync all tickets
 *
 * @param {string} prevStartDate Previous start date before latest set date time changes
 * @export
 * @yield
 */
export function* syncTicketsSaleEndWithEventStart( prevStartDate ) {
	const ticketIds = yield select( selectors.getTicketsAllClientIds );
	for ( let index = 0; index < ticketIds.length; index++ ) {
		const clientId = ticketIds[ index ];
		yield call( syncTicketSaleEndWithEventStart, prevStartDate, clientId );
	}
}

/**
 * Will sync Tickets sale end to be the same as event start date and time, if field has not been manually edited
 *
 * @borrows TEC - Functionality requires TEC to be enabled
 * @param {string} prevStartDate Previous start date before latest set date time changes
 * @param {string} clientId      Client ID of ticket block
 * @export
 * @yield
 */
export function* syncTicketSaleEndWithEventStart( prevStartDate, clientId ) {
	try {
		const tempEndMoment = yield select(
			selectors.getTicketTempEndDateMoment,
			{ clientId }
		);
		const endMoment = yield select( selectors.getTicketEndDateMoment, {
			clientId,
		} );
		const { moment: prevEventStartMoment } = yield call(
			createDates,
			prevStartDate
		);

		// NOTE: Mutation
		// Convert to use local timezone
		yield all( [
			call( [ tempEndMoment, 'local' ] ),
			call( [ endMoment, 'local' ] ),
			call( [ prevEventStartMoment, 'local' ] ),
		] );

		// If initial end and current end are the same, the RSVP has not been modified
		const isNotManuallyEdited = yield call(
			[ tempEndMoment, 'isSame' ],
			endMoment,
			'minute'
		);
		const isSyncedToEventStart = yield call(
			[ tempEndMoment, 'isSame' ],
			prevEventStartMoment,
			'minute'
		);
		const isEvent = yield call( isTribeEventPostType );

		// This if statement may be redundant given the try-catch statement above.
		// Only run this on events post type.
		if (
			isEvent &&
			window.tec.events &&
			isNotManuallyEdited &&
			isSyncedToEventStart
		) {
			const eventStart = yield select(
				window.tec.events.app.main.data.blocks.datetime.selectors
					.getStart
			);
			const {
				moment: endDateMoment,
				date: endDate,
				dateInput: endDateInput,
				time: endTime,
				timeInput: endTimeInput,
			} = yield call( createDates, eventStart );

			yield all( [
				put( actions.setTicketTempEndDate( clientId, endDate ) ),
				put(
					actions.setTicketTempEndDateInput( clientId, endDateInput )
				),
				put(
					actions.setTicketTempEndDateMoment(
						clientId,
						endDateMoment
					)
				),
				put( actions.setTicketTempEndTime( clientId, endTime ) ),
				put(
					actions.setTicketTempEndTimeInput( clientId, endTimeInput )
				),

				// Sync Ticket end items as well so as not to make state 'manually edited'
				put( actions.setTicketEndDate( clientId, endDate ) ),
				put( actions.setTicketEndDateInput( clientId, endDateInput ) ),
				put(
					actions.setTicketEndDateMoment( clientId, endDateMoment )
				),
				put( actions.setTicketEndTime( clientId, endTime ) ),
				put( actions.setTicketEndTimeInput( clientId, endTimeInput ) ),

				// Trigger UI button
				put( actions.setTicketHasChanges( clientId, true ) ),

				// Handle ticket duration error
				call( handleTicketDurationError, clientId ),
			] );
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		// eslint-disable-next-line no-console
		console.error( error );
	}
}

/**
 * Listens for event start date and time changes after RSVP block is loaded.
 *
 * @borrows TEC - Functionality requires TEC to be enabled and post type to be event
 * @export
 * @yield
 */
export function* handleEventStartDateChanges() {
	try {
		// Ensure we have a postType set before proceeding
		const postTypeChannel = yield call( hasPostTypeChannel );
		yield take( postTypeChannel );
		yield call( [ postTypeChannel, 'close' ] );

		const isEvent = yield call( isTribeEventPostType );
		if ( isEvent && window.tec.events ) {
			const { SET_START_DATE_TIME, SET_START_TIME } =
				window.tec.events.app.main.data.blocks.datetime.types;

			let syncTask;
			while ( true ) {
				// Cache current event start date for comparison
				const eventStart = yield select(
					window.tec.events.app.main.data.blocks.datetime.selectors
						.getStart
				);

				// Wait til use changes date or time on TEC datetime block
				yield take( [ SET_START_DATE_TIME, SET_START_TIME ] );

				// Important to cancel any pre-existing forks to prevent bad data from being sent
				if ( syncTask ) {
					yield cancel( syncTask );
				}
				syncTask = yield fork(
					syncTicketsSaleEndWithEventStart,
					eventStart
				);
			}
		}
	} catch ( error ) {
		// ¯\_(ツ)_/¯
		console.error( error );
	}
}

export function* handleTicketDurationError( clientId ) {
	let hasDurationError = false;
	const startDateMoment = yield select(
		selectors.getTicketTempStartDateMoment,
		{ clientId }
	);
	const endDateMoment = yield select( selectors.getTicketTempEndDateMoment, {
		clientId,
	} );

	if ( ! startDateMoment || ! endDateMoment ) {
		hasDurationError = true;
	} else {
		const startTime = yield select( selectors.getTicketTempStartTime, {
			clientId,
		} );
		const endTime = yield select( selectors.getTicketTempEndTime, {
			clientId,
		} );
		const startTimeSeconds = yield call(
			timeUtil.toSeconds,
			startTime,
			timeUtil.TIME_FORMAT_HH_MM_SS
		);
		const endTimeSeconds = yield call(
			timeUtil.toSeconds,
			endTime,
			timeUtil.TIME_FORMAT_HH_MM_SS
		);
		const startDateTimeMoment = yield call(
			momentUtil.setTimeInSeconds,
			startDateMoment.clone(),
			startTimeSeconds
		);
		const endDateTimeMoment = yield call(
			momentUtil.setTimeInSeconds,
			endDateMoment.clone(),
			endTimeSeconds
		);
		const durationHasError = yield call(
			[ startDateTimeMoment, 'isSameOrAfter' ],
			endDateTimeMoment
		);

		if ( durationHasError ) {
			hasDurationError = true;
		}
	}

	yield put(
		actions.setTicketHasDurationError( clientId, hasDurationError )
	);
}

export function* handleTicketStartDate( action ) {
	const { clientId, date, dayPickerInput } = action.payload;
	const startDateMoment = yield date
		? call( momentUtil.toMoment, date )
		: undefined;
	const startDate = yield date
		? call( momentUtil.toDatabaseDate, startDateMoment )
		: '';
	yield put( actions.setTicketTempStartDate( clientId, startDate ) );
	yield put(
		actions.setTicketTempStartDateInput( clientId, dayPickerInput )
	);
	yield put(
		actions.setTicketTempStartDateMoment( clientId, startDateMoment )
	);
}

export function* handleTicketEndDate( action ) {
	const { clientId, date, dayPickerInput } = action.payload;
	const endDateMoment = yield date
		? call( momentUtil.toMoment, date )
		: undefined;
	const endDate = yield date
		? call( momentUtil.toDatabaseDate, endDateMoment )
		: '';
	yield put( actions.setTicketTempEndDate( clientId, endDate ) );
	yield put( actions.setTicketTempEndDateInput( clientId, dayPickerInput ) );
	yield put( actions.setTicketTempEndDateMoment( clientId, endDateMoment ) );
}

export function* handleTicketSaleStartDate( action ) {
	const { clientId, date, dayPickerInput } = action.payload;
	const startDateMoment = yield date
		? call( momentUtil.toMoment, date )
		: undefined;
	const startDate = yield date
		? call( momentUtil.toDatabaseDate, startDateMoment )
		: '';

	yield put( actions.setTicketTempSaleStartDate( clientId, startDate ) );
	yield put(
		actions.setTicketTempSaleStartDateInput( clientId, dayPickerInput )
	);
	yield put(
		actions.setTicketTempSaleStartDateMoment( clientId, startDateMoment )
	);
}

export function* handleTicketSaleEndDate( action ) {
	const { clientId, date, dayPickerInput } = action.payload;
	const endDateMoment = yield date
		? call( momentUtil.toMoment, date )
		: undefined;
	const endDate = yield date
		? call( momentUtil.toDatabaseDate, endDateMoment )
		: '';
	yield put( actions.setTicketTempSaleEndDate( clientId, endDate ) );
	yield put(
		actions.setTicketTempSaleEndDateInput( clientId, dayPickerInput )
	);
	yield put(
		actions.setTicketTempSaleEndDateMoment( clientId, endDateMoment )
	);
}
export function* handleTicketStartTime( action ) {
	const { clientId, seconds } = action.payload;
	const startTime = yield call(
		timeUtil.fromSeconds,
		seconds,
		timeUtil.TIME_FORMAT_HH_MM
	);
	yield put(
		actions.setTicketTempStartTime( clientId, `${ startTime }:00` )
	);
}

export function* handleTicketStartTimeInput( action ) {
	const { clientId, seconds } = action.payload;
	const startTime = yield call(
		timeUtil.fromSeconds,
		seconds,
		timeUtil.TIME_FORMAT_HH_MM
	);
	const startTimeMoment = yield call(
		momentUtil.toMoment,
		startTime,
		momentUtil.TIME_FORMAT,
		false
	);
	const startTimeInput = yield call( momentUtil.toTime, startTimeMoment );
	yield put(
		actions.setTicketTempStartTimeInput( clientId, startTimeInput )
	);
}

export function* handleTicketEndTime( action ) {
	const { clientId, seconds } = action.payload;
	const endTime = yield call(
		timeUtil.fromSeconds,
		seconds,
		timeUtil.TIME_FORMAT_HH_MM
	);
	yield put( actions.setTicketTempEndTime( clientId, `${ endTime }:00` ) );
}

export function* handleTicketEndTimeInput( action ) {
	const { clientId, seconds } = action.payload;
	const endTime = yield call(
		timeUtil.fromSeconds,
		seconds,
		timeUtil.TIME_FORMAT_HH_MM
	);
	const endTimeMoment = yield call(
		momentUtil.toMoment,
		endTime,
		momentUtil.TIME_FORMAT,
		false
	);
	const endTimeInput = yield call( momentUtil.toTime, endTimeMoment );
	yield put( actions.setTicketTempEndTimeInput( clientId, endTimeInput ) );
}

export function* handleTicketMove() {
	const ticketClientIds = yield select( selectors.getTicketsAllClientIds );
	const modalClientId = yield select( moveSelectors.getModalClientId );

	if ( ticketClientIds.includes( modalClientId ) ) {
		yield put( actions.setTicketIsSelected( modalClientId, false ) );
		yield put( actions.removeTicketBlock( modalClientId ) );
		yield call(
			[ wpDispatch( 'core/block-editor' ), 'removeBlocks' ],
			[ modalClientId ]
		);
	}
}

export function* handler( action ) {
	switch ( action.type ) {
		case types.SET_TICKETS_INITIAL_STATE:
			yield call( setTicketsInitialState, action );
			break;

		case types.RESET_TICKETS_BLOCK:
			yield call( resetTicketsBlock );
			break;

		case types.SET_TICKET_INITIAL_STATE:
			yield call( setTicketInitialState, action );
			break;

		case types.FETCH_TICKET:
			yield call( fetchTicket, action );
			break;

		case types.CREATE_NEW_TICKET:
			yield call( createNewTicket, action );
			break;

		case types.UPDATE_TICKET:
			yield call( updateTicket, action );
			break;

		case types.DELETE_TICKET:
			yield call( deleteTicket, action );
			break;

		case types.FETCH_TICKETS_HEADER_IMAGE:
			yield call( fetchTicketsHeaderImage, action );
			break;

		case types.UPDATE_TICKETS_HEADER_IMAGE:
			yield call( updateTicketsHeaderImage, action );
			break;

		case types.DELETE_TICKETS_HEADER_IMAGE:
			yield call( deleteTicketsHeaderImage );
			break;

		case types.SET_TICKET_DETAILS:
			yield call( setTicketDetails, action );
			break;

		case types.SET_TICKET_TEMP_DETAILS:
			yield call( setTicketTempDetails, action );
			break;

		case types.HANDLE_TICKET_START_DATE:
			yield call( handleTicketStartDate, action );
			yield call( handleTicketDurationError, action.payload.clientId );
			yield put(
				actions.setTicketHasChanges( action.payload.clientId, true )
			);
			break;

		case types.HANDLE_TICKET_END_DATE:
			yield call( handleTicketEndDate, action );
			yield call( handleTicketDurationError, action.payload.clientId );
			yield put(
				actions.setTicketHasChanges( action.payload.clientId, true )
			);
			break;

		case types.HANDLE_TICKET_SALE_START_DATE:
			yield call( handleTicketSaleStartDate, action );
			yield put(
				actions.setTicketHasChanges( action.payload.clientId, true )
			);
			break;

		case types.HANDLE_TICKET_SALE_END_DATE:
			yield call( handleTicketSaleEndDate, action );
			yield put(
				actions.setTicketHasChanges( action.payload.clientId, true )
			);
			break;

		case types.HANDLE_TICKET_START_TIME:
			yield call( handleTicketStartTime, action );
			yield call( handleTicketStartTimeInput, action );
			yield call( handleTicketDurationError, action.payload.clientId );
			yield put(
				actions.setTicketHasChanges( action.payload.clientId, true )
			);
			break;

		case types.HANDLE_TICKET_END_TIME:
			yield call( handleTicketEndTime, action );
			yield call( handleTicketEndTimeInput, action );
			yield call( handleTicketDurationError, action.payload.clientId );
			yield put(
				actions.setTicketHasChanges( action.payload.clientId, true )
			);
			break;

		case MOVE_TICKET_SUCCESS:
			yield call( handleTicketMove );
			break;

		case types.UPDATE_UNEDITABLE_TICKETS:
			yield call( updateUneditableTickets );
			break;

		default:
			break;
	}
}

export default function* watchers() {
	yield takeEvery(
		[
			types.SET_TICKETS_INITIAL_STATE,
			types.RESET_TICKETS_BLOCK,
			types.SET_TICKET_INITIAL_STATE,
			types.FETCH_TICKET,
			types.CREATE_NEW_TICKET,
			types.UPDATE_TICKET,
			types.DELETE_TICKET,
			types.FETCH_TICKETS_HEADER_IMAGE,
			types.UPDATE_TICKETS_HEADER_IMAGE,
			types.DELETE_TICKETS_HEADER_IMAGE,
			types.SET_TICKET_DETAILS,
			types.SET_TICKET_TEMP_DETAILS,
			types.HANDLE_TICKET_START_DATE,
			types.HANDLE_TICKET_END_DATE,
			types.HANDLE_TICKET_START_TIME,
			types.HANDLE_TICKET_END_TIME,
			types.HANDLE_TICKET_SALE_START_DATE,
			types.HANDLE_TICKET_SALE_END_DATE,
			MOVE_TICKET_SUCCESS,
			types.UPDATE_UNEDITABLE_TICKETS,
		],
		handler
	);

	yield fork( handleEventStartDateChanges );
}
