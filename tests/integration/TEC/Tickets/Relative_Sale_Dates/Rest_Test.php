<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket as Ticket_Endpoint;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_REST_Response;

class Rest_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The whole feature is registered: the rule reaches the save through the REST mapping.
	 *
	 * @var string
	 */
	protected $controller_class = Controller::class;

	/**
	 * The sub-controllers the feature controller registers, lowered in the test container so they register again.
	 *
	 * @var string[]
	 */
	protected $sub_controller_classes = [ Ticket_Save::class, Event_Listener::class, Rest::class ];

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function should_store_the_rule_the_block_editor_sends_with_a_new_ticket(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];

		$response = $this->send_block_editor_ticket_save( 'POST', '/tickets', $event_id, 'add_ticket_nonce', [ 'ticket' => [ 'relative_sale_dates' => wp_json_encode( $rule ) ] ] );

		$this->assertFalse( $response->is_error() );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$this->assertCount( 1, $ticket_ids );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( reset( $ticket_ids ) ) );
		$sales_start = $event_start->modify( '-2 weeks' );
		$this->assertSame( [ $sales_start->format( 'Y-m-d' ), $sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( reset( $ticket_ids ) ) );
	}

	/**
	 * @test
	 */
	public function should_store_the_rule_the_block_editor_sends_with_a_ticket_update(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => $this->relative( 1, Rule::UNIT_DAYS ) ];

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $event_id, 'edit_ticket_nonce', [ 'ticket' => [ 'relative_sale_dates' => wp_json_encode( $rule ) ] ] );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_reject_a_window_the_block_editor_sends_that_ends_before_it_starts(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$rule     = [ 'start' => $this->relative( 1, Rule::UNIT_HOURS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ];

		$response = $this->send_block_editor_ticket_save( 'POST', '/tickets', $event_id, 'add_ticket_nonce', [ 'ticket' => [ 'relative_sale_dates' => wp_json_encode( $rule ) ] ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'Ticket sales cannot end before they start. Please adjust the sales window.', $response->get_data()['message'] );
		$this->assertSame( [], tribe( Module::class )->get_tickets_ids( $event_id ) );
	}

	/**
	 * @test
	 */
	public function should_ignore_the_rule_the_block_editor_sends_with_an_rsvp(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		// A window that ends before it starts would be rejected if the rule applied to the RSVP.
		$rule = [ 'start' => $this->relative( 1, Rule::UNIT_HOURS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ];

		$response = $this->send_block_editor_ticket_save(
			'POST',
			'/tickets',
			$event_id,
			'add_ticket_nonce',
			[
				'provider' => 'Tribe__Tickets__RSVP',
				'ticket'   => [ 'relative_sale_dates' => wp_json_encode( $rule ) ],
			]
		);

		$this->assertFalse( $response->is_error() );
		$rsvp_ids = tribe( 'tickets.rsvp' )->get_tickets_ids( $event_id );
		$this->assertCount( 1, $rsvp_ids );
		$this->assertSame( [], tribe( Rule_Store::class )->get( reset( $rsvp_ids ) ) );
	}

	/**
	 * @test
	 */
	public function should_store_and_return_the_rule_sent_to_the_tec_rest_api_with_a_new_ticket(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];

		$response = $this->upsert_through_tec_rest_api(
			[
				'event'               => $event_id,
				'title'               => 'TEC REST ticket',
				'price'               => 10,
				'relative_sale_dates' => $rule,
			]
		);

		$ticket_id = $response->get_data()['id'];
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertSame( $rule, $response->get_data()['relative_sale_dates'] );
		$sales_start = $event_start->modify( '-2 weeks' );
		$this->assertSame( [ $sales_start->format( 'Y-m-d' ), $sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_keep_the_rule_when_a_tec_rest_api_update_leaves_it_out(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$rule      = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];
		$ticket_id = $this->upsert_through_tec_rest_api(
			[
				'event'               => $event_id,
				'title'               => 'TEC REST ticket',
				'price'               => 10,
				'relative_sale_dates' => $rule,
			]
		)->get_data()['id'];

		$this->upsert_through_tec_rest_api(
			[
				'id'    => $ticket_id,
				'price' => 20,
			],
			'update'
		);

		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_remove_the_rule_when_a_tec_rest_api_update_sends_null(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->upsert_through_tec_rest_api(
			[
				'event'               => $event_id,
				'title'               => 'TEC REST ticket',
				'price'               => 10,
				'relative_sale_dates' => [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ],
			]
		)->get_data()['id'];
		$this->assertNotSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );

		$this->upsert_through_tec_rest_api(
			[
				'id'                  => $ticket_id,
				'relative_sale_dates' => null,
			],
			'update'
		);

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_reject_a_window_sent_to_the_tec_rest_api_that_ends_before_it_starts(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		try {
			$this->upsert_through_tec_rest_api(
				[
					'event'               => $event_id,
					'title'               => 'TEC REST ticket',
					'price'               => 10,
					'relative_sale_dates' => [ 'start' => $this->relative( 1, Rule::UNIT_HOURS ), 'end' => $this->relative( 2, Rule::UNIT_HOURS ) ],
				]
			);
			$this->fail( 'The ticket should have been rejected.' );
		} catch ( InvalidRestArgumentException $e ) {
			$this->assertSame( 400, $e->to_wp_error()->get_error_data()['status'] );
			$this->assertSame( 'Ticket sales cannot end before they start. Please adjust the sales window.', $e->getMessage() );
		}

		$this->assertSame( [], tribe( Module::class )->get_tickets_ids( $event_id ) );
	}

	/**
	 * @test
	 */
	public function should_return_the_stored_rule_in_the_block_editor_ticket_data(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		$rule      = [ 'start' => $this->relative( 2, Rule::UNIT_WEEKS ), 'end' => [ 'mode' => 'default' ] ];
		tribe( Rule_Store::class )->save( $ticket_id, $rule );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $ticket_id );

		$this->assertSame( $rule, $data['relative_sale_dates'] );
	}

	/**
	 * @test
	 */
	public function should_return_null_in_the_block_editor_ticket_data_of_a_ticket_without_a_rule(): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $ticket_id );

		$this->assertArrayHasKey( 'relative_sale_dates', $data );
		$this->assertNull( $data['relative_sale_dates'] );
	}

	/**
	 * Creates or updates a ticket the way the TEC REST API endpoint does, from its already sanitized parameters.
	 *
	 * The endpoint's routes are only registered when Tickets Commerce is on as the plugin loads, which this suite
	 * turns on later, so the endpoint methods are called directly.
	 *
	 * @param array<string,mixed> $params    The ticket parameters.
	 * @param string              $operation The operation, `create` or `update`.
	 *
	 * @return WP_REST_Response The endpoint's response.
	 */
	private function upsert_through_tec_rest_api( array $params, string $operation = 'create' ): WP_REST_Response {
		$endpoint = tribe( Ticket_Endpoint::class );

		return $endpoint->upsert( $endpoint->filter_upsert_params( $params ), $operation );
	}
}
