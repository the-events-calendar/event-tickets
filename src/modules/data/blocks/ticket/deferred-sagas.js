/**
 * Deferred ticket save for the block editor: the sagas.
 *
 * On a post that uses deferred save, the Ticket block's create, update, delete and move stage the
 * change here instead of calling the tickets REST endpoints. The staged payload rides on the post
 * save as the `tec_tickets` edit (the pattern Events Calendar Pro uses for its save option), and the
 * server's answer, created IDs by position and errors per entry, is applied back to the blocks.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { eventChannel } from 'redux-saga';
import { call, put, select, take } from 'redux-saga/effects';
import { dispatch as wpDispatch, select as wpSelect } from '@wordpress/data';
import { doAction, addAction, removeAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as selectors from './selectors';
import { buildPayload, usesDeferredSave } from './deferred';

/**
 * The REST body entries per ticket block, kept out of the store because a `FormData` is not state.
 *
 * @type {Object<string, Array<Array<string>>>}
 */
const bodies = {};

/**
 * Remembers the body a ticket block would have sent, for the payload.
 *
 * @since TBD
 *
 * @param {string}               clientId The ticket block.
 * @param {Array<Array<string>>} entries  The body as `[ key, value ]` pairs.
 */
export const rememberBody = ( clientId, entries ) => {
	bodies[ clientId ] = entries;
};

/**
 * Forgets a ticket block's body.
 *
 * @since TBD
 *
 * @param {string} clientId The ticket block.
 */
export const forgetBody = ( clientId ) => {
	delete bodies[ clientId ];
};

/**
 * Hands the payload to the editor as an edit, which dirties the post and sends it with the save.
 *
 * @since TBD
 *
 * @param {Object} payload The `tec_tickets` payload.
 */
export const editPostPayload = ( payload ) => {
	wpDispatch( 'core/editor' ).editPost( { tec_tickets: payload } );
};

/**
 * Makes the editor's `tec_tickets` edit equal the saved record's field, so core drops it and the post is clean.
 *
 * @since TBD
 *
 * @param {Object} response The `tec_tickets` field of the saved record.
 */
export const settlePayloadEdit = ( response ) => {
	wpDispatch( 'core/editor' ).editPost( { tec_tickets: response } );
};

/**
 * Rebuilds the payload from the store and hands it to the editor.
 *
 * @since TBD
 */
export function* refreshPayload() {
	const clientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const stagedDeletes = yield select( selectors.getStagedDeletes );
	const stagedMoves = yield select( selectors.getStagedMoves );

	const { payload, createOrder } = buildPayload( { clientIds, byClientId, bodies, stagedDeletes, stagedMoves } );

	yield put( actions.setStagedCreateOrder( createOrder ) );
	yield call( editPostPayload, payload );
}

/**
 * Stages a ticket block's create or update: the shown details become the staged ones.
 *
 * @since TBD
 *
 * @param {string}               clientId The ticket block.
 * @param {Array<Array<string>>} entries  The body as `[ key, value ]` pairs.
 */
export function* stageTicket( clientId, entries ) {
	rememberBody( clientId, entries );

	const tempDetails = yield select( selectors.getTicketTempDetails, { clientId } );

	yield put( actions.setTicketDetails( clientId, tempDetails ) );
	yield put( actions.setTicketIsStaged( clientId, true ) );
	yield put( actions.setTicketSaveError( clientId, '' ) );
	yield put( actions.setTicketHasChanges( clientId, false ) );
	yield call( refreshPayload );

	doAction( 'tec.tickets.blocks.ticketStaged', clientId );
}

/**
 * Stages the deletion of a saved ticket whose block was removed.
 *
 * @since TBD
 *
 * @param {string} clientId The removed ticket block.
 * @param {number} ticketId The saved ticket.
 */
export function* stageDelete( clientId, ticketId ) {
	forgetBody( clientId );
	yield put( actions.stageTicketDelete( ticketId ) );
	yield call( refreshPayload );
}

/**
 * Stages the move of a saved ticket.
 *
 * @since TBD
 *
 * @param {number} ticketId      The saved ticket.
 * @param {number} destinationId The destination post.
 */
export function* stageMove( ticketId, destinationId ) {
	yield put( actions.stageTicketMove( ticketId, destinationId ) );
	yield call( refreshPayload );
}

