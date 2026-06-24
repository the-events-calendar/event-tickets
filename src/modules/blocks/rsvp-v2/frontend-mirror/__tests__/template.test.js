/**
 * External dependencies
 */
import React, { createRef } from 'react';

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
	_x: ( text ) => text,
} ) );

jest.mock( '@wordpress/components', () => ( {
	Dashicon: () => null,
} ) );

/**
 * Internal dependencies
 */
import RSVPFrontendMirror from '../template';

jest.mock( '../../../rsvp/action-buttons', () => ( {
	AttendeesActionButton: () => <a data-testid="view-attendees">View Attendees</a>,
} ) );

describe( 'RSVPFrontendMirror', () => {
	const defaultProps = {
		available: 1000,
		goingCount: 0,
		notGoingCount: 1,
		onEditRemaining: jest.fn(),
		remainingRef: createRef(),
		showEditAffordances: false,
		showNotGoing: true,
		title: 'RSVP',
	};

	it( 'should mirror frontend markup structure', () => {
		const component = renderer.create( <RSVPFrontendMirror { ...defaultProps } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	it( 'should use frontend RSVP class names', () => {
		const component = renderer.create( <RSVPFrontendMirror { ...defaultProps } /> );
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'tribe-tickets__rsvp' );
		expect( json ).toContain( 'tribe-tickets__rsvp-attendance-number' );
		expect( json ).toContain( 'tribe-tickets__rsvp-actions-button-going' );
		expect( json ).not.toContain( 'RSVP Here' );
	} );
} );
