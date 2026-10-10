/**
 * Deferred ticket save for the block editor: the sagas.
 *
 * On a post that uses deferred save, the Ticket block's create, update, delete and move stage the
 * change here instead of calling the tickets REST endpoints. The staged payload rides on the post
 * save as the `tec_tickets` edit (the pattern Events Calendar Pro uses for its save option), and the
 * server's answer, created IDs by position and errors per entry, is applied back to the blocks.
 *
 * Right before the save request leaves, the payload is rebuilt from the blocks that exist, in block
 * order, and what it carries is recorded; the answer settles only that.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { buffers, eventChannel } from 'redux-saga';
import { call, fork, put, select, take } from 'redux-saga/effects';
import { createBlock } from '@wordpress/blocks';
import { dispatch as wpDispatch, select as wpSelect, subscribe } from '@wordpress/data';
import { doAction, addAction, addFilter, removeAction, removeFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import * as actions from './actions';
import * as selectors from './selectors';
import { buildPayload, reconcileSaveResponse, usesDeferredSave } from './deferred';
import { setBodyDetails } from './sagas';

const NAMESPACE = 'tec/tickets/deferred-save';

/**
 * The REST body entries per ticket block, kept out of the store because a `FormData` is not state.
 *
 * @type {Object<string, Array<Array<string>>>}
 */
const bodies = {};

/**
 * The block index each ticket had when it was loaded or last saved, to tell a reordered ticket.
 *
 * @type {Object<string, number>}
 */
const positions = {};

/**
 * What the post save in flight carries, recorded on `editor.preSavePost`; `null` when it carries nothing.
 *
 * @type {Object|null}
 */
let sent = null;

/**
 * The last response object applied. Core backfills a record's `tec_tickets` from the previous save
 * when the server did not answer with one, and that same object must never be applied twice.
 *
 * @type {Object|null}
 */
let lastApplied = null;

/**
 * Whether the next post save is the one that stores created IDs in the content, which carries no payload.
 *
 * @type {boolean}
 */
let sendNoPayload = false;

/**
 * The ticket block each staged delete removed, by ticket ID: `ticketDeleted` names the block, as the REST save did.
 *
 * @type {Object<number, string>}
 */
const deletedBlocks = {};

/**
 * The payload edits are bookkeeping, not changes the admin made: undoing one would leave the post clean while
 * its tickets still say they are not saved.
 *
 * @type {Object}
 */
const UNDO_IGNORE = { undoIgnore: true };

const hasOwn = ( target, key ) => Object.prototype.hasOwnProperty.call( target, key );

const blockEditor = () => wpSelect( 'core/block-editor' );

/**
 * Whether a ticket block still exists in the editor.
 *
 * @param {string} clientId The block.
 *
 * @return {boolean} Whether the block editor knows it.
 */
const blockExists = ( clientId ) => {
	const editor = blockEditor();

	return ! editor || 'function' !== typeof editor.getBlock || !! editor.getBlock( clientId );
};

/**
 * The index of a ticket block among its siblings, or -1 when the editor cannot tell.
 *
 * @param {string} clientId The block.
 *
 * @return {number} The index.
 */
const blockIndex = ( clientId ) => {
	const editor = blockEditor();

	return editor && 'function' === typeof editor.getBlockIndex ? editor.getBlockIndex( clientId ) : -1;
};

/**
 * The ticket blocks that still exist, in block order.
 *
 * @param {Array<string>} clientIds The ticket blocks the store knows.
 *
 * @return {Array<string>} The live ones, ordered.
 */
const liveClientIds = ( clientIds ) =>
	clientIds
		.filter( blockExists )
		.map( ( clientId ) => [ clientId, blockIndex( clientId ) ] )
		.sort( ( a, b ) => a[ 1 ] - b[ 1 ] )
		.map( ( [ clientId ] ) => clientId );

/**
 * A body with its `menu_order` set to the block's index now, as the legacy save sent it.
 *
 * @param {Array<Array<string>>} entries The body.
 * @param {number}               index   The block index, -1 when unknown.
 *
 * @return {Array<Array<string>>} The body.
 */
