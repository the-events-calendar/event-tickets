/**
 * Internal dependencies
 */
import { constants } from '@moderntribe/tickets/data/blocks/ticket';

describe( 'Ticket Constants', () => {
	test( 'tc', () => {
		expect( constants.TC ).toEqual( 'tribe-commerce' );
	} );

	test( 'edd', () => {
		expect( constants.EDD ).toEqual( 'edd' );
	} );

	test( 'woo', () => {
		expect( constants.WOO ).toEqual( 'woo' );
	} );

	test( 'tc class', () => {
		expect( constants.TC_CLASS ).toEqual( 'Tribe__Tickets__Commerce__PayPal__Main' );
	} );

	test( 'edd class', () => {
		expect( constants.EDD_CLASS ).toEqual( 'Tribe__Tickets_Plus__Commerce__EDD__Main' );
	} );

	test( 'woo class', () => {
		expect( constants.WOO_CLASS ).toEqual( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' );
	} );

	test( 'provider class to constant mapping', () => {
		expect( constants.PROVIDER_CLASS_TO_PROVIDER_MAPPING )
			.toEqual( {
				Tribe__Tickets__Commerce__PayPal__Main: 'tribe-commerce',
				Tribe__Tickets_Plus__Commerce__EDD__Main: 'edd',
				Tribe__Tickets_Plus__Commerce__WooCommerce__Main: 'woo',
			} );
	} );

	test( 'provider types', () => {
		expect( constants.PROVIDER_TYPES ).toEqual( [
			'tribe-commerce',
			'edd',
			'woo',
		] );
	} );
} );
