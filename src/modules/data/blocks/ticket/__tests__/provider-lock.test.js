jest.mock( '@moderntribe/common/utils/recurrence', () => ( {
	hasRecurrenceRules: () => false,
} ) );

/*
 * The ticket constants read their labels from the editor config when first imported, so the config has
 * to exist before the container module is required.
 */
window.tribe_editor_config = {
	tickets: {
		ticketLabels: { ticket: { pluralLowercase: 'tickets' } },
	},
};

const { mapStateToProps } = require( '../../../../../Tickets/Blocks/Tickets/app/editor/controls/container' );
const { DEFAULT_STATE } = require( '@moderntribe/tickets/data/blocks/ticket/reducer' );
const {
	DEFAULT_STATE: TICKET_DEFAULT_STATE,
} = require( '@moderntribe/tickets/data/blocks/ticket/reducers/tickets/ticket' );

const buildState = ( hasBeenCreated ) => ( {
	tickets: {
		blocks: {
			ticket: {
				...DEFAULT_STATE,
				tickets: {
					allClientIds: [ 'ticket-block' ],
					byClientId: {
						'ticket-block': { ...TICKET_DEFAULT_STATE, hasBeenCreated },
					},
				},
			},
		},
	},
} );

describe( 'Ticket provider controls container', () => {
	test( 'keeps the provider selectable while the ticket block has not been created yet', () => {
		expect( mapStateToProps( buildState( false ), {} ).hasTickets ).toBe( false );
	} );

	test( 'locks the provider once a ticket has been created', () => {
		expect( mapStateToProps( buildState( true ), {} ).hasTickets ).toBe( true );
	} );
} );
