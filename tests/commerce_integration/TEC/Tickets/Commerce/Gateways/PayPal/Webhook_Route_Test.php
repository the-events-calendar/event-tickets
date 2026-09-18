<?php
/**
 * Regression test for the PayPal webhook route being served.
 *
 * Tickets Commerce registers a webhook with PayPal pointing at this route, from the onboarding
 * endpoint and from the "refresh webhook" admin action. The route itself was never registered with
 * WordPress, so PayPal delivered every event to a URL that answered 404 and nothing on the site ever
 * heard that a payment had been captured. The browser redirect was then the only thing that could
 * complete an order, and a buyer who lost it was stranded in Pending with no attendee.
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\PayPal\REST\Webhook_Endpoint;
use Tribe__Utils__Array as Arr;

class Webhook_Route_Test extends WPTestCase {

	/**
	 * The route PayPal is told to post to has to exist, or every delivery is a 404.
	 */
	public function test_webhook_route_is_registered(): void {
		$this->assertArrayHasKey(
			$this->webhook_route(),
			$this->registered_routes(),
			'The PayPal webhook route must be registered, because PayPal is told to deliver events to it.'
		);
	}

	/**
	 * The route has to accept the POST PayPal actually sends.
	 */
	public function test_webhook_route_accepts_post(): void {
		$routes = $this->registered_routes();

		$methods = [];
		foreach ( $routes[ $this->webhook_route() ] as $handler ) {
			$methods += Arr::get( $handler, 'methods', [] );
		}

		$this->assertArrayHasKey(
			'POST',
			$methods,
			'PayPal delivers webhook events by POST, so the route must accept it.'
		);
	}

	/**
	 * The url handed to PayPal when the webhook is registered must be the url that is served, or the
	 * two halves point at different places.
	 */
	public function test_registered_webhook_url_matches_the_served_route(): void {
		// Asserted on the whole url: with plain permalinks the route rides in the query string.
		$this->assertStringContainsString(
			$this->webhook_route(),
			tribe( Webhook_Endpoint::class )->get_route_url(),
			'The url registered with PayPal must resolve to the route this plugin serves.'
		);
	}

	/**
	 * Boots the REST routes the way a real request does, and returns them.
	 *
	 * @return array<string,array> The registered routes, keyed by route.
	 */
	private function registered_routes(): array {
		// rest_get_server() fires rest_api_init, which is where the gateway registers its endpoints.
		return rest_get_server()->get_routes();
	}

	/**
	 * The namespaced route PayPal posts webhook events to.
	 */
	private function webhook_route(): string {
		$namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();

		return '/' . $namespace . '/commerce/paypal/webhook';
	}
}
