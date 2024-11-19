<?php
/**
 * Coupons API.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\API;

use Exception;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Order_Modifiers\Modifiers\Coupon as Modifier;
use TEC\Tickets\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager as Manager;
use TEC\Tickets\Order_Modifiers\Repositories\Coupons as Coupons_Repository;
use TEC\Tickets\Order_Modifiers\Traits\Coupons as CouponsTrait;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;

/**
 * Class Coupons
 *
 * @since TBD
 */
class Coupons extends Base_API {

	use CouponsTrait;

	/**
	 * The namespace for the API.
	 *
	 * @var string
	 */
	protected string $namespace = 'tribe/tickets/v1';

	/**
	 * TThe modifier manager instance to handle relationship updates.
	 *
	 * @var Manager
	 */
	protected Manager $manager;

	/**
	 * Coupons constructor.
	 *
	 * @since TBD
	 *
	 * @param ?Coupons_Repository $repository The coupons repository.
	 */
	public function __construct( ?Coupons_Repository $repository = null ) {
		$this->repo    = $repository ?? new Coupons_Repository();

		// todo: add this as a constructor arg, in a way that the Container will play nicely with.
		$this->manager = new Manager( new Fee() );
	}

	/**
	 * Register the routes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/coupons',
			[
				[
					'methods'             => Server::READABLE,
					'callback'            => fn( Request $request ) => $this->get_coupons( $request ),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [],
				],
				[
					'methods'             => Server::CREATABLE,
					'callback'            => fn( Request $request ) => $this->create_coupon( $request ),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_endpoint_args( 'create' ),
				],
				'schema' => $this->get_schema(),
			]
		);

		register_rest_route(
			$this->namespace,
			'/coupons/validate',
			[
				'methods'             => Server::CREATABLE,
				'callback'            => [ $this, 'validate_coupon' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_endpoint_args( 'validate' ),
			]
		);

		register_rest_route(
			$this->namespace,
			'/coupons/apply',
			[
				[
					'methods'             => Server::CREATABLE,
					'callback'            => fn( Request $request ) => $this->apply_coupon( $request ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_endpoint_args( 'apply' ),
				],
				'schema' => $this->get_schema(),
			]
		);

		register_rest_route(
			$this->namespace,
			'/coupons/remove',
			[
				[
					'methods'             => Server::CREATABLE,
					'callback'            => fn( Request $request ) => $this->remove_coupon( $request ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_endpoint_args( 'remove' ),
				],
				'schema' => $this->get_schema(),
			]
		);
	}

	/**
	 * Get the coupons.
	 *
	 * @since TBD
	 *
	 * @return Response
	 */
	protected function get_coupons( Request $request ) {
		$coupons  = $this->repo->get_all();
		$response = array_map(
			fn( Order_Modifier $coupon ) => $this->prepare_coupon_for_response( $coupon ),
			$coupons
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Create a coupon.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object.
	 */
	protected function create_coupon( Request $request ) {
		try {
			$data   = $request->get_params();
			$coupon = Coupon::create(
				[
					'slug'             => $data['slug'],
					'display_name'     => $data['display_name'],
					'status'           => $data['status'],
					'fee_amount_cents' => $data['amount'],
					'sub_type'         => $data['sub_type'] ?? 'flat',
					'start_time'       => $data['start_time'] ?? null,
					'end_time'         => $data['end_time'] ?? null,
				]
			);

			return rest_ensure_response( $this->prepare_coupon_for_response( $coupon ) );
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_create_coupon_error',
					$e->getMessage(),
					[
						'status' => $e->getCode() ?: 500,
					]
				)
			);
		}
	}

	/**
	 * Validate a coupon.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object.
	 */
	protected function validate_coupon( Request $request ): Response {
		$coupon_slug = $request->get_param( 'coupon' );

		return rest_ensure_response(
			[
				'valid' => $this->is_coupon_slug_valid( $coupon_slug ),
			]
		);
	}

