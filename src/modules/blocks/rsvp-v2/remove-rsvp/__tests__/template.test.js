/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import RSVPRemoveRsvp from '../template';

describe( 'RSVPRemoveRsvp', () => {
	it( 'should render Remove RSVP button', () => {
		const component = renderer.create(
			<RSVPRemoveRsvp isDisabled={ false } isLoading={ false } onRemove={ jest.fn() } />
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
