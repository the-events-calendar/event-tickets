<?php
/**
 * Fees API.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\API;

use TEC\Tickets\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager as Manager;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationships;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Modifiers;
use TEC\Tickets\Order_Modifiers\Traits\Fee_Types;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;

/**
 * Class Fees
 *
 * @since TBD
 */
class Fees extends Base_API {

	use Fee_Types;

	/**
	 * TThe modifier manager instance to handle relationship updates.
	 *
	 * @var Manager
	 */
	protected Manager $manager;

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
	 * @param ?Manager       $manager       The manager for the order modifiers.
	 */
	public function __construct(
		?Modifiers $modifiers = null,
		?Relationships $relationships = null,
		?Manager $manager = null
	) {
		$this->modifiers_repository = $modifiers ?? new Modifiers( 'fee' );
		$this->relationships        = $relationships ?? new Relationships();
		$this->manager              = $manager ?? new Manager( new Fee() );
	}

	/**
	 * Registers additional methods/logic with WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	protected function register_additional_hooks(): void {
		/**
		 * Filter whether the fee data is added to the ticket API response.
		 *
		 * @since TBD
		 *
		 * @param bool $add_fees_to_ticket_data Whether to add the fee data to the ticket data. Default false.
		 */
		if ( ! apply_filters( 'tec_tickets_add_fees_to_ticket_data', false ) ) {
			return;
		}

		add_filter(
			'tribe_rest_single_ticket_data',
			fn( array $data, Request $request ) => $this->add_fees_to_ticket_data( $data, $request ),
			20,
			2
		);
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
			'/fees',
			[
				'methods'             => Server::READABLE,
				'callback'            => fn( Request $request ) => $this->get_fees_response( $request ),
				'permission_callback' => $this->get_permission_callback(),
			]
		);

		register_rest_route(
			$this->namespace,
			'/tickets/(?P<id>\\d+)/fees',
			[
				'methods'             => Server::READABLE,
				'callback'            => fn( Request $request ) => $this->get_fees_for_ticket_response( $request ),
				'permission_callback' => $this->get_permission_callback(),
			]
		);

		register_rest_route(
			$this->namespace,
			'/tickets/(?P<id>\\d+)/fees',
			[
				'methods'             => Server::EDITABLE,
				'callback'            => fn( Request $request ) => $this->update_fees_for_ticket_response( $request ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => [], // @todo Add args.
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
	protected function get_fees_response( Request $request ): Response {
		$all = $this->get_all_fees();

		return rest_ensure_response(
			[
				'fees'       => $all,
				'automatic'  => $this->get_automatic_fees( $all ),
				'selectable' => $this->get_selectable_fees( $all ),
			]
		);
	}

	/**
	 * Get the fees for a specific ticket.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response The response object.
	 */
	protected function get_fees_for_ticket_response( Request $request ): Response {
		$ticket_id = (int) $request->get_param( 'id' );

		return rest_ensure_response( $this->get_fees_for_ticket( $ticket_id ) );
	}

	/**
	 * Get the fees for a specific ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array The fees for the ticket.
	 */
	protected function get_fees_for_ticket( int $ticket_id ) {
		$ticket_fees = $this->relationships->find_by_post_id( $ticket_id );
		$ticket_fees ??= [];

		$automatic_fees = $this->get_automatic_fees( $this->get_all_fees() );

		return [
			'fees'      => $ticket_fees,
			'automatic' => $automatic_fees,
		];
	}

	/**
	 * Add fees to the ticket data.
	 *
	 * @since TBD
	 *
	 * @param array   $data    The ticket data.
	 * @param Request $request The request object.
	 *
	 * @return array The ticket data with fees.
	 */
	protected function add_fees_to_ticket_data( array $data, Request $request ): array {
		$ticket_id    = (int) $request->get_param( 'id' );
		$ticket_fees  = $this->get_fees_for_ticket( $ticket_id );
		$data['fees'] = $ticket_fees;

		return $data;
	}

	/**
	 * Update the fees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response
	 */
	protected function update_fees_for_ticket_response( Request $request ) {
		$ticket_id = (int) $request->get_param( 'id' );
		$fees      = $request->get_param( 'fees' );

		$this->manager->delete_relationships_by_post( $ticket_id );

		$fee_ids = array_map( 'absint', $fees );

		$this->manager->sync_modifier_relationships( $fee_ids, [ $ticket_id ] );

		return rest_ensure_response( $this->get_fees_for_ticket( $ticket_id ) );
	}
}
