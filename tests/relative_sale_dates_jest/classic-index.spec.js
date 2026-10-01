global.DateFormatter = require( 'php-date-formatter' );

const UNIT_DAYS = 86400;
const UNIT_WEEKS = 604800;

/**
 * Renders the tickets metabox with the sales window fields of a relative start and a default end.
 */
function renderMetabox() {
	document.body.innerHTML = `
		<div id="tribetickets">
			<div id="tribe_panel_edit">
				<select id="ticket_sales_start_mode"><option value="relative" selected>Relative</option></select>
				<input type="number" id="ticket_sales_start_value" value="2" />
				<select id="ticket_sales_start_unit"><option value="${ UNIT_WEEKS }" selected>weeks</option></select>
				<select id="ticket_sales_start_anchor"><option value="start" selected>starts</option></select>
				<select id="ticket_sales_end_mode"><option value="default" selected>Default</option></select>
				<input type="number" id="ticket_sales_end_value" value="1" />
				<select id="ticket_sales_end_unit"><option value="3600" selected>hours</option></select>
				<select id="ticket_sales_end_anchor"><option value="start" selected>starts</option></select>
				<input type="hidden" name="relative_sale_dates" id="ticket_relative_sale_dates" value="" />
			</div>
		</div>`;
}

/**
 * Loads the classic editor script and waits for its DOM-ready callbacks to run.
 *
 * @return {Promise<Object>} Resolves, once the script is set up, to the hooks module the script was loaded with.
 */
async function loadScript() {
	let hooks;

	jest.isolateModules( () => {
		require( '@tec/tickets/relative-sale-dates/classic/index' );
		hooks = require( '@wordpress/hooks' );
	} );

	await new Promise( ( resolve ) => jQuery( resolve ) );

	return hooks;
}

/**
 * Renders the event fields and the tickets metabox with a relative start and a relative end.
 *
 * @param {Object} start The relative start: `value`, `unit` and `anchor`.
 */
function renderEventForm( start = { value: 2, unit: UNIT_WEEKS, anchor: 'start' } ) {
	document.body.innerHTML = `
		<input id="EventStartDate" value="6/24/2099" />
		<input id="EventStartTime" value="7:00pm" />
		<input id="EventEndDate" value="6/24/2099" />
		<input id="EventEndTime" value="10:00pm" />
		<input type="checkbox" id="allDayCheckbox" />
		<select id="event-timezone"><option value="America/New_York" selected>New York</option></select>
		${ renderPanel( start ) }`;
}

/**
 * @param {Object} start The relative start: `value`, `unit` and `anchor`.
 *
 * @return {string} The tickets metabox with the ticket edit panel.
 */
function renderPanel( { value, unit, anchor } ) {
	return `
		<div id="tribetickets">
			<div id="tribe_panel_edit">
				<select id="ticket_sales_start_mode"><option value="relative" selected>Relative</option></select>
				<input type="number" id="ticket_sales_start_value" value="${ value }" />
				<select id="ticket_sales_start_unit"><option value="${ unit }" selected>unit</option></select>
				<select id="ticket_sales_start_anchor"><option value="${ anchor }" selected>anchor</option></select>
				<span id="ticket_sales_start_helper"></span>
				<select id="ticket_sales_end_mode"><option value="relative" selected>Relative</option></select>
				<input type="number" id="ticket_sales_end_value" value="1" />
				<select id="ticket_sales_end_unit"><option value="3600" selected>hours</option></select>
				<select id="ticket_sales_end_anchor"><option value="start" selected>starts</option></select>
				<span id="ticket_sales_end_helper"></span>
				<input type="hidden" name="relative_sale_dates" id="ticket_relative_sale_dates" value="" />
			</div>
		</div>`;
}

/**
 * @param {string} end The end of the window, `start` or `end`.
 *
 * @return {string} The helper text of that end.
 */
function getHelperText( end ) {
	return document.getElementById( `ticket_sales_${ end }_helper` ).textContent;
}

