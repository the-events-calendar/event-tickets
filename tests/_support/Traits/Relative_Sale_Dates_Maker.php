<?php

namespace Tribe\Tickets\Test\Traits;

use ActionScheduler_Action;
use ActionScheduler_Store;
use DateTimeImmutable;
use TEC\Tickets\Commerce\Module;
use RuntimeException;
use TEC\Tickets\Relative_Sale_Dates\Rule_Store;
use TEC\Tickets\Ticket_Actions;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Builds the events, rules and lookups the Relative Sale Dates tests share.
 *
 * `create_ruled_ticket()` needs the Tickets Commerce `Ticket_Maker` trait in the test case as well.
 */
trait Relative_Sale_Dates_Maker {
	/**
	 * @param int $value The number of units before the event start.
	 * @param int $unit  The unit, one of the `*_IN_SECONDS` constants from `MINUTE_IN_SECONDS` to `WEEK_IN_SECONDS`.
	 *
	 * @return array{mode: string, value: int, unit: int, anchor: string} A relative edge of the window, anchored on the event start.
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
	 * Sends a ticket save the way the classic editor does and returns its JSON response.
	 *
	 * @param int    $post_id The ticketed post ID.
	 * @param string $data    The ticket form, URL-encoded as `tickets.js` serializes it.
	 *
	 * @return array{success: bool, data: mixed} The decoded JSON response.
	 */
	protected function send_classic_ticket_form( int $post_id, string $data ): array {
		// WordPress slashes the request, the URL-encoded form string included.
		$_POST = wp_slash(
			[
				'post_id' => $post_id,
				'nonce'   => wp_create_nonce( 'add_ticket_nonce' ),
				'data'    => $data,
			]
		);

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static fn() => static function () {
				throw new RuntimeException( 'The AJAX response was sent.' );
			}
		);

		ob_start();
		try {
			tribe( 'tickets.metabox' )->ajax_ticket_add();
		} catch ( RuntimeException $e ) {
			// wp_send_json_*() ends the request through the die handler.
		}

		return json_decode( ob_get_clean(), true );
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

	/**
	 * Asserts a ticket's dates and sales actions follow the rule "2 weeks before the start to 1 day before the start".
	 *
	 * @param DateTimeImmutable $event_start The event start the ticket should be resolved from, in the event timezone.
	 * @param int               $ticket_id   The ticket post ID.
	 *
	 * @return void
	 */
	protected function assert_resolved_from( DateTimeImmutable $event_start, int $ticket_id ): void {
		$sales_start = $event_start->modify( '-2 weeks' );
		$sales_end   = $event_start->modify( '-1 day' );
		// Ticket_Actions schedules each action 30 minutes ahead of the date it announces.
		$lead_time = 30 * MINUTE_IN_SECONDS;

		$this->assertSame( [ $sales_start->format( 'Y-m-d' ), $sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ $sales_end->format( 'Y-m-d' ), $sales_end->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
		$this->assertSame( [ $sales_start->getTimestamp() - $lead_time ], $this->get_scheduled_timestamps( Ticket_Actions::TICKET_START_SALES_HOOK, $ticket_id ) );
		$this->assertSame( [ $sales_end->getTimestamp() - $lead_time ], $this->get_scheduled_timestamps( Ticket_Actions::TICKET_END_SALES_HOOK, $ticket_id ) );
	}

	/**
	 * Creates a Tickets Commerce ticket and stores the rule "2 weeks before the start to 1 day before the start" on it.
	 *
	 * @param int                   $event_id  The event post ID.
	 * @param array<string,string>  $overrides The ticket data to override.
	 *
	 * @return int The ticket post ID.
	 */
	protected function create_ruled_ticket( int $event_id, array $overrides = [] ): int {
		$ticket_id = $this->create_tc_ticket( $event_id, 1, $overrides );
		tribe( Rule_Store::class )->save(
			$ticket_id,
			[
				'start' => $this->relative( 2, WEEK_IN_SECONDS ),
				'end'   => $this->relative( 1, DAY_IN_SECONDS ),
			]
		);

		return $ticket_id;
	}

	/**
	 * Ticket_Actions schedules nothing for a sales window that has already ended, so the events are a year away.
	 *
	 * @return DateTimeImmutable An event start at 19:00 UTC, a year from now.
	 */
	protected function get_future_event_start(): DateTimeImmutable {
		return new DateTimeImmutable( ( new DateTimeImmutable( '+1 year' ) )->format( 'Y-m-d 19:00:00' ) );
	}
}
