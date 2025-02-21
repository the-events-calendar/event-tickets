<?php
/**
 * Coupons API.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use Exception;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon_Modifier_Manager as Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons as Coupons_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Coupons as CouponsTrait;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Commerce\Traits\Type;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;

/**
 * Class Coupons
 *
 * @since 5.18.0
 */
class Coupons extends Base_API {

	use CouponsTrait;
	use Type;

	/**
	 * TThe modifier manager instance to handle relationship updates.
	 *
	 * @var Manager
	 */
	protected Manager $manager;

	/**
	 * The repository for interacting with the order modifiers table.
	 *
	 * @since 5.18.0
	 *
	 * @var Coupons_Repository
	 */
	protected Coupons_Repository $repo;

	/**
	 * Coupons constructor.
	 *
	 * @since 5.18.0
	 *
	 * @param Container          $container  The DI container.
	 * @param Coupons_Repository $repository The coupons repository.
	 * @param Manager            $manager    The manager for the order modifiers.
	 */
	public function __construct( Container $container, Coupons_Repository $repository, Manager $manager ) {
		parent::__construct( $container );
		$this->repo    = $repository;
		$this->manager = $manager;
	}

	/**
	 * Register the routes.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	protected function register_routes(): void {
		register_rest_route(
			static::NAMESPACE,
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
			static::NAMESPACE,
			'/coupons/validate',
			[
				'methods'             => Server::CREATABLE,
				'callback'            => fn( Request $request ) => $this->validate_coupon( $request ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_endpoint_args( 'validate' ),
			]
		);

		register_rest_route(
			static::NAMESPACE,
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
			static::NAMESPACE,
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
	 * @since 5.18.0
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response
	 */
	protected function get_coupons( Request $request ): Response {
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
	 * @since 5.18.0
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
					'slug'         => $data['slug'],
					'display_name' => $data['display_name'],
					'status'       => $data['status'],
					'raw_amount'   => $data['amount'],
					'sub_type'     => $data['sub_type'] ?? 'flat',
					'start_time'   => $data['start_time'] ?? null,
					'end_time'     => $data['end_time'] ?? null,
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object on success, or an error object if an error.
	 */
	protected function apply_coupon( Request $request ): Response {
		try {
			// Get and validate the coupon slug.
			$coupon_slug = $request->get_param( 'coupon' );
			$this->validate_coupon_slug( $coupon_slug );

			/** @var Coupon $coupon */
			$coupon = $this->repo->find_by_slug( $coupon_slug );

			/** @var Cart $cart_page */
			$cart_page = tribe( Cart::class );
			$cart_page->set_cart_hash( $request->get_param( 'cart_hash' ) );
			$cart = $cart_page->get_repository();

			$original_total = Currency_Value::create_from_float( $cart->get_cart_total() );

			// Add the coupon to the cart.
			$cart->upsert_item(
				$this->get_unique_type_id( $coupon->id, 'coupon' ),
				1,
				[ 'type' => 'coupon' ]
			);
			$cart->save();

			$cart_total = Currency_Value::create_from_float( $cart->get_cart_total() );
			$discount   = Currency_Value::create_from_float( $coupon->get_discount_amount( $original_total->get_raw_value()->get() ) );

			// Update the payment intent with the new value
			Payment_Intent::update(
				$request->get_param( 'payment_intent_id' ),
				[ 'amount' => $cart_total->get_raw_value()->get_as_integer() ]
			);

			return rest_ensure_response(
				[
					'success'     => true,
					'discount'    => $discount->get(),
					'label'       => esc_html( $coupon->display_name ),
					'message'     => sprintf(
						/* translators: %s: the coupon code */
						esc_html__( 'Coupon "%s" applied successfully.', 'event-tickets' ),
						$coupon->slug
					),
					'cart_amount' => $cart_total->get(),
				]
			);
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_apply_coupon_error',
					$e->getMessage(),
					[
						'status'  => $e->getCode() ?: 500,
						'success' => false,
					]
				)
			);
		}
	}

	/**
	 * Remove a coupon.
	 *
	 * @since 5.18.0
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object on success, or an error object if an error.
	 */
	protected function remove_coupon( Request $request ) {
		try {
			// Get and validate the coupon slug.
			$coupon_slug = $request->get_param( 'coupon' );
			if ( ! $this->does_coupon_slug_exist( $coupon_slug ) ) {
				throw new Exception( esc_html__( 'Invalid coupon.', 'event-tickets' ), 400 );
			}

			/** @var Coupon $coupon */
			$coupon = $this->repo->find_by_slug( $coupon_slug );

			/** @var Cart $cart_page */
			$cart_page = tribe( Cart::class );
			$cart_page->set_cart_hash( $request->get_param( 'cart_hash' ) );
			$cart = $cart_page->get_repository();

			// Remove the item from the cart.
			$cart->remove_item( $this->get_unique_type_id( $coupon->id, 'coupon' ) );
			$cart->save();

			$cart_total = Currency_Value::create_from_float( $cart->get_cart_total() );

			// Update the payment intent with the new value.
			Payment_Intent::update(
				$request->get_param( 'payment_intent_id' ),
				[ 'amount' => $cart_total->get_raw_value()->get_as_integer() ]
			);

			return rest_ensure_response(
				[
					'success'     => true,
					'message'     => sprintf(
						/* translators: %s: the coupon code */
						esc_html__( 'Coupon "%s" removed successfully.', 'event-tickets' ),
						$coupon->slug
					),
					'cart_amount' => $cart_total->get(),
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
	 * @since 5.18.0
	 *
	 * @param Order_Modifier $coupon The coupon.
	 *
	 * @return array
	 */
	protected function prepare_coupon_for_response( Order_Modifier $coupon ) {
		// @todo: better processing of the response.
		$raw_amount = $coupon->raw_amount;

		return [
			'id'         => $coupon->id,
			'slug'       => $coupon->slug,
			'name'       => $coupon->display_name,
			'sub_type'   => $coupon->sub_type,
			'amount'     => $coupon->raw_amount,
			'status'     => $coupon->status,
			'start_time' => $coupon->start_time,
			'end_time'   => $coupon->end_time,
		];
	}

	/**
	 * Get the schema for the API.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
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
			'cart_hash'         => [
				'description' => esc_html__( 'The cart hash.', 'event-tickets' ),
				'type'        => 'string',
				'format'      => 'text-field',
				'required'    => true,
			],
			'payment_intent_id' => [
				'description' => esc_html__( 'The payment intent to apply the coupon to.', 'event-tickets' ),
				'type'        => 'string',
				'format'      => 'text-field',
				'required'    => true,
			],
			'purchaser_data'    => [
				'description'       => esc_html__( 'The purchaser data.', 'event-tickets' ),
				'type'              => 'object',
				'sanitize_callback' => function ( $raw_value ) {
					return [
						'name'  => sanitize_text_field( $raw_value['name'] ?? '' ),
						'email' => sanitize_email( $raw_value['email'] ?? '' ),
					];
				},
				'properties'        => [
					'name'  => [
						'description' => esc_html__( 'The purchaser name.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'text-field',
					],
					'email' => [
						'description' => esc_html__( 'The purchaser email.', 'event-tickets' ),
						'type'        => 'string',
						'format'      => 'email',
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
	 * Get the purchaser information.
	 *
	 * @since 5.18.0
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
			'purchaser_first_name' => $first_name ?? $purchaser_data['name'],
			'purchaser_last_name'  => $last_name ?? '',
			'purchaser_email'      => sanitize_email( $purchaser_data['email'] ),
		];
	}

	/**
	 * Get the coupon slug from the request object and validate it.
	 *
	 * @since TBD
	 *
	 * @param string $coupon_slug The coupon slug.
	 *
	 * @throws Exception If the coupon slug is invalid.
	 */
	protected function validate_coupon_slug( string $coupon_slug ) {
		if ( ! $this->is_coupon_slug_valid( $coupon_slug ) ) {
			throw new Exception( esc_html__( 'Invalid coupon.', 'event-tickets' ), 400 );
		}
	}
}
