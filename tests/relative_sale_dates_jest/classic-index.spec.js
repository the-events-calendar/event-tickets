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
 * @param {Object} end   The end: `mode`, and `value`, `unit` and `anchor` for a relative one.
 */
function renderEventForm( start = { value: 2, unit: UNIT_WEEKS, anchor: 'start' }, end = undefined ) {
	document.body.innerHTML = `
		<input id="EventStartDate" value="6/24/2099" />
		<input id="EventStartTime" value="7:00pm" />
		<input id="EventEndDate" value="6/24/2099" />
		<input id="EventEndTime" value="10:00pm" />
		<input type="checkbox" id="allDayCheckbox" />
		<select id="event-timezone"><option value="America/New_York" selected>New York</option></select>
		${ renderListRow() }
		${ renderPanel( start, end ) }`;
}

/**
 * @return {string} The sale dates of a saved ticket that starts 2 weeks before the event and ends when it starts.
 */
function renderListRow() {
	const rule = JSON.stringify( {
		start: { mode: 'relative', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
		end: { mode: 'default' },
	} ).replace( /"/g, '&quot;' );

	return `<div id="list-row-dates" data-relative-sale-dates="${ rule }" data-sale-start="2099-06-10" data-sale-end="2099-06-24">stored</div>`;
}

/**
 * @param {Object} start The relative start: `value`, `unit` and `anchor`.
 * @param {Object} end   The end: `mode`, and `value`, `unit` and `anchor` for a relative one.
 *
 * @return {string} The tickets metabox with the ticket edit panel.
 */
function renderPanel( { value, unit, anchor }, end = { mode: 'relative', value: 1, unit: 3600, anchor: 'start' } ) {
	return `
		<div id="tribetickets">
			<div id="tribe_panel_edit">
				<select id="ticket_sales_start_mode"><option value="relative" selected>Relative</option></select>
				<input type="number" id="ticket_sales_start_value" value="${ value }" />
				<select id="ticket_sales_start_unit"><option value="${ unit }" selected>unit</option></select>
				<select id="ticket_sales_start_anchor"><option value="${ anchor }" selected>anchor</option></select>
				<span id="ticket_sales_start_helper"></span>
				<select id="ticket_sales_end_mode"><option value="${ end.mode }" selected>mode</option></select>
				<input type="number" id="ticket_sales_end_value" value="${ end.value }" />
				<select id="ticket_sales_end_unit"><option value="${ end.unit }" selected>unit</option></select>
				<select id="ticket_sales_end_anchor"><option value="${ end.anchor }" selected>anchor</option></select>
				<span id="ticket_sales_end_helper"></span>
				<input id="ticket_end_date" value="${ end.date || '' }" />
				<input id="ticket_end_time" value="${ end.time || '' }" />
				<p id="ticket_sales_window_error"></p>
				<input type="hidden" name="relative_sale_dates" id="ticket_relative_sale_dates" value="" />
			</div>
		</div>`;
}

const INVALID_WINDOW = 'Ticket sales cannot end before they start. Please adjust the sales window.';

/**
 * Asks for the extra validation `tickets.js` runs before it saves a ticket.
 *
 * @return {*} What the validation handlers answered; `false` blocks the save.
 */
function validateTicket() {
	return jQuery( '#tribetickets' ).triggerHandler( 'additionalValidation.tribe', [ true ] );
}

/**
 * @return {boolean} Whether the end of the window is marked invalid, and points at the error that says why.
 */
function isEndMarkedInvalid() {
	const endMode = document.getElementById( 'ticket_sales_end_mode' );

	return 'true' === endMode.getAttribute( 'aria-invalid' ) && 'ticket_sales_window_error' === endMode.getAttribute( 'aria-describedby' );
}

/**
 * @param {string} end The end of the window, `start` or `end`.
 *
 * @return {string} The name of the unit selected for that end.
 */
function getUnitLabel( end ) {
	const unit = document.getElementById( `ticket_sales_${ end }_unit` );

	return unit.options[ unit.selectedIndex ].textContent;
}

/**
 * @return {string} The sale dates the listed ticket shows.
 */
function getListRowText() {
	return document.getElementById( 'list-row-dates' ).textContent;
}

/**
 * @return {string} The sales window error shown in the form.
 */
function getWindowError() {
	return document.getElementById( 'ticket_sales_window_error' ).textContent;
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
			listDateFormat: 'F j, Y',
			timezones: {},
			allDay: { start: '00:00:00', end: '23:59:59', endDays: 0 },
			text: {
				start: 'Sales start %1$s at %2$s',
				end: 'Sales end %1$s at %2$s',
				invalidWindow: 'Ticket sales cannot end before they start. Please adjust the sales window.',
			},
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

	it( 'should name the unit in the plural form of the number', async () => {
		renderEventForm();
		await loadScript();

		expect( getUnitLabel( 'start' ) ).toBe( 'weeks' );
		expect( getUnitLabel( 'end' ) ).toBe( 'hour' );
	} );

	it( 'should change the unit name when the number changes', async () => {
		renderEventForm();
		await loadScript();

		jQuery( '#ticket_sales_start_value' ).val( '1' ).trigger( 'input' );
		jQuery( '#ticket_sales_end_value' ).val( '3' ).trigger( 'input' );

		expect( getUnitLabel( 'start' ) ).toBe( 'week' );
		expect( getUnitLabel( 'end' ) ).toBe( 'hours' );
	} );

	it( 'should change the sale price unit name when the number changes', async () => {
		renderEventForm();
		document.getElementById( 'tribe_panel_edit' ).insertAdjacentHTML(
			'beforeend',
			`<input type="number" id="ticket_sale_end_value" value="1" />
			<select id="ticket_sale_end_unit">
				<option value="${ UNIT_DAYS }">day</option>
				<option value="${ UNIT_WEEKS }" selected>week</option>
			</select>`
		);
		await loadScript();

		jQuery( '#ticket_sale_end_value' ).val( '3' ).trigger( 'input' );

		const unit = document.getElementById( 'ticket_sale_end_unit' );
		expect( Array.from( unit.options ).map( ( option ) => option.textContent ) ).toStrictEqual( [ 'days', 'weeks' ] );
	} );

	it( 'should write the sale dates of a listed ticket from the event dates in the form', async () => {
		renderEventForm();
		await loadScript();

		expect( getListRowText() ).toBe( 'June 10, 2099 - June 24, 2099' );
	} );

	it( 'should update the sale dates of a listed ticket when the event date changes', async () => {
		renderEventForm();
		await loadScript();

		jQuery( '#EventStartDate' ).val( '6/30/2099' ).trigger( 'change' );

		expect( getListRowText() ).toBe( 'June 16, 2099 - June 30, 2099' );
	} );

	it( 'should keep the sale dates of a listed ticket when a field of the ticket form changes', async () => {
		renderEventForm();
		await loadScript();

		jQuery( '#ticket_sales_start_value' ).val( '3' ).trigger( 'change' );

		expect( getListRowText() ).toBe( 'June 10, 2099 - June 24, 2099' );
	} );

	it( 'should write the sale dates of a list the editor replaced', async () => {
		renderEventForm();
		const hooks = await loadScript();

		jQuery( '#list-row-dates' ).replaceWith( renderListRow() );
		jQuery( '#EventStartDate' ).val( '6/30/2099' );
		hooks.doAction( 'tec.tickets.admin.panels.refreshed', {} );

		expect( getListRowText() ).toBe( 'June 16, 2099 - June 30, 2099' );
	} );

	it( 'should block the save of a window that ends before it starts', async () => {
		renderEventForm( { value: 1, unit: 3600, anchor: 'start' }, { mode: 'relative', value: 2, unit: 3600, anchor: 'start' } );
		await loadScript();

		expect( validateTicket() ).toBe( false );
		expect( getWindowError() ).toBe( INVALID_WINDOW );
		expect( isEndMarkedInvalid() ).toBe( true );
	} );

	it( 'should block the save of a window that ends when it starts', async () => {
		renderEventForm( { value: 1, unit: 3600, anchor: 'start' }, { mode: 'relative', value: 1, unit: 3600, anchor: 'start' } );
		await loadScript();

		expect( validateTicket() ).toBe( false );
	} );

	it( 'should block the save of a specific end before a relative start', async () => {
		renderEventForm( undefined, { mode: 'specific', value: 1, unit: 3600, anchor: 'start', date: '6/1/2099', time: '10:00' } );
		await loadScript();

		expect( validateTicket() ).toBe( false );
	} );

	it( 'should keep the end marked invalid when common validation clears its error classes', async () => {
		renderEventForm( { value: 1, unit: 3600, anchor: 'start' }, { mode: 'relative', value: 2, unit: 3600, anchor: 'start' } );
		await loadScript();
		validateTicket();

		// Common's validation runs again after the save click and strips its class from every field.
		jQuery( '#tribe_panel_edit' ).find( 'input, select, textarea' ).removeClass( 'tribe-validation-error' );

		expect( isEndMarkedInvalid() ).toBe( true );
	} );

	it( 'should let a valid window be saved', async () => {
		renderEventForm();
		await loadScript();

		expect( validateTicket() ).toBe( true );
		expect( getWindowError() ).toBe( '' );
		expect( isEndMarkedInvalid() ).toBe( false );
	} );

	it( 'should keep the save blocked when a handler bound later lets it through', async () => {
		renderEventForm( { value: 1, unit: 3600, anchor: 'start' }, { mode: 'relative', value: 2, unit: 3600, anchor: 'start' } );
		await loadScript();
		// Event Tickets Plus returns the value it was passed when its own check passes.
		jQuery( '#tribetickets' ).on( 'additionalValidation.tribe', ( event, valid ) => valid );

		expect( validateTicket() ).toBe( false );
	} );

	it( 'should keep the save blocked by a handler bound earlier', async () => {
		renderEventForm();
		jQuery( '#tribetickets' ).on( 'additionalValidation.tribe', () => false );
		await loadScript();

		expect( validateTicket() ).toBe( false );
	} );

	it( 'should clear the error once the window changes', async () => {
		renderEventForm( { value: 1, unit: 3600, anchor: 'start' }, { mode: 'relative', value: 2, unit: 3600, anchor: 'start' } );
		await loadScript();
		validateTicket();

		jQuery( '#ticket_sales_end_value' ).val( '0' ).trigger( 'change' );

		expect( getWindowError() ).toBe( '' );
		expect( isEndMarkedInvalid() ).toBe( false );
	} );

	it( 'should show the sales window error the server rejected the save with on the end of the window', async () => {
		renderEventForm();
		const hooks = await loadScript();

		hooks.doAction( 'tec.tickets.admin.ticketSaveFailed', {
			success: false,
			data: { message: INVALID_WINDOW },
		} );

		expect( getWindowError() ).toBe( INVALID_WINDOW );
		expect( isEndMarkedInvalid() ).toBe( true );
	} );

	it( 'should show another error the server rejected the save with without marking the sales window', async () => {
		renderEventForm();
		const hooks = await loadScript();

		hooks.doAction( 'tec.tickets.admin.ticketSaveFailed', {
			success: false,
			data: { message: 'The ticket can&#039;t be saved.' },
		} );

		expect( getWindowError() ).toBe( "The ticket can't be saved." );
		expect( isEndMarkedInvalid() ).toBe( false );
	} );

	// The server's `Boundary` takes 1 to 60: these are just outside that range, and a cleared field.
	it.each( [
		[ 'start', '0' ],
		[ 'start', '61' ],
		[ 'start', '' ],
		[ 'end', '0' ],
		[ 'end', '61' ],
	] )( 'should block the save of a relative %s number out of the range the server takes: "%s"', async ( end, value ) => {
		renderEventForm();
		await loadScript();
		jQuery( `#ticket_sales_${ end }_value` ).val( value );

		expect( validateTicket() ).toBe( false );
		expect( getWindowError() ).toBe( INVALID_WINDOW );
	} );

	// The ends of the range the server takes, with an end an hour before the event and a start further back.
	it.each( [ [ '1', 86400 ], [ '60', 86400 ] ] )( 'should let the save of a relative start of %s go through', async ( value, unit ) => {
		renderEventForm( { value: Number( value ), unit, anchor: 'start' } );
		await loadScript();

		expect( validateTicket() ).toBe( true );
	} );

	it( 'should block a specific start whose date fields are disabled, as the server gets no date for it', async () => {
		renderEventForm();
		jQuery( '#ticket_sales_start_mode' ).html( '<option value="specific" selected>Specific</option>' );
		jQuery( '#tribe_panel_edit' ).append( '<input id="ticket_start_date" value="6/1/2099" disabled /><input id="ticket_start_time" value="10:00" disabled />' );
		await loadScript();

		expect( validateTicket() ).toBe( false );
	} );

	it( 'should judge a Now start without the date its hidden, disabled field holds', async () => {
		// The end falls on 2099-06-24 at 18:00, an hour before the event; the date left in the hidden field is later.
		renderEventForm();
		jQuery( '#ticket_sales_start_mode' ).html( '<option value="default" selected>Now</option>' );
		jQuery( '#tribe_panel_edit' ).append( '<input id="ticket_start_date" value="7/1/2099" disabled /><input id="ticket_start_time" value="10:00" disabled />' );
		await loadScript();

		expect( validateTicket() ).toBe( true );
		expect( getWindowError() ).toBe( '' );
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

	it( 'should write the sale price rule before the ticket is saved', async () => {
		renderMetabox();
		document.getElementById( 'tribe_panel_edit' ).insertAdjacentHTML(
			'beforeend',
			`<select id="ticket_sale_start_mode"><option value="now" selected>Now</option></select>
			<input type="number" id="ticket_sale_start_value" value="2" />
			<select id="ticket_sale_start_unit"><option value="${ UNIT_WEEKS }" selected>weeks</option></select>
			<select id="ticket_sale_end_mode"><option value="relative" selected>Relative</option></select>
			<input type="number" id="ticket_sale_end_value" value="3" />
			<select id="ticket_sale_end_unit"><option value="${ UNIT_DAYS }" selected>days</option></select>
			<input type="hidden" name="ticket_sale_price_relative" id="ticket_sale_price_relative" value="" />`
		);
		await loadScript();

		jQuery( '#tribetickets' ).trigger( 'pre-save-ticket.tribe' );

		expect( JSON.parse( document.getElementById( 'ticket_sale_price_relative' ).value ) ).toStrictEqual( {
			start: { mode: 'now' },
			end: { mode: 'relative', value: 3, unit: UNIT_DAYS },
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
