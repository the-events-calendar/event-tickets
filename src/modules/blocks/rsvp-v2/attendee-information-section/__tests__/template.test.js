/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import RSVPAttendeeInformationSection from '../template';

jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( hook, value ) => value ),
} ) );

describe( 'RSVPAttendeeInformationSection', () => {
	const defaultProps = {
		fieldNames: [ 'Email', 'Phone' ],
		hasAttendeeInfoFields: true,
		onEdit: jest.fn(),
		rsvpId: 42,
		showEditAffordances: true,
	};

	it( 'should render attendee information section', () => {
		const component = renderer.create( <RSVPAttendeeInformationSection { ...defaultProps } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	it( 'should show no fields configured when empty', () => {
		const component = renderer.create(
			<RSVPAttendeeInformationSection
				{ ...defaultProps }
				fieldNames={ [] }
				hasAttendeeInfoFields={ false }
			/>
		);
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'No fields configured' );
	} );
} );