const withPosition = ( entries, index ) =>
	index < 0
		? entries
		: entries.map( ( [ key, value ] ) => ( 'menu_order' === key ? [ key, String( index ) ] : [ key, value ] ) );

const isEmptyPayload = ( payload ) =>
	! payload.create.length &&
	! Object.keys( payload.update ).length &&
	! payload.delete.length &&
	! Object.keys( payload.move ).length;

/**
 * Runs a lifecycle hook so that a listener that throws cannot stop the save being applied.
 *
 * @param {string} name The hook.
 * @param {...*}   args Its arguments.
 */
const runHook = ( name, ...args ) => {
	try {
		doAction( name, ...args );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( error );
	}
};

/**
 * Shows an error in the editor's notices: the place for changes whose block is gone.
 *
 * @param {string} message The message.
 */
export const showNotice = ( message ) => {
	const notices = wpDispatch( 'core/notices' );

	if ( notices && 'function' === typeof notices.createErrorNotice ) {
		notices.createErrorNotice( message, { isDismissible: true } );
	}
};

/**
 * Puts back the block of a ticket whose removal or move the server refused: the ticket is still on the post.
 *
 * @param {number} ticketId The ticket.
 */
const restoreTicketBlock = ( ticketId ) => {
	const editor = blockEditor();
	const blocks = wpDispatch( 'core/block-editor' );
	const [ parent ] =
		editor && 'function' === typeof editor.getBlocksByName ? editor.getBlocksByName( 'tribe/tickets' ) : [];

	if ( parent && blocks && 'function' === typeof blocks.insertBlock ) {
		blocks.insertBlock(
			createBlock( 'tribe/tickets-item', { hasBeenCreated: true, ticketId } ),
			undefined,
			parent,
			false
		);
	}
};

/**
 * Writes attributes onto a ticket block now, rather than when the block next renders them from the store.
 *
 * @param {string} clientId   The ticket block.
 * @param {Object} attributes The attributes.
 */
const writeBlockAttributes = ( clientId, attributes ) => {
	const blocks = wpDispatch( 'core/block-editor' );

	if ( blocks && 'function' === typeof blocks.updateBlockAttributes ) {
		blocks.updateBlockAttributes( clientId, attributes );
	}
};

/**
 * Whether the editor is saving the post.
 *
 * @return {boolean} Whether a save is in flight.
 */
const isSavingPost = () => {
	const editor = wpSelect( 'core/editor' );

	return !! editor && 'function' === typeof editor.isSavingPost && editor.isSavingPost();
};

/**
 * Resolves once the post save in flight has finished.
 *
 * @return {Promise} The promise.
 */
const saveFinished = () =>
	new Promise( ( resolve ) => {
		const unsubscribe = subscribe( () => {
			if ( ! isSavingPost() ) {
				unsubscribe();
				resolve();
			}
		} );
	} );

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
 * Remembers where a ticket block is, so a later move in the block order is saved.
 *
 * @since TBD
 *
 * @param {string} clientId The ticket block.
 */
export const rememberPosition = ( clientId ) => {
	positions[ clientId ] = blockIndex( clientId );
};

/**
 * Hands the payload to the editor as an edit, which dirties the post and sends it with the save.
 *
 * @since TBD
 *
 * @param {Object} payload The `tec_tickets` payload.
 */
export const editPostPayload = ( payload ) => {
	if ( isEmptyPayload( payload ) ) {
		// Nothing staged: make the edit equal the saved record's field so core drops it and the post is clean.
		const post = wpSelect( 'core/editor' ).getCurrentPost();
		wpDispatch( 'core/editor' ).editPost( { tec_tickets: post ? post.tec_tickets : undefined }, UNDO_IGNORE );
		return;
	}

	wpDispatch( 'core/editor' ).editPost( { tec_tickets: payload }, UNDO_IGNORE );
};

/**
 * Makes the editor's `tec_tickets` edit equal the saved record's field, so core drops it and the post is clean.
 *
 * @since TBD
 *
 * @param {Object} response The `tec_tickets` field of the saved record.
 */
