<?php
/**
 * Base API.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use TEC\Tickets\Registerable;
use WP_Error;
use WP_REST_Response as Response;

/**
 * Class Base_API
 *
 * @since TBD
 */
abstract class Base_API implements Registerable {

	/**
	 * The namespace for the API.
	 *
	 * @var string
	 */
	protected string $namespace = 'tribe/tickets/v1';

	/**
	 * Get the permission callback.
	 *
	 * @since TBD
	 *
	 * @return callable The permission callback.
	 */
	protected function get_permission_callback() {
		/**
		 * Filters the role required to access the API.
		 *
		 * @since TBD
		 *
		 * @param string $role The role required to access the API.
		 */
		$role = apply_filters( 'tec_tickets_commerce_order_modifiers_api_role', 'manage_options' );

		return static function () use ( $role ) {
			return current_user_can( $role );
		};
	}

	/**
	 * Registers the class with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void {
		add_action( 'rest_api_init', fn() => $this->register_routes() );
		$this->register_additional_hooks();
	}

	/**
	 * Registers additional methods/logic with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	protected function register_additional_hooks(): void {
		/*
		 * Override this method in a child class to register additional hooks.
		 */
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
	 * Register the routes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	abstract protected function register_routes(): void;
}
