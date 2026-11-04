jest.mock( 'react-redux', () => ( {
	connect: jest.fn( () => ( Component ) => Component ),
} ) );

jest.mock( '@moderntribe/common/hoc', () => ( {
	withStore: () => ( Component ) => Component,
} ) );

jest.mock( '../../src/Tickets/Blocks/Ticket/app/editor/container-content/attendees-registration/template', () => () => null );

jest.mock( '../../src/modules/data/blocks/ticket', () => ( {
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
import '../../src/Tickets/Blocks/Ticket/app/editor/container-content/attendees-registration/container';

const mapDispatchToProps = connect.mock.calls[ 0 ][ 1 ];

/**
 * Builds an iframe whose page holds the attendee information form.
 *
 * @param {string|null} sortables The markup of the form's fields container, or null for a page without one.
 *
 * @return {Object} The iframe.
 */
const makeIframe = ( sortables ) => {
	const iframeWindow = new EventTarget();
	iframeWindow.document = document.implementation.createHTMLDocument( '' );
	iframeWindow.document.body.innerHTML = `<form id="event-tickets-attendee-information"></form>${ sortables ?? '' }`;

	return { contentWindow: iframeWindow, nextSibling: document.createElement( 'div' ) };
};

const withField = '<div id="tribe-tickets-attendee-sortables"><div class="meta-postbox"></div></div>';
const withoutField = '<div id="tribe-tickets-attendee-sortables"></div>';

describe( 'Ticket attendee registration container', () => {
	it( 'closes the modal and records the fields when the saved form leaves the page', () => {
		const dispatch = jest.fn();
		const iframe = makeIframe( withField );

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
		const iframe = makeIframe( withoutField );

		mapDispatchToProps( dispatch, { clientId: 'ticket-1' } ).onIframeLoad( iframe );
		iframe.contentWindow.dispatchEvent( new Event( 'pagehide' ) );
		iframe.contentWindow.dispatchEvent( new Event( 'pagehide' ) );

		expect( dispatch ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'closes the modal when the page has no fields container', () => {
		const dispatch = jest.fn();
		const iframe = makeIframe( null );

		mapDispatchToProps( dispatch, { clientId: 'ticket-1' } ).onIframeLoad( iframe );
		iframe.contentWindow.dispatchEvent( new Event( 'pagehide' ) );

		expect( dispatch ).toHaveBeenCalledTimes( 1 );
		expect( dispatch ).toHaveBeenCalledWith( {
			type: 'SET_TICKET_IS_MODAL_OPEN',
			payload: { clientId: 'ticket-1', isOpen: false },
		} );
	} );
} );
