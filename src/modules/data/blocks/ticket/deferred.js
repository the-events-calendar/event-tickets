/**
 * Deferred ticket save for the block editor: pure helpers.
 *
 * On a post that uses deferred save, ticket changes are staged in the store and travel with the
 * post save as the `tec_tickets` payload instead of being sent to the tickets REST endpoints.
 * Everything here is pure so it is covered by Jest; the sagas wire it to the editor.
 *
 * @since TBD
 */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { globals } from '@moderntribe/common/utils';

/**
 * Whether the post being edited defers ticket saves to the post save.
 *
 * @since TBD
 *
 * @return {boolean} Whether it does.
 */
export const usesDeferredSave = () => !! globals.tickets().usesDeferredSave;

/**
 * The REST body keys the block editor sends, and the keys the ticket save reads instead.
 *
 * Mirrors the mapping in `Tribe__Tickets__Editor__REST__V1__Endpoints__Single_Ticket::add_ticket()`.
 */
const KEY_MAP = {
	provider: 'ticket_provider',
	name: 'ticket_name',
	description: 'ticket_description',
	price: 'ticket_price',
	show_description: 'ticket_show_description',
	start_date: 'ticket_start_date',
	start_time: 'ticket_start_time',
	end_date: 'ticket_end_date',
	end_time: 'ticket_end_time',
	sku: 'ticket_sku',
	iac: 'ticket_iac',
	menu_order: 'ticket_menu_order',
	'ticket[sale_price][checked]': 'ticket_add_sale_price',
	'ticket[sale_price][price]': 'ticket_sale_price',
	'ticket[sale_price][start_date]': 'ticket_sale_start_date',
	'ticket[sale_price][end_date]': 'ticket_sale_end_date',
};

const DROPPED_KEYS = [ 'post_id', 'add_ticket_nonce', 'edit_ticket_nonce', 'remove_ticket_nonce' ];

/**
 * What the tickets REST endpoint fills in when the body leaves it out (`Single_Ticket::ticket_args()`).
 *
 * `ticket_add()` reads a missing `ticket_show_description` as "no", so without this every ticket the block
 * saves would hide its description.
 */
const REST_DEFAULTS = { ticket_show_description: 'yes' };

/**
 * Path segments that would write through the prototype chain instead of onto the data.
 */
const FORBIDDEN_SEGMENTS = [ '__proto__', 'constructor', 'prototype' ];

const hasOwn = ( target, key ) => Object.prototype.hasOwnProperty.call( target, key );

/**
 * Splits a bracketed key into its path: `ticket[fees][selected_fees][]` → `[ 'ticket', 'fees', 'selected_fees', '' ]`.
 *
 * @param {string} key The key.
 *
 * @return {Array<string>} The path; a trailing empty segment means "append to a list".
 */
const pathOf = ( key ) => {
	const open = key.indexOf( '[' );

	if ( -1 === open ) {
		return [ key ];
	}

	const rest = key.slice( open ).match( /\[([^\]]*)\]/g ) || [];

	return [ key.slice( 0, open ), ...rest.map( ( segment ) => segment.slice( 1, -1 ) ) ];
};

const setPath = ( target, path, value ) => {
	if ( path.some( ( segment ) => FORBIDDEN_SEGMENTS.includes( segment ) ) ) {
		return;
	}

	let cursor = target;

	path.forEach( ( segment, index ) => {
		const last = index === path.length - 1;

		if ( last ) {
			if ( '' === segment ) {
				return;
			}
			cursor[ segment ] = value;
			return;
		}

		const next = path[ index + 1 ];

		if ( '' === next ) {
			cursor[ segment ] =
				hasOwn( cursor, segment ) && Array.isArray( cursor[ segment ] ) ? cursor[ segment ] : [];
			cursor[ segment ].push( value );
			return;
		}

		// Descend only through own plain objects, never through anything inherited.
		cursor[ segment ] =
			hasOwn( cursor, segment ) &&
			cursor[ segment ] &&
			'object' === typeof cursor[ segment ] &&
			! Array.isArray( cursor[ segment ] )
				? cursor[ segment ]
				: {};
		cursor = cursor[ segment ];
	} );
};

