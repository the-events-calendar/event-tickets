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
	 * Whether DONOTCACHEPAGE was already defined when the test started.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private $donotcachepage_was_defined = false;

	/**
	 * @before
	 */
	public function record_donotcachepage_state(): void {
		$this->donotcachepage_was_defined = defined( 'DONOTCACHEPAGE' );
	}

	/**
	 * @after
	 */
	public function restore_donotcachepage_state(): void {
		if ( $this->donotcachepage_was_defined || ! defined( 'DONOTCACHEPAGE' ) ) {
			return;
		}

		uopz_undefine( 'DONOTCACHEPAGE' );
	}

	/**
	 * The checkout page must send no-cache headers so proxies and CDNs never store a render that
	 * belongs to one visitor's cart cookie.
	 *
	 * @since TBD
	 */
	public function test_checkout_page_sends_nocache_headers(): void {
		$this->assertTrue(
			$this->run_checkout_parse_request_capturing_nocache(),
			'The checkout page must send no-cache headers so shared caches do not store one visitor cart render.'
		);
	}

	/**
	 * The checkout page must also opt out of the WordPress-side page caches, which buffer output and
	 * never see the response headers.
	 *
	 * @since TBD
	 */
	public function test_checkout_page_defines_donotcachepage(): void {
		$this->run_checkout_parse_request_capturing_nocache();

		$this->assertTrue(
			defined( 'DONOTCACHEPAGE' ) && true === constant( 'DONOTCACHEPAGE' ),
			'The checkout page must define DONOTCACHEPAGE so WordPress page caches skip it.'
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
	 * Drives the real Checkout::parse_request() on the checkout page.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the no-cache headers were emitted.
	 */
	private function run_checkout_parse_request_capturing_nocache(): bool {
		// parse_request() only acts on the checkout page; force that gate open.
		$this->set_class_fn_return( Checkout::class, 'is_current_page', true );

		return $this->capture_nocache_during( static fn() => tribe( Checkout::class )->parse_request() );
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
