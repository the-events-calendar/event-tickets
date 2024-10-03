<?php
/**
 * Fees API.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\API;

use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationships;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Modifiers;
use TEC\Tickets\Order_Modifiers\Traits\Fee_Types;
use TEC\Tickets\Registerable;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

/**
 * Class Fees
 *
 * @since TBD
 */
class Fees implements Registerable {

	use Fee_Types;

	/**
	 * The repository for interacting with the order modifiers relationships.
	 *
	 * @var Relationships
	 */
	protected Relationships $relationships;

	/**
	 * Fees constructor.
	 *
	 * @param ?Modifiers     $modifiers     The repository for interacting with the order modifiers.
	 * @param ?Relationships $relationships The repository for interacting with the order modifiers relationships.
	 */
	public function __construct( ?Modifiers $modifiers = null, ?Relationships $relationships = null ) {
		$this->modifiers_repository = $modifiers ?? new Modifiers();
		$this->relationships        = $relationships ?? new Relationships();
	}

	/**
	 * Registers the class with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function () {
				$this->register_routes();
			}
		);
	}

	/**
	 * Register the routes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_routes() {
		tribe_register_rest_route(
			'event-tickets/v1',
			'/fees',
			[
				'methods'             => 'GET',
				'callback'            => fn( Request $request ) => $this->get_fees( $request ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			]
		);
	}

	/**
	 * Get the fees.
	 *
	 * This method returns all fees, automatic fees, and selectable fees.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response
	 */
	protected function get_fees( Request $request ): Response {
		$all = $this->get_all_fees();

		return rest_ensure_response(
			[
				'fees'       => $all,
				'automatic'  => $this->get_automatic_fees( $all ),
				'selectable' => $this->get_selectable_fees( $all ),
			]
		);
	}
}