	/**
	 * Apply a coupon.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object on success, or an error object if an error.
	 */
	protected function apply_coupon( Request $request ): Response {
		try {
			// Get and validate the coupon slug.
			$coupon_slug = $request->get_param( 'coupon' );
			if ( ! $this->is_coupon_slug_valid( $coupon_slug ) ) {
				throw new Exception( esc_html__( 'Invalid coupon.', 'event-tickets' ), 400 );
			}

			$coupon = $this->repo->find_by_slug( $coupon_slug );

			// @todo: Use another method to get the Order class object.
			$order_class = tribe( Order::class );

			$purchaser_data    = $this->get_purchaser_information( $request );
			$payment_intent_id = $request->get_param( 'payment_intent_id' );

			// @todo: paypal gateway also.
			$order = $order_class->create_from_cart( tribe( Gateway::class ), $purchaser_data );
			if ( empty( $order ) ) {
				throw new Exception( esc_html__( 'Could not create order.', 'event-tickets' ), 500 );
			}

			$original_order_value = $order->total_value->get_integer();
			$new_order_value      = max( 0, $original_order_value - $coupon->fee_amount_cents );

			// Update the payment intent with the new value
			Payment_Intent::update(
				$payment_intent_id,
				[
					'amount' => $new_order_value,
				]
			);

			$modifier = new Modifier();

			return rest_ensure_response(
				[
					'discount' => Value::create( $modifier->convert_from_cents( $coupon->fee_amount_cents ) )->get_currency(),
					'message'  => sprintf(
					/* translators: %s: the coupon code */
						esc_html__( 'Coupon "%s" applied successfully.', 'event-tickets' ),
						$coupon->slug
					),
					'amount'   => Value::create( $modifier->convert_from_cents( $new_order_value ) )->get_currency(),
				]
			);
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_apply_coupon_error',
					$e->getMessage(),
					[
						'status' => $e->getCode() ?: 500,
					]
				)
			);
		}
	}

	/**
	 * Remove a coupon.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object on success, or an error object if an error.
	 */
	protected function remove_coupon( Request $request ) {
		try {
			// Get and validate the coupon slug.
			$coupon_slug = $request->get_param( 'coupon' );
			if ( ! $this->is_coupon_slug_valid( $coupon_slug ) ) {
				throw new Exception( esc_html__( 'Invalid coupon.', 'event-tickets' ), 400 );
			}

			$coupon = $this->repo->find_by_slug( $coupon_slug );

			// @todo: Use another method to get the Order class object.
			$order_class = tribe( Order::class );

			$purchaser_data    = $this->get_purchaser_information( $request );
			$payment_intent_id = $request->get_param( 'payment_intent_id' );

			// @todo: paypal gateway also.
			$order = $order_class->create_from_cart( tribe( Gateway::class ), $purchaser_data );
			if ( empty( $order ) ) {
				throw new Exception( esc_html__( 'Could not create order.', 'event-tickets' ), 500 );
			}

			$original_order_value = $order->total_value->get_integer();

			Payment_Intent::update(
				$payment_intent_id,
				[
					'amount' => $original_order_value,
				]
			);

			return rest_ensure_response(
				[
					'message' => sprintf(
					/* translators: %s: the coupon code */
						esc_html__( 'Coupon "%s" removed successfully.', 'event-tickets' ),
						$coupon->slug
					),
					'amount'  => $order->total_value->get_currency(),
				]
			);
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_apply_coupon_error',
					$e->getMessage(),
					[
						'status' => $e->getCode() ?: 500,
					]
				)
			);
		}
	}

	/**
	 * Prepare a coupon for the response.
	 *
	 * @since TBD
	 *
	 * @param Order_Modifier $coupon The coupon.
	 *
	 * @return array
	 */
	protected function prepare_coupon_for_response( Order_Modifier $coupon ) {
		// @todo: better processing of the response.
		$raw_amount = $coupon->fee_amount_cents;

		return [
			'id'         => $coupon->id,
			'slug'       => $coupon->slug,
			'name'       => $coupon->display_name,
			'sub_type'   => $coupon->sub_type,
			'amount'     => $coupon->fee_amount_cents,
			'status'     => $coupon->status,
			'start_time' => $coupon->start_time,
			'end_time'   => $coupon->end_time,
		];
	}

	/**
	 * Get the schema for the API.
	 *
	 * @since TBD
	 *
	 * @return array The schema.
	 */
	protected function get_schema() {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'coupon',
			'type'       => 'object',
			'properties' => [
				'id'         => [
					'description' => esc_html__( 'The coupon ID.', 'event-tickets' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'slug'       => [
					'description' => esc_html__( 'The coupon slug.', 'event-tickets' ),
					'type'        => 'string',
				],
				'name'       => [
					'description' => esc_html__( 'The coupon name.', 'event-tickets' ),
					'type'        => 'string',
				],
				'sub_type'   => [
					'description' => esc_html__( 'The coupon sub type.', 'event-tickets' ),
					'type'        => 'string',
				],
				'amount'     => [
					'description' => esc_html__( 'The coupon amount.', 'event-tickets' ),
					'type'        => 'integer',
				],
				'status'     => [
					'description' => esc_html__( 'The coupon status.', 'event-tickets' ),
					'type'        => 'string',
				],
				'start_time' => [
					'description' => esc_html__( 'The coupon start time.', 'event-tickets' ),
					'type'        => 'string',
					'format'      => 'date-time',
				],
				'end_time'   => [
					'description' => esc_html__( 'The coupon end time.', 'event-tickets' ),
					'type'        => 'string',
					'format'      => 'date-time',
				],
			],
		];
	}

	/**
	 * Get the arguments for an endpoint.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint
	 *
	 * @return array|array[]
	 */
	protected function get_endpoint_args( string $endpoint ) {
		$coupon_args = [
			'coupon' => [
				'description' => esc_html__( 'The coupon slug.', 'event-tickets' ),
				'type'        => 'string',
				'format'      => 'text-field',
				'required'    => true,
			],
		];

		$common_args = [
			'payment_intent_id' => [
				'description' => esc_html__( 'The payment intent to apply the coupon to.', 'event-tickets' ),
				'type'        => 'string',
				'format'      => 'text-field',
				'required'    => true,
			],
			'purchaser_data'    => [
				'description' => esc_html__( 'The purchaser data.', 'event-tickets' ),
				'type'        => 'object',
				'required'    => true,
				'properties'  => [
					'name'  => [
						'description' => esc_html__( 'The purchaser name.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'text-field',
						'required'    => true,
					],
					'email' => [
						'description' => esc_html__( 'The purchaser email.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'email',
						'required'    => true,
					],
				],
			],
		];

		switch ( $endpoint ) {
			case 'apply':
			case 'remove':
				return array_merge( $coupon_args, $common_args );

			case 'validate':
				return $coupon_args;

			case 'create':
				return [
					'slug'         => [
						'description' => esc_html__( 'The coupon slug.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'text-field',
						'required'    => true,
					],
					'display_name' => [
						'description' => esc_html__( 'The coupon display name.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'text-field',
						'required'    => true,
					],
					'status'       => [
						'description' => esc_html__( 'The coupon status.', 'event-tickets' ),
						'type'        => 'string',
						'enum'        => [ 'active', 'inactive', 'draft' ],
						'required'    => true,
					],
					'amount'       => [
						'description' => esc_html__( 'The coupon amount.', 'event-tickets' ),
						'type'        => 'integer',
						'required'    => true,
					],
					'sub_type'     => [
						'description' => esc_html__( 'The coupon sub type.', 'event-tickets' ),
						'type'        => 'string',
						'enum'        => [ 'percent', 'flat' ],
						'required'    => false,
						'default'     => 'flat',
					],
					'start_time'   => [
						'description' => esc_html__( 'The coupon start time.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'default'     => null,
					],
					'end_time'     => [
						'description' => esc_html__( 'The coupon end time.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'default'     => null,
					],
				];

			default:
				return [];
		}
	}

	/**
	 * Convert a WP_Error object to a response.
	 *
	 * @since TBD
	 *
	 * @param WP_Error $error The error object.
	 *
	 * @return Response
	 */
	protected function convert_error_to_response( WP_Error $error ): Response {
		return rest_convert_error_to_response( $error );
	}

	/**
	 * Get the purchaser information.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return array
	 */
	protected function get_purchaser_information( Request $request ) {
		$purchaser_data = $request->get_param( 'purchaser_data' );

		[ $first_name, $last_name ] = explode( ' ', $purchaser_data['name'], 2 );

		return [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => $purchaser_data['name'],
			'purchaser_first_name' => $first_name,
			'purchaser_last_name'  => $last_name,
			'purchaser_email'      => sanitize_email( $purchaser_data['email'] ),
		];
	}
}
