/**
 * External dependencies
 */
import React from 'react';
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import ActionButton, { positions } from '../element';
import Button from '@moderntribe/common/elements/button';

const Icon = () => ( <span role="img" aria-label="Emoji">🦖</span> );

describe( 'ActionButton', () => {
	test( 'component rendered', () => {
		const component = renderer.create(
			<ActionButton icon={ <Icon /> }>Custom Action</ActionButton>,
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'component rendered with the correct class when icon is on the right', () => {
		const component = renderer.create(
			<ActionButton icon={ <Icon /> } placement={ positions.right }>Custom Action</ActionButton>,
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'component positions', () => {
		expect( positions.right ).toBe( 'right' );
		expect( positions.left ).toBe( 'left' );
	} );

	test( 'component rendered as link', () => {
		const component = renderer.create(
			<ActionButton asLink={ true } icon={ <Icon /> } href="#">Test Action</ActionButton>,
		);
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
