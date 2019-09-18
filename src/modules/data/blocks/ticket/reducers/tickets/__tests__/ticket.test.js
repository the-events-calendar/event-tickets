/**
 * Internal dependencies
 */
import * as actions from '@moderntribe/tickets/data/blocks/ticket/actions';
import reducer, { DEFAULT_STATE } from '../ticket';

jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'July 19, 2018 7:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Details reducer', () => {
	it( 'should set the default state', () => {
		expect( reducer( undefined, {} ) ).toEqual( DEFAULT_STATE );
	} );

	it( 'should set the sold', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketSold( 'block-id', 20 ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the available', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketAvailable( 'block-id', 20 ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the ticket id', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketId( 'block-id', 1094 ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the currency symbol', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketCurrencySymbol( 'block-id', '$' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the currency position', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketCurrencyPosition( 'block-id', 'postfix' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the provider', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketProvider( 'block-id', 'provider' ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the has attendee info fields', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketHasAttendeeInfoFields( 'block-id', true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the is loading', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketIsLoading( 'block-id', true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the is modal open', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketIsModalOpen( 'block-id', true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the has been created', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketHasBeenCreated( 'block-id', true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the has changes', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketHasChanges( 'block-id', true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the has duration error', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketHasDurationError( 'block-id', true ),
		) ).toMatchSnapshot();
	} );

	it( 'should set the is selected', () => {
		expect( reducer(
			DEFAULT_STATE,
			actions.setTicketIsSelected( 'block-id', true ),
		) ).toMatchSnapshot();
	} );
} );
