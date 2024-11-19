<?php
/**
 * Fees API.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee_Modifier_Manager as Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationships;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as Fee_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Fee_Types;
use WP_Error;
use TEC\Common\Contracts\Container;
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
	 * @param Container      $container      The DI container.
	 * @param Fee_Repository $fee_repository The repository for interacting with the order modifiers.
	 * @param Relationships  $relationships  The repository for interacting with the order modifiers relationships.
	 * @param Manager        $manager        The manager for the order modifiers.
	 */
	public function __construct(
		Container $container,
		Fee_Repository $fee_repository,
		Relationships $relationships,
		Manager $manager
	) {
		parent::__construct( $container );
		$this->modifiers_repository = $fee_repository;
		$this->relationships        = $relationships;
		$this->manager              = $manager;
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
		if ( ! apply_filters( 'tec_tickets_commerce_add_fees_to_ticket_data', false ) ) {
			return;
		}

		add_filter(
			'tribe_rest_single_ticket_data',
			$this->get_single_ticket_data_callback(),
			20,
			2
		);

		add_filter(
			'tec_tickets_commerce_rest_ticket_archive_data',
			$this->get_tickets_archive_data_callback(),
			10,
			2
		);

		add_action(
			'tribe_tickets_ticket_added',
			$this->get_after_ticket_added_callback(),
			10,
			3
		);
	}

	/**
	 * Removes additional methods/logic from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function unregister_additional_hooks(): void {
		remove_filter(
			'tribe_rest_single_ticket_data',
			$this->get_single_ticket_data_callback(),
			20
		);

		remove_filter(
			'tec_tickets_commerce_rest_ticket_archive_data',
			$this->get_tickets_archive_data_callback()
		);

		remove_action(
			'tribe_tickets_ticket_added',
			$this->get_after_ticket_added_callback()
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
				'args'                => [
					'selected_fees' => [
						'description' => __( 'The selected fees for the ticket.', 'event-tickets' ),
						'type'        => 'array',
						'required'    => true,
						'items'       => [
							'type' => 'integer',
						],
					],
				],
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
		try {
			$all = $this->get_all_fees();

			return rest_ensure_response(
				[
					'all_fees'        => $all,
					'automatic_fees'  => $this->get_automatic_fees( $all ),
					'selectable_fees' => $this->get_selectable_fees( $all ),
				]
			);
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_commerce_get_fees_error',
					$e->getMessage(),
					[ 'status' => $e->getCode() ?: 500 ]
				)
			);
		}
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
		try {
			$ticket_id = (int) $request->get_param( 'id' );

			return rest_ensure_response( $this->get_fees_for_ticket( $ticket_id ) );
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_commerce_get_fees_for_ticket_error',
					$e->getMessage(),
					[ 'status' => $e->getCode() ?: 500 ]
				)
			);
		}
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
		$all_fees    = $this->get_all_fees();
		$ticket_fees = $this->relationships->find_by_post_id( $ticket_id );
		$ticket_fees ??= [];

		$automatic_fees = $this->get_automatic_fees( $all_fees );

		return [
			'available_fees' => $this->get_selectable_fees( $all_fees ),
			'selected_fees'  => $ticket_fees,
			'automatic_fees' => $automatic_fees,
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
		// Only add fees to the default ticket type.
		if ( array_key_exists( 'type', $data ) && 'default' !== $data['type'] ) {
			return $data;
		}

		try {
			$ticket_id    = (int) ( $data['id'] ?? $request->get_param( 'id' ) );
			$ticket_fees  = $this->get_fees_for_ticket( $ticket_id );
			$data['fees'] = $ticket_fees;
		} finally {
			return $data;
		}
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
		try {
			$ticket_id = (int) $request->get_param( 'id' );
			$fees      = $request->get_param( 'selected_fees' );

			$this->update_fees_for_ticket( $ticket_id, $fees );

			return rest_ensure_response( $this->get_fees_for_ticket( $ticket_id ) );
		} catch ( Exception $e ) {
			return $this->convert_error_to_response(
				new WP_Error(
					'tickets_commerce_update_fees_for_ticket_error',
					$e->getMessage(),
					[ 'status' => $e->getCode() ?: 500 ]
				)
			);
		}
	}

	/**
	 * Save the fees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id   The ticket ID.
	 * @param array $ticket_data The ticket data.
	 *
	 * @return void
	 */
	protected function save_fees_for_ticket( $ticket_id, $ticket_data ) {
		if ( ! isset( $ticket_data['fees']['selected_fees'] ) ) {
			return;
		}

		try {
			// Map the comma-separated string of fees to an array of integers.
			$fee_string = (string) $ticket_data['fees']['selected_fees'];
			$fees       = explode( ',', $fee_string );
			$fee_ids    = array_map( 'absint', $fees );
			$fee_ids    = array_filter( $fee_ids );

			// Update the fees for the ticket.
			$this->update_fees_for_ticket( $ticket_id, $fee_ids );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			// @todo: Log the error?
		}
	}

	/**
	 * Update the fees for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ticket ID.
	 * @param int[] $fees      The fees to update.
	 *
	 * @return void
	 *
	 * @throws Exception If the fees are not selectable.
	 */
	protected function update_fees_for_ticket( $ticket_id, $fees ) {
		// Validate that the fees are actually selectable.
		$all_fees        = $this->get_all_fees();
		$selectable_fees = wp_list_pluck( $this->get_selectable_fees( $all_fees ), 'id', 'id' );
		$invalid_fees    = [];
		foreach ( $fees as $fee ) {
			if ( ! array_key_exists( $fee, $selectable_fees ) ) {
				$invalid_fees[] = $fee;
			}
		}

		if ( ! empty( $invalid_fees ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: The invalid fees. */
					__( 'The following fees are not selectable: %s', 'event-tickets' ),
					implode( ', ', $invalid_fees )
				),
				400
			);
		}

		// Ensure that the fees are integers.
		$fee_ids = array_map( 'absint', $fees );

		$this->manager->delete_relationships_by_post( $ticket_id );
		$this->manager->sync_modifier_relationships( $fee_ids, [ $ticket_id ] );
	}

	/**
	 * Get the callback for adding fees to the ticket data.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for adding fees to the ticket data.
	 */
	protected function get_single_ticket_data_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( array $data, Request $request ) => $this->add_fees_to_ticket_data( $data, $request );
		}

		return $callback;
	}

	/**
	 * Get the callback for adding fees to the ticket data archive.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for adding fees to the ticket data archive.
	 */
	protected function get_tickets_archive_data_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = function ( array $tickets, Request $request ) {
				foreach ( $tickets as $key => $ticket ) {
					$tickets[ $key ] = $this->add_fees_to_ticket_data( $ticket, $request );
				}

				return $tickets;
			};
		}

		return $callback;
	}

	/**
	 * Get the callback to run after saving a ticket.
	 *
	 * @since TBD
	 *
	 * @return callable The callback to run after saving a ticket.
	 */
	protected function get_after_ticket_added_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = function ( $post_id, $ticket_id, $ticket_data ) {
				$this->save_fees_for_ticket( $ticket_id, $ticket_data['tribe-ticket'] ?? [] );
			};
		}

		return $callback;
	}
}
