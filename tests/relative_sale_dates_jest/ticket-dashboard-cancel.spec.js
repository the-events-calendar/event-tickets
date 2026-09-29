import React from 'react';
import { combineReducers, createStore } from 'redux';
import { addAction, removeAction } from '@wordpress/hooks';
import reducer from '@moderntribe/tickets/data/blocks/ticket/reducer';
import { registerTicketBlock, setTicketHasBeenCreated } from '@moderntribe/tickets/data/blocks/ticket/actions';

jest.mock( '@wordpress/data', () => ( {
	dispatch: () => ( { clearSelectedBlock: () => {}, removeBlocks: () => {} } ),
	select: () => ( {} ),
} ) );

/*
 * The container only reaches the Cancel handler through the props it hands its template, so the template is replaced
 * with one that keeps them.
 */
const mockTemplateProps = {};
jest.mock( '../../src/Tickets/Blocks/Ticket/app/editor/dashboard/template', () => ( props ) => {
	Object.assign( mockTemplateProps, props );

	return null;
} );

let mockStore;
jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => ( props ) => <Component { ...props } store={ mockStore } />,
} ) );

const TicketDashboard = require( '../../src/Tickets/Blocks/Ticket/app/editor/dashboard/container' ).default;

const HOOK = 'tec.tickets.blocks.ticketCancelled';
const NAMESPACE = 'tests/relative-sale-dates/cancel';
const CLIENT_ID = 'ticket-block-cancel';

/**
 * Renders the dashboard of a ticket block and clicks its Cancel button.
 *
 * @param {boolean} hasBeenCreated Whether the ticket has been saved already.
 *
 * @return {void}
 */
function clickCancel( hasBeenCreated ) {
	mockStore = createStore( combineReducers( { tickets: combineReducers( { blocks: combineReducers( { ticket: reducer } ) } ) } ) );
	mockStore.dispatch( registerTicketBlock( CLIENT_ID ) );
	mockStore.dispatch( setTicketHasBeenCreated( CLIENT_ID, hasBeenCreated ) );

	const dashboard = global.renderer.create( <TicketDashboard clientId={ CLIENT_ID } /> );
	mockTemplateProps.onCancelClick();
	dashboard.unmount();
}

describe( 'the Ticket block Cancel button', () => {
	let cancelled;

	beforeEach( () => {
		cancelled = [];
		addAction( HOOK, NAMESPACE, ( clientId ) => cancelled.push( clientId ) );
	} );

	afterEach( () => {
		removeAction( HOOK, NAMESPACE );
	} );

	it( 'should fire tec.tickets.blocks.ticketCancelled for a saved ticket', () => {
		clickCancel( true );

		expect( cancelled ).toStrictEqual( [ CLIENT_ID ] );
	} );

	it( 'should not fire tec.tickets.blocks.ticketCancelled for a ticket that was never saved', () => {
		clickCancel( false );

		expect( cancelled ).toStrictEqual( [] );
	} );
} );
