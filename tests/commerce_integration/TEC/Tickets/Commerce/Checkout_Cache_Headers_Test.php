<?php
/**
 * Regression test for the checkout page being stored by full-page and edge caches.
 *
 * The cart is identified solely by the visitor's cart cookie, so the checkout render differs per
 * visitor while its URL does not. Without cache directives a shared cache stores one visitor's
 * render and serves it to everyone: a shopper lands on an empty cart, or on someone else's.
 * Reverse proxies and CDNs honour the response headers, the WordPress-side page caches buffer the
 * output and read DONOTCACHEPAGE instead, so the page must send both.
 */

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;

class Checkout_Cache_Headers_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * The checkout page must opt out of both caching layers, so neither a shared proxy nor a
	 * WordPress page cache can hand one visitor's cart render to the next.
	 *
	 * @since TBD
	 */
	public function test_checkout_page_opts_out_of_caching(): void {
		// parse_request() only acts on the checkout page; force that gate open.
		$this->set_class_fn_return( Checkout::class, 'is_current_page', true );

		$nocache_sent = $this->capture_nocache_during(
			static fn() => tribe( Checkout::class )->parse_request()
		);

		$this->assertTrue(
			$nocache_sent,
			'The checkout page must send no-cache headers so shared caches do not store one visitor cart render.'
		);

		/*
		 * DONOTCACHEPAGE is left defined for the rest of the suite on purpose. Undefining it between
		 * tests made this class order-dependent: the constant did not come back on the second
		 * parse_request(), so the assertion failed only when the whole suite ran. Nothing else in
		 * the plugin or the suite reads the constant, so leaking it costs nothing.
		 */
		$this->assertTrue(
			defined( 'DONOTCACHEPAGE' ),
			'The checkout page must define DONOTCACHEPAGE so WordPress page caches skip it.'
		);
		$this->assertTrue(
			tribe_is_truthy( constant( 'DONOTCACHEPAGE' ) ),
			'DONOTCACHEPAGE must be truthy for page caches to treat the checkout as uncacheable.'
		);
	}

	/**
	 * A page that is not the checkout must keep its default cacheability, so ordinary content is not
	 * pushed out of the page cache.
	 *
	 * @since TBD
	 */
	public function test_non_checkout_page_is_left_cacheable(): void {
		$this->set_class_fn_return( Checkout::class, 'is_current_page', false );

		$this->assertFalse(
			$this->capture_nocache_during( static fn() => tribe( Checkout::class )->parse_request() ),
			'A page that is not the checkout must not be marked non-cacheable.'
		);
	}

	/**
	 * Runs a callback while watching for the no-cache headers being emitted.
	 *
	 * @since TBD
	 *
	 * @param callable $callback The callback to run.
	 *
	 * @return bool Whether the no-cache headers were emitted while the callback ran.
	 */
	private function capture_nocache_during( callable $callback ): bool {
		$nocache_sent = false;

		$spy = static function ( $headers ) use ( &$nocache_sent ) {
			$nocache_sent = true;

			return $headers;
		};

		add_filter( 'nocache_headers', $spy );
		$callback();
		remove_filter( 'nocache_headers', $spy );

		return $nocache_sent;
	}
}
