/**
 * Tests for TC-RSVP block IAC-aware AJAX routing.
 */

describe( 'RSVP block IAC routing', () => {
	let obj;
	let requestMock;

	beforeEach( () => {
		jest.resetModules();

		window.tribe = {
			tickets: {
				rsvp: {
					manager: {
						request: jest.fn(),
					},
				},
			},
		};

		window.TecRsvp = {
			nonces: {
				rsvpHandle: 'test-nonce',
			},
		};

		// Load the module after globals are set.
		require( '../../../src/resources/js/commerce/rsvp-block.js' );
		obj = window.tribe.tickets.rsvp.block;
		requestMock = window.tribe.tickets.rsvp.manager.request;
	} );

	describe( 'hasIac', () => {
		it.each( [
			[ 'none', false ],
			[ '', false ],
			[ undefined, false ],
			[ 'required', true ],
			[ 'allowed', true ],
		] )( 'returns %s -> %s', ( iacValue, expected ) => {
			const $container = jQuery( '<div>' ).data( 'iac', iacValue );

			if ( expected ) {
				expect( obj.hasIac( $container ) ).toBeTruthy();
			} else {
				expect( obj.hasIac( $container ) ).toBeFalsy();
			}
		} );
	} );

	describe( 'bindGoing', () => {
		it( 'routes to the going step when IAC is inactive', () => {
			const $container = jQuery(
				'<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="42" data-iac="none">' +
					'<button class="tribe-tickets__rsvp-actions-button-going"></button>' +
				'</div>'
			);

			obj.bindGoing( $container );
			$container.find( obj.selectors.goingButton ).trigger( 'click' );

			expect( requestMock ).toHaveBeenCalledWith(
				expect.objectContaining( {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: 42,
					step: 'going',
					nonce: 'test-nonce',
				} ),
				$container
			);
		} );

		it( 'routes to ARI when IAC is active', () => {
			const $container = jQuery(
				'<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="42" data-iac="required">' +
					'<button class="tribe-tickets__rsvp-actions-button-going"></button>' +
				'</div>'
			);

			obj.bindGoing( $container );
			$container.find( obj.selectors.goingButton ).trigger( 'click' );

			expect( requestMock ).toHaveBeenCalledWith(
				expect.objectContaining( {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: 42,
					step: 'ari',
					going: 'going',
					nonce: 'test-nonce',
				} ),
				$container
			);
		} );
	} );

	describe( 'bindNotGoing', () => {
		it( 'routes to the not-going step when IAC is inactive', () => {
			const $container = jQuery(
				'<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="99" data-iac="none">' +
					'<button class="tribe-tickets__rsvp-actions-button-not-going"></button>' +
				'</div>'
			);

			obj.bindNotGoing( $container );
			$container.find( obj.selectors.notGoingButton ).trigger( 'click' );

			expect( requestMock ).toHaveBeenCalledWith(
				expect.objectContaining( {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: 99,
					step: 'not-going',
					nonce: 'test-nonce',
				} ),
				$container
			);
		} );

		it( 'routes to ARI when IAC is active', () => {
			const $container = jQuery(
				'<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="99" data-iac="allowed">' +
					'<button class="tribe-tickets__rsvp-actions-button-not-going"></button>' +
				'</div>'
			);

			obj.bindNotGoing( $container );
			$container.find( obj.selectors.notGoingButton ).trigger( 'click' );

			expect( requestMock ).toHaveBeenCalledWith(
				expect.objectContaining( {
					action: 'tribe_tickets_rsvp_handle',
					ticket_id: 99,
					step: 'ari',
					going: 'not-going',
					nonce: 'test-nonce',
				} ),
				$container
			);
		} );
	} );
} );
