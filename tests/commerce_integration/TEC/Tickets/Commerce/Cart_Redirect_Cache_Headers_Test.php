<?php
/**
 * Regression test for the cart-to-checkout redirect dropping the cart-hash cookie behind edge caches.
 *
 * Since SVUL-L34 the cart hash is carried across the redirect only by the server-set cart cookie. A
 * cacheable 302 lets full-page and edge caches (e.g. Pantheon, Varnish) cache the redirect and strip
 * its Set-Cookie header, so the checkout page then loads with no cart. The redirect must therefore be
 * sent with no-cache headers.
 */

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;

class Cart_Redirect_Cache_Headers_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * The cart-to-checkout redirect must send no-cache headers before redirecting so edge caches keep
	 * the cart-hash Set-Cookie header intact.
	 *
	 * @since TBD
	 */
	public function test_cart_to_checkout_redirect_sends_nocache_headers_before_redirecting(): void {
		$state = $this->run_redirect_capturing_cache_state();

		$this->assertTrue(
			$state['redirected'],
			'The cart page in redirect mode must redirect to checkout.'
		);
		$this->assertSame(
			tribe( Checkout::class )->get_url(),
			$state['url'],
			'The redirect must target the Tickets Commerce checkout page.'
		);
		$this->assertTrue(
			$state['nocache_before_redirect'],
			'No-cache headers must be sent before the cart-to-checkout redirect so edge caches do not strip the cart cookie.'
		);
	}

	/**
	 * Drives the real Cart::parse_request() redirect branch, capturing whether the no-cache headers
	 * were emitted and whether that happened before the redirect fired.
	 *
	 * @since TBD
	 *
	 * @return array{nocache_before_redirect: bool, redirected: bool, url: string|null}
	 */
	private function run_redirect_capturing_cache_state(): array {
		$state = [
			'nocache_before_redirect' => false,
			'redirected'              => false,
			'url'                     => null,
		];

		$nocache_sent = false;

		// parse_request() only redirects on the cart page in redirect mode; force both gates open.
		$this->set_class_fn_return( Cart::class, 'is_current_page', true );
		$this->set_class_fn_return( Cart::class, 'get_mode', Cart::REDIRECT_MODE );

		add_filter(
			'nocache_headers',
			static function ( $headers ) use ( &$nocache_sent ) {
				$nocache_sent = true;

				return $headers;
			}
		);

		$this->set_fn_return( 'tribe_exit', true );
		$this->set_fn_return(
			'wp_safe_redirect',
			function ( $url ) use ( &$state, &$nocache_sent ) {
				$state['nocache_before_redirect'] = $nocache_sent;
				$state['redirected']              = true;
				$state['url']                     = $url;

				return true;
			},
			true
		);

		tribe( Cart::class )->parse_request();

		return $state;
	}
}