export const settlePayloadEdit = ( response ) => {
	wpDispatch( 'core/editor' ).editPost( { tec_tickets: response }, UNDO_IGNORE );
};

/**
 * Builds the payload from the store and the blocks that exist now, in block order.
 *
 * @since TBD
 *
 * @param {Array<string>} excluded Ticket blocks to leave out, staged or not.
 *
 * @return {Object} The payload, its create order, the live client IDs and the tickets they were read from.
 */
export function* buildLivePayload( excluded = [] ) {
	const allClientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const stagedDeletes = yield select( selectors.getStagedDeletes );
	const stagedMoves = yield select( selectors.getStagedMoves );
	// A block removed through the editor's own toolbar leaves its store entry behind; it must not be saved.
	const clientIds = liveClientIds( allClientIds );
	const positioned = {};

	clientIds.forEach( ( clientId ) => {
		if ( bodies[ clientId ] ) {
			positioned[ clientId ] = withPosition( bodies[ clientId ], blockIndex( clientId ) );
		}
	} );

	// Undo brings back a removed block, not its staged delete or move: a ticket a block holds is neither.
	const held = clientIds
		.map( ( clientId ) => byClientId[ clientId ] )
		.filter( ( ticket ) => ticket && ticket.hasBeenCreated && ticket.ticketId )
		.map( ( ticket ) => Number( ticket.ticketId ) );

	const { payload, createOrder } = buildPayload( {
		clientIds: clientIds.filter( ( clientId ) => ! excluded.includes( clientId ) ),
		byClientId,
		bodies: positioned,
		stagedDeletes: stagedDeletes.filter( ( ticketId ) => ! held.includes( Number( ticketId ) ) ),
		stagedMoves: Object.fromEntries(
			Object.entries( stagedMoves ).filter( ( [ ticketId ] ) => ! held.includes( Number( ticketId ) ) )
		),
	} );

	return { payload, createOrder, clientIds, byClientId };
}

/**
 * Rebuilds the payload from the store and hands it to the editor.
 *
 * @since TBD
 */
