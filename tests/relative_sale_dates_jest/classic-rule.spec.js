import { readRule, writeRule } from '@tec/tickets/relative-sale-dates/classic/rule';
import { SALES_WINDOW, SALE_PRICE_WINDOW } from '@tec/tickets/relative-sale-dates/window-kinds';

const UNIT_HOURS = 3600;
const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

/**
 * @param {Array} values   The option values.
 * @param {*}     selected The value of the selected option.
 *
 * @return {string} The options of a select.
 */
function renderOptions( values, selected ) {
	return values
		.map( ( value ) => `<option value="${ value }" ${ value === selected ? 'selected' : '' }>${ value }</option>` )
		.join( '' );
}

/**
 * @param {Object} kind   The window kind.
 * @param {string} key    The end of the window, `start` or `end`.
 * @param {string} anchor The selected anchor.
 *
 * @return {string} The anchor select of one end of the window.
 */
function renderAnchor( kind, key, anchor ) {
	const options = renderOptions( [ 'start', 'end' ], anchor );

	return `<select id="${ kind.fieldPrefix }_${ key }_anchor">${ options }</select>`;
}

/**
 * Renders the fields of one window kind in the classic ticket form.
 *
 * @param {Object} kind  The window kind.
 * @param {Object} start The start fields: `mode`, `value`, `unit` and, for a kind that takes one, `anchor`.
 * @param {Object} end   The end fields: `mode`, `value`, `unit` and, for a kind that takes one, `anchor`.
 */
function renderForm( kind, start, end ) {
	const fields = ( key, { mode, value, unit, anchor } ) => `
		<select id="${ kind.fieldPrefix }_${ key }_mode">
			${ renderOptions( [ 'default', 'now', 'relative', 'specific' ], mode ) }
		</select>
		<input type="number" id="${ kind.fieldPrefix }_${ key }_value" value="${ value }" />
		<select id="${ kind.fieldPrefix }_${ key }_unit">
			${ renderOptions( [ UNIT_HOURS, UNIT_DAYS, UNIT_WEEKS ], unit ) }
		</select>
		${ kind.takesAnchor ? renderAnchor( kind, key, anchor ) : '' }`;

	document.body.innerHTML = `
		<div id="tribe_panel_edit">
			${ fields( 'start', start ) }
			${ fields( 'end', end ) }
			<input type="hidden" name="${ kind.ruleFieldId }" id="${ kind.ruleFieldId }" value="" />
		</div>`;
}

/**
 * @param {Object} kind The window kind.
 *
 * @return {Object} The rule written into the kind's hidden field, decoded.
 */
function getWrittenRule( kind ) {
	return JSON.parse( document.getElementById( kind.ruleFieldId ).value );
}

/**
 * @param {Object} kind     The window kind.
 * @param {Object} boundary A relative boundary as rendered, with its anchor.
 *
 * @return {Object} The boundary as the kind writes it: without the anchor for a kind that takes none.
 */
function asWritten( kind, boundary ) {
	const { anchor, ...withoutAnchor } = boundary;

	return kind.takesAnchor ? { ...withoutAnchor, anchor } : withoutAnchor;
}

describe.each( [
	[ 'sales window', SALES_WINDOW, 'default' ],
	[ 'sale price window', SALE_PRICE_WINDOW, 'now' ],
] )( 'the rule of the %s', ( name, kind, openStartMode ) => {
	const relativeStart = { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' };
	const relativeEnd = { mode: 'relative', value: 3, unit: UNIT_DAYS, anchor: 'start' };

	it( 'should read the rule the fields express', () => {
		renderForm( kind, relativeStart, { mode: 'specific', value: 1, unit: UNIT_DAYS, anchor: 'start' } );

		expect( readRule( document, kind ) ).toStrictEqual( {
			start: asWritten( kind, relativeStart ),
			end: { mode: 'specific' },
		} );
	} );

	it( 'should write the value and unit of a relative end as integers, with an anchor only for a kind that takes one', () => {
		renderForm( kind, relativeStart, relativeEnd );

		writeRule( document, kind );

		expect( getWrittenRule( kind ) ).toStrictEqual( {
			start: asWritten( kind, relativeStart ),
			end: asWritten( kind, relativeEnd ),
		} );
	} );

	it( 'should write only the mode of an end that is not relative', () => {
		renderForm(
			kind,
			{ mode: openStartMode, value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			{ mode: 'specific', value: 1, unit: UNIT_HOURS, anchor: 'start' }
		);

		writeRule( document, kind );

		expect( getWrittenRule( kind ) ).toStrictEqual( {
			start: { mode: openStartMode },
			end: { mode: 'specific' },
		} );
	} );

	it( 'should leave a form without the fields of the kind alone', () => {
		document.body.innerHTML = '<div id="tribe_panel_edit"><input type="text" name="ticket_start_date" /></div>';
		const before = document.body.innerHTML;

		writeRule( document, kind );

		expect( document.body.innerHTML ).toBe( before );
	} );
} );

describe( 'the rule of a form read without a kind', () => {
	it( 'should be the sales window rule', () => {
		const start = { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'end' };
		const end = { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'start' };
		renderForm( SALES_WINDOW, start, end );

		writeRule( document );

		expect( readRule( document ) ).toStrictEqual( { start, end } );
		expect( getWrittenRule( SALES_WINDOW ) ).toStrictEqual( { start, end } );
	} );
} );
