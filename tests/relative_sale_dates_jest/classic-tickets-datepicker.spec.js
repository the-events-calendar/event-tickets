/**
 * jQuery UI fires `change` after a calendar pick only when no `onSelect` option is set, and `tickets.js` sets one.
 */

const $ = require( 'jquery' );

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
		'should tell the Relative Sale Dates script, and only it, that a date was picked on %s',
		( id ) => {
			const [ options ] = setUpPanel();
			const input = document.getElementById( id );
			const onRelativeSaleDatesChange = jest.fn();
			const onChange = jest.fn();
			$( input ).on( 'change.tecRelativeSaleDates', onRelativeSaleDatesChange );
			$( input ).on( 'change', onChange );

			options.onSelect.call( input, '6/1/2099', { id } );

			expect( onRelativeSaleDatesChange ).toHaveBeenCalledTimes( 1 );
			// Other scripts listening for `change` see no new event, with or without the feature.
			expect( onChange ).not.toHaveBeenCalled();
		}
	);
} );
