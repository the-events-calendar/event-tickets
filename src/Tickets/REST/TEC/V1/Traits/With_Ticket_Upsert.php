<?php
/**
 * Trait to provide ticket upsert.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use WP_REST_Response;

/**
 * Trait With_Ticket_Upsert.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Ticket_Upsert {
	/**
	 * Creates a ticket.
	 *
	 * @since 5.26.0
	 *
	 * @param array $params The parameters for the ticket.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function create( array $params = [] ): WP_REST_Response {
		return $this->upsert( $params, _x( 'create', 'This is being used as a verb.', 'event-tickets' ) );
	}

	/**
	 * Updates a ticket.
	 *
	 * @since 5.26.0
	 *
	 * @param array $params The parameters for the ticket.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function update( array $params = [] ): WP_REST_Response {
		$post_params   = $params['post_params'] ?? [];
		$ticket_params = $params['ticket_params'];

		$id = $ticket_params['id'] ?? null;

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

		return $this->upsert( compact( 'post_params', 'ticket_params' ), _x( 'update', 'This is being used as a verb.', 'event-tickets' ) );
	}

	/**
	 * Upserts a ticket.
	 *
	 * This method will create a ticket if it doesn't exist, or update it if it does.
	 *
	 * @since 5.26.0
	 *
	 * @param array  $params    The parameters for the ticket.
	 * @param string $operation The operation to perform: create or update.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function upsert( array $params = [], string $operation = 'create' ): WP_REST_Response {
		$post_params   = $params['post_params'] ?? [];
		$ticket_params = $params['ticket_params'];

		$tickets = $this->get_provider();

		$event = $ticket_params['event'];
		unset( $ticket_params['event'] );

		$ticket_id = $tickets->ticket_add( $event, $ticket_params );

		if ( ! $ticket_id ) {
			return new WP_REST_Response(
				[
					// translators: 1) is the operation: create or update.
					'error' => sprintf( __( 'Failed to %s ticket.', 'event-tickets' ), $operation ),
				],
				500
			);
		}

		if ( ! empty( $post_params ) ) {
			$ticket_update_result = $this->get_orm()->by_args(
				[
					'id'     => $ticket_id,
					'status' => 'any',
				]
			)->set_args( $post_params )->save();

			if ( ! $ticket_update_result ) {
				return new WP_REST_Response(
					[
						// translators: 1) is the operation: create or update.
						'error' => sprintf( __( 'Failed to %s partial data of the ticket.', 'event-tickets' ), $operation ),
					],
					500
				);
			}
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
