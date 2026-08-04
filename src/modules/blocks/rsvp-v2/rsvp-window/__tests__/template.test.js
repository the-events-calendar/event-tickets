/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import RSVPRsvpWindow from '../template';

describe( 'RSVPRsvpWindow', () => {
	const defaultProps = {
		dateRange: '3/5/26 - 3/25/26',
		onEditWindow: jest.fn(),
		showEditAffordances: false,
	};

	it( 'should render RSVP Window section', () => {
		const component = renderer.create( <RSVPRsvpWindow { ...defaultProps } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	it( 'should return null when dateRange is empty', () => {
		const component = renderer.create( <RSVPRsvpWindow { ...defaultProps } dateRange="" /> );
		expect( component.toJSON() ).toBeNull();
	} );

	it( 'should show edit icon when edit affordances are enabled', () => {
		const component = renderer.create(
			<RSVPRsvpWindow { ...defaultProps } showEditAffordances={ true } />
		);
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'edit' );
	} );
} );
