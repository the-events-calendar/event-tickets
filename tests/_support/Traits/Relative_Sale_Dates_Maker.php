<?php

namespace Tribe\Tickets\Test\Traits;

use ActionScheduler_Action;
use ActionScheduler_Store;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Ticket_Actions;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Builds the events, rules and lookups the Relative Sale Dates tests share.
 */
trait Relative_Sale_Dates_Maker {
	/**
	 * @param int $value The number of units before the event start.
	 * @param int $unit  The unit, one of the `Rule::UNIT_*` constants.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor: string} A relative end of the window, anchored on the event start.
	 */
	protected function relative( int $value, int $unit ): array {
		return [
			'mode'   => 'relative',
			'value'  => $value,
			'unit'   => $unit,
			'anchor' => 'start',
		];
	}

	/**
	 * Creates an event lasting three hours.
	 *
	 * @param string $start    The event start, local `Y-m-d H:i:s`.
	 * @param string $timezone The event timezone.
	 *
	 * @return int The event post ID.
	 */
	protected function create_event( string $start, string $timezone = 'UTC' ): int {
		return tribe_events()->set_args(
			[
				'title'      => 'Relative Sale Dates event',
				'status'     => 'publish',
				'start_date' => $start,
				'timezone'   => $timezone,
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
	}

	/**
	 * @param string $hook      The sales action hook.
	 * @param int    $ticket_id The ticket post ID.
	 *
	 * @return int[] The timestamps the pending actions of the ticket are scheduled at.
	 */
	protected function get_scheduled_timestamps( string $hook, int $ticket_id ): array {
		$actions = as_get_scheduled_actions(
			[
				'hook'   => $hook,
				'args'   => [ $ticket_id ],
				'group'  => Ticket_Actions::AS_TICKET_ACTIONS_GROUP,
				'status' => ActionScheduler_Store::STATUS_PENDING,
			],
			OBJECT
		);

		return array_values( array_map( static fn( ActionScheduler_Action $action ): int => $action->get_schedule()->get_date()->getTimestamp(), $actions ) );
	}

	/**
	 * Sends a ticket save the way the block editor does.
	 *
	 * @param string              $method       The HTTP method.
	 * @param string              $route        The route, relative to the tickets namespace.
	 * @param int                 $post_id      The ticketed post ID.
	 * @param string              $nonce_action The nonce action the endpoint checks.
	 * @param array<string,mixed> $overrides    The body params to replace, merged recursively.
	 *
	 * @return WP_REST_Response The response.
	 */
	protected function send_block_editor_ticket_save( string $method, string $route, int $post_id, string $nonce_action, array $overrides = [] ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/' . tribe( 'tickets.rest-v1.main' )->get_events_route_namespace() . $route );
		$request->set_body_params(
			array_replace_recursive(
				[
					'post_id'          => $post_id,
					$nonce_action      => wp_create_nonce( $nonce_action ),
					'provider'         => Module::class,
					'name'             => 'Block editor ticket',
					'description'      => '',
					'price'            => '10',
					'show_description' => 'yes',
					'start_date'       => '',
					'start_time'       => '',
					'end_date'         => '',
					'end_time'         => '',
					'sku'              => '',
					'iac'              => 'none',
					'menu_order'       => 0,
					'ticket'           => [
						'mode'     => 'own',
						'capacity' => 50,
					],
				],
				$overrides
			)
		);

		return rest_do_request( $request );
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{0: string, 1: string} The stored sales start date and time.
	 */
	protected function get_ticket_start( int $ticket_id ): array {
		return [ get_post_meta( $ticket_id, '_ticket_start_date', true ), get_post_meta( $ticket_id, '_ticket_start_time', true ) ];
	}

	/**
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{0: string, 1: string} The stored sales end date and time.
	 */
	protected function get_ticket_end( int $ticket_id ): array {
		return [ get_post_meta( $ticket_id, '_ticket_end_date', true ), get_post_meta( $ticket_id, '_ticket_end_time', true ) ];
	}
}
