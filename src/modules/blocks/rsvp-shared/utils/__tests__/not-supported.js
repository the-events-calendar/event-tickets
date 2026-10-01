/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

// SVG imports resolve to a string under Jest, which React cannot render as a component.
jest.mock( '../../../../icons', () => ( {
	Bulb: () => <span>Bulb Icon</span>,
} ) );

/**
 * Internal dependencies
 */
import { renderBlockNotSupported } from '../not-supported';

describe( 'renderBlockNotSupported', () => {
	const render = () => renderer.create( renderBlockNotSupported( 'client-id' ) ).root;

	it( 'should render an RSVP card like the disabled Tickets block', () => {
		const root = render();

		const card = root.findByProps( { className: 'tribe-editor__card tribe-editor__not-supported-message tribe-editor__rsvp-not-supported' } );
		expect( card.findByProps( { className: 'tickets-heading tickets-row-line' } ).children ).toEqual( [ 'RSVP' ] );
	} );

	it( 'should explain recurring events are not supported in a notice with the plans link', () => {
		const notice = render().findByProps( { className: 'tribe-editor__notice' } );

		expect( JSON.stringify( notice.findByType( 'p' ).props.children ) ).toContain(
			'RSVPs are not yet supported on recurring events.'
		);
		expect( notice.findByType( 'a' ).props ).toMatchObject( {
			className: 'helper-link',
			href: 'https://evnt.is/1b7a',
		} );
	} );

	it( 'should not render a remove block button', () => {
		expect( render().findAllByType( 'button' ) ).toHaveLength( 0 );
	} );
} );
