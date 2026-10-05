/**
 * Pure helpers for staging ticket changes in the classic editor.
 *
 * Nothing here touches the DOM, so it is covered by Jest. The staged state mirrors the
 * `tec_tickets` payload contract: `create` (a list of field sets), `update` (ticket ID to
 * field set), `delete` (ticket IDs) and `move` (ticket ID to destination post ID).
 *
 * A "field set" is a list of `[ name, value ]` pairs as `jQuery.serializeArray()` yields them,
 * so checkboxes, radios and multi-selects keep the semantics the AJAX save has today.
 *
 * @since TBD
 */

/**
 * Turns an input name into the bracket segment appended to a payload prefix.
 *
 * `ticket_name` becomes `[ticket_name]`, `tribe-ticket[capacity]` becomes `[tribe-ticket][capacity]`
 * and a list name keeps its trailing `[]`.
 *
 * @since TBD
 *
 * @param {string} name The input name.
 *
 * @return {string} The bracketed segment.
 */
export const bracketName = ( name ) => {
	const open = name.indexOf( '[' );

	if ( -1 === open ) {
		return `[${ name }]`;
	}

	return `[${ name.slice( 0, open ) }]${ name.slice( open ) }`;
};

/**
 * Removes the `ticket_id` pair from a field set, so a create never carries one.
 *
 * @since TBD
 *
 * @param {Array<Array<string>>} fields The field set.
 *
 * @return {Array<Array<string>>} The field set without `ticket_id`.
 */
export const fieldsWithoutTicketId = ( fields ) => fields.filter( ( [ name ] ) => 'ticket_id' !== name );

const firstValue = ( fields, name ) => {
	const pair = fields.find( ( [ fieldName ] ) => fieldName === name );

	return pair ? String( pair[ 1 ] ) : '';
};

/**
 * Reads what a staged row shows from a field set.
 *
 * @since TBD
 *
 * @param {Array<Array<string>>} fields The field set.
 *
 * @return {{name: string, price: string, capacity: string, type: string, provider: string}} The summary.
 */
export const summaryFromFields = ( fields ) => ( {
	name: firstValue( fields, 'ticket_name' ),
	price: firstValue( fields, 'ticket_price' ),
	capacity: firstValue( fields, 'tribe-ticket[capacity]' ),
	type: firstValue( fields, 'ticket_type' ),
	provider: firstValue( fields, 'ticket_provider' ),
} );

const entry = ( fields ) => ( { fields, summary: summaryFromFields( fields ) } );

/**
 * Creates the staged state.
 *
 * @since TBD
 *
 * @return {Object} The state, with the methods below.
 */
