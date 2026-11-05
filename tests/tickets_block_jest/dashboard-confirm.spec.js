import React from 'react';
import { combineReducers, createStore } from 'redux';
import { addFilter, removeFilter } from '@wordpress/hooks';
import reducer from '@moderntribe/tickets/data/blocks/ticket/reducer';
import {
	registerTicketBlock,
	setTicketHasChanges,
	setTicketHasDurationError,
	setTicketTempCapacityType,
	setTicketTempPrice,
	setTicketTempTitle,
} from '@moderntribe/tickets/data/blocks/ticket/actions';

jest.mock( '@wordpress/data', () => ( {
	dispatch: () => ( {} ),
	select: () => ( {} ),
} ) );

// The container hands the button state to its template only through props, so the template keeps them.
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

const HOOK = 'tec.tickets.blocks.confirmButton.hasDurationError';
const NAMESPACE = 'tests/tickets-block/confirm';
const CLIENT_ID = 'ticket-block-confirm';

/**
 * Renders the dashboard of a ticket block whose only problem is a sales duration error, and reads whether its Create or
 * Update button is disabled.
 *
 * @return {boolean} Whether the button is disabled.
 */
function isConfirmDisabled() {
	mockStore = createStore( combineReducers( { tickets: combineReducers( { blocks: combineReducers( { ticket: reducer } ) } ) } ) );
	mockStore.dispatch( registerTicketBlock( CLIENT_ID ) );
	mockStore.dispatch( setTicketTempTitle( CLIENT_ID, 'General admission' ) );
	mockStore.dispatch( setTicketTempPrice( CLIENT_ID, '10' ) );
	mockStore.dispatch( setTicketTempCapacityType( CLIENT_ID, 'unlimited' ) );
	mockStore.dispatch( setTicketHasChanges( CLIENT_ID, true ) );
	mockStore.dispatch( setTicketHasDurationError( CLIENT_ID, true ) );

	const dashboard = global.renderer.create( <TicketDashboard clientId={ CLIENT_ID } /> );
	dashboard.unmount();

	return mockTemplateProps.isConfirmDisabled;
}

describe( 'the Ticket block Create or Update button', () => {
	afterEach( () => {
		removeFilter( HOOK, NAMESPACE );
	} );

	it( 'should be disabled by a sales duration error', () => {
		expect( isConfirmDisabled() ).toBe( true );
	} );

	it( 'should be enabled when a filter clears the sales duration error', () => {
		const calls = [];
		addFilter( HOOK, NAMESPACE, ( hasDurationError, state, ownProps ) => {
			calls.push( [ hasDurationError, state, ownProps.clientId ] );

			return false;
		} );

		expect( isConfirmDisabled() ).toBe( false );
		expect( calls ).toStrictEqual( [ [ true, mockStore.getState(), CLIENT_ID ] ] );
	} );
} );
