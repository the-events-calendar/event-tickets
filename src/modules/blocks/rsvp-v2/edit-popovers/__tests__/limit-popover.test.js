/**
 * External dependencies
 */
import React, { createRef } from 'react';

/**
 * Internal dependencies
 */
import RSVPLimitPopover from '../limit-popover';

jest.mock( '@wordpress/components', () => ( {
	Popover: ( { children } ) => <div data-testid="popover">{ children }</div>,
	Button: ( { children, onClick } ) => <button onClick={ onClick }>{ children }</button>,
} ) );

jest.mock( '@moderntribe/common/elements', () => ( {
	NumberInput: ( { onChange, value } ) => (
		<input data-testid="limit-input" onChange={ onChange } value={ value } />
	),
} ) );

describe( 'RSVPLimitPopover', () => {
	const defaultProps = {
		anchorRef: createRef(),
		isOpen: true,
		isSaving: false,
		onCancel: jest.fn(),
		onSave: jest.fn(),
		onTempCapacityChange: jest.fn(),
		tempCapacity: '25',
	};

	it( 'should render RSVP Limit title and help text', () => {
		const component = renderer.create( <RSVPLimitPopover { ...defaultProps } /> );
		const json = JSON.stringify( component.toJSON() );
		expect( json ).toContain( 'RSVP Limit' );
		expect( json ).toContain( 'Leave blank for unlimited' );
	} );

	it( 'should return null when closed', () => {
		const component = renderer.create( <RSVPLimitPopover { ...defaultProps } isOpen={ false } /> );
		expect( component.toJSON() ).toBeNull();
	} );
} );
