<?php
/**
 * Fees API.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\API;

use Exception;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee_Modifier_Manager as Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Relationships;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Fee_Types;
use WP_Error;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Seating\Logging;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_REST_Server as Server;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Fees
 *
 * @since 5.18.0
 */
class Fees extends Base_API {

	use Fee_Types;
	use Logging;

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
	 * @param Container     $container     The DI container.
	 * @param Relationships $relationships The repository for interacting with the order modifiers relationships.
	 * @param Manager       $manager       The manager for the order modifiers.
	 */
	public function __construct(
		Container $container,
		Relationships $relationships,
		Manager $manager
	) {
		parent::__construct( $container );
		$this->relationships = $relationships;
		$this->manager       = $manager;
	}

	/**
	 * Registers additional methods/logic with WordPress hooks.
	 *
	 * @since 5.18.0
	 *
	 * @return void The method does not return any value.
	 */
	protected function register_additional_hooks(): void {
		add_filter(
			'tec_tickets_rest_api_archive_results',
			[ $this, 'add_fees_to_ticket_data_archive' ],
			10,
			2
		);

		add_filter(
			'tribe_rest_single_ticket_data',
			[ $this, 'add_fees_to_ticket_data' ],
			20,
			2
		);

		add_action(
			'tribe_tickets_ticket_added',
			[ $this, 'save_fees_for_ticket' ],
			10,
			3
		);
	}

	/**
	 * Removes additional methods/logic from WordPress hooks.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	protected function unregister_additional_hooks(): void {
		remove_filter(
			'tec_tickets_rest_api_archive_results',
			[ $this, 'add_fees_to_ticket_data_archive' ]
		);

		remove_filter(
			'tribe_rest_single_ticket_data',
			[ $this, 'add_fees_to_ticket_data' ],
			20
		);

		remove_action(
			'tribe_tickets_ticket_added',
			[ $this, 'save_fees_for_ticket' ]
		);
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
			'/fees',
			[
				'methods'             => Server::READABLE,
				'callback'            => fn( Request $request ) => $this->get_fees_response( $request ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => [
					'status' => [
						'description' => __( 'The status of the fees to retrieve.', 'event-tickets' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'active', 'inactive', 'draft' ],
						],
					],
				],
			]
		);

		register_rest_route(
			static::NAMESPACE,
			'/tickets/(?P<id>\\d+)/fees',
			[
				'methods'             => Server::READABLE,
				'callback'            => fn( Request $request ) => $this->get_fees_for_ticket_response( $request ),
				'permission_callback' => $this->get_permission_callback(),
			]
		);

		register_rest_route(
			static::NAMESPACE,
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
	 * @since 5.18.0
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response
	 */
	protected function get_fees_response( Request $request ): Response {
		try {
			$status = $request->get_param( 'status' ) ?? [ 'active' ];

			$all = $this->get_all_fees( [ 'status' => $status ] );

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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array The fees for the ticket.
	 */
	public function get_fees_for_ticket( int $ticket_id ) {
		$all_fees = $this->get_all_fees();

		return [
			'available_fees' => $this->get_selectable_fees( $all_fees ),
			'automatic_fees' => $this->get_automatic_fees( $all_fees ),
			'selected_fees'  => $this->get_selected_fees( $ticket_id ),
		];
	}

	/**
	 * Get the selected fees for a post by ticket.
	 *
	 * @since 5.18.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array<int, array> The selected fees for the post by ticket.
	 */
	public function get_selected_fees_for_post_by_ticket( int $post_id ) {
		$provider = Tickets::get_event_ticket_provider( $post_id );

		if ( Module::class !== $provider ) {
			return [];
		}

		$fees_for_post_by_ticket = [];

		foreach ( tribe_tickets()->where( 'event', $post_id )->get_ids( true ) as $ticket_id ) {
			$fees_for_post_by_ticket[ $ticket_id ] = $this->get_selected_fees( (int) $ticket_id );
		}

		return $fees_for_post_by_ticket;
	}

	/**
	 * Add fees to the ticket data.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $data    The ticket data.
	 * @param Request $request The request object.
	 *
	 * @return array The ticket data with fees.
	 */
	public function add_fees_to_ticket_data( array $data, Request $request ): array {
		/** @var \Tribe__Tickets__REST__V1__Main */
		$ticket_rest = tribe( 'tickets.rest-v1.main' );

		if ( ! $ticket_rest->request_has_manage_access() ) {
			return $data;
		}

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
	 * Add fees to the tickets archive.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $tickets The tickets data.
	 * @param Request $request The request object.
	 *
	 * @return array The ticket data with fees.
	 */
	public function add_fees_to_ticket_data_archive( array $tickets, Request $request ): array {
		/** @var \Tribe__Tickets__REST__V1__Main */
		$ticket_rest = tribe( 'tickets.rest-v1.main' );

		if ( ! $ticket_rest->request_has_manage_access() ) {
			return $tickets;
		}

		foreach ( $tickets as &$ticket ) {
			// Only add fees to the default ticket type.
			if ( array_key_exists( 'type', $ticket ) && 'default' !== $ticket['type'] ) {
				continue;
			}

			$ticket_id      = (int) $ticket['id'];
			$ticket['fees'] = $this->get_fees_for_ticket( $ticket_id );
		}

		return $tickets;
	}

	/**
	 * Update the fees for a ticket.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param int   $post_id     The post ID.
	 * @param int   $ticket_id   The ticket ID.
	 * @param array $ticket_data The ticket data.
	 *
	 * @return void
	 */
	public function save_fees_for_ticket( $post_id, $ticket_id, $ticket_data ) {
		if ( ! isset( $ticket_data['tribe-ticket']['fees']['selected_fees'] ) ) {
			return;
		}

		try {
			// Map the comma-separated string of fees to an array of integers.
			$fee_string = (string) $ticket_data['tribe-ticket']['fees']['selected_fees'];
			$fees       = explode( ',', $fee_string );
			$fee_ids    = array_map( 'absint', $fees );
			$fee_ids    = array_unique( array_filter( $fee_ids ) );

			// Update the fees for the ticket.
			$this->update_fees_for_ticket( $ticket_id, $fee_ids );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
			$this->log_error(
				'Unrecognized fee id was given for a relationship with ticket.',
				[
					'source'    => __METHOD__,
					'error'     => $e->getMessage(),
					'fee_ids'   => $fee_ids,
					'ticket_id' => $ticket_id,
				]
			);
		}
	}
}
