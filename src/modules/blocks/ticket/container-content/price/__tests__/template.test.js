/**
 * External dependencies
 */
import React from 'react';

import Price from './../template';

describe( 'Price', () => {
	test( 'Render the component with no errors', () => {
		const onTempPriceChange = jest.fn();
		const component = renderer.create( <Price onTempPriceChange={ onTempPriceChange } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

} );
