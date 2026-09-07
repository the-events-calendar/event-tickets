/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

jest.mock( '../../../../elements', () => ( {
	AttendeesRegistration: ( { helperText, linkText, showHelperText } ) => (
		<div data-testid="ar-element">
			<span data-testid="link-text">{ linkText }</span>
			<span data-testid="helper-text">{ showHelperText ? helperText : null }</span>
		</div>
	),
} ) );

/**
 * Internal dependencies
 */
import RSVPAttendeeRegistration from '../template';

describe( 'RSVPAttendeeRegistration', () => {
	const defaultProps = {
		attendeeRegistrationURL: 'http://example.test/edit.php?post_type=tribe_events&page=attendee-registration&ticket_id=0&tribe_events_modal=1', // eslint-disable-line max-len
		hasAttendeeInfoFields: false,
		isDisabled: false,
		isModalOpen: false,
		onClick: jest.fn(),
		onClose: jest.fn(),
		onIframeLoad: jest.fn(),
		helperText: 'Helper text',
		showHelperText: false,
	};

	it( 'should render the attendee registration element', () => {
		const component = renderer.create( <RSVPAttendeeRegistration { ...defaultProps } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	it( 'should render helper text when showHelperText is true', () => {
		const component = renderer.create(
			<RSVPAttendeeRegistration { ...defaultProps } showHelperText={ true } />
		);
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'Helper text' );
	} );

	it( 'should not render helper text when showHelperText is false', () => {
		const component = renderer.create(
			<RSVPAttendeeRegistration { ...defaultProps } showHelperText={ false } />
		);
		const json = JSON.stringify( component.toJSON() );
		expect( json ).not.toContain( 'Helper text' );
	} );

	it( 'should use the add link text when no attendee info fields exist', () => {
		const component = renderer.create(
			<RSVPAttendeeRegistration
				{ ...defaultProps }
				linkTextAdd="+ Collect attendee information"
				linkTextEdit="Edit attendee information"
			/>
		);
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( '+ Collect attendee information' );
		expect( json ).not.toContain( 'Edit attendee information' );
	} );

	it( 'should use the edit link text when attendee info fields exist', () => {
		const component = renderer.create(
			<RSVPAttendeeRegistration
				{ ...defaultProps }
				hasAttendeeInfoFields={ true }
				linkTextAdd="+ Collect attendee information"
				linkTextEdit="Edit attendee information"
			/>
		);
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'Edit attendee information' );
	} );
} );
