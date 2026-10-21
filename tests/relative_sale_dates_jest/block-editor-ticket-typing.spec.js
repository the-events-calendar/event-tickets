import React from 'react';
import renderer, { act } from 'react-test-renderer';
import { useTicketTyping } from '@tec/tickets/relative-sale-dates/block-editor/use-ticket-typing';

jest.mock( '@wordpress/element', () => require( 'react' ) );

/**
 * Renders nothing, and hands the typing of a ticket block to the test on each render.
 *
 * @param {Object}   props          The component props.
 * @param {string}   props.clientId The client ID of the ticket block.
 * @param {Function} props.onRender Called with the typing on each render.
 *
 * @return {null} Nothing.
 */
function TypingProbe( { clientId, onRender } ) {
	onRender( useTicketTyping( clientId ) );

	return null;
}

describe( 'useTicketTyping', () => {
	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'should forget the typing of a ticket once its last window unmounts, with its pending settle', () => {
		jest.useFakeTimers();
		let typing;
		let tree;
		const render = () =>
			act( () => {
				tree = renderer.create(
					<TypingProbe clientId="ticket-block" onRender={ ( current ) => ( typing = current ) } />
				);
			} );

		render();
		act( () => typing.type() );

		expect( typing.isTyping ).toBe( true );

		act( () => tree.unmount() );

		expect( jest.getTimerCount() ).toBe( 0 );

		render();

		expect( typing.isTyping ).toBe( false );
	} );
} );
