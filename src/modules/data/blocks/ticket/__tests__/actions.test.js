/**
 * Internal dependencies
 */
import * as actions from '../actions';

describe( 'Ticket actions', () => {
	const clientId = 'modern-tribe';

	describe( 'Tickets actions', () => {
		test( 'set tickets initial state', () => {
			const props = {
				clientId: 'modern-tribe',
				attributes: {
					header: 99,
					sharedCapacity: 19,
				},
			};
			expect( actions.setTicketsInitialState( props ) ).toMatchSnapshot();
		} );

		test( 'reset tickets block', () => {
			expect( actions.resetTicketsBlock() ).toMatchSnapshot();
		} );

		test( 'set tickets header image', () => {
			expect( actions.setTicketsHeaderImage( { image: 10 } ) ).toMatchSnapshot();
		} );

		test( 'set tickets is selected', () => {
			expect( actions.setTicketsIsSelected( true ) ).toMatchSnapshot();
			expect( actions.setTicketsIsSelected( false ) ).toMatchSnapshot();
		} );

		test( 'set tickets is settings open', () => {
			expect( actions.setTicketsIsSettingsOpen( true ) ).toMatchSnapshot();
			expect( actions.setTicketsIsSettingsOpen( false ) ).toMatchSnapshot();
		} );

		test( 'set tickets is settings loading', () => {
			expect( actions.setTicketsIsSettingsLoading( true ) ).toMatchSnapshot();
			expect( actions.setTicketsIsSettingsLoading( false ) ).toMatchSnapshot();
		} );

		test( 'open settings', () => {
			expect( actions.openSettings() ).toMatchSnapshot();
		} );

		test( 'close settings', () => {
			expect( actions.closeSettings() ).toMatchSnapshot();
		} );

		test( 'set tickets provider', () => {
			expect( actions.setTicketsProvider( 'Tribe__Tickets__Commerce__PayPal__Main' ) )
				.toMatchSnapshot();
		} );

		test( 'set tickets shared capacity', () => {
			expect( actions.setTicketsSharedCapacity( 99 ) ).toMatchSnapshot();
		} );

		test( 'set tickets temp shared capacity', () => {
			expect( actions.setTicketsTempSharedCapacity( 99 ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Header image saga actions', () => {
		test( 'fetch tickets header image', () => {
			expect( actions.fetchTicketsHeaderImage( 1 ) ).toMatchSnapshot();
		} );

		test( 'update tickets header image', () => {
			expect( actions.updateTicketsHeaderImage( { id: 1, alt: 'hi', src: '#' } ) )
				.toMatchSnapshot();
		} );

		test( 'delete tickets header image', () => {
			expect( actions.deleteTicketsHeaderImage() ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket details actions', () => {
		test( 'set ticket title', () => {
			expect( actions.setTicketTitle( clientId, 'Modern Tribe' ) );
		} );

		test( 'set ticket description', () => {
			expect( actions.setTicketDescription( clientId, 'The Next Generation of Digital Agency' ) )
				.toMatchSnapshot();
		} );

		test( 'set ticket price', () => {
			expect( actions.setTicketPrice( clientId, 99 ) ).toMatchSnapshot();
		} );

		test( 'set ticket sku', () => {
			expect( actions.setTicketSku( clientId, 'my-sku' ) ).toMatchSnapshot();
		} );

		test( 'set ticket iac setting', () => {
			expect( actions.setTicketIACSetting( clientId, 'allowed' ) ).toMatchSnapshot();
		} );

		test( 'set ticket start date', () => {
			expect( actions.setTicketStartDate( clientId, '2018-01-01' ) ).toMatchSnapshot();
		} );

		test( 'set ticket start date input', () => {
			expect( actions.setTicketStartDateInput( clientId, 'January 1, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set ticket start moment', () => {
			expect( actions.setTicketStartDateMoment( clientId, { type: 'moment' } ) ).toMatchSnapshot();
			expect( actions.setTicketStartDateMoment( clientId, null ) ).toMatchSnapshot();
		} );

		test( 'set ticket end date', () => {
			expect( actions.setTicketEndDate( clientId, '2018-01-10' ) ).toMatchSnapshot();
		} );

		test( 'set ticket end date input', () => {
			expect( actions.setTicketEndDateInput( clientId, 'January 10, 2018' ) ).toMatchSnapshot();
		} );

		test( 'set ticket end moment', () => {
			expect( actions.setTicketEndDateMoment( clientId, { type: 'moment' } ) ).toMatchSnapshot();
			expect( actions.setTicketEndDateMoment( clientId, null ) ).toMatchSnapshot();
		} );

		test( 'set ticket start time', () => {
			expect( actions.setTicketStartTime( clientId, '10:00' ) ).toMatchSnapshot();
		} );

		test( 'set ticket end time', () => {
			expect( actions.setTicketEndTime( clientId, '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set ticket start time input', () => {
			expect( actions.setTicketStartTimeInput( clientId, '10:00' ) ).toMatchSnapshot();
		} );

		test( 'set ticket end time input', () => {
			expect( actions.setTicketEndTimeInput( clientId, '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set ticket capacity type', () => {
			expect( actions.setTicketCapacityType( clientId, 'unlimited' ) ).toMatchSnapshot();
		} );

		test( 'set ticket capacity', () => {
			expect( actions.setTicketCapacity( clientId, '10' ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket temp details actions', () => {
		test( 'set ticket temp title', () => {
			expect( actions.setTicketTempTitle( clientId, 'Modern Tribe' ) );
		} );

		test( 'set ticket temp description', () => {
			expect( actions.setTicketTempDescription(
				clientId,
				'The Next Generation of Digital Agency',
			) ).toMatchSnapshot();
		} );

		test( 'set ticket temp price', () => {
			expect( actions.setTicketTempPrice( clientId, 99 ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp sku', () => {
			expect( actions.setTicketTempSku( clientId, 'my-sku' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp iac setting', () => {
			expect( actions.setTicketTempIACSetting( clientId, 'allowed' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp start date', () => {
			expect( actions.setTicketTempStartDate( clientId, '2018-01-01' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp start date input', () => {
			expect( actions.setTicketTempStartDateInput( clientId, 'January 1, 2018' ) )
				.toMatchSnapshot();
		} );

		test( 'set ticket temp start moment', () => {
			expect( actions.setTicketTempStartDateMoment( clientId, { type: 'moment' } ) )
				.toMatchSnapshot();
			expect( actions.setTicketTempStartDateMoment( clientId, null ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp end date', () => {
			expect( actions.setTicketTempEndDate( clientId, '2018-01-10' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp end date input', () => {
			expect( actions.setTicketTempEndDateInput( clientId, 'January 10, 2018' ) )
				.toMatchSnapshot();
		} );

		test( 'set ticket temp end moment', () => {
			expect( actions.setTicketTempEndDateMoment( clientId, { type: 'moment' } ) )
				.toMatchSnapshot();
			expect( actions.setTicketTempEndDateMoment( clientId, null ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp start time', () => {
			expect( actions.setTicketTempStartTime( clientId, '10:00' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp end time', () => {
			expect( actions.setTicketTempEndTime( clientId, '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp start time input', () => {
			expect( actions.setTicketTempStartTimeInput( clientId, '10:00' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp end time input', () => {
			expect( actions.setTicketTempEndTimeInput( clientId, '12:34' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp capacity type', () => {
			expect( actions.setTicketTempCapacityType( clientId, 'unlimited' ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp capacity', () => {
			expect( actions.setTicketTempCapacity( clientId, '10' ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket actions', () => {
		test( 'register ticket block', () => {
			expect( actions.registerTicketBlock( clientId ) ).toMatchSnapshot();
		} );

		test( 'remove ticket block', () => {
			expect( actions.removeTicketBlock( clientId ) ).toMatchSnapshot();
		} );

		test( 'remove ticket blocks', () => {
			expect( actions.removeTicketBlocks() ).toMatchSnapshot();
		} );

		test( 'set ticket sold', () => {
			expect( actions.setTicketSold( clientId, 23 ) ).toMatchSnapshot();
		} );

		test( 'set ticket availability', () => {
			expect( actions.setTicketAvailable( clientId, 99 ) ).toMatchSnapshot();
		} );

		test( 'set ticket id', () => {
			expect( actions.setTicketId( clientId, 99 ) ).toMatchSnapshot();
		} );

		test( 'set ticket currency symbol', () => {
			expect( actions.setTicketCurrencySymbol( clientId, '$' ) ).toMatchSnapshot();
		} );

		test( 'set ticket currency positioin', () => {
			expect( actions.setTicketCurrencyPosition( clientId, 'suffix' ) ).toMatchSnapshot();
		} );

		test( 'set ticket provider', () => {
			expect( actions.setTicketProvider( clientId, 'provider' ) ).toMatchSnapshot();
		} );

		test( 'set ticket has attendee info fields', () => {
			expect( actions.setTicketHasAttendeeInfoFields( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketHasAttendeeInfoFields( clientId, false ) ).toMatchSnapshot();
		} );

		test( 'set ticket is loading', () => {
			expect( actions.setTicketIsLoading( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketIsLoading( clientId, false ) ).toMatchSnapshot();
		} );

		test( 'set ticket is modal open', () => {
			expect( actions.setTicketIsModalOpen( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketIsModalOpen( clientId, false ) ).toMatchSnapshot();
		} );

		test( 'set ticket has been created', () => {
			expect( actions.setTicketHasBeenCreated( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketHasBeenCreated( clientId, false ) ).toMatchSnapshot();
		} );

		test( 'set ticket has changes', () => {
			expect( actions.setTicketHasChanges( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketHasChanges( clientId, false ) ).toMatchSnapshot();
		} );

		test( 'set ticket has duration error', () => {
			expect( actions.setTicketHasDurationError( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketHasDurationError( clientId, false ) ).toMatchSnapshot();
		} );

		test( 'set ticket is selected', () => {
			expect( actions.setTicketIsSelected( clientId, true ) ).toMatchSnapshot();
			expect( actions.setTicketIsSelected( clientId, false ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket saga actions', () => {
		test( 'set ticket details', () => {
			expect( actions.setTicketDetails( clientId, {} ) ).toMatchSnapshot();
		} );

		test( 'set ticket temp details', () => {
			expect( actions.setTicketTempDetails( clientId, {} ) ).toMatchSnapshot();
		} );

		test( 'handle ticket start date', () => {
			expect( actions.handleTicketStartDate( clientId, {}, {} ) ).toMatchSnapshot();
		} );

		test( 'handle ticket start date', () => {
			expect( actions.handleTicketEndDate( clientId, {}, {} ) ).toMatchSnapshot();
		} );

		test( 'handle ticket start date', () => {
			expect( actions.handleTicketStartTime( clientId, 1000 ) ).toMatchSnapshot();
		} );

		test( 'handle ticket start date', () => {
			expect( actions.handleTicketEndTime( clientId, 1000 ) ).toMatchSnapshot();
		} );

		test( 'fetch ticket', () => {
			expect( actions.fetchTicket( clientId, 99 ) ).toMatchSnapshot();
		} );

		test( 'create new ticket', () => {
			expect( actions.createNewTicket( clientId ) ).toMatchSnapshot();
		} );

		test( 'update ticket', () => {
			expect( actions.updateTicket( clientId ) ).toMatchSnapshot();
		} );

		test( 'delete ticket', () => {
			expect( actions.deleteTicket( clientId ) ).toMatchSnapshot();
		} );

		test( 'set ticket initial state', () => {
			const props = {
				clientId: 'modern-tribe',
				attributes: {
					ticketId: 99,
				},
			};
			expect( actions.setTicketInitialState( props ) ).toMatchSnapshot();
		} );
	} );
} );
