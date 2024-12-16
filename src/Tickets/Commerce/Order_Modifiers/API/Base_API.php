<?php
/**
 * Base API.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use WP_Error;
use WP_REST_Response as Response;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Base_API
 *
 * @since 5.18.0
 */
abstract class Base_API extends Controller_Contract {

	/**
	 * The namespace for the API.
	 *
	 * @var string
	 */
	public const NAMESPACE = 'tribe/tickets/v1';

	/**
	 * Get the permission callback.
	 *
	 * @since 5.18.0
	 *
	 * @return callable The permission callback.
	 */
	protected function get_permission_callback() {
		/**
		 * Filters the role required to access the API.
		 *
		 * @since 5.18.0
		 *
		 * @param string $role The role required to access the API.
		 */
		$role = apply_filters( 'tec_tickets_commerce_order_modifiers_api_role', 'manage_options' );

		return static fn() => current_user_can( $role );
	}

	/**
	 * Registers the class with WordPress hooks.
	 *
	 * @since 5.18.0
	 *
	 * @return void The method does not return any value.
	 */
	public function do_register(): void {
		add_action( 'rest_api_init', $this->get_register_routes_callback() );
		$this->register_additional_hooks();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'rest_api_init', $this->get_register_routes_callback() );
		$this->unregister_additional_hooks();
	}

	/**
	 * Registers additional methods/logic with WordPress hooks.
	 *
	 * @since 5.18.0
	 *
	 * @return void The method does not return any value.
	 */
	protected function register_additional_hooks(): void {
		/*
		 * Override this method in a child class to register additional hooks.
		 */
	}

	/**
	 * Removes additional methods/logic from WordPress hooks.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	protected function unregister_additional_hooks(): void {
		/*
		 * Override this method in a child class to unregister additional hooks.
		 */
	}

	/**
	 * Convert a WP_Error object to a response.
	 *
	 * @since 5.18.0
	 *
	 * @param WP_Error $error The error object.
	 *
	 * @return Response
	 */
	protected function convert_error_to_response( WP_Error $error ): Response {
		return rest_convert_error_to_response( $error );
	}

	/**
	 * Get the register routes callback.
	 *
	 * @since 5.18.0
	 *
	 * @return callable The register routes callback.
	 */
	protected function get_register_routes_callback(): callable {
		static $callbacks = [];
		if ( ! array_key_exists( static::class, $callbacks ) ) {
			$callbacks[ static::class ] = fn() => $this->register_routes();
		}

		return $callbacks[ static::class ];
	}

	/**
	 * Register the routes.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	abstract protected function register_routes(): void;
}
