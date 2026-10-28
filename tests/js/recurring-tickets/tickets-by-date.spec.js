import React from 'react';
import renderer, { act } from 'react-test-renderer';
import TicketsByDate from '../../../src/Tickets/Recurring_Tickets/app/tickets-by-date/section';

// The shared stub has no _x() and no numbered placeholders; with no translations loaded, the real one returns English.
jest.mock( '@wordpress/i18n', () => jest.requireActual( '@wordpress/i18n' ) );

const ticket = ( overrides = {} ) => ( {
	id: 1000000001,
	name: 'General Admission',
	price: '$20.00',
	regular_price: null,
	capacity: 10,
	available: 7,
	sales_window: 'June 1, 2026 - September 16, 2026',
	fields: [],
	...overrides,
} );

const date = ( n, overrides = {} ) => ( {
	id: 10000000 + n,
	label: `Date ${ n }`,
	time: '5:00 pm',
	past: false,
	capacity: 10,
	available: 7,
	attendees_url: `https://example.com/attendees&tec_tickets_recurring_date=${ 10000000 + n }`,
	orders_url: `https://example.com/orders&tec_tickets_recurring_date=${ 10000000 + n }`,
	tickets: [ ticket() ],
	...overrides,
} );

const data = ( upcoming, past = 0 ) => {
	const dates = [
		...Array.from( { length: past }, ( _, i ) => date( i + 1, { past: true } ) ),
		...Array.from( { length: upcoming }, ( _, i ) => date( past + i + 1 ) ),
	];

	return { counts: { upcoming, all: upcoming + past }, dates };
};

const render = ( props ) => {
	let rendered;
	act( () => {
		rendered = renderer.create( <TicketsByDate unsaved={ false } { ...props } /> );
	} );

	return rendered;
};

const text = ( node ) =>
	node.children.map( ( child ) => ( 'string' === typeof child ? child : text( child ) ) ).join( '' );

const headers = ( rendered ) => rendered.root.findAll( ( n ) => 'button' === n.props.role ).map( text );
const openPanels = ( rendered ) => rendered.root.findAll( ( n ) => n.props.className === 'tec-tickets-by-date__table' );
const select = ( rendered ) => rendered.root.findByType( 'select' );
const pageButtons = ( rendered ) =>
	rendered.root.findAll( ( n ) => 'button' === n.type && /tec-tickets-by-date__page( |$)/.test( n.props.className ?? '' ) );

describe( 'Tickets by Date', () => {
	test( 'names the section and counts the upcoming and all dates', () => {
		const rendered = render( { data: data( 2, 1 ) } );
		const all = text( rendered.root );

		expect( all ).toContain( 'Tickets by Date' );
		expect( all ).toContain( 'Expand a date to see its ticket details.' );
		expect( select( rendered ).findAllByType( 'option' ).map( text ) ).toEqual( [ 'Upcoming dates (2)', 'All dates (3)' ] );
	} );

	test( 'lists the upcoming dates first, with only the first one open', () => {
		const rendered = render( { data: data( 2, 1 ) } );

		expect( headers( rendered ).map( ( h ) => h.slice( 0, 6 ) ) ).toEqual( [ 'Date 2', 'Date 3' ] );
		expect( openPanels( rendered ) ).toHaveLength( 1 );
	} );

	test( 'opens several dates at once', () => {
		const rendered = render( { data: data( 3 ) } );
		const [ , second ] = rendered.root.findAll( ( n ) => 'button' === n.props.role );

		act( () => second.props.onClick() );

		expect( openPanels( rendered ) ).toHaveLength( 2 );
	} );

	test( 'lists every date, oldest first and past ones marked, with all dates chosen', () => {
		const rendered = render( { data: data( 2, 1 ) } );

		act( () => select( rendered ).props.onChange( { target: { value: 'all' } } ) );

		const listed = headers( rendered );
		expect( listed ).toHaveLength( 3 );
		expect( listed[ 0 ] ).toContain( 'Past' );
		expect( listed[ 1 ] ).not.toContain( 'Past' );
		expect( openPanels( rendered ) ).toHaveLength( 0 );
	} );

	test( 'pages 10 dates at a time', () => {
		const rendered = render( { data: data( 25 ) } );

		expect( headers( rendered ) ).toHaveLength( 10 );
		expect( pageButtons( rendered ).map( text ) ).toEqual( [ '1', '2', '3' ] );
		expect( rendered.root.findByProps( { 'aria-label': 'Previous page' } ).props.disabled ).toBe( true );

		act( () => rendered.root.findByProps( { 'aria-label': 'Next page' } ).props.onClick() );

		expect( headers( rendered )[ 0 ] ).toContain( 'Date 11' );
		expect( openPanels( rendered ) ).toHaveLength( 0 );

		act( () => pageButtons( rendered )[ 2 ].props.onClick() );

		expect( headers( rendered ) ).toHaveLength( 5 );
		expect( rendered.root.findByProps( { 'aria-label': 'Next page' } ).props.disabled ).toBe( true );
	} );

	test( 'has no pages for 10 dates or fewer', () => {
		expect( pageButtons( render( { data: data( 10 ) } ) ) ).toHaveLength( 0 );
	} );

	test( 'shows a date\'s availability, its tickets and links to its attendees and orders', () => {
		const rendered = render( { data: data( 1 ) } );
		const all = text( rendered.root );
		const links = rendered.root.findAllByType( 'a' );

		expect( headers( rendered )[ 0 ] ).toContain( 'Available 7 / 10' );
		expect( all ).toContain( 'General Admission' );
		expect( all ).toContain( 'June 1, 2026 - September 16, 2026' );
		expect( all ).toContain( 'Total Ticket Capacity: 10' );
		expect( links.map( text ) ).toEqual( [ 'View Attendees', 'View Orders' ] );
		expect( links[ 0 ].props.href ).toContain( 'tec_tickets_recurring_date=10000001' );
		expect( rendered.root.findByProps( { title: 'Total does not include Series Passes' } ) ).toBeTruthy();
	} );

	test( 'strikes through the regular price on sale, names attendee fields and says unlimited', () => {
		const sale = data( 1 );
		sale.dates[ 0 ].tickets = [ ticket( { price: '$24.00', regular_price: '$30.00', fields: [ 'Name', 'Email' ], capacity: -1, available: -1 } ) ];
		sale.dates[ 0 ].capacity = -1;
		sale.dates[ 0 ].available = -1;

		const rendered = render( { data: sale } );
		const all = text( rendered.root );

		expect( rendered.root.findByProps( { className: 'tec-tickets-by-date__regular-price' } ).children ).toEqual( [ '$30.00' ] );
		expect( all ).toContain( 'Name, Email' );
		expect( all ).toContain( 'Unlimited' );
	} );

	test( 'says when tickets or dates have unsaved changes', () => {
		const message = 'You have unsaved changes to tickets or dates. Save the event to update this list.';

		expect( text( render( { data: data( 1 ) } ).root ) ).not.toContain( message );
		expect( text( render( { data: data( 1 ), unsaved: true } ).root ) ).toContain( message );
	} );
} );
