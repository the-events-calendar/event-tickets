<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_REST_Request;
use WP_REST_Response;

class Block_Save_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	private array $deferred_posts = [];

	public function setUp(): void {
		parent::setUp();
		add_filter(
			'tec_tickets_deferred_save_enabled',
			function ( bool $enabled, int $post_id ): bool {
				return in_array( $post_id, $this->deferred_posts, true ) ? true : $enabled;
			},
			10,
			2
		);
	}

	public function tearDown(): void {
		$this->deferred_posts = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function log_in_as_admin(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function create_deferred_post(): int {
		$post_id                = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->deferred_posts[] = $post_id;

		return $post_id;
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
	public function it_saves_the_payload_and_returns_created_ids_by_position(): void {
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
	public function a_rejected_entry_is_reported_while_the_rest_saves(): void {
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
	public function a_post_that_does_not_use_deferred_save_is_untouched_and_the_payload_is_answered_with_an_error(): void {
		$this->log_in_as_admin();
		$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$response = $this->save_through_rest(
			"/wp/v2/pages/{$post_id}",
			[ 'title' => 'Plain save', 'tec_tickets' => [ 'create' => [ $this->ticket_data( 'Should not exist' ) ] ] ]
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( [], $data['tec_tickets']['created'] );
		$this->assertCount( 1, $data['tec_tickets']['errors'] );
		$this->assertNull( $data['tec_tickets']['errors'][0]['part'] );
		$this->assertSame( [], $this->ticket_names( $post_id ) );
	}

	/**
	 * @test
	 */
	public function a_save_without_a_payload_gets_no_field(): void {
		$this->log_in_as_admin();
		$post_id = $this->create_deferred_post();

		$response = $this->save_through_rest( "/wp/v2/pages/{$post_id}", [ 'title' => 'No tickets here' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'tec_tickets', $response->get_data() );
	}

	/**
	 * @test
	 */
	public function an_autosave_commits_nothing(): void {
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
	public function a_ticket_is_attached_to_a_post_created_through_rest(): void {
		$this->log_in_as_admin();
		add_filter( 'tec_tickets_deferred_save_enabled', '__return_true' );

		$response = $this->save_through_rest(
			'/wp/v2/pages',
			[ 'title' => 'Brand new', 'status' => 'publish', 'tec_tickets' => [ 'create' => [ $this->ticket_data( 'On a new post' ) ] ] ]
		);

		$this->assertSame( 201, $response->get_status() );
		$new_post_id = $response->get_data()['id'];
		$this->assertSame( [ 'On a new post' ], $this->ticket_names( $new_post_id ) );
		$this->assertSame( [ 0 ], array_keys( $response->get_data()['tec_tickets']['created'] ) );
	}
}
