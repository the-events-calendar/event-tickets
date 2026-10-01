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
		 * Stages an edit of a saved ticket.
		 *
		 * @param {number}               ticketId The ticket ID.
		 * @param {Array<Array<string>>} fields   The panel's field set.
		 */
		stageUpdate( ticketId, fields ) {
			update = { ...update, [ ticketId ]: entry( fields ) };
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
		 * Stages the deletion of a saved ticket, dropping any staged edit of it.
		 *
		 * @param {number} ticketId The ticket ID.
		 */
		stageDelete( ticketId ) {
			const { [ ticketId ]: _dropped, ...rest } = update;
			update = rest;
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
		 * Stages a move of a saved ticket.
		 *
		 * @param {number} ticketId         The ticket ID.
		 * @param {number} destinationId    The destination post ID.
		 * @param {string} destinationTitle The destination title, for the row marker.
		 */
		stageMove( ticketId, destinationId, destinationTitle ) {
			move = { ...move, [ ticketId ]: { destinationId, destinationTitle } };
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

const numberOrNull = ( value ) => {
	const text = String( value ?? '' ).trim();

	if ( '' === text ) {
		return null;
	}

	const number = Number( text.replace( ',', '.' ) );

	return Number.isFinite( number ) ? number : NaN;
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
 * @param {Array<Array<string>>} fields  The field set.
 * @param {{sold?: number}}      context What the page knows about the ticket.
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

		const start = firstValue( fields, 'ticket_sale_start_date' );
		const end = firstValue( fields, 'ticket_sale_end_date' );

		if ( start && end && new Date( start ) > new Date( end ) ) {
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
