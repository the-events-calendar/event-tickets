<?php
/**
 * Base API.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\API;

use TEC\Tickets\Registerable;

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
		return static function () {
			return current_user_can( 'manage_options' );
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
	 * Register the routes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	abstract protected function register_routes(): void;
}
