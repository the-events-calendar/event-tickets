jest.mock( '../../create-form/template', () => ( { clientId } ) => (
	<div data-testid="rsvp-create-form">{ clientId }</div>
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

	it( 'renders nothing when add/edit is closed', () => {
		const component = renderer.create(
			<RSVPContainerContent clientId="test-client-id" isAddEditOpen={ false } />
		);

		expect( component.toJSON() ).toBeNull();
	} );
} );
