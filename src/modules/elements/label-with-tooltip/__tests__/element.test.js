/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import LabelWithTooltip from '../element';

jest.mock( '@wordpress/components', () => ( {
	/* eslint-disable react/prop-types */
	Tooltip: ( { text, position, children } ) => (
		<div>
			<span>{ text }</span>
			<span>{ position }</span>
			<span>{ children }</span>
		</div>
	),
	/* eslint-enable react/prop-types */
} ) );

describe( 'Tooltip Element', () => {
	it( 'renders a tooltip', () => {
		const props = {
			className: 'element-class',
			label: 'some label',
			tooltipLabel: 'tooltip label',
			tooltipPosition: 'bottom left',
			tooltipText: 'here is the tooltip text',
		};
		const component = renderer.create( <LabelWithTooltip { ...props } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
