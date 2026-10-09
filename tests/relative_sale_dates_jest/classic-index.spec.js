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
 * @return {Promise<void>} Resolves once the script is set up.
 */
async function loadScript() {
	jest.isolateModules( () => {
		require( '@tec/tickets/relative-sale-dates/classic/index' );
	} );

	await new Promise( ( resolve ) => jQuery( resolve ) );
}

describe( 'classic editor script', () => {
	afterEach( () => {
		jQuery( document ).off( '.tribe' );
		document.body.innerHTML = '';
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