/**
 * Loads a created ticket's details from the server so the block shows what was saved.
 *
 * Kept as a call target so tests can assert it without running the fetch.
 *
 * @since TBD
 *
 * @param {string} clientId The ticket block.
 * @param {number} ticketId The created ticket.
 */
export function* hydrateTicket( clientId, ticketId ) {
	forgetBody( clientId );
	yield put( actions.fetchTicket( clientId ) );
	doAction( 'tec.tickets.blocks.ticketCreated', clientId, ticketId, {} );
}

/**
 * Applies the server's answer to the blocks after a post save.
 *
 * @since TBD
 *
 * @param {{created: Object<number,number>, errors: Array}} response The saved record's `tec_tickets` field.
 */
export function* applySaveResponse( response ) {
	if ( ! response || 'object' !== typeof response ) {
		return;
	}

	const created = response.created || {};
	const errors = Array.isArray( response.errors ) ? response.errors : [];
	const createOrder = yield select( selectors.getStagedCreateOrder );
	const clientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const stagedDeletes = yield select( selectors.getStagedDeletes );

	const errorFor = ( part, key ) => {
		const error = errors.find( ( e ) => e.part === part && String( e.key ) === String( key ) );

		return error ? error.message : null;
	};

	// New tickets, by the position their create entry had.
	for ( const [ position, clientId ] of createOrder.entries() ) {
		const ticketId = created[ position ];

		if ( ticketId ) {
			yield put( actions.setTicketId( clientId, parseInt( ticketId, 10 ) ) );
			yield put( actions.setTicketHasBeenCreated( clientId, true ) );
			yield put( actions.setTicketIsStaged( clientId, false ) );
			yield put( actions.setTicketSaveError( clientId, '' ) );
			yield call( hydrateTicket, clientId, parseInt( ticketId, 10 ) );
			continue;
		}

		const message = errorFor( 'create', position );

		if ( message ) {
			yield put( actions.setTicketSaveError( clientId, message ) );
		}
	}

	// Saved tickets with a staged update.
	for ( const clientId of clientIds ) {
		const ticket = byClientId[ clientId ];

		if ( ! ticket || ! ticket.isStaged || ! ticket.hasBeenCreated || createOrder.includes( clientId ) ) {
			continue;
		}

		const message = errorFor( 'update', ticket.ticketId );

		if ( message ) {
			yield put( actions.setTicketSaveError( clientId, message ) );
			continue;
		}

		forgetBody( clientId );
		yield put( actions.setTicketIsStaged( clientId, false ) );
		yield put( actions.setTicketSaveError( clientId, '' ) );
		doAction( 'tec.tickets.blocks.ticketUpdated', clientId, ticket.ticketId, {} );
	}

	stagedDeletes.forEach( ( ticketId ) => {
		if ( ! errorFor( 'delete', ticketId ) ) {
			doAction( 'tec.tickets.blocks.ticketDeleted', null, ticketId );
		}
	} );

	yield put( actions.clearStagedTickets() );
	yield call( settlePayloadEdit, response );
	yield call( refreshPayload );
}

/**
 * Applies the response of the post save that just finished, when the record carries one.
 *
 * @since TBD
 */
export function* applyLastSaveResponse() {
	const post = wpSelect( 'core/editor' ).getCurrentPost();

	yield call( applySaveResponse, post ? post.tec_tickets : undefined );
}

/**
 * A channel that emits once for every successful post save, from the editor's `editor.savePost` action.
 *
 * @since TBD
 *
 * @return {Object} The redux-saga event channel.
 */
export const createPostSavedChannel = () =>
	eventChannel( ( emitter ) => {
		const namespace = 'tec/tickets/deferred-save';
		addAction( 'editor.savePost', namespace, ( post, options = {} ) => {
			if ( ! options.isAutosave && ! options.isPreview ) {
				emitter( post || {} );
			}
		} );

		return () => removeAction( 'editor.savePost', namespace );
	} );

/**
 * Applies the server's answer after every post save, while the post defers ticket saves.
 *
 * @since TBD
 */
export function* watchPostSaves() {
	if ( ! usesDeferredSave() ) {
		return;
	}

	const channel = yield call( createPostSavedChannel );

	try {
		while ( true ) {
			yield take( channel );
			yield call( applyLastSaveResponse );
		}
	} finally {
		channel.close();
	}
}
