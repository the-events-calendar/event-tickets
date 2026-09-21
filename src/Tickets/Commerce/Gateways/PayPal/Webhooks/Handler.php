<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Order;
use Tribe__Utils__Array as Arr;
use WP_Error;

/**
 * Class Handler
 *
 * @since 5.1.10
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks
 */
class Handler {

	/**
	 * Gets the parent payment link from the list of Links on the response.
	 *
	 * @since 5.1.10
	 * @deprecated TBD The `parent_payment` relation belongs to Payments v1 and appears on none of the v2
	 *             events this gateway subscribes to.
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	protected function get_parent_payment_link( $links ) {
		_deprecated_function( __METHOD__, 'TBD' );

		return current( array_filter( $links, static function ( $link ) {
			return 'parent_payment' === $link['rel'];
		} ) );
	}

	/**
	 * Resolves the PayPal order id an event belongs to.
	 *
	 * The events this gateway subscribes to are Payments v2, whose captures carry the order id outright
	 * under supplementary data. Failing that, the resource's links are read: one of them addresses the
	 * order, and the id is the last segment of that url, so it can be taken straight from the payload.
	 *
	 * Nothing is fetched from PayPal either way. This used to follow a link with the `parent_payment`
	 * relation, which belongs to Payments v1 and appears on no v2 event, so it resolved nothing at all.
	 *
	 * @since TBD
	 *
	 * @param array $event The PayPal payment event object.
	 *
	 * @return string The PayPal order id, or an empty string when it cannot be resolved.
	 */
	private function get_gateway_order_id( array $event ): string {
		$order_id = Arr::get( $event, [ 'resource', 'supplementary_data', 'related_ids', 'order_id' ], '' );

		if ( is_string( $order_id ) && '' !== $order_id ) {
			return $order_id;
		}

		$links = Arr::get( $event, [ 'resource', 'links' ], [] );

		return is_array( $links ) ? $this->get_order_id_from_links( $links ) : '';
	}

	/**
	 * Reads the order id out of whichever of an event's links addresses an order.
	 *
	 * Only a link to this environment's own orders endpoint is read. A capture's links also point at the
	 * capture, the refund and, for an authorized payment, the authorization, and the id in any of those
	 * is not an order id: taking one would send the lookup after an order that does not exist.
	 *
	 * @since TBD
	 *
	 * @param array $links The links on the event's resource.
	 *
	 * @return string The order id, or an empty string when no link addresses an order.
	 */
	private function get_order_id_from_links( array $links ): string {
		foreach ( $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$href = Arr::get( $link, 'href', '' );

			if ( ! is_string( $href ) ) {
				continue;
			}

			$order_id = $this->get_order_id_from_url( $href );

			if ( '' !== $order_id ) {
				return $order_id;
			}
		}

		return '';
	}

	/**
	 * Reads the order id out of a url, when that url addresses an order on PayPal's own API.
	 *
	 * The host is pinned because an event is not a trusted source of urls. It is matched rather than
	 * prefixed, because PayPal serves the same API under two names and its v2 webhook links use the
	 * `api-m` one while the rest of this gateway talks to `api`.
	 *
	 * @since TBD
	 *
	 * @param string $url The url to read.
	 *
	 * @return string The order id, or an empty string when the url does not address an order.
	 */
	private function get_order_id_from_url( string $url ): string {
		$parts = wp_parse_url( $url );

		if ( 'https' !== strtolower( Arr::get( $parts, 'scheme', '' ) ) ) {
			return '';
		}

		if ( ! in_array( strtolower( Arr::get( $parts, 'host', '' ) ), $this->get_api_hosts(), true ) ) {
			return '';
		}

		if ( ! preg_match( '~^/v2/checkout/orders/(?<order_id>[A-Za-z0-9_-]+)/?$~', Arr::get( $parts, 'path', '' ), $matches ) ) {
			return '';
		}

		return $matches['order_id'];
	}

