<?php

namespace TEC\Tickets\Deferred_Save\Security;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Deferred_Save\Block_Save;
use Tribe\Tests\Tickets\Traits\Deferred_Save_Attacks;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Attacks through the block editor's post save: real REST requests as the user in question.
 */
class Rest_Entry_Point_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Deferred_Save_Attacks;

	public function setUp(): void {
		parent::setUp();
		$this->enable_switch_filter();
		// The REST hooks are attached per ticketable type at boot; `post` becomes ticketable only in this suite.
		$block_save = tribe( Block_Save::class );
		add_action( 'rest_after_insert_post', [ $block_save, 'on_rest_after_insert' ], Block_Save::PRIORITY, 3 );
		add_filter( 'rest_prepare_post', [ $block_save, 'add_result_to_response' ], 10, 3 );
	}

	public function tearDown(): void {
		$this->deferred_posts = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function save_through_rest( int $post_id, array $payload ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_param( 'title', get_the_title( $post_id ) . ' saved' );
		$request->set_param( 'tec_tickets', $payload );

		return rest_do_request( $request );
	}

	protected function result_of( WP_REST_Response $response ): ?array {
		$data = $response->get_data();

		return is_array( $data ) && isset( $data['tec_tickets'] ) ? $data['tec_tickets'] : null;
	}

	public function roles_that_cannot_edit_someone_elses_post(): Generator {
		yield 'logged out' => [ '' ];
		yield 'subscriber' => [ 'subscriber' ];
		yield 'contributor' => [ 'contributor' ];
		yield 'author' => [ 'author' ];
	}

	/**
	 * @test
	 * @dataProvider roles_that_cannot_edit_someone_elses_post
	 */
	public function a_user_who_cannot_edit_the_post_is_refused_by_rest_itself( string $role ): void {
		$this->given_two_posts_with_tickets();
		if ( '' === $role ) {
			wp_set_current_user( 0 );
		} else {
			$this->log_in_as( $role );
		}
		$before = $this->snapshot();

		$response = $this->save_through_rest( $this->post_a, $this->hostile_payload() );

		$this->assertContains( $response->get_status(), [ 401, 403 ], 'The posts controller refuses the save before any ticket code runs.' );
		$this->assert_world_unchanged( $before, "A $role changed something through the REST save." );
	}

	/**
	 * @test
	 */
	public function an_author_of_post_a_cannot_reach_post_b_through_any_part(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		wp_update_post( [ 'ID' => $this->post_b, 'post_author' => static::factory()->user->create( [ 'role' => 'administrator' ] ) ] );
		$author_of_a = $this->log_in_as( 'author' );
		wp_update_post( [ 'ID' => $this->post_a, 'post_author' => $author_of_a ] );
		$before = $this->snapshot();

		$response = $this->save_through_rest( $this->post_a, $this->hostile_payload() );

		$this->assertSame( 200, $response->get_status() );
		$this->assert_b_untouched_and_a_created( $before );
		$result = $this->result_of( $response );
		$this->assertNotNull( $result );
		$this->assertSame( [ 0 ], array_keys( $result['created'] ) );
		$keys = array_map( fn( array $e ) => [ $e['part'], $e['key'] ], $result['errors'] );
		$this->assertEqualSets(
			[
				[ 'update', $this->ticket_b ],
				[ 'update', $this->attendee_b ],
				[ 'update', $this->post_b ],
				[ 'move', $this->ticket_a ],
			],
			$keys
		);
	}

	/**
	 * @test
	 */
	public function a_payload_over_the_cap_changes_nothing(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		add_filter( 'tec_tickets_deferred_save_max_entries', static fn() => 3 );
		$before = $this->snapshot();

		$response = $this->save_through_rest( $this->post_a, [ 'create' => array_fill( 0, 4, $this->ticket_data( 'Flood' ) ) ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assert_world_unchanged( $before );
		$this->assertCount( 1, $this->result_of( $response )['errors'] );
	}

	/**
	 * @test
	 */
	public function a_payload_for_a_post_that_does_not_defer_is_refused_and_answered(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		$plain = static::factory()->post->create( [ 'post_author' => $this->owner_id ] );

		$response = $this->save_through_rest( $plain, [ 'create' => [ $this->ticket_data( 'Should not exist' ) ] ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [], tribe_tickets()->where( 'event', $plain )->get_ids() );
		$result = $this->result_of( $response );
		$this->assertSame( [], $result['created'] );
		$this->assertNull( $result['errors'][0]['part'] );
	}

	/**
	 * @test
	 */
	public function an_autosave_never_commits(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( $this->owner_id );
		$before = $this->snapshot();

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$this->post_a}/autosaves" );
		$request->set_param( 'title', 'Autosaved' );
		$request->set_param( 'content', 'Autosave body' );
		$request->set_param( 'tec_tickets', [ 'create' => [ $this->ticket_data( 'From an autosave' ) ] ] );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), [ 200, 201 ] );
		$this->assert_world_unchanged( $before );
	}

	/**
	 * @test
	 */
	public function data_is_sanitized_exactly_as_the_ajax_save_sanitizes_it(): void {
		$this->given_two_posts_with_tickets();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$data = $this->ticket_data(
			'<b>Bold</b> <script>alert(1)</script> name',
			[
				'ticket_description' => '<p>Kept</p><script>alert(2)</script><img src=x onerror=alert(3)>',
				'ticket_sku'         => 'SKU <i>x</i>',
			]
		);

		$post_c = static::factory()->post->create();
		$_POST  = [
			'post_id'     => $post_c,
			'data'        => $data,
			'ticket_type' => 'default',
			'nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
		];
		$this->assertIsArray( tribe( 'tickets.metabox' )->ajax_ticket_add( true ) );
		$_POST    = [];
		$ajax_ids = tribe_tickets()->where( 'event', $post_c )->get_ids();
		$ajax_id  = (int) reset( $ajax_ids );

		$response    = $this->save_through_rest( $this->post_a, [ 'create' => [ $data ] ] );
		$deferred_id = (int) ( $this->result_of( $response )['created'][0] ?? 0 );

		$this->assertGreaterThan( 0, $ajax_id );
		$this->assertGreaterThan( 0, $deferred_id );
		$stored = fn( int $id ) => [
			'title'   => get_post_field( 'post_title', $id, 'raw' ),
			'excerpt' => get_post_field( 'post_excerpt', $id, 'raw' ),
			'sku'     => get_post_meta( $id, '_sku', true ),
			'price'   => get_post_meta( $id, '_price', true ),
		];
		$this->assertSame( $stored( $ajax_id ), $stored( $deferred_id ) );
	}
}