export function* refreshPayload() {
	const { payload } = yield call( buildLivePayload );

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

	runHook( 'tec.tickets.blocks.ticketStaged', clientId );
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
	deletedBlocks[ ticketId ] = clientId;
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
 * Stages the saved tickets whose changes the post save would otherwise drop.
 *
 * Before deferred save every post save sent every saved ticket again, so a ticket moved in the block
 * order, an end date moved with the event start, or an edit not confirmed yet was saved with the post.
 * Here those tickets are staged right before the request leaves, when they pass the shared rules.
 *
 * @since TBD
 *
 * @return {Array<string>} The ticket blocks whose latest changes fail the rules: this save must leave them out,
 *                         even one staged before, or it would send an older body and settle it as saved.
 */
export function* stagePendingChanges() {
	const allClientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const refused = [];

	for ( const clientId of liveClientIds( allClientIds ) ) {
		const ticket = byClientId[ clientId ];

		// A ticket never confirmed is not the post save's to send; a saved or a staged one is.
		if ( ! ticket || ! ( ticket.hasBeenCreated || ticket.isStaged ) ) {
			continue;
		}

		// A staged body takes the block's position when the payload is built; an unstaged saved ticket needs staging.
		const moved =
			! ticket.isStaged && hasOwn( positions, clientId ) && positions[ clientId ] !== blockIndex( clientId );

		if ( ! ticket.hasChanges && ! moved ) {
			continue;
		}

		const isValid = yield select( selectors.isTicketValid, { clientId } );
		const isSalePriceValid = yield select( selectors.isTicketSalePriceValid, { clientId } );
		const hasDurationError = yield select( selectors.getTicketHasDurationError, { clientId } );

		// The rules the confirm button applies; a ticket that fails them is not saved, and the block says so.
		if ( ! isValid || ! isSalePriceValid || hasDurationError ) {
			yield put(
				actions.setTicketSaveError(
					clientId,
					__( 'Not saved with the post: fix the ticket and confirm it.', 'event-tickets' )
				)
			);
			refused.push( clientId );
			continue;
		}

		const body = yield call( setBodyDetails, clientId );
		yield call( stageTicket, clientId, [ ...body.entries() ] );
	}

	return refused;
}

/**
 * Builds the payload the save request carries and records what it carries.
 *
 * @since TBD
 *
 * @param {Object}   edits   The edits the editor is about to send.
 * @param {Function} resolve Receives the edits to send instead.
 */
export function* prepareSave( edits, resolve ) {
	let prepared = edits;
	// eslint-disable-next-line camelcase
	const { tec_tickets: previous, ...withoutPayload } = edits;

	if ( sendNoPayload ) {
		// The save that stores created IDs in the content: what is still staged waits for the next save.
		sendNoPayload = false;
		sent = null;
		resolve( withoutPayload );
		return;
	}

	try {
		const refused = yield call( stagePendingChanges );
		const { payload, createOrder, clientIds, byClientId } = yield call( buildLivePayload, refused );

		if ( isEmptyPayload( payload ) ) {
			sent = null;
			prepared = withoutPayload;
		} else {
			const updates = {};

			clientIds.forEach( ( clientId ) => {
				const ticket = byClientId[ clientId ];

				if ( ticket && ticket.isStaged && ticket.hasBeenCreated && hasOwn( payload.update, ticket.ticketId ) ) {
					updates[ clientId ] = ticket.ticketId;
				}
			} );

			const sentPositions = {};
			clientIds.forEach( ( clientId ) => {
				sentPositions[ clientId ] = blockIndex( clientId );
			} );

			// The lifecycle hooks describe what was saved, not what the admin changed while the request was out.
			const sentDetails = {};
			[ ...createOrder, ...Object.keys( updates ) ].forEach( ( clientId ) => {
				sentDetails[ clientId ] = byClientId[ clientId ] ? byClientId[ clientId ].details : undefined;
			} );

			sent = {
				createOrder,
				updates,
				bodies: { ...bodies },
				deletes: [ ...payload.delete ],
				deletedBlocks: { ...deletedBlocks },
				moves: { ...payload.move },
				positions: sentPositions,
				details: sentDetails,
			};
			prepared = { ...withoutPayload, tec_tickets: payload };
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( error );
		// Never the payload the edit held before: it may name blocks removed since. The changes stay staged.
		sent = null;
		prepared = withoutPayload;
		showNotice(
			__(
				'The ticket changes could not be prepared, so they were not saved with the post. They are still staged: save the post again.',
				'event-tickets'
			)
		);
	} finally {
		resolve( prepared );
	}
}

/**
 * Fires the lifecycle hook for a ticket with its details, as the REST save fired it.
 *
 * @since TBD
 *
 * @param {string} name     The hook.
 * @param {string} clientId The ticket block.
 * @param {number} ticketId The ticket.
 * @param {Object} details  The details the save sent; the store's when not given.
 */
export function* announce( name, clientId, ticketId, details = undefined ) {
	const announced = details || ( yield select( selectors.getTicketDetails, { clientId } ) );

	runHook( name, clientId, ticketId, announced );
}

/**
 * Loads a created ticket's details from the server and announces it.
 *
 * @since TBD
 *
 * @param {string} clientId The ticket block.
 * @param {number} ticketId The created ticket.
 * @param {Object} details  The details the save sent.
 */
export function* hydrateTicket( clientId, ticketId, details = undefined ) {
	yield put( actions.fetchTicket( clientId, ticketId ) );
	yield call( announce, 'tec.tickets.blocks.ticketCreated', clientId, ticketId, details );
}

/**
 * Applies the server's answer to what the save sent.
 *
 * @since TBD
 *
 * @param {{created: Object<number,number>, errors: Array}} response The saved record's `tec_tickets` field.
 * @param {Object}                                          sentNow  What the save carried, recorded on `editor.preSavePost`.
 */
export function* applySaveResponse( response, sentNow ) {
	if ( ! response || 'object' !== typeof response || response === lastApplied ) {
		return;
	}

	lastApplied = response;

	const clientIds = yield select( selectors.getTicketsAllClientIds );
	const byClientId = yield select( selectors.getTicketsByClientId );
	const ticketIds = liveClientIds( clientIds )
		.map( ( clientId ) => byClientId[ clientId ] )
		.filter( ( ticket ) => ticket && ticket.hasBeenCreated && ticket.ticketId )
		.map( ( ticket ) => Number( ticket.ticketId ) );
	const outcome = reconcileSaveResponse( { response, sent: sentNow, live: { clientIds, bodies, ticketIds } } );

	for ( const block of outcome.blocks ) {
		if ( 'created' === block.hook ) {
			yield put( actions.setTicketId( block.clientId, block.ticketId ) );
			yield put( actions.setTicketHasBeenCreated( block.clientId, true ) );
			yield call( writeBlockAttributes, block.clientId, { ticketId: block.ticketId, hasBeenCreated: true } );
		}

		yield put( actions.setTicketIsStaged( block.clientId, block.staged ) );
		yield put( actions.setTicketSaveError( block.clientId, block.error ) );

		if ( ! block.staged ) {
			forgetBody( block.clientId );
		}
	}

	yield put( actions.clearStagedTickets( outcome.settle ) );
	yield call( settlePayloadEdit, response );
	yield call( refreshPayload );
	// The position each committed ticket was sent with; one it was moved to since is still unsaved.
	outcome.blocks.forEach( ( { clientId, hook } ) => {
		if ( hook && sentNow.positions && hasOwn( sentNow.positions, clientId ) ) {
			positions[ clientId ] = sentNow.positions[ clientId ];
		}
	} );

	// Effects outside the store come last, once it is settled.
	for ( const block of outcome.blocks ) {
		const details = sentNow.details ? sentNow.details[ block.clientId ] : undefined;

		if ( 'created' === block.hook ) {
			const ticket = byClientId[ block.clientId ];

			// Loading the saved ticket would overwrite what the admin changed since the request left.
			if ( block.staged || ( ticket && ticket.hasChanges ) ) {
				yield call( announce, 'tec.tickets.blocks.ticketCreated', block.clientId, block.ticketId, details );
			} else {
				yield call( hydrateTicket, block.clientId, block.ticketId, details );
			}
		} else if ( 'updated' === block.hook ) {
			yield call( announce, 'tec.tickets.blocks.ticketUpdated', block.clientId, block.ticketId, details );
		}
	}

	outcome.deleted.forEach( ( ticketId ) => {
		const deletedBlocksSent = sentNow.deletedBlocks || {};
		const clientId = hasOwn( deletedBlocksSent, ticketId ) ? deletedBlocksSent[ ticketId ] : null;

		/**
		 * Fires once a staged delete was committed with the post save, not when the block is removed.
		 *
		 * @since 5.20.0
		 * @since TBD On a post that defers ticket saves, fires after the post save that deleted the ticket.
		 *
		 * @param {string|null} clientId The removed ticket block's client ID; `null` when it is not known.
		 * @param {number}      ticketId The ticket's ID.
		 */
		runHook( 'tec.tickets.blocks.ticketDeleted', clientId, ticketId );
		delete deletedBlocks[ ticketId ];
	} );
	outcome.notices.forEach( showNotice );
	outcome.restore.forEach( restoreTicketBlock );

	// A created ticket's ID reaches its block after the content was saved: save again so the content has it,
	// or a reload shows the block empty next to a second block for the saved ticket.
	if ( outcome.blocks.some( ( { hook } ) => 'created' === hook ) ) {
		yield call( savePostAgain );
	}
}

/**
 * Saves the post again, without a payload, so its content has the IDs of the tickets the last save created.
 *
 * @since TBD
 */
export function* savePostAgain() {
	const editor = wpDispatch( 'core/editor' );

	if ( ! editor || 'function' !== typeof editor.savePost ) {
		return;
	}

	// The answer is applied inside the first save's `editor.savePost` action; core refuses a save until it finishes.
	if ( isSavingPost() ) {
		yield call( saveFinished );
	}

	sendNoPayload = true;

	try {
		yield call( [ editor, editor.savePost ] );
	} finally {
		// A save core refused never asked for the payload; the next one carries it.
		sendNoPayload = false;
	}
}

/**
 * Applies the answer of the post save that just finished.
 *
 * @since TBD
 */
export function* applyLastSaveResponse() {
	const post = wpSelect( 'core/editor' ).getCurrentPost();
	const response = post ? post.tec_tickets : undefined;
	const sentNow = sent;
	sent = null;

	if ( ! sentNow ) {
		// Nothing went out with this save; keep the editor's edit in step with what is staged.
		yield call( refreshPayload );
		return;
	}

	if ( response && 'object' === typeof response && response !== lastApplied ) {
		yield call( applySaveResponse, response, sentNow );
		return;
	}

	// A payload went out and no fresh answer came back: the server did not commit it. Say so on what was sent.
	const message = __( 'The ticket changes were not saved with the post.', 'event-tickets' );
	const clientIds = yield select( selectors.getTicketsAllClientIds );

	for ( const clientId of [ ...sentNow.createOrder, ...Object.keys( sentNow.updates ) ] ) {
		if ( clientIds.includes( clientId ) ) {
			yield put( actions.setTicketSaveError( clientId, message ) );
		}
	}

	if ( sentNow.deletes.length || Object.keys( sentNow.moves ).length ) {
		showNotice( message );
	}
}

/**
 * A channel that emits, for every post save that is not an autosave or a preview, the edits about to be
 * sent and the function that hands back the edits to send instead.
 *
 * The filter answers with a promise core awaits, so the payload is built from the store right before the
 * request leaves. Buffered, so a second save never waits on a dropped event.
 *
 * @since TBD
 *
 * @return {Object} The redux-saga event channel.
 */
export const createPreSaveChannel = () =>
	eventChannel( ( emitter ) => {
		addFilter( 'editor.preSavePost', NAMESPACE, ( edits, options = {} ) => {
			if ( options.isAutosave || options.isPreview ) {
				// A draft's preview is a real save whose answer is never applied: what it committed would stay staged.
				// eslint-disable-next-line camelcase, no-unused-vars
				const { tec_tickets, ...withoutPayload } = edits;

				return withoutPayload;
			}

			return new Promise( ( resolve ) => emitter( { edits, resolve } ) );
		} );

		return () => removeFilter( 'editor.preSavePost', NAMESPACE );
	}, buffers.expanding() );

/**
 * A channel that emits once for every successful post save, from the editor's `editor.savePost` action.
 *
 * @since TBD
 *
 * @return {Object} The redux-saga event channel.
 */
export const createPostSavedChannel = () =>
	eventChannel( ( emitter ) => {
		addAction( 'editor.savePost', NAMESPACE, ( post, options = {} ) => {
			if ( ! options.isAutosave && ! options.isPreview ) {
				emitter( post || {} );
			}
		} );

		return () => removeAction( 'editor.savePost', NAMESPACE );
	}, buffers.expanding() );

/**
 * Prepares the payload of every post save.
 *
 * @since TBD
 */
export function* watchPreSaves() {
	const channel = yield call( createPreSaveChannel );

	try {
		while ( true ) {
			const { edits, resolve } = yield take( channel );
			yield call( prepareSave, edits, resolve );
		}
	} finally {
		channel.close();
	}
}

/**
 * Prepares and applies every post save while the post defers ticket saves.
 *
 * @since TBD
 */
export function* watchPostSaves() {
	if ( ! usesDeferredSave() ) {
		return;
	}

	yield fork( watchPreSaves );
	const channel = yield call( createPostSavedChannel );

	try {
		while ( true ) {
			yield take( channel );

			try {
				yield call( applyLastSaveResponse );
			} catch ( error ) {
				// One bad answer must not stop the next save from being applied.
				// eslint-disable-next-line no-console
				console.error( error );
			}
		}
	} finally {
		channel.close();
	}
}