describe( 'classic editor script', () => {
	beforeEach( () => {
		window.tec = { tickets: { relativeSaleDates: {} } };
		window.tec.tickets.relativeSaleDates.classicData = {
			timeFormat: 'g:i a',
			dateWithYear: 'F j, Y',
			dateNoYear: 'F j',
			timezones: {},
			allDay: { start: '00:00:00', end: '23:59:59', endDays: 0 },
			text: { start: 'Sales start %1$s at %2$s', end: 'Sales end %1$s at %2$s' },
		};
		window.tribe_dynamic_help_text = {
			date_with_year: 'F j, Y',
			date_no_year: 'F j',
			datepicker_format: 'n/j/Y',
		};
	} );

	afterEach( () => {
		jQuery( document ).off();
		document.body.innerHTML = '';
	} );

	it( 'should show when each relative end of the window works out to', async () => {
		renderEventForm();
		await loadScript();

		expect( getHelperText( 'start' ) ).toBe( 'Sales start June 10, 2099 at 7:00 pm' );
		expect( getHelperText( 'end' ) ).toBe( 'Sales end June 24, 2099 at 6:00 pm' );
	} );

	it( 'should format the helper text with the localized TEC formats, as the block editor does', async () => {
		window.tribe_dynamic_help_text.date_with_year = 'Y-m-d';
		window.tec.tickets.relativeSaleDates.classicData.dateWithYear = 'j F Y';
		renderEventForm();
		await loadScript();

		expect( getHelperText( 'start' ) ).toBe( 'Sales start 10 June 2099 at 7:00 pm' );
	} );

	it( 'should update the helper text when the event date changes', async () => {
		renderEventForm();
		await loadScript();

		jQuery( '#EventStartDate' ).val( '6/25/2099' ).trigger( 'change' );

		expect( getHelperText( 'start' ) ).toBe( 'Sales start June 11, 2099 at 7:00 pm' );
	} );

	it( 'should update the helper text when a relative field changes', async () => {
		renderEventForm();
		await loadScript();

		jQuery( '#ticket_sales_start_value' ).val( '3' ).trigger( 'change' );

		expect( getHelperText( 'start' ) ).toBe( 'Sales start June 3, 2099 at 7:00 pm' );
	} );

	it( 'should show the helper text of a panel the editor replaced', async () => {
		renderEventForm();
		const hooks = await loadScript();

		jQuery( '#tribetickets' ).replaceWith( renderPanel( { value: 1, unit: UNIT_DAYS, anchor: 'end' } ) );
		hooks.doAction( 'tec.tickets.admin.panels.refreshed', {} );

		expect( getHelperText( 'start' ) ).toBe( 'Sales start June 23, 2099 at 10:00 pm' );
	} );

	it( 'should leave the helper text of an end that is not relative empty', async () => {
		renderEventForm();
		jQuery( '#ticket_sales_start_mode' ).html( '<option value="default" selected>Default</option>' );
		await loadScript();

		expect( getHelperText( 'start' ) ).toBe( '' );
	} );

	it( 'should write the rule before the ticket is saved', async () => {
		renderMetabox();
		await loadScript();

		jQuery( '#tribetickets' ).trigger( 'pre-save-ticket.tribe' );

		expect( JSON.parse( document.getElementById( 'ticket_relative_sale_dates' ).value ) ).toStrictEqual( {
			start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default' },
		} );
	} );

	it( 'should write the rule when another handler stops the save event from bubbling', async () => {
		renderMetabox();
		// Event Tickets Plus binds a handler like this one on the metabox.
		jQuery( '#tribetickets' ).on( 'pre-save-ticket.tribe', ( event ) => event.stopPropagation() );
		await loadScript();

		jQuery( '#tribetickets' ).trigger( 'pre-save-ticket.tribe' );

		expect( document.getElementById( 'ticket_relative_sale_dates' ).value ).not.toBe( '' );
	} );
} );
