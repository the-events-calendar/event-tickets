/**
 * jQuery UI fires `change` after a calendar pick only when no `onSelect` option is set, and `tickets.js` sets one.
 */

const $ = require( 'jquery' );

const mockDoAction = jest.fn();

// `tickets.js` is loaded in an isolated module registry, so the spec reads its actions through a shared mock.
jest.mock( '@wordpress/hooks', () => ( {
	...jest.requireActual( '@wordpress/hooks' ),
	doAction: ( ...args ) => mockDoAction( ...args ),
} ) );

/**
 * Sets the ticket panel up the way `tickets.js` does, with a datepicker that records the options it is given.
 *
 * @return {Object[]} The options each date input's datepicker was set up with.
 */
function setUpPanel() {
	document.body.innerHTML = `
		<div id="tribetickets">
			<input id="ticket_start_date" />
			<input id="ticket_end_date" />
			<input id="ticket_sale_start_date" />
			<input id="ticket_sale_end_date" />
		</div>`;

	const options = [];
	// Common's validation and dependency scripts, which the panel set-up calls once the pickers are ready.
	global.tribe = { validation: { selectors: { item: '.tribe-validation' } } };
	$.fn.validation = function () {
		return this;
	};
	$.fn.dependency = function () {
		return this;
	};
	global.TribeTickets = { ajaxurl: '' };
	global.ajaxurl = '';
	global._ = require( 'lodash' );
	global.tribe_l10n_datatables = { datepicker: {} };
	global.tribe_timepickers = { setup_timepickers: () => {} };
	global.MTAccordion = () => {};
	// jQuery UI is not loaded here: a stand-in keeps the options the panel passes to it.
	$.fn.datepicker = function ( opts ) {
		if ( 'object' === typeof opts ) {
			options.push( opts );
		}

		return this;
	};
	$.datepicker = { parseDate: () => new Date( 2099, 5, 1 ), _clearDate: () => {} };

	jest.isolateModules( () => {
		require( '../../src/resources/js/tickets' );
	} );
	window.tribe.tickets.editor.setupPanels();

	return options;
}

describe( 'tickets.js date pickers', () => {
	it.each( [ [ 'ticket_start_date' ], [ 'ticket_end_date' ], [ 'ticket_sale_start_date' ], [ 'ticket_sale_end_date' ] ] )(
		'should tell other scripts through an action, and not a change event, that a date was picked on %s',
		( id ) => {
			const [ options ] = setUpPanel();
			const input = document.getElementById( id );
			const onChange = jest.fn();
			$( input ).on( 'change', onChange );
			mockDoAction.mockClear();

			options.onSelect.call( input, '6/1/2099', { id } );

			expect( mockDoAction ).toHaveBeenCalledWith( 'tec.tickets.admin.ticketDatePicked', input );
			// Scripts listening for `change` see no new event.
			expect( onChange ).not.toHaveBeenCalled();
		}
	);
} );

describe( 'tickets.js ticket save', () => {
	/**
	 * Sets the ticket panel up with a save button, and answers the save request with the given response.
	 *
	 * @param {Object} response The response of the save request.
	 */
	function setUpSave( response ) {
		setUpPanel();
		document.body.insertAdjacentHTML(
			'beforeend',
			`<input id="post_ID" value="1" />
			<div id="ticket_form_table"><button name="ticket_form_save">Save ticket</button></div>`
		);
		global.tribe.validation.hasErrors = () => false;
		global.TribeTickets.add_ticket_nonce = 'nonce';
		$.post = ( url, params, callback ) => callback( response );
		window.tribe.tickets.editor.refreshPanels = () => {};
	}

	beforeEach( () => {
		// Each load of `tickets.js` binds its save handler on the document again.
		$( document ).off( '.tribe' );
		mockDoAction.mockClear();
	} );

	it( 'should tell other scripts the server rejected the save', () => {
		const response = { success: false, data: { message: 'Ticket sales cannot end before they start.' } };
		setUpSave( response );

		$( '[name="ticket_form_save"]' ).trigger( 'click' );

		expect( mockDoAction ).toHaveBeenCalledWith( 'tec.tickets.admin.ticketSaveFailed', response );
	} );

	it( 'should not tell other scripts about a save the server accepted', () => {
		setUpSave( { success: true, data: {} } );

		$( '[name="ticket_form_save"]' ).trigger( 'click' );

		expect( mockDoAction ).not.toHaveBeenCalledWith( 'tec.tickets.admin.ticketSaveFailed', expect.anything() );
	} );
} );
