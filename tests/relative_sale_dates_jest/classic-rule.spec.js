import { writeRule } from '@tec/tickets/relative-sale-dates/classic/rule';

const UNIT_HOURS = 3600;
const UNIT_WEEKS = 604800;

/**
 * Renders the sales window fields of the classic ticket form.
 *
 * @param {Object} start The start fields: `mode`, `value`, `unit` and `anchor`.
 * @param {Object} end   The end fields: `mode`, `value`, `unit` and `anchor`.
 */
function renderForm( start, end ) {
	const fields = ( key, { mode, value, unit, anchor } ) => `
		<select id="ticket_sales_${ key }_mode">
			<option value="default" ${ 'default' === mode ? 'selected' : '' }>Default</option>
			<option value="relative" ${ 'relative' === mode ? 'selected' : '' }>Relative</option>
			<option value="specific" ${ 'specific' === mode ? 'selected' : '' }>Specific</option>
		</select>
		<input type="number" id="ticket_sales_${ key }_value" value="${ value }" />
		<select id="ticket_sales_${ key }_unit">
			<option value="60">minutes</option>
			<option value="${ UNIT_HOURS }" ${ UNIT_HOURS === unit ? 'selected' : '' }>hours</option>
			<option value="${ UNIT_WEEKS }" ${ UNIT_WEEKS === unit ? 'selected' : '' }>weeks</option>
		</select>
		<select id="ticket_sales_${ key }_anchor">
			<option value="start" ${ 'start' === anchor ? 'selected' : '' }>starts</option>
			<option value="end" ${ 'end' === anchor ? 'selected' : '' }>ends</option>
		</select>`;

	document.body.innerHTML = `
		<div id="tribe_panel_edit">
			${ fields( 'start', start ) }
			${ fields( 'end', end ) }
			<input type="hidden" name="relative_sale_dates" id="ticket_relative_sale_dates" value="" />
		</div>`;
}

/**
 * @return {Object} The rule written into the hidden field, decoded.
 */
function getWrittenRule() {
	return JSON.parse( document.getElementById( 'ticket_relative_sale_dates' ).value );
}

describe( 'writeRule', () => {
	it( 'should write the value and unit of a relative end as integers', () => {
		renderForm(
			{ mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'end' },
			{ mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'start' }
		);

		writeRule( document );

		expect( getWrittenRule() ).toStrictEqual( {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'end' },
			end: { mode: 'relative', value: 1, unit: UNIT_HOURS, anchor: 'start' },
		} );
	} );

	it( 'should write only the mode of an end that is not relative', () => {
		renderForm(
			{ mode: 'default', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			{ mode: 'specific', value: 1, unit: UNIT_HOURS, anchor: 'start' }
		);

		writeRule( document );

		expect( getWrittenRule() ).toStrictEqual( {
			start: { mode: 'default' },
			end: { mode: 'specific' },
		} );
	} );

	it( 'should leave a form without the sales window fields alone', () => {
		document.body.innerHTML = '<div id="tribe_panel_edit"><input type="text" name="ticket_start_date" /></div>';
		const before = document.body.innerHTML;

		writeRule( document );

		expect( document.body.innerHTML ).toBe( before );
	} );
} );
