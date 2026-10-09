<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Main as Tickets_Main;
use WP_REST_Request;
use WP_REST_Response;

class Block_Save_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function log_in_as_admin(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function create_deferred_post(): int {
		return static::factory()->post->create( [ 'post_type' => 'page' ] );
	}

	protected function ticket_data( string $name, array $overrides = [] ): array {
		return array_merge(
			[
				'ticket_name'     => $name,
				'ticket_price'    => '10',
				'ticket_provider' => Module::class,
				'tribe-ticket'    => [ 'mode' => 'own', 'capacity' => '25' ],
			],
			$overrides
		);
	}

	protected function save_through_rest( string $route, array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_do_request( $request );
	}

	protected function ticket_names( int $post_id ): array {
		return array_map( 'get_the_title', tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_save_the_payload_and_return_created_ids_by_position(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$response = $this->save_through_rest(
			"/wp/v2/pages/{$post_id}",
			[
				'title'       => 'Saved from the block editor',
				'tec_tickets' => [ 'create' => [ $this->ticket_data( 'Block one' ), $this->ticket_data( 'Block two' ) ] ],
			]
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Saved from the block editor', $data['title']['raw'] );
		$this->assertArrayHasKey( 'tec_tickets', $data );
		$this->assertSame( [ 0, 1 ], array_keys( $data['tec_tickets']['created'] ) );
		$this->assertSame( [], $data['tec_tickets']['errors'] );
		$this->assertSame( [ 'Block one', 'Block two' ], array_map( 'get_the_title', $data['tec_tickets']['created'] ) );
		$this->assertEqualSets( $data['tec_tickets']['created'], tribe_tickets()->where( 'event', $post_id )->get_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_report_a_rejected_entry_while_the_rest_saves(): void {
		$this->log_in_as_admin();
		$post_id       = $this->create_deferred_post();
		$other_post_id = static::factory()->post->create();
		$foreign_id    = $this->create_tc_ticket( $other_post_id, 10 );

		$response = $this->save_through_rest(
			"/wp/v2/pages/{$post_id}",
			[
				'title'       => 'Partly saved',
				'tec_tickets' => [
					'update' => [ $foreign_id => [ 'ticket_name' => 'Not yours' ] ],
					'create' => [ $this->ticket_data( 'Mine' ) ],
				],
			]
		);

		$data = $response->get_data();
		$this->assertSame( 'Partly saved', $data['title']['raw'] );
		$this->assertSame( [ 0 ], array_keys( $data['tec_tickets']['created'] ) );
		$this->assertCount( 1, $data['tec_tickets']['errors'] );
		$this->assertSame( 'update', $data['tec_tickets']['errors'][0]['part'] );
		$this->assertSame( $foreign_id, $data['tec_tickets']['errors'][0]['key'] );
		$this->assertNotSame( 'Not yours', get_the_title( $foreign_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_save_a_non_ticketable_post_type_without_a_field_and_without_tickets(): void {
		$this->log_in_as_admin();
		$this->assertNotContains( 'attachment', Tickets_Main::instance()->post_types(), 'Attachments must not be ticketable here.' );
		$attachment_id = static::factory()->attachment->create_object( 'image.jpg', 0, [ 'post_mime_type' => 'image/jpeg' ] );

		$response = $this->save_through_rest(
			"/wp/v2/media/{$attachment_id}",
			[ 'title' => 'Plain save', 'tec_tickets' => [ 'create' => [ $this->ticket_data( 'Should not exist' ) ] ] ]
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'tec_tickets', $response->get_data() );
		$this->assertSame( [], $this->ticket_names( $attachment_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_add_no_field_to_a_save_without_a_payload(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$response = $this->save_through_rest( "/wp/v2/pages/{$post_id}", [ 'title' => 'No tickets here' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'tec_tickets', $response->get_data() );
	}

	/**
	 * @test
	 */
	public function it_should_commit_nothing_on_an_autosave(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$response = $this->save_through_rest(
			"/wp/v2/pages/{$post_id}/autosaves",
			[ 'title' => 'Autosaved', 'content' => 'Autosave body', 'tec_tickets' => [ 'create' => [ $this->ticket_data( 'From an autosave' ) ] ] ]
		);

		$this->assertContains( $response->get_status(), [ 200, 201 ] );
		$this->assertArrayNotHasKey( 'tec_tickets', $response->get_data() );
		$this->assertSame( [], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_attach_a_ticket_to_a_post_created_through_rest(): void {
		$this->log_in_as_admin();

		$response = $this->save_through_rest(
			'/wp/v2/pages',
			[ 'title' => 'Brand new', 'status' => 'publish', 'tec_tickets' => [ 'create' => [ $this->ticket_data( 'On a new post' ) ] ] ]
		);

		$this->assertSame( 201, $response->get_status() );
		$new_post_id = $response->get_data()['id'];
		$this->assertSame( [ 'On a new post' ], $this->ticket_names( $new_post_id ) );
		$this->assertSame( [ 0 ], array_keys( $response->get_data()['tec_tickets']['created'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_answer_only_the_request_that_committed_the_payload(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$this->save_through_rest( "/wp/v2/pages/{$post_id}", [ 'tec_tickets' => [ 'create' => [ $this->ticket_data( 'Once' ) ] ] ] );
		$second_save = $this->save_through_rest( "/wp/v2/pages/{$post_id}", [ 'title' => 'Saved again' ] );
		$read        = rest_do_request( new WP_REST_Request( 'GET', "/wp/v2/pages/{$post_id}" ) );

		$this->assertArrayNotHasKey( 'tec_tickets', $second_save->get_data() );
		$this->assertArrayNotHasKey( 'tec_tickets', $read->get_data() );
	}

	/**
	 * @test
	 */
	public function it_should_tell_two_equal_answers_apart(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$first  = $this->save_through_rest( "/wp/v2/pages/{$post_id}", [ 'tec_tickets' => [] ] )->get_data()['tec_tickets'];
		$second = $this->save_through_rest( "/wp/v2/pages/{$post_id}", [ 'tec_tickets' => [] ] )->get_data()['tec_tickets'];

		$this->assertSame( [ 'created' => [], 'errors' => [] ], array_diff_key( $first, [ 'id' => true ] ) );
		$this->assertNotEmpty( $first['id'] );
		$this->assertNotSame( $first['id'], $second['id'] );
	}

	/**
	 * @test
	 */
	public function it_should_pass_on_a_prepared_answer_that_is_not_a_response(): void {
		// The filter runs on every read of every ticketable type; an earlier callback may hand on anything.
		$post_id = $this->create_deferred_post();
		$error   = new \WP_Error( 'earlier_callback', 'Not a response' );

		$answer = tribe( Block_Save::class )->add_result_to_response( $error, get_post( $post_id ), new WP_REST_Request() );

		$this->assertSame( $error, $answer );
	}
}
