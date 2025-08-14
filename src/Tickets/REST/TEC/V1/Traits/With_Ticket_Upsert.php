<?php
/**
 * Trait to provide ticket upsert.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use WP_REST_Response;
use TEC\Tickets\Commerce\Module;

/**
 * Trait With_Ticket_Upsert.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Ticket_Upsert {
	/**
	 * Creates a ticket.
	 *
	 * @since TBD
	 *
	 * @param array $params The parameters for the ticket.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function create( array $params = [] ): WP_REST_Response {
		return $this->upsert( $params, 'create' );
	}

	/**
	 * Updates a ticket.
	 *
	 * @since TBD
	 *
	 * @param array $params The parameters for the ticket.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function update( array $params = [] ): WP_REST_Response {
		$id = $params['id'] ?? null;

		unset( $params['id'] );

		if ( ! $id ) {
			return new WP_REST_Response(
				[
					'error' => __( 'The ticket could not be updated.', 'event-tickets' ),
				],
				404
			);
		}

		if ( get_post_type( $id ) !== $this->get_post_type() ) {
			return new WP_REST_Response(
				[
					'error' => __( 'The ticket could not be updated.', 'event-tickets' ),
				],
				404
			);
		}

		return $this->upsert( $params, 'update' );
	}

	/**
	 * Upserts a ticket.
	 *
	 * This method will create a ticket if it doesn't exist, or update it if it does.
	 *
	 * @since TBD
	 *
	 * @param array  $params    The parameters for the ticket.
	 * @param string $operation The operation to perform: create or update.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function upsert( array $params = [], string $operation = 'create' ): WP_REST_Response {
		$tickets = tribe( Module::class );

		$event = $params['event'];
		unset( $params['event'] );

		$ticket_id = $tickets->ticket_add( $event, $params );

		if ( ! $ticket_id ) {
			return new WP_REST_Response(
				[
					// translators: 1) is the operation: create or update.
					'error' => sprintf( __( 'Failed to %s ticket.', 'event-tickets' ), $operation ),
				],
				500
			);
		}

		$ticket_entity = $this->get_orm()->by_args(
			[
				'id'     => $ticket_id,
				'status' => 'any',
			]
		)->first();

		if ( ! $ticket_entity ) {
			return new WP_REST_Response(
				[
					// translators: 1) is the operation: create or update.
					'error' => sprintf( __( 'Ticket not found after %s.', 'event-tickets' ), $operation ),
				],
				500
			);
		}

		return new WP_REST_Response(
			$this->get_formatted_entity( $ticket_entity ),
			'update' === $operation ? 200 : 201
		);
	}
}