export const createState = () => {
	let create = [];
	let update = {};
	let deleted = [];
	let move = {};

	const api = {
		/**
		 * Stages a new ticket.
		 *
		 * @param {Array<Array<string>>} fields The panel's field set.
		 *
		 * @return {number} The position of the new entry.
		 */
		stageCreate( fields ) {
			create.push( entry( fieldsWithoutTicketId( fields ) ) );

			return create.length - 1;
		},

		/**
		 * Replaces a staged new ticket after it was edited again.
		 *
		 * @param {number}               position The entry's position.
		 * @param {Array<Array<string>>} fields   The panel's field set.
		 */
		restageCreate( position, fields ) {
			if ( undefined !== create[ position ] ) {
				create[ position ] = entry( fieldsWithoutTicketId( fields ) );
			}
		},

		/**
		 * Drops a staged new ticket; later entries move up.
		 *
		 * @param {number} position The entry's position.
		 */
		dropCreate( position ) {
			create = create.filter( ( _, index ) => index !== position );
		},

		/**
		 * Returns a staged new ticket.
		 *
		 * @param {number} position The entry's position.
		 *
		 * @return {Object|undefined} The entry.
		 */
		getCreate( position ) {
			return create[ position ];
		},

		/**
		 * Stages an edit of a saved ticket, unless a move of it is staged.
		 *
		 * A saved ticket carries one staged change at most: the server drops every entry of a ticket named
		 * in more than one of `update`, `move` and `delete`.
		 *
		 * @param {number}               ticketId The ticket ID.
		 * @param {Array<Array<string>>} fields   The panel's field set.
		 *
		 * @return {boolean} Whether the edit was staged.
		 */
		stageUpdate( ticketId, fields ) {
			if ( move[ ticketId ] ) {
				return false;
			}

			update = { ...update, [ ticketId ]: entry( fields ) };

			return true;
		},

		/**
		 * Returns the staged edit of a saved ticket.
		 *
		 * @param {number} ticketId The ticket ID.
		 *
		 * @return {Object|undefined} The entry.
		 */
		getUpdate( ticketId ) {
			return update[ ticketId ];
		},

		/**
		 * Stages the deletion of a saved ticket, replacing any staged edit or move of it.
		 *
		 * @param {number} ticketId The ticket ID.
		 */
		stageDelete( ticketId ) {
			const { [ ticketId ]: _droppedUpdate, ...restUpdate } = update;
			const { [ ticketId ]: _droppedMove, ...restMove } = move;
			update = restUpdate;
			move = restMove;
			if ( ! deleted.includes( ticketId ) ) {
				deleted = [ ...deleted, ticketId ];
			}
		},

		/**
		 * Takes a saved ticket out of the staged deletions.
		 *
		 * @param {number} ticketId The ticket ID.
		 */
		undoDelete( ticketId ) {
			deleted = deleted.filter( ( id ) => id !== ticketId );
		},

		/**
		 * Whether a saved ticket is staged for deletion.
		 *
		 * @param {number} ticketId The ticket ID.
		 *
		 * @return {boolean} Whether it is.
		 */
		isDeleted( ticketId ) {
			return deleted.includes( ticketId );
		},

		/**
		 * Stages a move of a saved ticket, unless an edit or a deletion of it is staged.
		 *
		 * @param {number} ticketId         The ticket ID.
		 * @param {number} destinationId    The destination post ID.
		 * @param {string} destinationTitle The destination title, for the row marker.
		 *
		 * @return {boolean} Whether the move was staged.
		 */
		stageMove( ticketId, destinationId, destinationTitle ) {
			if ( update[ ticketId ] || deleted.includes( ticketId ) ) {
				return false;
			}

			move = { ...move, [ ticketId ]: { destinationId, destinationTitle } };

			return true;
		},

		/**
		 * Returns the staged move of a saved ticket.
		 *
		 * @param {number} ticketId The ticket ID.
		 *
		 * @return {{destinationId: number, destinationTitle: string}|undefined} The move.
		 */
		getMove( ticketId ) {
			return move[ ticketId ];
		},

		/**
		 * Takes a saved ticket out of the staged moves.
		 *
		 * @param {number} ticketId The ticket ID.
		 */
		undoMove( ticketId ) {
			const { [ ticketId ]: _dropped, ...rest } = move;
			move = rest;
		},

		/**
		 * Whether anything is staged.
		 *
		 * @return {boolean} Whether there are staged changes.
		 */
		hasChanges() {
			return (
				create.length > 0 ||
				Object.keys( update ).length > 0 ||
				deleted.length > 0 ||
				Object.keys( move ).length > 0
			);
		},

		/**
		 * Returns the staged state in the shape of the payload, with summaries for the rows.
		 *
		 * @return {{create: Array, update: Object, delete: Array<number>, move: Object}} The payload view.
		 */
		toPayload() {
			return {
				create: [ ...create ],
				update: { ...update },
				delete: [ ...deleted ],
				move: Object.fromEntries( Object.entries( move ).map( ( [ id, m ] ) => [ id, m.destinationId ] ) ),
			};
		},
	};

	return api;
};

/**
 * Builds the hidden inputs the post form carries for the staged state.
 *
 * @since TBD
 *
 * @param {Object} state The staged state.
 *
 * @return {Array<Array<string>>} `[ name, value ]` pairs.
 */
export const buildHiddenFields = ( state ) => {
	const payload = state.toPayload();
	const fields = [];

	payload.create.forEach( ( { fields: entryFields }, position ) => {
		entryFields.forEach( ( [ name, value ] ) => {
			fields.push( [ `tec_tickets[create][${ position }]${ bracketName( name ) }`, String( value ) ] );
		} );
	} );

	Object.entries( payload.update ).forEach( ( [ ticketId, { fields: entryFields } ] ) => {
		entryFields.forEach( ( [ name, value ] ) => {
			fields.push( [ `tec_tickets[update][${ ticketId }]${ bracketName( name ) }`, String( value ) ] );
		} );
	} );

	payload.delete.forEach( ( ticketId ) => {
		fields.push( [ 'tec_tickets[delete][]', String( ticketId ) ] );
	} );

	Object.entries( payload.move ).forEach( ( [ ticketId, destinationId ] ) => {
		fields.push( [ `tec_tickets[move][${ ticketId }]`, String( destinationId ) ] );
	} );

	return fields;
};

/**
 * Reads a price the way the panel lets an admin type it: digits with an optional thousands separator and
 * decimal separator, either of `.` and `,`. The last separator is the decimal one when one or two digits
 * follow it; otherwise every separator groups thousands.
 *
 * @param {*} value The value.
 *
 * @return {number|null} The number, `NaN` when it is not a non-negative number, `null` when empty.
 */
