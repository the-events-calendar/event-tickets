<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Gateways\PayPal\Status;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Success;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class Order Endpoint.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	protected $path = '/commerce/paypal/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.1.9
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_create_order' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z]+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->update_order_args(),
				'callback'            => [ $this, 'handle_update_order' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'args'                => $this->fail_order_args(),
				'callback'            => [ $this, 'handle_fail_order' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since 5.1.9
	 * @since 5.6.4 Include Event/Post title in the Ticket name.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_create_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$messages = $this->get_error_messages();
		$data = $request->get_json_params();
		$purchaser = tribe( Order::class )->get_purchaser_data( $data );

		if ( is_wp_error( $purchaser ) ) {
			return $purchaser;
		}

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );

		if ( ! $order ) {
			return new WP_Error( 'tec-tc-gateway-paypal-failed-creating-order', $messages['failed-creating-order'], $order );
		}

		$unit = [
			'reference_id' => $order->ID,
			'value'        => (string) $order->total_value->get_decimal(),
			'currency'     => $order->currency,
			'first_name'   => $order->purchaser['first_name'],
			'last_name'    => $order->purchaser['last_name'],
			'email'        => $order->purchaser['email'],
		];

		foreach ( $order->items as $item ) {
			$unit['items'][] = $this->get_unit_data( $item, $order );
		}

		$paypal_order = tribe( Client::class )->create_order( $unit );

		if ( empty( $paypal_order['id'] ) || empty( $paypal_order['create_time'] ) ) {
			return new WP_Error( 'tec-tc-gateway-paypal-failed-creating-order', $messages['failed-creating-order'], $order );
		}

		$debug_header = tribe( Client::class )->get_debug_header();
		if ( ! empty( $debug_header ) ) {
			$paypal_order['debug_id'] = $debug_header;
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG, [
			'gateway_payload'  => $paypal_order,
			'gateway_order_id' => $paypal_order['id'],
		] );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		// Respond with the ID for Paypal Usage.
		$response['success'] = true;
		$response['id']      = $paypal_order['id'];

		return new WP_REST_Response( $response );
	}

	/**
	 * Retrieves the unit data for an item in the cart.
	 *
	 * By default, the item type will be considered a 'ticket' if not specified.
	 * This method handles different item types with a switch case, providing custom logic
	 * for 'ticket' and a default behavior for other item types.
	 * An overarching filter allows for customization of the final returned data.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $item The cart item for which to retrieve unit data.
	 * @param WP_Post $order The order from the items in the cart.
	 *
	 * @return array The structured data for the item, including 'name', 'unit_amount', 'quantity', 'item_total', and
	 *     'sku'.
	 */
	public function get_unit_data( array $item, WP_Post $order ) {
		if ( ! $order->ID ) {
			return [];
		}

		$type = $item['type'] ?? 'ticket';

		switch ( $type ) {
			case 'ticket':
				$unit_data = $this->get_unit_data_for_ticket( $item, $order );
				break;

			default:
				/**
				 * Filters the unit data for custom item types in the cart.
				 *
				 * This filter allows external developers to generate and customize the unit data
				 * for items in the cart based on the item type (other than 'ticket').
				 *
				 * The filter name is dynamic and uses the item type (`$type`) to provide flexibility for
				 * different item categories.
				 *
				 * Example: If `$type` is 'fee', the filter will be `tec_commerce_get_unit_data_fee`.
				 *
				 * @since 5.18.0
				 *
				 * @param array   $item   The cart item for which the unit data is being generated.
				 * @param WP_Post $order  The current order object.
				 *
				 * @return array The unit data for the item.
				 */
				$unit_data = apply_filters( "tec_tickets_commerce_paypal_order_get_unit_data_{$type}", $item, $order );
				break;
		}

		/**
		 * Filters the unit data for an item in REST context.
		 *
		 * @since 5.18.0
		 *
		 * @param array   $unit_data The structured data for the item.
		 * @param array   $item      The order item for which the unit data is being generated.
		 * @param WP_Post $order     The current order object.
		 */
		return apply_filters( 'tec_tickets_commerce_paypal_order_get_unit_data', $unit_data, $item, $order );
	}

	/**
	 * Retrieves the default unit data for a ticket in the cart.
	 *
	 * This method is used when the item type is 'ticket', and it structures the data
	 * for a ticket item, including details such as name, price, quantity, and SKU.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $item The cart item (representing the ticket).
	 * @param WP_Post $order The order from the items in the cart.
	 *
	 * @return array<string,mixed> The structured data for the ticket item.
	 */
	protected function get_unit_data_for_ticket( array $item, WP_Post $order ) {
		if ( ! $order->ID ) {
			return [];
		}

		// Default ticket logic.
		$ticket     = Tickets::load_ticket_object( $item['ticket_id'] );
		$post_title = get_the_title( $item['event_id'] );
		$item_name  = "{$ticket->name} - {$post_title}";

		return [
			'name'        => $this->format_order_item_name( $item_name ),
			'unit_amount' => [
				'value'         => (string) $item['price'],
				'currency_code' => $order->currency,
			],
			'quantity'    => (string) $item['quantity'],
			'item_total'  => [
				'value'         => (string) $item['sub_total'],
				'currency_code' => $order->currency,
			],
			'sku'         => $ticket->sku,
		];
	}

	/**
	 * Handles the request that updates an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since 5.1.9
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_update_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$messages = $this->get_error_messages();

		$paypal_order_id = $request->get_param( 'order_id' );

		$order = tec_tc_orders()->by_args( [
			'status'           => tribe( Pending::class )->get_wp_slug(),
			'gateway_order_id' => $paypal_order_id,
		] )->first();

		if ( ! $order ) {
			return new WP_Error( 'tec-tc-gateway-paypal-nonexistent-order-id', $messages['nonexistent-order-id'], $order );
		}

		$recheck = $request->get_param( 'recheck' );

		if ( $recheck ) {
			return $this->handle_recheck_order( $paypal_order_id, $order );
		}

		$payer_id = $request->get_param( 'payer_id' );

		$paypal_capture_response = tribe( Client::class )->capture_order( $paypal_order_id, $payer_id );

		$debug_header = tribe( Client::class )->get_debug_header();
		if ( ! empty( $debug_header ) ) {
			$paypal_capture_response['debug_id'] = $debug_header;
		}

		if (
			'UNPROCESSABLE_ENTITY' === Arr::get( $paypal_capture_response, 'name' )
		) {
			// Flag the order as Denied.
			tribe( Order::class )->modify_status( $order->ID, Denied::SLUG, [
				'gateway_payload' => $paypal_capture_response,
			] );

			return new WP_Error( 'tec-tc-gateway-paypal-failed-capture', $messages['failed-capture'], $paypal_capture_response );
		}

		$response['success']  = true;
		$response['order_id'] = $paypal_order_id;

		return new WP_REST_Response( $response );
	}

	/**
	 * Gets the Order object again, in another request, to check for purchases possibly denied after creation.
	 *
	 * @since 5.4.0.2
	 *
	 * @param string   $order_id The PayPal order ID.
	 * @param WP_Post $order    The TC Order object.
	 *
	 * @return bool|WP_Error|WP_REST_Response
	 */
	public function handle_recheck_order( $order_id, $order ) {

		$paypal_order_response       = tribe( Client::class )->get_order( $order_id );
		$paypal_order_status         = Arr::get( $paypal_order_response, [ 'status' ] );
		$paypal_order_purchase_units = Arr::get( $paypal_order_response, [ 'purchase_units' ], [] );
		$paypal_order_captures       = [];
		$messages                    = $this->get_error_messages();

		foreach( $paypal_order_purchase_units as $unit ) {
			if ( ! empty( $unit['payments']['captures'] ) ) {
				$paypal_order_captures[] = $unit['payments']['captures'];
			}
		}

		if ( Status::CREATED === $paypal_order_status && ! empty( $paypal_order_captures ) ) {
			$paypal_order_captures = array_shift( $paypal_order_captures );
			if ( count( $paypal_order_captures ) > 1 ) {
				// Sort the captures array by the update timestamp
				usort( $paypal_order_captures, function( $a, $b ) {
					return strtotime( $a['update_time'] ) <=> strtotime( $b['update_time'] );
				} );
			}

			foreach( $paypal_order_captures as $capture ) {
				$paypal_order_status = $capture['status'];
				$final = $capture['final_capture'] ?? false;

				if ( $final ) {
					break;
				}
			}
		}

		$status = tribe( Status::class )->convert_to_commerce_status( $paypal_order_status );

		if ( ! $status ) {
			return new WP_Error( 'tec-tc-gateway-paypal-invalid-capture-status', $messages['invalid-capture-status'], $paypal_order_response );
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, $status->get_slug(), [
			'gateway_payload' => $paypal_order_response,
		] );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		if ( in_array( $paypal_order_status, [ Status::FAILED, Status::DECLINED ], true ) ) {
			return new WP_Error( 'tec-tc-gateway-paypal-capture-declined', $messages['capture-declined'], $paypal_order_response );
		}

		$response['success']  = true;
		$response['status']   = $status->get_slug();
		$response['order_id'] = $order->ID;

		// When we have success we clear the cart.
		tribe( Cart::class )->clear_cart();

		$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $order_id ], tribe( Success::class )->get_url() );

		return new WP_REST_Response( $response );
	}

	/**
	 * Handles the request that handles failing an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_fail_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$paypal_order_id = $request->get_param( 'order_id' );
		$order           = tec_tc_orders()->by_args( [
			'status'           => 'any',
			'gateway_order_id' => $paypal_order_id,
		] )->first();

		$messages = $this->get_error_messages();

		if ( ! $order ) {
			return new WP_Error( 'tec-tc-gateway-paypal-nonexistent-order-id', null, $order );
		}

		$failed_reason = $request->get_param( 'failed_reason' );
		$failed_status = $request->get_param( 'failed_status' );
		if ( empty( $failed_status ) ) {
			$failed_status = 'not-completed';
		}

		$status = tribe( Status_Handler::class )->get_by_slug( $failed_status );

		if ( ! $status ) {
			return new WP_Error( 'tec-tc-gateway-paypal-invalid-failed-status', null, [
				'failed_status' => $failed_status,
				'failed_reason' => $failed_reason
			] );
		}

		/**
		 * @todo possible determine if we should have error code associated with the failing of this order.
		 */
		$updated = tribe( Order::class )->modify_status( $order->ID, $status->get_slug() );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$response['success']  = true;
		$response['status']   = $status->get_slug();
		$response['order_id'] = $order->ID;
		$response['title']    = $messages['canceled-creating-order'];

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the signup redirect.
	 *
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function create_order_args() {
		return [];
	}

	/**
	 * Arguments used for the updating order for PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function update_order_args() {
		return [
			'order_id' => [
				'description'       => __( 'Order ID in PayPal', 'event-tickets' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The order ID argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'payer_id' => [
				'description'       => __( 'Payer ID token from PayPal', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The payer ID argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Arguments used for the deleting order for PayPal.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 */
	public function fail_order_args() {
		return [
			'order_id'      => [
				'description'       => __( 'Order ID in PayPal', 'event-tickets' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The order ID argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'failed_status' => [
				'description'       => __( 'To which status the failing should change this order to', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The failed status argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'failed_reason' => [
				'description'       => __( 'Why this particular order has failed.', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The failed reason argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @since 5.1.9
	 *
	 * @param mixed $value Value of the 'filter' argument.
	 *
	 * @return string|array
	 */
	public function sanitize_callback( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Returns an array of error messages that are used by the API responses.
	 *
	 * @since 5.2.0
	 *
	 * @return array $messages Array of error messages.
	 */
	public function get_error_messages() {
		$messages = [
			'failed-creating-order'   => __( 'Creating new PayPal order failed. Please try again.', 'event-tickets' ),
			'canceled-creating-order' => __( 'Your PayPal order was cancelled.', 'event-tickets' ),
			'nonexistent-order-id'    => __( 'Provided Order id is not valid.', 'event-tickets' ),
			'failed-capture'          => __( 'There was a problem while processing your payment, please try again.', 'event-tickets' ),
			'capture-declined'        => __( 'Your payment was declined.', 'event-tickets' ),
			'invalid-capture-status'  => __( 'There was a problem with the Order status change, please try again.', 'event-tickets' ),
		];

		/**
		 * Filter the error messages for PayPal checkout.
		 *
		 * @since 5.2.0
		 *
		 * @param array $messages Array of error messages.
		 */
		return apply_filters( 'tec_tickets_commerce_order_endpoint_error_messages', $messages );
	}

	/**
	 * Formats the order item name by truncating it to a specified length.
	 * If the text exceeds the maximum character length, it is truncated at the last space
	 * within the limit and an ellipsis is added at the end.
	 *
	 * @since 5.6.5
	 *
	 * @param string $text The original order item name text.
	 *
	 * @return string The formatted order item name text.
	 */
	public function format_order_item_name( string $text ): string {
		$max_character_length = 127;
		$ellipsis             = '...';
		$truncate_length      = $max_character_length - strlen( $ellipsis );

		if ( strlen( $text ) <= $max_character_length ) {
			return $text;
		}

		// Cut the text to the desired length
		$truncated_text = substr( $text, 0, $truncate_length );

		// Find the last space within the truncated text
		$last_space = strrpos( $truncated_text, ' ' );

		// Cut the text at the last space to avoid cutting in the middle of a word
		if ( $last_space !== false ) {
			$truncated_text = substr( $truncated_text, 0, $last_space );
		}

		// Add an ellipsis at the end
		$truncated_text .= $ellipsis;

		return $truncated_text;
	}
}
