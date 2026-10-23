import React from 'react';
import moment from 'moment';
import { combineReducers, createStore } from 'redux';
import { addFilter, removeFilter } from '@wordpress/hooks';
import reducer from '@moderntribe/tickets/data/blocks/ticket/reducer';
import {
	registerTicketBlock,
	setTicketTempEndDateMoment,
	setTicketTempStartDateMoment,
} from '@moderntribe/tickets/data/blocks/ticket/actions';

/*
 * The container passes the sale dates to its template as props, so the template is replaced with one that keeps
 * them.
 */
const mockTemplateProps = {};
jest.mock(
	'../../src/Tickets/Blocks/Ticket/app/editor/container-header/title/attendee-registration-icons/template',
	() => ( props ) => {
		Object.assign( mockTemplateProps, props );

		return null;
	}
);

let mockStore;
jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => ( props ) => <Component { ...props } store={ mockStore } />,
} ) );

// The container reads the selected block and the site date format from globals the block editor sets.
global.wp.data.select = () => ( { getSelectedBlock: () => null } );
window.tribe_editor_config = { common: { dateSettings: { formats: { date: 'F j, Y' } } } };

const SaleWindowIcons =
	require( '../../src/Tickets/Blocks/Ticket/app/editor/container-header/title/attendee-registration-icons/container' ).default;

const HOOK = 'tec.tickets.blocks.Ticket.SaleWindow.dates';
const NAMESPACE = 'tests/relative-sale-dates/sale-window';
const CLIENT_ID = 'ticket-block-sale-window';

/**
 * Renders the attendee registration and sale window icons of a ticket header.
 *
 * @param {Object[]} [ticketActions] The actions to dispatch on the ticket block before it renders.
 *
 * @return {void}
 */
function renderIcons( ticketActions = [] ) {
	mockStore = createStore( combineReducers( { tickets: combineReducers( { blocks: combineReducers( { ticket: reducer } ) } ) } ) );
	mockStore.dispatch( registerTicketBlock( CLIENT_ID ) );
	ticketActions.forEach( ( action ) => mockStore.dispatch( action ) );

	const icons = global.renderer.create( <SaleWindowIcons clientId={ CLIENT_ID } /> );
	icons.unmount();
}

describe( 'the ticket header sale window', () => {
	afterEach( () => {
		removeFilter( HOOK, NAMESPACE );
	} );

	it( 'should pass tec.tickets.blocks.Ticket.SaleWindow.dates the sale dates and the client ID of the ticket block', () => {
		const filter = jest.fn( ( dates ) => dates );
		addFilter( HOOK, NAMESPACE, filter );

		renderIcons();

		const [ dates, clientId ] = filter.mock.calls[ 0 ];
		expect( dates ).toStrictEqual( { fromDate: mockTemplateProps.fromDate, toDate: mockTemplateProps.toDate } );
		expect( clientId ).toBe( CLIENT_ID );
	} );

	it( 'should show the date and time each end of the ticket\'s sales window falls on', () => {
		renderIcons( [
			setTicketTempStartDateMoment( CLIENT_ID, moment( '2040-10-06 19:00:00' ) ),
			setTicketTempEndDateMoment( CLIENT_ID, moment( '2040-10-20 18:00:00' ) ),
		] );

		expect( mockTemplateProps.fromDate ).toBe( 'October 6, 2040 7:00 pm' );
		expect( mockTemplateProps.toDate ).toBe( 'October 20, 2040 6:00 pm' );
	} );

	it( 'should show the sale dates tec.tickets.blocks.Ticket.SaleWindow.dates returns', () => {
		addFilter( HOOK, NAMESPACE, () => ( { fromDate: 'October 6, 2040', toDate: 'October 20, 2040' } ) );

		renderIcons();

		expect( mockTemplateProps.fromDate ).toBe( 'October 6, 2040' );
		expect( mockTemplateProps.toDate ).toBe( 'October 20, 2040' );
	} );
} );
