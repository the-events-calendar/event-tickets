import React from 'react';
import renderer, { act } from 'react-test-renderer';
import { applyFilters } from '@wordpress/hooks';
import { TicketsByDateAction, hasUnsavedDates } from '../../../src/Tickets/Recurring_Tickets/app/block-editor/tickets-by-date';

// Not installed: WordPress provides them in the editor.
jest.mock(
	'@wordpress/components',
	() => ( {
		Button: ( { children, ...props } ) => <button { ...props }>{ children }</button>,
		Modal: ( { title, children, onRequestClose } ) => (
			<div role="dialog" aria-label={ title } data-close={ onRequestClose }>
				{ children }
			</div>
		),
	} ),
	{ virtual: true }
);
jest.mock( '@wordpress/data', () => ( { useSelect: ( callback ) => callback( global.select ) } ), { virtual: true } );

// The shared stub has no _x() and no numbered placeholders; with no translations loaded, the real one returns English.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );

const data = {
	counts: { upcoming: 1, all: 1 },
	dates: [
		{
			id: 10000001,
			label: 'October 29, 2026',
			time: '7:00 pm',
			past: false,
			capacity: 100,
			available: 95,
			attendees_url: 'https://example.com/attendees',
			orders_url: 'https://example.com/orders',
			tickets: [],
		},
	],
};

const editor = ( { post = {}, editedMeta = post.meta, ticketBlocks = 0 } = {} ) => {
	global.select = ( store ) =>
		'core/block-editor' === store
			? { getGlobalBlockCount: () => ticketBlocks }
			: { getCurrentPost: () => post, getEditedPostAttribute: () => editedMeta };
};

const render = () => {
	let rendered;
	act( () => {
		rendered = renderer.create( <TicketsByDateAction /> );
	} );

	return rendered;
};

const text = ( node ) =>
	node.children.map( ( child ) => ( 'string' === typeof child ? child : text( child ) ) ).join( '' );

describe( 'Tickets by Date in the block editor', () => {
	afterEach( () => {
		delete global.select;
	} );

	test( 'offers nothing for an event without Tickets by Date', () => {
		editor( { post: { tec_tickets_by_date: null } } );

		expect( render().toJSON() ).toBeNull();
	} );

	test( 'opens the section in a modal', () => {
		editor( { post: { tec_tickets_by_date: data } } );
		const rendered = render();

		expect( rendered.root.findAllByProps( { role: 'dialog' } ) ).toHaveLength( 0 );

		act( () => rendered.root.findByType( 'button' ).props.onClick() );

		const dialog = rendered.root.findByProps( { role: 'dialog' } );
		expect( dialog.props[ 'aria-label' ] ).toBe( 'Tickets by Date' );
		expect( text( dialog ) ).toContain( 'October 29, 2026' );
		expect( text( dialog ) ).not.toContain( 'unsaved changes' );
	} );

	test( 'says when a ticket was saved since the event was saved', () => {
		editor( { post: { tec_tickets_by_date: data } } );
		const rendered = render();
		act( () => rendered.root.findByType( 'button' ).props.onClick() );

		act( () => {
			applyFilters( 'tec.tickets.blocks.setBodyDetails', new FormData(), 'ticket' );
		} );

		expect( text( rendered.root.findByProps( { role: 'dialog' } ) ) ).toContain( 'You have unsaved changes to tickets or dates.' );
	} );

	test( 'says when a ticket was added or removed since the event was saved', () => {
		editor( { post: { tec_tickets_by_date: data }, ticketBlocks: 1 } );
		const rendered = render();
		act( () => rendered.root.findByType( 'button' ).props.onClick() );

		expect( text( rendered.root.findByProps( { role: 'dialog' } ) ) ).toContain( 'You have unsaved changes to tickets or dates.' );
	} );

	test( 'says when dates or recurrence rules are edited, not other fields', () => {
		const saved = { _EventStartDate: '2026-10-29 19:00:00', _tribe_blocks_recurrence_rules: '[]', _EventURL: '' };

		expect( hasUnsavedDates( saved, { ...saved, _tribe_blocks_recurrence_rules: '[{"type":"Weekly"}]' } ) ).toBe( true );
		expect( hasUnsavedDates( saved, { ...saved, _EventStartDate: '2026-10-29 20:00:00' } ) ).toBe( true );
		expect( hasUnsavedDates( saved, { ...saved, _EventURL: 'https://example.com' } ) ).toBe( false );
		// As TEC's event blocks write every key to the edits on load, unchanged.
		expect( hasUnsavedDates( saved, { ...saved } ) ).toBe( false );
		expect( hasUnsavedDates( saved, {} ) ).toBe( false );
	} );

	test( 'says when the dates are edited, in the modal', () => {
		const meta = { _EventStartDate: '2026-10-29 19:00:00' };
		editor( { post: { tec_tickets_by_date: data, meta }, editedMeta: { _EventStartDate: '2026-10-29 20:00:00' } } );
		const rendered = render();
		act( () => rendered.root.findByType( 'button' ).props.onClick() );

		expect( text( rendered.root.findByProps( { role: 'dialog' } ) ) ).toContain( 'You have unsaved changes to tickets or dates.' );
	} );

	test( 'adds its button to the Tickets block dashboard', () => {
		const actions = applyFilters( 'tec.tickets.blocks.Tickets.TicketsDashboardAction.actions', [ 'settings' ], {} );

		expect( actions ).toHaveLength( 2 );
		expect( actions[ 1 ].type ).toBe( TicketsByDateAction );
	} );
} );