	/**
	 * The hostnames PayPal serves this environment's API under.
	 *
	 * @since TBD
	 *
	 * @return array<int,string>
	 */
	private function get_api_hosts(): array {
		$host = wp_parse_url( tribe( Client::class )->get_environment_url(), PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return [];
		}

		$host = strtolower( $host );

		return array_unique( [ $host, str_replace( 'api.', 'api-m.', $host ) ] );
	}

	/**
	 * Process a given PayPal Webhook event, possibly updating the local order with the status sent by the request.
	 *
	 * @since 5.1.10
	 *
	 * @since TBD Resolves the order from the event itself instead of following a `parent_payment` link,
	 *        and answers a redelivery of a settled order as a success so PayPal stops retrying it.
	 *
	 * @param array $event The PayPal payment event object.
	 *
	 * @return \WP_Post|WP_Error Whether the event was processed successfully.
	 */
	public function process_event( $event ) {
		// Invalid event.
		if ( empty( $event['event_type'] ) || empty( $event['resource'] ) ) {
			return new WP_Error( 'tec-tickets-commerce-paypal-webhook-invalid-payload', null, [ 'event' => $event ] );
		}

		// Check if the event type matches.
		if ( ! tribe( Events::class )->is_valid( $event['event_type'] ) ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment event.
					__( 'Invalid event type for webhook event: %s', 'event-tickets' ),
					wp_json_encode( $event )
				),
				'tickets-commerce-gateway-paypal'
			);

			return new WP_Error( 'tec-tickets-commerce-paypal-webhook-invalid-type', null, [ 'event' => $event ] );
		}

		$new_status = tribe( Events::class )->convert_to_commerce_status( $event['event_type'] );

		$gateway_order_id = $this->get_gateway_order_id( $event );

		if ( '' === $gateway_order_id ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment event.
					__( 'No PayPal order could be resolved for webhook event: %s', 'event-tickets' ),
					wp_json_encode( $event )
				),
				'tickets-commerce-gateway-paypal'
			);

			return new WP_Error( 'tec-tickets-commerce-paypal-webhook-unresolved-order', null, [ 'event' => $event ] );
		}

		$order = tec_tc_orders()->by_args( [
			'status'           => 'any',
			'gateway_order_id' => $gateway_order_id,
		] )->first();

		// If there's no matching payment then it's not tracked by Tickets Commerce.
		if ( ! $order instanceof \WP_Post ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment ID.
					__( 'Missing order for PayPal payment from webhook: %s', 'event-tickets' ),
					$gateway_order_id
				),
				'tickets-commerce-gateway-paypal'
			);

			return new WP_Error( 'tec-tickets-commerce-paypal-webhook-order-not-found', null, [
				'gateway_order_id' => $gateway_order_id,
				'event'            => $event,
			] );
		}

		// Don't do anything if the status is already set.
		if ( $new_status->get_wp_slug() === $order->post_status ) {
			tribe( 'logger' )->log_debug(
				sprintf(
				// Translators: %s: The PayPal payment ID.
					__( 'PayPal Order "%1$s" already on status "%2$s" from webhook: %3$s', 'event-tickets' ),
					$gateway_order_id,
					$new_status->get_slug(),
					wp_json_encode( $event )
				),
				'tickets-commerce-gateway-paypal'
			);

			/*
			 * Answered as success: the delivery was understood and the order is already where it asks
			 * for. Without a status this fell back to 500, which PayPal reads as a failure and retries --
			 * and every retry finds the same settled order, so the redelivery never stops.
			 */
			return new WP_Error( 'tec-tickets-commerce-paypal-webhook-order-status-already-updated', null, [
				'status'           => 200,
				'gateway_order_id' => $gateway_order_id,
				'new_status'       => $new_status->get_slug(),
			] );
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, $new_status->get_slug(), [
			'gateway_payload' => $event,
		] );

		tribe( 'logger' )->log_debug(
			sprintf(
			// Translators: %1$s: The status name; %2$s: The payment information.
				__( 'Change %1$s in PayPal from webhook: %2$s', 'event-tickets' ),
				$new_status->get_slug(),
				sprintf( '[Order ID: %s; PayPal Order ID: %s]', $order->ID, $gateway_order_id )
			),
			'tickets-commerce-gateway-paypal'
		);

		return $updated;
	}
}
