import { writeSalePriceRule } from '@tec/tickets/relative-sale-dates/classic/sale-price-rule';

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

/**
 * Renders the sale price fields of the classic ticket form.
 *
 * @param {Object} start The start fields: `mode`, `value` and `unit`.
 * @param {Object} end   The end fields: `mode`, `value` and `unit`.
 */
function renderForm( start, end ) {
	const fields = ( key, modes, { mode, value, unit } ) => `
		<select id="ticket_sale_${ key }_mode">
			${ modes.map( ( option ) => `<option value="${ option }" ${ option === mode ? 'selected' : '' }>${ option }</option>` ).join( '' ) }
		</select>
		<input type="number" id="ticket_sale_${ key }_value" value="${ value }" />
		<select id="ticket_sale_${ key }_unit">
			<option value="${ UNIT_DAYS }" ${ UNIT_DAYS === unit ? 'selected' : '' }>days</option>
			<option value="${ UNIT_WEEKS }" ${ UNIT_WEEKS === unit ? 'selected' : '' }>weeks</option>
		</select>`;

	document.body.innerHTML = `
		<div id="tribe_panel_edit">
			${ fields( 'start', [ 'now', 'relative', 'specific' ], start ) }
			${ fields( 'end', [ 'relative', 'specific' ], end ) }
			<input type="hidden" name="ticket_sale_price_relative" id="ticket_sale_price_relative" value="" />
		</div>`;
}

/**
 * @return {Object} The rule written into the hidden field, decoded.
 */
function getWrittenRule() {
	return JSON.parse( document.getElementById( 'ticket_sale_price_relative' ).value );
}

describe( 'writeSalePriceRule', () => {
	it( 'should write the value and unit of a relative boundary as integers, without an anchor', () => {
		renderForm( { mode: 'relative', value: 10, unit: UNIT_DAYS }, { mode: 'relative', value: 1, unit: UNIT_WEEKS } );

		writeSalePriceRule( document );

		expect( getWrittenRule() ).toStrictEqual( {
			start: { mode: 'relative', value: 10, unit: UNIT_DAYS },
			end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
		} );
	} );

	it( 'should write only the mode of a boundary that is not relative', () => {
		renderForm( { mode: 'now', value: 2, unit: UNIT_WEEKS }, { mode: 'specific', value: 1, unit: UNIT_WEEKS } );

		writeSalePriceRule( document );

		expect( getWrittenRule() ).toStrictEqual( {
			start: { mode: 'now' },
			end: { mode: 'specific' },
		} );
	} );

	it( 'should leave a form without the sale price options alone', () => {
		document.body.innerHTML = '<div id="tribe_panel_edit"><input type="text" name="ticket_sale_start_date" /></div>';
		const before = document.body.innerHTML;

		writeSalePriceRule( document );

		expect( document.body.innerHTML ).toBe( before );
	} );
} );
