jest.mock( 'react-redux', () => ( {
	connect: jest.fn( () => ( Component ) => Component ),
} ) );

jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => Component,
} ) );

jest.mock( '../../../src/Tickets/Blocks/Ticket/app/editor/container-content/attendees-registration/template', () => () => null );

jest.mock( '../../../src/modules/data/blocks/ticket', () => ( {
	actions: {
		setTicketHasAttendeeInfoFields: jest.fn( ( clientId, hasFields ) => ( {
			type: 'SET_TICKET_HAS_ATTENDEE_INFO_FIELDS',
			payload: { clientId, hasFields },
		} ) ),
		setTicketIsModalOpen: jest.fn( ( clientId, isOpen ) => ( {
			type: 'SET_TICKET_IS_MODAL_OPEN',
			payload: { clientId, isOpen },
		} ) ),
	},
	selectors: {},
} ) );

import { connect } from 'react-redux';
import '../../../src/Tickets/Blocks/Ticket/app/editor/container-content/attendees-registration/container';

const mapDispatchToProps = connect.mock.calls[ 0 ][ 1 ];

/**
 * Builds an iframe whose page holds the attendee information form, with or without a field.
 *
 * @param {boolean} withField Whether the form holds an attendee information field.
 *
 * @return {Object} The iframe.
 */
const makeIframe = ( withField ) => {
	const iframeWindow = new EventTarget();
	iframeWindow.document = document.implementation.createHTMLDocument( '' );
	iframeWindow.document.body.innerHTML = `
		<form id="event-tickets-attendee-information"></form>
		<div id="tribe-tickets-attendee-sortables">${ withField ? '<div class="meta-postbox"></div>' : '' }</div>
	`;

	return { contentWindow: iframeWindow, nextSibling: document.createElement( 'div' ) };
};

describe( 'Ticket attendee registration container', () => {
	it( 'closes the modal and records the fields when the saved form leaves the page', () => {
		const dispatch = jest.fn();
		const iframe = makeIframe( true );

		mapDispatchToProps( dispatch, { clientId: 'ticket-1' } ).onIframeLoad( iframe );
		iframe.contentWindow.dispatchEvent( new Event( 'pagehide' ) );

		expect( dispatch ).toHaveBeenCalledWith( {
			type: 'SET_TICKET_HAS_ATTENDEE_INFO_FIELDS',
			payload: { clientId: 'ticket-1', hasFields: true },
		} );
		expect( dispatch ).toHaveBeenCalledWith( {
			type: 'SET_TICKET_IS_MODAL_OPEN',
			payload: { clientId: 'ticket-1', isOpen: false },
		} );
	} );

	it( 'stops listening once the page has left', () => {
		const dispatch = jest.fn();
		const iframe = makeIframe( false );

		mapDispatchToProps( dispatch, { clientId: 'ticket-1' } ).onIframeLoad( iframe );
		iframe.contentWindow.dispatchEvent( new Event( 'pagehide' ) );
		iframe.contentWindow.dispatchEvent( new Event( 'pagehide' ) );

		expect( dispatch ).toHaveBeenCalledTimes( 2 );
	} );
} );