const numberOrNull = ( value ) => {
	const text = String( value ?? '' ).replace( /\s/g, '' );

	if ( '' === text ) {
		return null;
	}

	if ( ! /^[\d.,]*\d[\d.,]*$/.test( text ) ) {
		return NaN;
	}

	const last = Math.max( text.lastIndexOf( '.' ), text.lastIndexOf( ',' ) );
	const decimals = last < 0 ? '' : text.slice( last + 1 );

	if ( last >= 0 && decimals.length >= 1 && decimals.length <= 2 ) {
		return Number( `${ text.slice( 0, last ).replace( /[.,]/g, '' ) || '0' }.${ decimals }` );
	}

	return Number( text.replace( /[.,]/g, '' ) );
};

/**
 * Where the year, month and day sit in each datepicker format, by the index the site option stores
 * (`Tribe__Date_Utils::datepicker_formats()`, the list `tickets.js` uses).
 */
const DATE_ORDERS = [ 'ymd', 'mdy', 'mdy', 'dmy', 'dmy', 'mdy', 'mdy', 'dmy', 'dmy', 'ymd', 'mdy', 'dmy' ];

/**
 * Reads a date typed in the site's datepicker format.
 *
 * @since TBD
 *
 * @param {string} value       The date.
 * @param {number} formatIndex The datepicker format index.
 *
 * @return {Date|null} The date, or `null` when it cannot be read in that format.
 */
export const parseDatepickerDate = ( value, formatIndex ) => {
	const order = DATE_ORDERS[ formatIndex ];
	const parts = String( value ?? '' )
		.trim()
		.split( /[-/.]/ );

	if ( ! order || 3 !== parts.length || parts.some( ( part ) => ! /^\d+$/.test( part ) ) ) {
		return null;
	}

	const at = ( unit ) => parseInt( parts[ order.indexOf( unit ) ], 10 );
	const [ year, month, day ] = [ at( 'y' ), at( 'm' ), at( 'd' ) ];
	const date = new Date( year, month - 1, day );

	return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : null;
};

const isOn = ( value ) =>
	! [ '', '0', 'false', 'no', 'off' ].includes(
		String( value ?? '' )
			.trim()
			.toLowerCase()
	);

/**
 * Validates a staged field set with the rules the editors share.
 *
 * Rules: a name is present; the price, when given, is a non-negative number; when the sale price is
 * on it is a number below the price; the sale window start is not after its end; and the capacity,
 * when given and when the tickets sold are known, is not below them.
 *
 * @since TBD
 *
 * @param {Array<Array<string>>}                 fields  The field set.
 * @param {{sold?: number, dateFormat?: number}} context What the page knows: the tickets sold and the datepicker format index.
 *
 * @return {Array<string>} The failing rules: `name`, `price`, `sale_price`, `sale_window`, `capacity`.
 */
export const validateFields = ( fields, context = {} ) => {
	const errors = [];
	const name = firstValue( fields, 'ticket_name' );
	const price = numberOrNull( firstValue( fields, 'ticket_price' ) );
	const capacity = numberOrNull( firstValue( fields, 'tribe-ticket[capacity]' ) );

	if ( '' === name.trim() ) {
		errors.push( 'name' );
	}

	if ( null !== price && ( Number.isNaN( price ) || price < 0 ) ) {
		errors.push( 'price' );
	}

	if ( isOn( firstValue( fields, 'ticket_add_sale_price' ) ) ) {
		const salePrice = numberOrNull( firstValue( fields, 'ticket_sale_price' ) );

		if (
			null === salePrice ||
			Number.isNaN( salePrice ) ||
			null === price ||
			Number.isNaN( price ) ||
			salePrice >= price
		) {
			errors.push( 'sale_price' );
		}

		const dateFormat = undefined === context.dateFormat ? 0 : context.dateFormat;
		const start = parseDatepickerDate( firstValue( fields, 'ticket_sale_start_date' ), dateFormat );
		const end = parseDatepickerDate( firstValue( fields, 'ticket_sale_end_date' ), dateFormat );

		// A date the format cannot read is left to the server rather than reported as a bad window.
		if ( start && end && start > end ) {
			errors.push( 'sale_window' );
		}
	}

	if (
		'number' === typeof context.sold &&
		null !== capacity &&
		! Number.isNaN( capacity ) &&
		capacity < context.sold
	) {
		errors.push( 'capacity' );
	}

	return errors;
};

/**
 * Copies a field set for a duplicate: the name gets " (copy)", the ID and SKU are dropped.
 *
 * @since TBD
 *
 * @param {Array<Array<string>>} fields The field set to copy.
 *
 * @return {Array<Array<string>>} The copy.
 */
export const duplicateFields = ( fields ) =>
	fields
		.filter( ( [ name ] ) => ! [ 'ticket_id', 'ticket_sku' ].includes( name ) )
		.map( ( [ name, value ] ) => ( 'ticket_name' === name ? [ name, `${ value } (copy)` ] : [ name, value ] ) );
