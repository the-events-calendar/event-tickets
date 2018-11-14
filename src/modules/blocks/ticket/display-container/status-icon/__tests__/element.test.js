/**
 * External dependencies
 */
import renderer from 'react-test-renderer';
import React from 'react';

/**
 * Internal dependencies
 */
import TicketIcon from './../element';

// Mock to overwrite the default SVG icons mock
jest.mock( '@moderntribe/tickets/icons', () => ( {
	ClockActive: () => <span>Clock Active</span>,
	ClockInactive: () => <span>Clock Inactive</span>,
	TicketActive: () => <span>Ticket Active</span>,
	TicketInactive: () => <span>Ticket Inactive</span>,
} ) );

describe( 'Ticket Icon', () => {
	it( 'render the ticket icon', () => {
		const tree = renderer.create( <TicketIcon /> );
		expect( tree.toJSON() ).toMatchSnapshot();
	} );

	it( 'render the disabled ticket icon', () => {
		const tree = renderer.create( <TicketIcon disabled={ true } /> );
		expect( tree.toJSON() ).toMatchSnapshot();
	} );

	it( 'render the unlimited icon', () => {
		const tree = renderer.create( <TicketIcon expires={ true } /> );
		expect( tree.toJSON() ).toMatchSnapshot();
	} );

	it( 'render the unlimited icon when is disabled', () => {
		const tree = renderer.create( <TicketIcon expires={ true } disabled={ true } /> );
		expect( tree.toJSON() ).toMatchSnapshot();
	} );
} );
