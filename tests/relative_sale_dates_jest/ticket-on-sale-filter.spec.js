import React from 'react';
import { combineReducers, createStore } from 'redux';
import { addFilter, removeFilter } from '@wordpress/hooks';
import reducer from '@moderntribe/tickets/data/blocks/ticket/reducer';
import { registerTicketBlock, setTicketOnSale } from '@moderntribe/tickets/data/blocks/ticket/actions';

/*
 * Each container passes whether the ticket is on sale to its template as a prop, so the templates are replaced with
 * ones that keep their props.
 */
const mockPriceProps = {};
const mockContainerProps = {};
jest.mock( '../../src/Tickets/Blocks/Ticket/app/editor/container-header/price/template', () => ( props ) => {
	Object.assign( mockPriceProps, props );

	return null;
} );
jest.mock( '../../src/Tickets/Blocks/Ticket/app/editor/container/template', () => ( props ) => {
	Object.assign( mockContainerProps, props );

	return null;
} );

let mockStore;
jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => ( props ) => <Component { ...props } store={ mockStore } />,
} ) );

const Price = require( '../../src/Tickets/Blocks/Ticket/app/editor/container-header/price/container' ).default;
const Container = require( '../../src/Tickets/Blocks/Ticket/app/editor/container/container' ).default;

const HOOK = 'tec.tickets.blocks.Ticket.onSale';
const NAMESPACE = 'tests/relative-sale-dates/on-sale';
const CLIENT_ID = 'ticket-block-on-sale';

/**
 * Renders a container for a ticket the server last said is on sale.
 *
 * @param {Function} Component The container.
 *
 * @return {void}
 */
function render( Component ) {
	mockStore = createStore(
		combineReducers( { tickets: combineReducers( { blocks: combineReducers( { ticket: reducer } ) } ) } )
	);
	mockStore.dispatch( registerTicketBlock( CLIENT_ID ) );
	mockStore.dispatch( setTicketOnSale( CLIENT_ID, true ) );

	const rendered = global.renderer.create( <Component clientId={ CLIENT_ID } /> );
	rendered.unmount();
}

describe.each( [
	[ 'the ticket header price', Price, mockPriceProps, 'onSale' ],
	[ 'the ticket container', Container, mockContainerProps, 'isOnSale' ],
] )( '%s', ( name, Component, props, prop ) => {
	afterEach( () => {
		removeFilter( HOOK, NAMESPACE );
	} );

	it( 'should pass tec.tickets.blocks.Ticket.onSale the on sale state the server gave and the client ID', () => {
		const filter = jest.fn( ( onSale ) => onSale );
		addFilter( HOOK, NAMESPACE, filter );

		render( Component );

		expect( filter ).toHaveBeenCalledWith( true, CLIENT_ID );
	} );

	it( 'should show the on sale state tec.tickets.blocks.Ticket.onSale returns', () => {
		addFilter( HOOK, NAMESPACE, () => false );

		render( Component );

		expect( props[ prop ] ).toBe( false );
	} );
} );
