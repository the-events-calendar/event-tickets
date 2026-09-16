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
	 * under supplementary data. Reading it there settles the common case without a round trip to PayPal
	 * at all. Only when it is absent is a link followed, and then only a link back to PayPal's own API.
	 *
	 * This used to look for a link with the `parent_payment` relation, which belongs to Payments v1. No
	 * v2 capture carries one, so it resolved nothing for any real delivery.
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
		$link  = is_array( $links ) ? $this->get_order_link( $links ) : '';

		if ( ! $link ) {
			return '';
		}

		$payment = tribe( Client::class )->request( 'GET', $link );

		if ( ! is_array( $payment ) || empty( $payment['id'] ) || ! is_string( $payment['id'] ) ) {
			return '';
		}

		return $payment['id'];
	}

	/**
	 * Finds the link in an event's resource that points back at the order it belongs to.
	 *
	 * `up` is what a Payments v2 capture carries; `parent_payment` is its v1 predecessor and is accepted
	 * so an older payload still resolves. The link is required to address PayPal's own API, because the
	 * request made with it carries this merchant's access token and an event is not a trusted source of
	 * hostnames.
	 *
	 * @since TBD
	 *
	 * @param array $links The links on the event's resource.
	 *
	 * @return string The link to follow, or an empty string when there is none worth following.
	 */
	protected function get_order_link( array $links ): string {
		$api_base = tribe( Client::class )->get_environment_url();

		foreach ( [ 'up', 'parent_payment' ] as $relation ) {
			foreach ( $links as $link ) {
				if ( ! is_array( $link ) || $relation !== Arr::get( $link, 'rel' ) ) {
					continue;
				}

				$href = Arr::get( $link, 'href', '' );

				if ( is_string( $href ) && 0 === strpos( $href, $api_base . '/' ) ) {
					return $href;
				}
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
