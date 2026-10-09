/**
 * The classic panel script asks `tec.tickets.admin.ticket.intercepted` before it saves, deletes or
 * duplicates a ticket over AJAX: nobody answering keeps today's request, an answer of `true` skips it.
 */
import $ from 'jquery';
import lodash from 'lodash';

let hooks = null;

const load = () => {
	document.body.innerHTML = `
		<input id="post_ID" value="5">
		<div id="tribetickets">
			<div id="tribe_panel_base">
				<button type="button" class="ticket_delete" attr-ticket-id="12">Delete</button>
				<button type="button" class="ticket_duplicate" data-ticket-id="12">Duplicate</button>
			</div>
			<div id="tribe_panel_edit">
				<div id="ticket_form_table"><input id="ticket_id" name="ticket_id" value=""></div>
				<button type="button" name="ticket_form_save">Save</button>
			</div>
		</div>`;
	window.jQuery = $;
	window._ = lodash;
	window.TribeTickets = { ajaxurl: '/wp-admin/admin-ajax.php' };
	window.ajaxurl = window.TribeTickets.ajaxurl;
	window.tribe_ticket_notices = { confirm_alert: 'Delete?' };
	window.tribe = { tickets: {}, validation: { hasErrors: () => false } };
	window.confirm = jest.fn( () => true );
	$.post = jest.fn();
	jest.isolateModules( () => {
		hooks = require( '@wordpress/hooks' );
		require( '../tickets' );
	} );
};

const actions = {
	save: '[name="ticket_form_save"]',
	delete: '.ticket_delete',
	duplicate: '.ticket_duplicate',
};

describe( 'tec.tickets.admin.ticket.intercepted', () => {
	afterEach( () => {
		$( document ).off();
	} );

	it.each( Object.entries( actions ) )( 'sends the %s request when nothing intercepts it', ( action, selector ) => {
		load();

		$( selector ).trigger( 'click' );

		expect( $.post ).toHaveBeenCalledTimes( 1 );
	} );

	it.each( Object.entries( actions ) )( 'sends no %s request when a callback intercepts it', ( action, selector ) => {
		load();
		const seen = [];
		hooks.addFilter( 'tec.tickets.admin.ticket.intercepted', 'test', ( intercepted, asked, context ) => {
			seen.push( [ asked, context.ticketId ] );

			return asked === action ? true : intercepted;
		} );

		$( selector ).trigger( 'click' );

		expect( $.post ).not.toHaveBeenCalled();
		expect( seen[ 0 ][ 0 ] ).toBe( action );
		if ( 'save' !== action ) {
			expect( String( seen[ 0 ][ 1 ] ) ).toBe( '12' );
		}
	} );

	it( 'asks about a staged delete, which can be undone, on a deferred post', () => {
		load();
		window.tribe.tickets.deferredSave = { deleteConfirm: 'Delete this ticket when the post is saved?' };

		$( '.ticket_delete' ).trigger( 'click' );

		expect( window.confirm ).toHaveBeenCalledWith( 'Delete this ticket when the post is saved?' );
	} );

	it( 'asks the usual question when the post does not stage ticket changes', () => {
		load();

		$( '.ticket_delete' ).trigger( 'click' );

		expect( window.confirm ).toHaveBeenCalledWith( 'Delete?' );
	} );
} );
