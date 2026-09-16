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
	protected function get_gateway_order_id( array $event ): string {
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
	 * Only a link to this environment's own `/v2/checkout/orders/` is read. A capture's links also point
	 * at the capture, the refund and, for an authorized payment, the authorization, and the id in any of
	 * those is not an order id: taking one would send the lookup after an order that does not exist. The
	 * host is pinned for the same reason a fetch would need it -- an event is not a trusted source of
	 * urls -- even though nothing is requested here.
	 *
	 * @since TBD
	 *
	 * @param array $links The links on the event's resource.
	 *
	 * @return string The order id, or an empty string when no link addresses an order.
	 */
	protected function get_order_id_from_links( array $links ): string {
		$orders_url = tribe( Client::class )->get_environment_url() . '/v2/checkout/orders/';
		$pattern    = '~^' . preg_quote( $orders_url, '~' ) . '(?<order_id>[A-Za-z0-9_-]+)/?$~';

		foreach ( $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$href = Arr::get( $link, 'href', '' );

			if ( is_string( $href ) && preg_match( $pattern, $href, $matches ) ) {
				return $matches['order_id'];
			}
		}

		return '';
	}

	/**
	 * Process a given PayPal Webhook event, possibly updating the local order with the status sent by the request.
	 *
	 * @since 5.1.10
	 *
	 * @since TBD Reads the parent payment link from `href`, which is the key PayPal sends it under.
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

			return new WP_Error( 'tec-tickets-commerce-paypal-webhook-order-status-already-updated', null, [
				'gateway_order_id' => $gateway_order_id,
				'order'          => $order,
				'new_status'     => $new_status,
				'event'          => $event
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
