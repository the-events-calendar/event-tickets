/**
 * The Ticket block's dashboard and badges on a post that defers ticket saves.
 *
 * The block's own `__tests__` under `src/Tickets/Blocks` are outside Jest's `testMatch`, so these run from here.
 */
import React from 'react';

import reducer, { DEFAULT_STATE } from '../reducer';
import * as actions from '../actions';
import * as deferred from '../deferred';
import { getIsConfirmDisabled, onCancelClick } from '../../../../../Tickets/Blocks/Ticket/app/editor/dashboard/container';
import MoveDelete from '../../../../../Tickets/Blocks/Ticket/app/editor/dashboard/move-delete/template';
import Ticket from '../../../../../Tickets/Blocks/Ticket/app/editor/template';

// The labels come from the editor config the page prints.
jest.mock( '../constants', () => ( {
	...jest.requireActual( '../constants' ),
	TICKET_LABELS: { ticket: { singular: 'Ticket', plural: 'Tickets' } },
} ) );

// The block's own containers need the editor's stores; the badge does not.
jest.mock( '../../../../../Tickets/Blocks/Ticket/app/editor/container/container', () => () => null );
jest.mock( '../../../../../Tickets/Blocks/Ticket/app/editor/dashboard/container', () => {
	const actual = jest.requireActual( '../../../../../Tickets/Blocks/Ticket/app/editor/dashboard/container' );
	const Dashboard = () => null;
	Dashboard.getIsConfirmDisabled = actual.getIsConfirmDisabled;

	return { __esModule: true, ...actual, default: Dashboard };
} );
jest.mock( '../../../../../modules/elements/move-modal/container', () => () => null );

const mockEditor = { saving: false, clearSelectedBlock: jest.fn(), removeBlocks: jest.fn() };

jest.mock( '@wordpress/data', () => ( {
	select: () => ( { isSavingPost: () => mockEditor.saving, isAutosavingPost: () => false } ),
	dispatch: () => ( { clearSelectedBlock: mockEditor.clearSelectedBlock, removeBlocks: mockEditor.removeBlocks } ),
} ) );

const stateWith = ( fields = {} ) => {
	let block = DEFAULT_STATE;
	const apply = ( action ) => ( block = reducer( block, action ) );

	apply( actions.registerTicketBlock( 'a' ) );
	apply( actions.setTicketTempTitle( 'a', 'General' ) );
	apply( actions.setTicketTempPrice( 'a', '10' ) );
	apply( actions.setTicketTempCapacityType( 'a', 'unlimited' ) );
	apply( actions.setTicketHasChanges( 'a', true ) );
	apply( actions.setTicketIsStaged( 'a', !! fields.isStaged ) );
	apply( actions.setTicketHasBeenCreated( 'a', !! fields.hasBeenCreated ) );
	apply( actions.setTempSalePriceChecked( 'a', !! fields.salePriceChecked ) );
	apply( actions.setTempSalePrice( 'a', fields.salePrice || '' ) );

	// The currency format the block reads from the provider, which the sale price rule needs.
	const ticket = { ...block.tickets.byClientId.a, currencyDecimalPoint: '.', currencyNumberOfDecimals: 2, currencyThousandsSep: ',' };

	return { tickets: { blocks: { ticket: { ...block, tickets: { ...block.tickets, byClientId: { a: ticket } } } } } };
};

const ownProps = { clientId: 'a' };

describe( 'the deferred Ticket block dashboard', () => {
	beforeEach( () => {
		jest.spyOn( deferred, 'usesDeferredSave' ).mockReturnValue( true );
		mockEditor.saving = false;
		mockEditor.clearSelectedBlock.mockClear();
		mockEditor.removeBlocks.mockClear();
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'lets a valid change be confirmed while a post save is in flight, which keeps track of what it sent', () => {
		mockEditor.saving = true;

		expect( getIsConfirmDisabled( stateWith(), ownProps ) ).toBe( false );
	} );

	it( 'refuses a sale price above the price only when the sale price is on', () => {
		expect( getIsConfirmDisabled( stateWith( { salePriceChecked: true, salePrice: '20' } ), ownProps ) ).toBe( true );
		// Unticked, the sale price is hidden and not saved: it must not block Confirm.
		expect( getIsConfirmDisabled( stateWith( { salePriceChecked: false, salePrice: '20' } ), ownProps ) ).toBe( false );
	} );

	it( 'keeps a staged ticket on Cancel and drops only the unconfirmed changes', () => {
		const dispatched = [];

		onCancelClick( stateWith( { isStaged: true } ), ( action ) => dispatched.push( action ), ownProps )();

		expect( dispatched ).toContainEqual( actions.setTicketHasChanges( 'a', false ) );
		expect( dispatched ).not.toContainEqual( actions.removeTicketBlock( 'a' ) );
		expect( mockEditor.removeBlocks ).not.toHaveBeenCalled();
	} );

	it( 'removes a ticket that was never staged or saved on Cancel', () => {
		const dispatched = [];

		onCancelClick( stateWith(), ( action ) => dispatched.push( action ), ownProps )();

		expect( dispatched ).toContainEqual( actions.removeTicketBlock( 'a' ) );
		expect( mockEditor.removeBlocks ).toHaveBeenCalledWith( 'a' );
	} );

	it( 'says why a ticket with a staged edit cannot be moved', () => {
		const props = { clientId: 'a', ticketIsSelected: true, moveTicket: jest.fn(), removeTicket: jest.fn(), isDisabled: false };

		const blocked = JSON.stringify( renderer.create( <MoveDelete { ...props } isMoveBlocked /> ).toJSON() );
		const open = JSON.stringify( renderer.create( <MoveDelete { ...props } isMoveBlocked={ false } /> ).toJSON() );

		expect( blocked ).toContain( 'tribe-editor__ticket__move-blocked' );
		expect( open ).not.toContain( 'tribe-editor__ticket__move-blocked' );
	} );

	it( 'marks a staged ticket as not saved yet and shows the error of a ticket the save refused', () => {
		const render = ( props ) =>
			JSON.stringify( renderer.create( <Ticket clientId="a" isSelected={ false } showTicket onBlockUpdate={ () => {} } { ...props } /> ).toJSON() );

		const staged = render( { isStaged: true, saveError: '' } );
		const refused = render( { isStaged: true, saveError: 'Not allowed' } );
		const saved = render( { isStaged: false, saveError: '' } );

		expect( staged ).toContain( 'Not saved yet' );
		expect( staged ).not.toContain( 'tribe-editor__ticket__deferred-error' );
		expect( refused ).toContain( 'Not allowed' );
		expect( refused ).toContain( 'tribe-editor__ticket--save-error' );
		expect( saved ).not.toContain( 'tribe-editor__ticket__deferred-status' );
	} );
} );
