<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use Generator;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\REST\TEC\V1\Endpoints\Event as Event_Endpoint;
use TEC\Tickets\Commerce\Module;
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
	 * @dataProvider rule_kind_provider
	 */
	public function should_store_the_rule_the_block_editor_sends_with_a_new_ticket( array $kind ): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );

		$response = $this->send_block_editor_ticket_save( 'POST', '/tickets', $event_id, 'add_ticket_nonce', $kind['block_editor_body']( wp_json_encode( $kind['rule'] ) ) );

		$this->assertFalse( $response->is_error() );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$this->assertCount( 1, $ticket_ids );
		$this->assertSame( $kind['rule'], $this->get_stored_rule( reset( $ticket_ids ), $kind ) );
		$this->assert_start_resolved_from( $event_start, reset( $ticket_ids ), $kind );
	}

	/**
	 * @test
	 * @dataProvider rule_kind_provider
	 */
	public function should_store_the_rule_the_block_editor_sends_with_a_ticket_update( array $kind ): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $event_id, 'edit_ticket_nonce', $kind['block_editor_body']( wp_json_encode( $kind['rule'] ) ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( $kind['rule'], $this->get_stored_rule( $ticket_id, $kind ) );
		// The Ticket block keeps as saved the rule a save answers with.
		$this->assertSame( $kind['rule'], $this->get_at_path( $response->get_data(), $kind['response_path'] ) );
		$this->assertSame( 'tc', $response->get_data()['provider'] );
	}

	/**
	 * @test
	 * @dataProvider rule_kind_provider
	 */
	public function should_remove_the_rule_the_block_editor_sends_as_empty( array $kind ): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );
		$this->send_block_editor_ticket_save( 'POST', '/tickets', $event_id, 'add_ticket_nonce', $kind['block_editor_body']( wp_json_encode( $kind['rule'] ) ) );
		$ticket_ids = tribe( Module::class )->get_tickets_ids( $event_id );
		$ticket_id  = reset( $ticket_ids );
		$this->assertSame( $kind['rule'], $this->get_stored_rule( $ticket_id, $kind ) );

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $event_id, 'edit_ticket_nonce', $kind['block_editor_body']( '' ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * The Ticket block keeps the dates the save answers with, so a relative rule must answer with what it resolves to.
	 *
	 * @test
	 */
	public function should_answer_a_block_editor_save_with_the_dates_the_rule_resolves_to(): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_tc_ticket( $event_id );
		$rule        = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => $this->relative( 1, DAY_IN_SECONDS ) ];

		$response = $this->send_block_editor_ticket_save( 'PUT', "/tickets/{$ticket_id}", $event_id, 'edit_ticket_nonce', [ 'ticket' => [ 'relative_sale_dates' => wp_json_encode( $rule ) ] ] );

		$data = $response->get_data();
		$this->assertSame( $event_start->modify( '-2 weeks' )->format( 'Y-m-d H:i:s' ), $data['available_from'] );
		$this->assertSame( $event_start->modify( '-1 day' )->format( 'Y-m-d H:i:s' ), $data['available_until'] );
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
	 * @dataProvider rule_kind_provider
	 */
	public function should_store_and_return_the_rule_sent_to_the_tec_rest_api_with_a_new_ticket( array $kind ): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );

		$response = $this->upsert_through_tec_rest_api( $this->get_tec_rest_api_params( $event_id, $kind ) );

		$ticket_id = $response->get_data()['id'];
		$this->assertSame( $kind['rule'], $this->get_stored_rule( $ticket_id, $kind ) );
		$this->assertSame( $kind['rule'], $response->get_data()[ $kind['field'] ] );
		$this->assert_start_resolved_from( $event_start, $ticket_id, $kind );
	}

	/**
	 * @test
	 * @dataProvider rule_kind_provider
	 */
	public function should_keep_the_rule_when_a_tec_rest_api_update_leaves_it_out( array $kind ): void {
		$event_start = new DateTimeImmutable( '2027-06-24 19:00:00' );
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->upsert_through_tec_rest_api( $this->get_tec_rest_api_params( $event_id, $kind ) )->get_data()['id'];

		$this->upsert_through_tec_rest_api( array_merge( [ 'id' => $ticket_id ], $kind['tec_rest_api_update'] ), 'update' );

		$this->assertSame( $kind['rule'], $this->get_stored_rule( $ticket_id, $kind ) );
		// The rule is applied again, so its dates win over any the update sends.
		$this->assert_start_resolved_from( $event_start, $ticket_id, $kind );
	}

	/**
	 * @test
	 * @dataProvider rule_kind_provider
	 */
	public function should_remove_the_rule_when_a_tec_rest_api_update_sends_null( array $kind ): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->upsert_through_tec_rest_api( $this->get_tec_rest_api_params( $event_id, $kind ) )->get_data()['id'];
		$this->assertNotSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );

		$this->upsert_through_tec_rest_api(
			[
				'id'           => $ticket_id,
				$kind['field'] => null,
			],
			'update'
		);

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
	}

	/**
	 * @test
	 * @dataProvider rule_kind_provider
	 */
	public function should_return_the_stored_rule_when_the_tec_rest_api_reads_a_ticket( array $kind ): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );
		tribe( Rule_Store::class )->save( $ticket_id, $this->get_stored_values( $kind ) );

		$response = tribe( Ticket_Endpoint::class )->read( [ 'id' => $ticket_id ] );

		$this->assertSame( $kind['rule'], $response->get_data()[ $kind['field'] ] );
	}

	/**
	 * The request schema drops `null` values, so a rule sent as `null` must be put back to reach the ticket save.
	 *
	 * @test
	 * @dataProvider rule_kind_provider
	 */
	public function should_pass_a_rule_sent_as_null_to_the_tec_rest_api_through_to_the_ticket_save( array $kind ): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );
		$endpoint  = tribe( Ticket_Endpoint::class );

		$request_data = $endpoint->update_schema()->filter_before_request(
			[
				'id'           => $ticket_id,
				$kind['field'] => null,
			]
		);
		$params       = $endpoint->filter_upsert_params( $request_data );

		// An empty rule is the one the ticket save removes.
		$this->assertSame( '', $params['ticket_params'][ $kind['data_key'] ] ?? 'missing' );
		$this->assertArrayNotHasKey( $kind['field'], $params['post_params'] );
	}

	/**
	 * Every TEC REST API endpoint runs the same request filter, and an event saves the keys it is sent as its meta.
	 *
	 * @test
	 */
	public function should_not_put_a_rule_sent_as_null_back_into_a_request_whose_schema_does_not_document_it(): void {
		$event_id = $this->create_event( '2027-06-24 19:00:00' );

		$request_data = tribe( Event_Endpoint::class )->update_schema()->filter_before_request(
			[
				'id'                  => $event_id,
				'relative_sale_dates' => null,
				'sale_price_relative' => null,
			]
		);

		$this->assertArrayNotHasKey( 'relative_sale_dates', $request_data );
		$this->assertArrayNotHasKey( 'sale_price_relative', $request_data );
	}

	/**
	 * Only `null` removes the rule; an empty object is a rule without its start and end.
	 *
	 * @test
	 */
	public function should_reject_an_empty_rule_sent_to_the_tec_rest_api_and_keep_the_stored_one(): void {
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

		try {
			$this->upsert_through_tec_rest_api(
				[
					'id'                  => $ticket_id,
					'relative_sale_dates' => [],
				],
				'update'
			);
			$this->fail( 'The empty rule should have been rejected.' );
		} catch ( InvalidRestArgumentException $e ) {
			$this->assertSame( 400, $e->to_wp_error()->get_error_data()['status'] );
		}

		$this->assertSame( $rule, tribe( Rule_Store::class )->get( $ticket_id ) );
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
	 * @dataProvider rule_kind_provider
	 */
	public function should_return_the_stored_rule_in_the_block_editor_ticket_data( array $kind ): void {
		$event_id  = $this->create_event( '2027-06-24 19:00:00' );
		$ticket_id = $this->create_tc_ticket( $event_id );
		tribe( Rule_Store::class )->save( $ticket_id, $this->get_stored_values( $kind ) );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $ticket_id );

		$this->assertSame( $kind['rule'], $this->get_at_path( $data, $kind['response_path'] ) );
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>, 1: bool}>
	 */
	public function rule_kind_without_its_rule_provider(): Generator {
		foreach ( $this->rule_kind_provider() as $name => [ $kind ] ) {
			yield "{$name}, no rule stored" => [ $kind, false ];
			// The other kind's rule is stored next to it, and must not be read as this kind's.
			yield "{$name}, only the other rule stored" => [ $kind, true ];
		}
	}

	/**
	 * @test
	 * @dataProvider rule_kind_without_its_rule_provider
	 */
	public function should_return_null_in_the_block_editor_ticket_data_of_a_ticket_without_a_rule( array $kind, bool $stores_the_other_rule ): void {
		$ticket_id = $this->create_tc_ticket( $this->create_event( '2027-06-24 19:00:00' ) );

		if ( $stores_the_other_rule ) {
			tribe( Rule_Store::class )->save( $ticket_id, $kind['other_stored'] );
		}

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $ticket_id );

		$path = $kind['response_path'];
		$key  = array_pop( $path );
		$this->assertArrayHasKey( $key, $this->get_at_path( $data, $path ) );
		$this->assertNull( $this->get_at_path( $data, $kind['response_path'] ) );
	}

	/**
	 * @test
	 */
	public function should_not_add_a_sale_price_rule_to_the_block_editor_data_of_an_rsvp(): void {
		$rsvp_id = $this->create_ticket( 'tickets.rsvp', $this->create_event( '2027-06-24 19:00:00' ), 0 );

		$data = tribe( 'tickets.rest-v1.repository' )->get_ticket_data( $rsvp_id );

		$this->assertArrayNotHasKey( 'relative', $data['sale_price_data'] );
		// The sales window rule is returned for every ticket, as `null` for one it does not apply to.
		$this->assertArrayHasKey( 'relative_sale_dates', $data );
		$this->assertNull( $data['relative_sale_dates'] );
	}

	/**
	 * @return Generator<string,array{0: string, 1: array<string,mixed>, 2: string}>
	 */
	public function unmapped_block_editor_rule_provider(): Generator {
		yield 'a sales window rule sent with an RSVP' => [
			'tickets.rsvp',
			[ 'relative_sale_dates' => wp_json_encode( [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ] ) ],
			'relative_sale_dates',
		];
		yield 'a sale price rule sent with an RSVP' => [ 'tickets.rsvp', [ 'sale_price' => [ 'relative' => wp_json_encode( $this->get_sale_price_rule() ) ] ], 'ticket_sale_price_relative' ];
		yield 'a ticket sent without a sales window rule' => [ Module::class, [ 'mode' => 'own' ], 'relative_sale_dates' ];
		yield 'a sale price sent without a rule' => [
			Module::class,
			[
				'sale_price' => [
					'checked' => true,
					'price'   => '10',
				],
			],
			'ticket_sale_price_relative',
		];
	}

	/**
	 * @test
	 * @dataProvider unmapped_block_editor_rule_provider
	 */
	public function should_leave_the_ticket_data_without_a_rule_to_map( string $provider, array $ticket, string $data_key ): void {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'ticket', $ticket );

		$ticket_data = apply_filters( 'tec_tickets_rest_single_ticket_add_data', [ 'ticket_name' => 'Block editor ticket' ], $request, tribe( $provider ) );

		$this->assertArrayNotHasKey( $data_key, $ticket_data );
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>, 1: string}>
	 */
	public function rule_kind_definition_provider(): Generator {
		foreach ( $this->rule_kind_provider() as $name => [ $kind ] ) {
			yield "{$name}, request body definition" => [ $kind, 'tec_rest_swagger_ticket_request_body_definition' ];
			yield "{$name}, ticket definition" => [ $kind, 'tec_rest_swagger_ticket_definition' ];
		}
	}

	/**
	 * @test
	 * @dataProvider rule_kind_definition_provider
	 */
	public function should_document_the_rule_in_the_tec_rest_api_definitions( array $kind, string $filter ): void {
		$property = $this->get_documented_properties( $filter )[ $kind['field'] ] ?? null;

		$this->assertIsArray( $property );
		$this->assertSame( 'object', $property['type'] );
		$this->assertSame( [ 'start', 'end' ], array_keys( $property['properties'] ) );
		$this->assertSame( $kind['modes']['start'], $property['properties']['start']['properties']['mode']['enum'] );
		$this->assertSame( $kind['modes']['end'], $property['properties']['end']['properties']['mode']['enum'] );
		$this->assertSame( $kind['boundary_keys'], array_keys( $property['properties']['start']['properties'] ) );
	}

	/**
	 * The fixtures of each kind of rule, with the keys that carry it written as the REST APIs send them.
	 *
	 * Both rules start two weeks before the event start.
	 *
	 * @return Generator<string,array{0: array{field: string, data_key: string, store_key: ?string, rule: array{start: array<string,int|string>, end: array<string,int|string>}, other_stored: array<string,mixed>, tec_rest_api_params: array<string,int>, tec_rest_api_update: array<string,int|string>, block_editor_body: callable(string): array<string,mixed>, response_path: string[], start_formats: array<string,string>, modes: array{start: string[], end: string[]}, boundary_keys: string[]}}>
	 */
	public function rule_kind_provider(): Generator {
		$sales_rule = [ 'start' => $this->relative( 2, WEEK_IN_SECONDS ), 'end' => [ 'mode' => 'default' ] ];

		yield 'sales window' => [
			[
				'field'               => 'relative_sale_dates',
				'data_key'            => 'relative_sale_dates',
				'store_key'           => null,
				'rule'                => $sales_rule,
				'other_stored'        => [ 'sale_price' => $this->get_sale_price_rule() ],
				'tec_rest_api_params' => [ 'price' => 10 ],
				'tec_rest_api_update' => [ 'price' => 20 ],
				'block_editor_body'   => static fn( string $relative ): array => [ 'ticket' => [ 'relative_sale_dates' => $relative ] ],
				'response_path'       => [ 'relative_sale_dates' ],
				'start_formats'       => [
					'_ticket_start_date' => 'Y-m-d',
					'_ticket_start_time' => 'H:i:s',
				],
				'modes'               => [
					'start' => [ 'default', 'relative', 'specific' ],
					'end'   => [ 'default', 'relative', 'specific' ],
				],
				'boundary_keys'       => [ 'mode', 'value', 'unit', 'anchor' ],
			],
		];
		yield 'sale price' => [
			[
				'field'               => 'sale_price_relative',
				'data_key'            => 'ticket_sale_price_relative',
				'store_key'           => 'sale_price',
				'rule'                => $this->get_sale_price_rule(),
				'other_stored'        => $sales_rule,
				'tec_rest_api_params' => [
					'price'      => 20,
					'sale_price' => 10,
				],
				'tec_rest_api_update' => [
					'title'                 => 'TEC REST ticket, renamed',
					'sale_price'            => 10,
					'sale_price_start_date' => '2027-01-01',
				],
				'block_editor_body'   => fn( string $relative ): array => $this->get_block_editor_sale_price( $relative ),
				'response_path'       => [ 'sale_price_data', 'relative' ],
				'start_formats'       => [ '_sale_price_start_date' => 'Y-m-d' ],
				'modes'               => [
					'start' => [ 'now', 'relative', 'specific' ],
					'end'   => [ 'relative', 'specific' ],
				],
				'boundary_keys'       => [ 'mode', 'value', 'unit' ],
			],
		];
	}

	/**
	 * @param int                  $ticket_id The ticket post ID.
	 * @param array<string,mixed>  $kind      The fixtures of the kind of rule.
	 *
	 * @return array<string,mixed> The rule of the kind stored for the ticket, or an empty array.
	 */
	private function get_stored_rule( int $ticket_id, array $kind ): array {
		$stored = tribe( Rule_Store::class )->get( $ticket_id );

		return null === $kind['store_key'] ? $stored : ( $stored[ $kind['store_key'] ] ?? [] );
	}

	/**
	 * @param array<string,mixed> $kind The fixtures of the kind of rule.
	 *
	 * @return array<string,mixed> The values to store for the kind's rule, the way the rules meta keeps it.
	 */
	private function get_stored_values( array $kind ): array {
		return null === $kind['store_key'] ? $kind['rule'] : [ $kind['store_key'] => $kind['rule'] ];
	}

	/**
	 * @param array<string,mixed> $data The data to read.
	 * @param string[]            $path The keys to follow.
	 *
	 * @return mixed The value at the path, or `null` when the path is not in the data.
	 */
	private function get_at_path( array $data, array $path ) {
		return array_reduce( $path, static fn( $value, string $key ) => is_array( $value ) ? ( $value[ $key ] ?? null ) : null, $data );
	}

	/**
	 * Asserts the start of the kind's window was written as the fixture rule resolves it: two weeks before the event start.
	 *
	 * @param DateTimeImmutable   $event_start The event start.
	 * @param int                 $ticket_id   The ticket post ID.
	 * @param array<string,mixed> $kind        The fixtures of the kind of rule.
	 *
	 * @return void
	 */
	private function assert_start_resolved_from( DateTimeImmutable $event_start, int $ticket_id, array $kind ): void {
		$start = $event_start->modify( '-2 weeks' );

		$this->assertSame(
			array_map( static fn( string $format ): string => $start->format( $format ), $kind['start_formats'] ),
			array_combine( array_keys( $kind['start_formats'] ), array_map( static fn( string $meta ) => get_post_meta( $ticket_id, $meta, true ), array_keys( $kind['start_formats'] ) ) )
		);
	}

	/**
	 * @param int                 $event_id The event post ID.
	 * @param array<string,mixed> $kind     The fixtures of the kind of rule.
	 *
	 * @return array<string,mixed> The parameters of a new ticket sent with the kind's rule.
	 */
	private function get_tec_rest_api_params( int $event_id, array $kind ): array {
		return array_merge(
			[
				'event' => $event_id,
				'title' => 'TEC REST ticket',
			],
			$kind['tec_rest_api_params'],
			[ $kind['field'] => $kind['rule'] ]
		);
	}

	/**
	 * @return array{start: array{mode: string, value: int, unit: int}, end: array{mode: string, value: int, unit: int}} The sale price rule "14 days to 7 days before the event start".
	 */
	private function get_sale_price_rule(): array {
		return [
			'start' => [
				'mode'  => 'relative',
				'value' => 14,
				'unit'  => DAY_IN_SECONDS,
			],
			'end'   => [
				'mode'  => 'relative',
				'value' => 7,
				'unit'  => DAY_IN_SECONDS,
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
	 * @param string $filter The definition filter.
	 *
	 * @return array<string,array{type: string, properties?: array<string,array<string,mixed>>}> The properties the feature adds to the definition, by name.
	 */
	private function get_documented_properties( string $filter ): array {
		$definition = json_decode( wp_json_encode( apply_filters( $filter, [] ) ), true );

		return array_merge( [], ...array_column( $definition['allOf'] ?? [], 'properties' ) );
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
