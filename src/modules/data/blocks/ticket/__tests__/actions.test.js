/**
 * Internal dependencies
 */
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Gutenberg actions', () => {
	describe( 'Initial state', () => {
		const props = {
			clientId: 'modern-tribe',
			attributes: {
				header: 99,
				sharedCapacity: 19,
			},
		};
		expect( actions.setInitialState( props ) ).toMatchSnapshot();
	} );

	describe( 'Parent block is loading', () => {
		expect( actions.setParentBlockIsLoading( true ) ).toMatchSnapshot();
		expect( actions.setParentBlockIsLoading( false ) ).toMatchSnapshot();
	} );

	describe( 'Header actions', () => {
		test( 'Set null to remove header', () => {
			expect( actions.setHeader( null ) ).toMatchSnapshot();
		} );

		test( 'Set object into header', () => {
			expect( actions.setHeader( { image: 10 } ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Set shared capacity actions', () => {
		test( 'Set empty value', () => {
			expect( actions.setTotalSharedCapacity( 0 ) ).toMatchSnapshot();
		} );

		test( 'Set a large number', () => {
			expect( actions.setTotalSharedCapacity( 999 ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Set the status of the settings dashboard', () => {
		test( 'Open the settings dashboard', () => {
			expect( actions.openSettings() ).toMatchSnapshot();
			expect( actions.setSettingsOpen( true ) ).toMatchSnapshot();
		} );

		test( 'Close the settings dashboard', () => {
			expect( actions.closeSettings() ).toMatchSnapshot();
			expect( actions.setSettingsOpen( false ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Block selection', () => {
		test( 'Parent block selection', () => {
			expect( actions.setParentBlockSelected( true ) ).toMatchSnapshot();
			expect( actions.setParentBlockSelected( false ) ).toMatchSnapshot();
		} );

		test( 'Child block selection', () => {
			expect( actions.setChildBlockSelected( true ) ).toMatchSnapshot();
			expect( actions.setChildBlockSelected( false ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Active block selected', () => {
		test( 'Active block', () => {
			expect( actions.setActiveChildBlockId( 'modern-tribe' ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Temporarily values', () => {
		test( 'Temporarily capacity', () => {
			expect( actions.setTempSharedCapacity( 99 ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket Provider', () => {
		test( 'Set default provider', () => {
			expect( actions.setProvider( 'Tribe__Tickets__Commerce__PayPal__Main' ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Single block actions', () => {
		const blockId = 'modern-tribe';

		test( 'Block registration process', () => {
			expect( actions.registerTicketBlock( blockId ) ).toMatchSnapshot();
			expect( actions.removeTicketBlock( blockId ) ).toMatchSnapshot();
		} );

		test( 'Ticket title', () => {
			expect( actions.setTitle( blockId, 'Modern Tribe' ) );
		} );

		test( 'Ticket description', () => {
			expect( actions.setDescription( blockId, 'The Next Generation of Digital Agency' ) )
				.toMatchSnapshot();
		} );

		test( 'Ticket price', () => {
			expect( actions.setPrice( blockId, 99 ) ).toMatchSnapshot();
		} );

		test( 'Ticket SKU', () => {
			expect( actions.setSKU( blockId, 'my-sku' ) ).toMatchSnapshot();
		} );

		test( 'Ticket dates', () => {
			expect( actions.setStartDate( blockId, 'January 1, 2018' ) ).toMatchSnapshot();
			expect( actions.setStartTime( blockId, '10:00' ) ).toMatchSnapshot();
			expect( actions.setEndDate( blockId, 'January 10, 2018' ) ).toMatchSnapshot();
			expect( actions.setEndTime( blockId, '12:34' ) ).toMatchSnapshot();
		} );

		test( 'Ticket Capacity', () => {
			expect( actions.setCapacity( blockId, 'unlimited' ) ).toMatchSnapshot();
			expect( actions.setCapacityType( blockId, 'unlimited' ) ).toMatchSnapshot();
		} );

		test( 'Create a new ticket', () => {
			expect( actions.createNewTicket( blockId ) ).toMatchSnapshot();
		} );

		test( 'Update ticket', () => {
			expect( actions.updateTicket( blockId ) ).toMatchSnapshot();
		} );

		test( 'Set ticket date is pristine', () => {
			expect( actions.setTicketDateIsPristine( blockId, false ) ).toMatchSnapshot();
			expect( actions.setTicketDateIsPristine( blockId, true ) ).toMatchSnapshot();
		} );

		test( 'Set ticket post ID', () => {
			expect( actions.setTicketId( blockId, 99 ) ).toMatchSnapshot();
		} );

		test( 'Ticket editing flag', () => {
			expect( actions.setTicketIsEditing( true ) ).toMatchSnapshot();
			expect( actions.setTicketIsEditing( false ) ).toMatchSnapshot();
		} );

		test( 'Ticket start moment object', () => {
			expect( actions.setTicketStartDateMoment( blockId, { type: 'moment' } ) ).toMatchSnapshot();
			expect( actions.setTicketStartDateMoment( blockId, null ) ).toMatchSnapshot();
		} );

		test( 'Ticket end moment object', () => {
			expect( actions.setTicketEndDateMoment( blockId, { type: 'moment' } ) ).toMatchSnapshot();
			expect( actions.setTicketEndDateMoment( blockId, null ) ).toMatchSnapshot();
		} );

		test( 'Ticket has been created', () => {
			expect( actions.setTicketHasBeenCreated( blockId, true ) ).toMatchSnapshot();
			expect( actions.setTicketHasBeenCreated( blockId, false ) ).toMatchSnapshot();
		} );

		test( 'Ticket is loading', () => {
			expect( actions.setTicketIsLoading( blockId, true ) ).toMatchSnapshot();
			expect( actions.setTicketIsLoading( blockId, false ) ).toMatchSnapshot();
		} );

		test( 'fetch ticket details', () => {
			expect( actions.fetchTicketDetails( blockId, 99 ) ).toMatchSnapshot();
		} );

		test( 'cancel ticket edit', () => {
			expect( actions.cancelTicketEdit( blockId ) ).toMatchSnapshot();
		} );

		test( 'Set sold amount on a ticket', () => {
			expect( actions.setTicketSold( blockId, 23 ) ).toMatchSnapshot();
		} );

		test( 'Set ticket availability', () => {
			expect( actions.setTicketAvailable( blockId, 99 ) ).toMatchSnapshot();
		} );
	} );
} );
