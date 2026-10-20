/**
 * PayPal only tells the card form that a buyer closed their bank's 3D Secure verification: the order
 * it keeps carries no authentication result. The checkout has to pass that on, or the server captures.
 */

const $ = require( 'jquery' );

const orderEndpoint = '/wp-json/tribe/tickets/v1/commerce/paypal/order';
const failedAuthentication = 'Your card could not be verified by your bank, so no payment was taken. Please try again.';

const setup = () => {
	document.body.innerHTML = `
		<div class="tribe-tickets__commerce-checkout" data-js="tec-tickets-commerce-notice">
			<input type="hidden" id="tec-tc-checkout-nonce" value="nonce" />
			<div class="tribe-tickets__commerce-checkout-notice" style="display: none">
				<h3 class="tribe-tickets-notice__title"></h3>
				<div class="tribe-tickets__commerce-checkout-notice-content"></div>
			</div>
			<input type="text" id="tec-tc-card-holder-name" value="Three D Secure" />
		</div>
	`;

	/* tribe-common adds this to every page, and checkout.js reads selectors through it. */
	// eslint-disable-next-line no-extend-native
	String.prototype.className = function () {
		return this.replace( '.', '' );
	};

	global.tribe = {
		tickets: {
			commerce: {
				gateway: {},
				selectors: {
					checkoutContainer: '.tribe-tickets__commerce-checkout',
					nonce: '#tec-tc-checkout-nonce',
				},
				customEvents: {
					showLoader: 'showLoader.tecTicketsCommerce',
					hideLoader: 'hideLoader.tecTicketsCommerce',
				},
			},
			debug: { log: jest.fn() },
			loader: { show: jest.fn(), hide: jest.fn() },
		},
	};
	window.tribe = global.tribe;
	global.tecTicketsCommerceGatewayPayPalCheckout = {
		orderEndpoint,
		advancedPayments: {},
		requestFailedMessage: 'Request failed.',
	};
	global.fetch = jest.fn();

	jest.isolateModules( () => {
		require( '../../../notice' );
		require( '../checkout' );
	} );

	return window.tribe.tickets.commerce.gateway.paypal;
};

const respondWith = ( status, body ) =>
	global.fetch.mockResolvedValue( {
		ok: status < 400,
		status,
		json: () => Promise.resolve( body ),
	} );

const submitCardForm = async ( checkout, payload ) => {
	const cardFields = { submit: jest.fn().mockResolvedValue( payload ) };

	checkout.onHostedSubmit( { preventDefault: jest.fn() }, cardFields, $( '.tribe-tickets__commerce-checkout' ) );

	/* Lets the submit, fetch and json() promises settle. */
	await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
};

const sentBody = () => JSON.parse( global.fetch.mock.calls[ 0 ][ 1 ].body );

describe( 'PayPal card form 3D Secure result', () => {
	it.each( [
		[ 'the buyer skipped the bank verification', 'NO', 'SKIPPED_BY_BUYER' ],
		[ 'the bank verification failed', 'NO', undefined ],
		[ 'the bank verified the buyer', 'POSSIBLE', undefined ],
	] )( 'sends the liability shift to the server when %s', async ( _, liabilityShift, authenticationReason ) => {
		const checkout = setup();
		const orderId = 'ORDER-' + liabilityShift;
		respondWith( 402, { code: 'tec-tc-gateway-paypal-failed-authentication', message: failedAuthentication } );

		await submitCardForm( checkout, { orderId, liabilityShift, authenticationReason } );

		expect( global.fetch.mock.calls[ 0 ][ 0 ] ).toBe( orderEndpoint + '/' + orderId );
		expect( sentBody() ).toEqual( { advanced_payment: true, liability_shift: liabilityShift } );
	} );

	it( 'sends no liability shift when PayPal ran no 3D Secure', async () => {
		const checkout = setup();
		respondWith( 402, { message: failedAuthentication } );

		await submitCardForm( checkout, { orderId: 'ORDER-NO-3DS' } );

		expect( sentBody() ).toEqual( { advanced_payment: true } );
	} );

	it( 'shows the denial to the buyer and ends the payment', async () => {
		const checkout = setup();
		respondWith( 402, { code: 'tec-tc-gateway-paypal-failed-authentication', message: failedAuthentication } );

		await submitCardForm( checkout, { orderId: 'ORDER-SKIPPED', liabilityShift: 'NO', authenticationReason: 'SKIPPED_BY_BUYER' } );

		expect( $( '.tribe-tickets__commerce-checkout-notice-content' ).text() ).toBe( failedAuthentication );
		expect( $( '.tribe-tickets__commerce-checkout-notice' ).css( 'display' ) ).not.toBe( 'none' );
		expect( checkout.paymentInFlight ).toBe( false );
		expect( global.tribe.tickets.loader.hide ).toHaveBeenCalled();
	} );
} );
