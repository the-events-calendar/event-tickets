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

	clientIds.forEach( ( clientId ) => {
		const ticket = byClientId[ clientId ];

		if ( ! ticket || ! ticket.isStaged || ! bodies[ clientId ] ) {
			return;
		}

		const data = restBodyToTicketData( bodies[ clientId ] );

		if ( ticket.hasBeenCreated && ticket.ticketId ) {
			update[ ticket.ticketId ] = data;
			return;
		}

		create.push( data );
		createOrder.push( clientId );
	} );

	return {
		payload: {
			create,
			update,
			delete: [ ...stagedDeletes ],
			move: { ...stagedMoves },
		},
		createOrder,
	};
};
