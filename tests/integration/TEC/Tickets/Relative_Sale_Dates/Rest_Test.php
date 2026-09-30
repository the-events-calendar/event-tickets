<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use Generator;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket as Ticket_Endpoint;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_REST_Request;
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
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

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
		$rule      = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $event_id, 'edit_ticket_nonce', [ 'ticket' => [ 'relative_sale_dates' => wp_json_encode( $rule ) ] ] );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_reject_a_window_the_block_editor_sends_that_ends_before_it_starts(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$rule     = [ 'start' => $this->relative( 1, HOUR_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ];

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
		$rule = [ 'start' => $this->relative( 1, HOUR_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ];

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
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

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
		$rule      = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];
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
				'relative_sale_dates' => [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ],
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
					'relative_sale_dates' => [ 'start' => $this->relative( 1, HOUR_IN_SECONDS ), 'end' => $this->relative( 2, HOUR_IN_SECONDS ) ],
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
		$rule      = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];
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
	 * @test
	 */
	public function should_store_the_sale_price_rule_the_block_editor_sends(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$rule        = $this->get_sale_price_rule();

		$response = $this->send_block_editor_ticket_save( 'POST', '/tickets', $event_id, 'add_ticket_nonce', $this->get_block_editor_sale_price( wp_json_encode( $rule ) ) );

		$this->assertFalse( $response->is_error() );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$ticket_id  = reset( $ticket_ids );
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id )[ Sale_Price_Rule::KEY ] );
		$this->assertSame( $event_start->modify( '-14 days' )->format( 'Y-m-d' ), get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ) );
	}

	/**
	 * @test
	 */
	public function should_remove_the_sale_price_rule_the_block_editor_sends_as_empty(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$this->send_block_editor_ticket_save( 'POST', '/tickets', $event_id, 'add_ticket_nonce', $this->get_block_editor_sale_price( wp_json_encode( $this->get_sale_price_rule() ) ) );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$ticket_id  = reset( $ticket_ids );

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $event_id, 'edit_ticket_nonce', $this->get_block_editor_sale_price( '' ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @return Generator<string,array{0: string, 1: array{sale_price: array<string,string|bool>}}>
	 */
	public function unmapped_block_editor_sale_price_provider(): Generator {
		yield 'a sale price rule sent with an RSVP' => [ 'tickets.rsvp', [ 'sale_price' => [ 'relative' => wp_json_encode( $this->get_sale_price_rule() ) ] ] ];
		yield 'a sale price sent without a rule' => [
			Module::class,
			[
				'sale_price' => [
					'checked' => true,
					'price'   => '10',
				],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider unmapped_block_editor_sale_price_provider
	 */
	public function should_leave_the_ticket_data_without_a_sale_price_rule_to_map( string $provider, array $ticket ): void {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'ticket', $ticket );

		$ticket_data = apply_filters( 'tec_tickets_rest_single_ticket_add_data', [ 'ticket_name' => 'Block editor ticket' ], $request, tribe( $provider ) );

		$this->assertArrayNotHasKey( Sale_Price_Save::DATA_KEY, $ticket_data );
	}

	/**
	 * @test
	 */
	public function should_return_the_stored_sale_price_rule_in_the_block_editor_ticket_data(): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );
		$rule      = $this->get_sale_price_rule();
		tribe( Rule_Store::class )->save( $ticket_id, [ Sale_Price_Rule::KEY => $rule ] );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $ticket_id );

		$this->assertSame( $rule, $data['sale_price_data']['relative'] );
	}

	/**
	 * @test
	 */
	public function should_return_null_in_the_block_editor_sale_price_data_of_a_ticket_without_a_sale_price_rule(): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );
		tribe( Rule_Store::class )->save( $ticket_id, [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ] );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $ticket_id );

		$this->assertArrayHasKey( 'relative', $data['sale_price_data'] );
		$this->assertNull( $data['sale_price_data']['relative'] );
	}

	/**
	 * @test
	 */
	public function should_not_add_a_sale_price_rule_to_the_block_editor_data_of_an_rsvp(): void {
		$rsvp_id = $this->create_ticket( 'tickets.rsvp', $this->create_event( '2027-06-24 19:00:00' ), 0 );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $rsvp_id );

		$this->assertArrayNotHasKey( 'relative', $data['sale_price_data'] );
	}

	/**
	 * @test
	 */
	public function should_store_and_return_the_sale_price_rule_sent_to_the_tec_rest_api(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$rule     = $this->get_sale_price_rule();

		$response = $this->upsert_through_tec_rest_api( $this->get_tec_rest_api_sale_price( $event_id, $rule ) );

		$ticket_id = $response->get_data()['id'];
		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id )[ Sale_Price_Rule::KEY ] );
		$this->assertSame( $rule, $response->get_data()[ Rest::SALE_PRICE_RULE_FIELD ] );
	}

	/**
	 * @test
	 */
	public function should_keep_the_sale_price_rule_when_a_tec_rest_api_update_leaves_it_out(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$rule      = $this->get_sale_price_rule();
		$ticket_id = $this->upsert_through_tec_rest_api( $this->get_tec_rest_api_sale_price( $event_id, $rule ) )->get_data()['id'];

		$this->upsert_through_tec_rest_api(
			[
				'id'    => $ticket_id,
				'title' => 'TEC REST ticket, renamed',
			],
			'update'
		);

		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id )[ Sale_Price_Rule::KEY ] );
	}

	/**
	 * @test
	 */
	public function should_remove_the_sale_price_rule_when_a_tec_rest_api_update_sends_null(): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->upsert_through_tec_rest_api( $this->get_tec_rest_api_sale_price( $event_id, $this->get_sale_price_rule() ) )->get_data()['id'];

		$this->upsert_through_tec_rest_api(
			[
				'id'                        => $ticket_id,
				Rest::SALE_PRICE_RULE_FIELD => null,
			],
			'update'
		);

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @return array{start: array{mode: string, value: int, unit: int}, end: array{mode: string, value: int, unit: int}} The sale price rule "14 days to 7 days before the event start".
	 */
	private function get_sale_price_rule(): array {
		return [
			'start' => [
				'mode'  => Rule::MODE_RELATIVE,
				'value' => 14,
				'unit'  => Rule::UNIT_DAYS,
			],
			'end'   => [
				'mode'  => Rule::MODE_RELATIVE,
				'value' => 7,
				'unit'  => Rule::UNIT_DAYS,
			],
		];
	}

	/**
	 * @param string $relative The sale price rule as the block editor sends it, as JSON, or `''` to remove it.
	 *
	 * @return array{price: string, ticket: array{sale_price: array{checked: bool, price: string, relative: string}}} The body of a ticket priced 20 with a sale price of 10.
	 */
	private function get_block_editor_sale_price( string $relative ): array {
		return [
			'price'  => '20',
			'ticket' => [
				'sale_price' => [
					'checked'  => true,
					'price'    => '10',
					'relative' => $relative,
				],
			],
		];
	}

	/**
	 * @param int                                                                                                      $event_id The event post ID.
	 * @param array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}} $rule     The sale price rule.
	 *
	 * @return array{event: int, title: string, price: int, sale_price: int, sale_price_relative: array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}} The parameters of a new ticket priced 20 with a sale price of 10.
	 */
	private function get_tec_rest_api_sale_price( int $event_id, array $rule ): array {
		return [
			'event'                     => $event_id,
			'title'                     => 'TEC REST ticket',
			'price'                     => 20,
			'sale_price'                => 10,
			Rest::SALE_PRICE_RULE_FIELD => $rule,
		];
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