/**
 * Turns the REST body the block editor builds into the data array the ticket save accepts.
 *
 * Known keys are renamed, `ticket[...]` becomes `tribe-ticket[...]` so extensions such as Seating keep
 * finding their fields, and unknown keys pass through unchanged.
 *
 * @since TBD
 *
 * @param {Array<Array<string>>} entries The body as `[ key, value ]` pairs.
 *
 * @return {Object} The ticket data, nested where the keys were bracketed.
 */
export const restBodyToTicketData = ( entries ) => {
	const data = {};

	entries.forEach( ( [ key, value ] ) => {
		if ( DROPPED_KEYS.includes( key ) ) {
			return;
		}

		if ( hasOwn( KEY_MAP, key ) ) {
			data[ KEY_MAP[ key ] ] = value;
			return;
		}

		const path = pathOf( key );

		if ( path.some( ( segment ) => FORBIDDEN_SEGMENTS.includes( segment ) ) ) {
			return;
		}

		if ( 'ticket' === path[ 0 ] && path.length > 1 ) {
			path[ 0 ] = 'tribe-ticket';
		}

		setPath( data, path, value );
	} );

	return data;
};

/**
 * Builds the `tec_tickets` payload from the store.
 *
 * Each `create` entry carries its block's client ID as its key: a save whose answer never came is sent again
 * with the same keys, and the server saves those entries over the tickets it created instead of creating them twice.
 *
 * @since TBD
 *
 * @param {Object}                args               The inputs.
 * @param {Array<string>}         args.clientIds     The ticket blocks, in block order.
 * @param {Object}                args.byClientId    The per-ticket state.
 * @param {Object<string,Array>}  args.bodies        The REST body entries per client ID.
 * @param {Array<number>}         args.stagedDeletes The saved tickets staged for deletion.
 * @param {Object<string,number>} args.stagedMoves   Ticket ID to destination post ID.
 *
 * @return {{payload: {create: Array, update: Object, delete: Array, move: Object}, createOrder: Array<string>}} The payload and which block each `create` position is.
 */
export const buildPayload = ( { clientIds, byClientId, bodies, stagedDeletes, stagedMoves } ) => {
	const create = [];
	const update = {};
	const createOrder = [];

	// The server drops every entry of a ticket named in more than one part: a delete wins over a move and an edit, a move over an edit.
	const isDeleted = ( ticketId ) => stagedDeletes.includes( Number( ticketId ) );
	const isMoved = ( ticketId ) => hasOwn( stagedMoves, ticketId );

	clientIds.forEach( ( clientId ) => {
		const ticket = byClientId[ clientId ];

		if ( ! ticket || ! ticket.isStaged || ! bodies[ clientId ] ) {
			return;
		}

		const data = { ...REST_DEFAULTS, ...restBodyToTicketData( bodies[ clientId ] ) };

		if ( ticket.hasBeenCreated && ticket.ticketId ) {
			if ( ! isDeleted( ticket.ticketId ) && ! isMoved( ticket.ticketId ) ) {
				update[ ticket.ticketId ] = data;
			}
			return;
		}

		create.push( { ...data, tec_tickets_create_key: clientId } );
		createOrder.push( clientId );
	} );

	return {
		payload: {
			create,
			update,
			delete: [ ...stagedDeletes ],
			move: Object.fromEntries(
				Object.entries( stagedMoves ).filter( ( [ ticketId ] ) => ! isDeleted( ticketId ) )
			),
		},
		createOrder,
	};
};

/**
 * Works out what a post save's answer means for the staged changes that went out with it.
 *
 * Only what was sent is settled: a block staged again after the request left keeps its newer change, and
 * deletes or moves staged meanwhile stay staged. A payload-level error (no part) means nothing was
 * committed, so every sent change stays staged with the message. Refused deletes and moves have no block
 * left to show their error on, so they become notices, and their tickets, still on the post, come back as
 * blocks. An error marked `applied` is not a refusal: the change happened and something that runs after it
 * failed, so it is settled like a save and the error stays as a warning; a delete or move becomes a notice
 * and no block comes back. A create that carries both an ID and a refusal exists but did not finish saving:
 * the block gets the ID, so the next save sends an update instead of creating the ticket again.
 *
 * @since TBD
 *
 * @param {Object} args          The inputs.
 * @param {Object} args.response The `tec_tickets` field of the saved record: `created` by position and `errors`.
 * @param {Object} args.sent     What the payload carried: `createOrder`, `updates` (client ID to ticket ID),
 *                               the `bodies` it was built from, `deletes` and `moves`.
 * @param {Object} args.live     The ticket blocks now: `clientIds` and the current `bodies`.
 *
 * @return {{blocks: Array<Object>, deleted: Array<number>, settle: {deletes: Array<number>, moves: Array<number>}, notices: Array<string>, restore: Array<number>}} What to do.
 */
