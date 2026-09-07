jest.mock( '../../create-form/template', () => ( { clientId, hasTicketsPlus } ) => (
	<div data-testid="rsvp-create-form" data-has-tickets-plus={ String( hasTicketsPlus ) }>
		{ clientId }
	</div>
) );

/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import RSVPContainerContent from '../template';

describe( 'RSVPContainerContent', () => {
	it( 'renders the create form when add/edit is open', () => {
		const component = renderer.create(
			<RSVPContainerContent clientId="test-client-id" isAddEditOpen={ true } />
		);
		const tree = component.toJSON();

		expect( tree.props[ 'data-testid' ] ).toBe( 'rsvp-create-form' );
		expect( tree.children ).toEqual( [ 'test-client-id' ] );
	} );

	it( 'forwards the Event Tickets Plus flag to the create form', () => {
		const withPlus = renderer.create(
			<RSVPContainerContent clientId="test-client-id" hasTicketsPlus={ true } isAddEditOpen={ true } />
		);

		expect( withPlus.toJSON().props[ 'data-has-tickets-plus' ] ).toBe( 'true' );

		const withoutPlus = renderer.create(
			<RSVPContainerContent clientId="test-client-id" hasTicketsPlus={ false } isAddEditOpen={ true } />
		);

		expect( withoutPlus.toJSON().props[ 'data-has-tickets-plus' ] ).toBe( 'false' );
	} );

	it( 'renders nothing when add/edit is closed', () => {
		const component = renderer.create(
			<RSVPContainerContent clientId="test-client-id" isAddEditOpen={ false } />
		);

		expect( component.toJSON() ).toBeNull();
	} );
} );
