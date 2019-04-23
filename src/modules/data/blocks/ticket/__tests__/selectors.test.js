/**
 * Internal dependencies
 */
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { DEFAULT_STATE } from '@moderntribe/tickets/data/blocks/ticket/reducer';
import {
	DEFAULT_STATE as HEADER_IMAGE_DEFAULT_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/header-image';
import {
	DEFAULT_STATE as TICKET_DEFAULT_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/tickets/ticket';
import {
	DEFAULT_STATE as DETAILS_DEFAULT_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/tickets/ticket/details';
import {
	DEFAULT_STATE as TEMP_DETAILS_DEFAULT_STATE,
} from '@moderntribe/tickets/data/blocks/ticket/reducers/tickets/ticket/details';

jest.mock( 'moment', () => () => {
	const moment = require.requireActual( 'moment' );
	return moment( 'September 1, 2018 10:30 pm', 'MMMM D, Y h:mm a' );
} );

describe( 'Ticket block selectors', () => {
	let state;
	let ownProps;

	beforeEach( () => {
		state = {
			tickets: {
				blocks: {
					ticket: {
						...DEFAULT_STATE,
						headerImage: { ...HEADER_IMAGE_DEFAULT_STATE },
						tickets: {
							allClientIds: [ 'modern-tribe' ],
							byClientId: {
								'modern-tribe': {
									...TICKET_DEFAULT_STATE,
									details: { ...DETAILS_DEFAULT_STATE },
									tempDetails: { ...TEMP_DETAILS_DEFAULT_STATE },
								},
							},
						}
					},
				},
			},
		};
		ownProps = { clientId: 'modern-tribe' };
	} );

	describe( 'Block selectors', () => {
		test( 'getBlock', () => {
			expect( selectors.getBlock( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsIsSelected', () => {
			expect( selectors.getTicketsIsSelected( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsIsSettingsOpen', () => {
			expect( selectors.getTicketsIsSettingsOpen( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsIsSettingsLoading', () => {
			expect( selectors.getTicketsIsSettingsLoading( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsProvider', () => {
			expect( selectors.getTicketsProvider( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsSharedCapacity', () => {
			expect( selectors.getTicketsSharedCapacity( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsSharedCapacityInt', () => {
			expect( selectors.getTicketsSharedCapacityInt( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsTempSharedCapacity', () => {
			expect( selectors.getTicketsTempSharedCapacity( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsTempSharedCapacityInt', () => {
			expect( selectors.getTicketsTempSharedCapacityInt( state ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Header image selectors', () => {
		test( 'getTicketsHeaderImage', () => {
			expect( selectors.getTicketsHeaderImage( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsHeaderImageId', () => {
			expect( selectors.getTicketsHeaderImageId( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsHeaderImageSrc', () => {
			expect( selectors.getTicketsHeaderImageSrc( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsHeaderImageAlt', () => {
			expect( selectors.getTicketsHeaderImageAlt( state ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Tickets selectors', () => {
		test( 'getTickets', () => {
			expect( selectors.getTickets( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsAllClientIds', () => {
			expect( selectors.getTicketsAllClientIds( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsByClientId', () => {
			expect( selectors.getTicketsByClientId( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsArray', () => {
			expect( selectors.getTicketsArray( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsCount', () => {
			expect( selectors.getTicketsCount( state ) ).toMatchSnapshot();
		} );

		test( 'hasTickets', () => {
			expect( selectors.hasTickets( state ) ).toMatchSnapshot();
		} );

		test( 'hasCreatedTickets', () => {
			expect( selectors.hasCreatedTickets( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentTickets', () => {
			expect( selectors.getIndependentTickets( state ) ).toMatchSnapshot();
		} );

		test( 'getSharedTickets', () => {
			expect( selectors.getSharedTickets( state ) ).toMatchSnapshot();
		} );

		test( 'getSharedTicketsCount', () => {
			expect( selectors.getSharedTicketsCount( state ) ).toMatchSnapshot();
		} );

		test( 'getUnlimitedTickets', () => {
			expect( selectors.getUnlimitedTickets( state ) ).toMatchSnapshot();
		} );

		test( 'hasATicketSelected', () => {
			expect( selectors.hasATicketSelected( state ) ).toMatchSnapshot();
		} );

		test( 'getTicketsIdsInBlocks', () => {
			expect( selectors.getTicketsIdsInBlocks( state ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket selectors', () => {
		test( 'getTicketClientId', () => {
			expect( selectors.getTicketClientId( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicket', () => {
			expect( selectors.getTicket( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketSold', () => {
			expect( selectors.getTicketSold( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketAvailable', () => {
			expect( selectors.getTicketAvailable( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketId', () => {
			expect( selectors.getTicketId( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketCurrencySymbol', () => {
			expect( selectors.getTicketCurrencySymbol( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketCurrencyPosition', () => {
			expect( selectors.getTicketCurrencyPosition( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketProvider', () => {
			expect( selectors.getTicketProvider( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketHasAttendeeInfoFields', () => {
			expect( selectors.getTicketHasAttendeeInfoFields( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketIsLoading', () => {
			expect( selectors.getTicketIsLoading( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketIsModalOpen', () => {
			expect( selectors.getTicketIsModalOpen( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketHasBeenCreated', () => {
			expect( selectors.getTicketHasBeenCreated( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketHasChanges', () => {
			expect( selectors.getTicketHasChanges( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketHasDurationError', () => {
			expect( selectors.getTicketHasDurationError( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketIsSelected', () => {
			expect( selectors.getTicketIsSelected( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isTicketDisabled', () => {
			expect( selectors.isTicketDisabled( state, ownProps ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket details selectors', () => {
		test( 'getTicketDetails', () => {
			expect( selectors.getTicketDetails( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTitle', () => {
			expect( selectors.getTicketTitle( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketDescription', () => {
			expect( selectors.getTicketDescription( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketPrice', () => {
			expect( selectors.getTicketPrice( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketSku', () => {
			expect( selectors.getTicketSku( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketStartDate', () => {
			expect( selectors.getTicketStartDate( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketStartDateInput', () => {
			expect( selectors.getTicketStartDateInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketStartDateMoment', () => {
			expect( selectors.getTicketStartDateMoment( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketEndDate', () => {
			expect( selectors.getTicketEndDate( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketEndDateInput', () => {
			expect( selectors.getTicketEndDateInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketEndDateMoment', () => {
			expect( selectors.getTicketEndDateMoment( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketStartTime', () => {
			expect( selectors.getTicketStartTime( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketStartTimeNoSeconds', () => {
			expect( selectors.getTicketStartTimeNoSeconds( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketEndTime', () => {
			expect( selectors.getTicketEndTime( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketEndTimeNoSeconds', () => {
			expect( selectors.getTicketEndTimeNoSeconds( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketStartTimeInput', () => {
			expect( selectors.getTicketStartTimeInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketEndTimeInput', () => {
			expect( selectors.getTicketEndTimeInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketCapacityType', () => {
			expect( selectors.getTicketCapacityType( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketCapacity', () => {
			expect( selectors.getTicketCapacity( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketCapacityInt', () => {
			expect( selectors.getTicketCapacityInt( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isUnlimitedTicket', () => {
			expect( selectors.isUnlimitedTicket( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isSharedTicket', () => {
			expect( selectors.isSharedTicket( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isIndependentTicket', () => {
			expect( selectors.isIndependentTicket( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isTicketPast', () => {
			expect( selectors.isTicketPast( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isTicketFuture', () => {
			expect( selectors.isTicketFuture( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'isTicketOnSale', () => {
			expect( selectors.isTicketOnSale( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'hasTicketOnSale', () => {
			expect( selectors.hasTicketOnSale( state ) ).toMatchSnapshot();
		} );

		test( 'allTicketsPast', () => {
			expect( selectors.allTicketsPast( state ) ).toMatchSnapshot();
		} );

		test( 'allTicketsFuture', () => {
			expect( selectors.allTicketsFuture( state ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Ticket temp details selectors', () => {
		test( 'getTicketTempDetails', () => {
			expect( selectors.getTicketTempDetails( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempTitle', () => {
			expect( selectors.getTicketTempTitle( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempDescription', () => {
			expect( selectors.getTicketTempDescription( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempPrice', () => {
			expect( selectors.getTicketTempPrice( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempSku', () => {
			expect( selectors.getTicketTempSku( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempStartDate', () => {
			expect( selectors.getTicketTempStartDate( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempStartDateInput', () => {
			expect( selectors.getTicketTempStartDateInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempStartDateMoment', () => {
			expect( selectors.getTicketTempStartDateMoment( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempEndDate', () => {
			expect( selectors.getTicketTempEndDate( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempEndDateInput', () => {
			expect( selectors.getTicketTempEndDateInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempEndDateMoment', () => {
			expect( selectors.getTicketTempEndDateMoment( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempStartTime', () => {
			expect( selectors.getTicketTempStartTime( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempStartTimeNoSeconds', () => {
			expect( selectors.getTicketTempStartTimeNoSeconds( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempEndTime', () => {
			expect( selectors.getTicketTempEndTime( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempEndTimeNoSeconds', () => {
			expect( selectors.getTicketTempEndTimeNoSeconds( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempStartTimeInput', () => {
			expect( selectors.getTicketTempStartTimeInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempEndTimeInput', () => {
			expect( selectors.getTicketTempEndTimeInput( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempCapacityType', () => {
			expect( selectors.getTicketTempCapacityType( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempCapacity', () => {
			expect( selectors.getTicketTempCapacity( state, ownProps ) ).toMatchSnapshot();
		} );

		test( 'getTicketTempCapacityInt', () => {
			expect( selectors.getTicketTempCapacityInt( state, ownProps ) ).toMatchSnapshot();
		} );
	} );

	describe( 'isTempTitleValid', () => {
		it( 'should be valid', () => {
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.title = 'bob';
			expect( selectors.isTempTitleValid( state, ownProps ) ).toBe( true );
		} );

		it( 'should be invalid', () => {
			expect( selectors.isTempTitleValid( state, ownProps ) ).toBe( false );
		} );
	} );

	describe( 'isTempCapacityValid', () => {
		it( 'should be valid', () => {
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacity = '1';
			expect( selectors.isTempCapacityValid( state, ownProps ) ).toBe( true );
		} );

		it( 'should be invalid', () => {
			expect( selectors.isTempCapacityValid( state, ownProps ) ).toBe( false );
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacity = 'not a number';
			expect( selectors.isTempCapacityValid( state, ownProps ) ).toBe( false );
		} );
	} );

	describe( 'isTempSharedCapacityValid', () => {
		it( 'should be valid', () => {
			state.tickets.blocks.ticket.tempSharedCapacity = '1';
			expect( selectors.isTempSharedCapacityValid( state ) ).toBe( true );
		} );

		it( 'should be invalid', () => {
			expect( selectors.isTempSharedCapacityValid( state ) ).toBe( false );
			state.tickets.blocks.ticket.tempSharedCapacity = 'not a number';
			expect( selectors.isTempSharedCapacityValid( state ) ).toBe( false );
		} );
	} );

	describe( 'isTicketValid', () => {
		it( 'should be valid when unlimited', () => {
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.title = 'Modern Tribe';
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacityType = 'unlimited';
			expect( selectors.isTicketValid( state, ownProps ) ).toBe( true );
		} );

		it( 'should be valid when shared', () => {
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.title = 'Modern Tribe';
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacityType = 'capped';
			state.tickets.blocks.ticket.tempSharedCapacity = '10';
			expect( selectors.isTicketValid( state, ownProps ) ).toBe( true );
		} );

		it( 'should be invalid when independent', () => {
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacityType = 'own';
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.title = 'Modern Tribe';
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacity = '';
			expect( selectors.isTicketValid( state, ownProps ) ).toBe( false );
		} );

		it( 'should be invalid when independent with no title', () => {
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacityType = 'own';
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.title = '';
			state.tickets.blocks.ticket.tickets.byClientId[ 'modern-tribe' ].tempDetails.capacity = 1;
			expect( selectors.isTicketValid( state, ownProps ) ).toBe( false );
		} );
	} );

	describe( 'Amount reducers selectors', () => {
		describe( 'Reducer functions', () => {
			let tickets;

			beforeEach( () => {
				tickets = [
					{
						details: { capacity: 12 },
						tempDetails: { capacity: 12 },
						sold: 10,
						available: 2,
					}, {
						details: { capacity: 23 },
						tempDetails: { capacity: 21 },
						sold: 9,
						available: 14,
					},
				];
			} );

			test( '_getTotalCapacity', () => {
				expect( selectors._getTotalCapacity( tickets ) ).toMatchSnapshot();
			} );

			test( '_getTotalTempCapacity', () => {
				expect( selectors._getTotalTempCapacity( tickets ) ).toMatchSnapshot();
			} );

			test( '_getTotalSold', () => {
				expect( selectors._getTotalSold( tickets ) ).toMatchSnapshot();
			} );

			test( '_getTotalAvailable', () => {
				expect( selectors._getTotalAvailable( tickets ) ).toMatchSnapshot();
			} );
		} );

		test( 'getIndependentTicketsCapacity', () => {
			expect( selectors.getIndependentTicketsCapacity( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentTicketsTempCapacity', () => {
			expect( selectors.getIndependentTicketsTempCapacity( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentTicketsSold', () => {
			expect( selectors.getIndependentTicketsSold( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentTicketsAvailable', () => {
			expect( selectors.getIndependentTicketsAvailable( state ) ).toMatchSnapshot();
		} );

		test( 'getSharedTicketsSold', () => {
			expect( selectors.getSharedTicketsSold( state ) ).toMatchSnapshot();
		} );

		test( 'getSharedTicketsAvailable', () => {
			expect( selectors.getSharedTicketsAvailable( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentAndSharedTicketsCapacity', () => {
			expect( selectors.getIndependentAndSharedTicketsCapacity( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentAndSharedTicketsTempCapacity', () => {
			expect( selectors.getIndependentAndSharedTicketsTempCapacity( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentAndSharedTicketsSold', () => {
			expect( selectors.getIndependentAndSharedTicketsSold( state ) ).toMatchSnapshot();
		} );

		test( 'getIndependentAndSharedTicketsAvailable', () => {
			expect( selectors.getIndependentAndSharedTicketsAvailable( state ) ).toMatchSnapshot();
		} );
	} );

	describe( 'Misc selectors', () => {
		test( 'getTicketProviders', () => {
			expect( selectors.getTicketProviders() ).toMatchSnapshot();
		} );

		test( 'getDefaultTicketProvider', () => {
			expect( selectors.getDefaultTicketProvider() ).toMatchSnapshot();
		} );

		test( 'hasValidTicketProvider', () => {
			expect( selectors.hasValidTicketProvider() ).toMatchSnapshot();
		} );

		test( 'hasMultipleTicketProviders', () => {
			expect( selectors.hasMultipleTicketProviders() ).toMatchSnapshot();
		} );

		test( 'hasTicketProviders', () => {
			expect( selectors.hasTicketProviders() ).toMatchSnapshot();
		} );

		test( 'canCreateTickets', () => {
			expect( selectors.canCreateTickets() ).toMatchSnapshot();
		} );
	} );
} );