export const reconcileSaveResponse = ( { response, sent, live } ) => {
	const created = response.created || {};
	const errors = Array.isArray( response.errors ) ? response.errors : [];
	const errorOf = ( part, key ) => errors.find( ( e ) => e && e.part === part && String( e.key ) === String( key ) );
	const errorFor = ( part, key ) => {
		const error = errorOf( part, key );

		return error ? String( error.message ?? '' ) : '';
	};
	// Refused: the ticket is still where it was. An `applied` error is a write that happened, not a refusal.
	const isRefused = ( part, key ) => {
		const error = errorOf( part, key );

		return !! error && ! error.applied;
	};
	const payloadError = errors.find( ( e ) => e && null === e.part );
	const isLive = ( clientId ) => live.clientIds.includes( clientId );
	const changedSince = ( clientId ) => live.bodies[ clientId ] !== sent.bodies[ clientId ];
	const blocks = [];

	if ( payloadError ) {
		const message = String( payloadError.message ?? '' );

		[ ...sent.createOrder, ...Object.keys( sent.updates ) ].filter( isLive ).forEach( ( clientId ) => {
			blocks.push( { clientId, staged: true, error: message, hook: null } );
		} );

		return { blocks, deleted: [], settle: { deletes: [], moves: [] }, notices: [ message ], restore: [] };
	}

	sent.createOrder.forEach( ( clientId, position ) => {
		if ( ! isLive( clientId ) ) {
			// The block is gone; a ticket created for it appears on the next load.
			return;
		}

		const ticketId = parseInt( created[ position ], 10 );

		if ( ticketId ) {
			const error = errorFor( 'create', position );
			const staged = changedSince( clientId ) || isRefused( 'create', position );
			blocks.push( { clientId, ticketId, staged, error, hook: 'created' } );
			return;
		}

		blocks.push( {
			clientId,
			staged: true,
			error:
				errorFor( 'create', position ) ||
				__( 'The ticket changes were not saved with the post.', 'event-tickets' ),
			hook: null,
		} );
	} );

	Object.entries( sent.updates ).forEach( ( [ clientId, ticketId ] ) => {
		if ( ! isLive( clientId ) ) {
			return;
		}

		const error = errorFor( 'update', ticketId );

		blocks.push(
			isRefused( 'update', ticketId )
				? { clientId, staged: true, error, hook: null }
				: { clientId, ticketId, staged: changedSince( clientId ), error, hook: 'updated' }
		);
	} );

	const sentDeletes = sent.deletes.map( Number );
	const sentMoves = Object.keys( sent.moves ).map( Number );
	const refused = ( part, ticketId ) => {
		const error = errorFor( part, ticketId );

		if ( ! error ) {
			return '';
		}

		// Most of the server's reasons already name the ticket, in whatever language they are in.
		if ( new RegExp( `(^|\\D)${ ticketId }(\\D|$)` ).test( error ) ) {
			return error;
		}

		/* translators: %1$d: the ticket ID, %2$s: the reason it was not saved. */
		return sprintf( __( 'Ticket %1$d: %2$s', 'event-tickets' ), ticketId, error );
	};
	const notices = [
		...sentDeletes.map( ( id ) => refused( 'delete', id ) ),
		...sentMoves.map( ( id ) => refused( 'move', id ) ),
	].filter( Boolean );

	return {
		blocks,
		deleted: sentDeletes.filter( ( id ) => ! isRefused( 'delete', id ) ),
		settle: { deletes: sentDeletes, moves: sentMoves },
		notices,
		restore: [
			...sentDeletes.filter( ( id ) => isRefused( 'delete', id ) ),
			...sentMoves.filter( ( id ) => isRefused( 'move', id ) ),
		],
	};
};
