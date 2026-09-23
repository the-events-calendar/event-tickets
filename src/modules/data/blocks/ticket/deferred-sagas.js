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
import { doAction, addAction, addFilter, removeAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

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
 * The create order the payload had when the last post save request started, captured on
 * `editor.preSavePost`. The response is mapped against this, never against the live order.
 *
 * @type {Array<string>|null}
 */
let sentCreateOrder = null;

/**
 * The last response object applied. Core backfills a record's `tec_tickets` from the previous save
 * when the server did not answer with one, and that same object must never be applied twice.
 *
 * @type {Object|null}
 */
let lastApplied = null;

/**
 * The create order of the last payload built, mirrored outside the store for the pre-save capture.
 *
 * @type {Array<string>}
 */
let lastBuiltCreateOrder = [];

/**
 * Remembers the create order the payload is being sent with.
 *
 * @since TBD
 *
 * @param {Array<string>} order The client IDs in `create` position order.
 */
export const rememberSentCreateOrder = ( order ) => {
	sentCreateOrder = [ ...order ];
};

/**
 * Whether a ticket block still exists in the editor.
 *
 * @param {string} clientId The block.
 *
 * @return {boolean} Whether the block editor knows it.
 */
const blockExists = ( clientId ) => {
	const blockEditor = wpSelect( 'core/block-editor' );

	return ! blockEditor || 'function' !== typeof blockEditor.getBlock || !! blockEditor.getBlock( clientId );
};

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
	const isEmpty =
		! payload.create.length &&
		! Object.keys( payload.update ).length &&
		! payload.delete.length &&
		! Object.keys( payload.move ).length;

	if ( isEmpty ) {
		// Nothing staged: make the edit equal the saved record's field so core drops it and the post is clean.
		const post = wpSelect( 'core/editor' ).getCurrentPost();
		wpDispatch( 'core/editor' ).editPost( { tec_tickets: post ? post.tec_tickets : undefined } );
		return;
	}

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
	const allClientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const stagedDeletes = yield select( selectors.getStagedDeletes );
	const stagedMoves = yield select( selectors.getStagedMoves );
	// A block removed through the editor's own toolbar leaves its store entry behind; it must not be saved.
	const clientIds = allClientIds.filter( blockExists );

	const { payload, createOrder } = buildPayload( { clientIds, byClientId, bodies, stagedDeletes, stagedMoves } );

	lastBuiltCreateOrder = [ ...createOrder ];
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
 * Drops a staged ticket that was never saved: its create entry leaves the payload.
 *
 * @since TBD
 *
 * @param {string} clientId The removed ticket block.
 */
export function* dropStaged( clientId ) {
	forgetBody( clientId );
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
	yield put( actions.fetchTicket( clientId, ticketId ) );
	doAction( 'tec.tickets.blocks.ticketCreated', clientId, ticketId, {} );
}

/**
 * Applies the server's answer to the blocks after a post save.
 *
 * @since TBD
 *
 * @param {{created: Object<number,number>, errors: Array}} response The saved record's `tec_tickets` field.
 * @param {Array<string>|null}                              order    The create order the payload was sent with; the store's when `null`.
 */
export function* applySaveResponse( response, order = null ) {
	if ( ! response || 'object' !== typeof response || response === lastApplied ) {
		return;
	}

	lastApplied = response;

	const created = response.created || {};
	const errors = Array.isArray( response.errors ) ? response.errors : [];
	const createOrder = order || ( yield select( selectors.getStagedCreateOrder ) );
	const clientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const stagedDeletes = yield select( selectors.getStagedDeletes );

	const errorFor = ( part, key ) => {
		const error = errors.find( ( e ) => e.part === part && String( e.key ) === String( key ) );

		return error ? String( error.message ?? '' ) : null;
	};

	// New tickets, by the position their create entry had when the payload was sent.
	for ( const [ position, clientId ] of createOrder.entries() ) {
		const ticketId = created[ position ];

		if ( ! clientIds.includes( clientId ) ) {
			// The block is gone; the ticket exists on the server and appears on the next load.
			continue;
		}

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
	const response = post ? post.tec_tickets : undefined;
	const order = sentCreateOrder;
	sentCreateOrder = null;

	if ( response && 'object' === typeof response && response !== lastApplied ) {
		yield call( applySaveResponse, response, order );
		return;
	}

	// A payload went out and no fresh answer came back: the server did not commit it. Say so on the staged blocks.
	const clientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );

	for ( const clientId of clientIds ) {
		if ( byClientId[ clientId ] && byClientId[ clientId ].isStaged ) {
			yield put(
				actions.setTicketSaveError(
					clientId,
					__( 'The ticket changes were not saved with the post.', 'event-tickets' )
				)
			);
		}
	}
}

/**
 * A channel that emits once for every successful post save, from the editor's `editor.savePost` action.
 *
 * @since TBD
 *
 * @return {Object} The redux-saga event channel.
 */
/**
 * Captures the create order the payload is sent with, right before the editor sends the save request.
 *
 * @since TBD
 */
export const watchPreSave = () => {
	addFilter( 'editor.preSavePost', 'tec/tickets/deferred-save', ( edits, options = {} ) => {
		if (
			! options.isAutosave &&
			! options.isPreview &&
			edits &&
			edits.tec_tickets &&
			Array.isArray( edits.tec_tickets.create )
		) {
			rememberSentCreateOrder( wpSelect( 'core/editor' ) ? lastBuiltCreateOrder : [] );
		}

		return edits;
	} );
};

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

	watchPreSave();
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
