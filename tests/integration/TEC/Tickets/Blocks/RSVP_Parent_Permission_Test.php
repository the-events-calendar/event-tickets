<?php

namespace TEC\Tickets\Blocks;

use Closure;
use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;
use WP_REST_Request;
use WP_REST_Response;

/**
 * The core REST route for RSVPs only attaches an RSVP to a ticket-enabled post the user can edit.
 */
class RSVP_Parent_Permission_Test extends WPTestCase {
	use With_Ticket_Providers;

	/**
	 * @before
	 */
	public function hook_the_blocks_controller(): void {
		/*
		 * Each test restores the hooks snapshot taken by the first test of the run, and a `Controller_Test_Case`
		 * of the Blocks controller takes it after unregistering that controller. Without the meta the REST API
		 * drops the parent value; without the filter the permission check never runs. `ControllerTest` covers
		 * the controller adding the filter.
		 */
		tribe( 'tickets.editor.meta' )->register();
		add_filter( 'rest_pre_insert_tribe_rsvp_tickets', tribe_callback( RSVP_Parent_Permission::class, 'filter_rest_pre_insert' ), 10, 2 );
	}

	/**
	 * @test
	 */
	public function it_should_create_an_rsvp_on_a_post_the_user_can_edit(): void {
		$post_id = $this->log_in_as_author_with_own_post();

		$response = $this->create_request( $post_id );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( "{$post_id}", get_post_meta( $response->get_data()['id'], $this->event_key(), true ) );
	}

	/**
	 * @test
	 */
	public function it_should_let_an_editor_create_an_rsvp_on_another_authors_post(): void {
		$post_id = $this->log_in_as_editor_of_another_authors_post();

		$response = $this->create_request( $post_id );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( "{$post_id}", get_post_meta( $response->get_data()['id'], $this->event_key(), true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_an_rsvp_on_a_post_the_user_cannot_edit(): void {
		$other_post = static::factory()->post->create( [ 'post_author' => static::factory()->user->create( [ 'role' => 'editor' ] ) ] );
		$this->log_in_as_author_with_own_post();

		$response = $this->create_request( $other_post );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( [], $this->rsvps_of_current_user() );
	}

	/**
	 * @test
	 * @dataProvider unusable_parent_provider
	 */
	public function it_should_not_create_an_rsvp_on_a_parent_that_cannot_hold_tickets( Closure $make_parent ): void {
		$own_post = $this->log_in_as_author_with_own_post();

		$response = $this->create_request( $make_parent( $own_post ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( [], $this->rsvps_of_current_user() );
	}

	/**
	 * @return \Generator<string,array{0: Closure}> A callback turning the user's own post into a parent that cannot hold tickets.
	 */
	public function unusable_parent_provider(): \Generator {
		yield 'post type not enabled for tickets' => [
			static function ( int $own_post ): int {
				return static::factory()->attachment->create(
					[
						'post_author' => get_current_user_id(),
						'post_parent' => $own_post,
					]
				);
			},
		];

		yield 'post that does not exist' => [
			static function ( int $own_post ): int {
				wp_delete_post( $own_post, true );

				return $own_post;
			},
		];
	}

	/**
	 * @test
	 */
	public function it_should_not_move_an_rsvp_to_a_post_the_user_cannot_edit(): void {
		$other_post = static::factory()->post->create( [ 'post_author' => static::factory()->user->create( [ 'role' => 'editor' ] ) ] );
		$own_post   = $this->log_in_as_author_with_own_post();
		$rsvp_id    = $this->create_rsvp_ticket( $own_post, [ 'post_author' => get_current_user_id() ] );

		$response = $this->update_request( $rsvp_id, [ 'meta' => [ $this->event_key() => "{$other_post}" ] ] );

		$this->assertSame( 403, $response->get_status() );
		$this->assertEquals( $own_post, get_post_meta( $rsvp_id, $this->event_key(), true ) );
	}

	/**
	 * @test
	 */
	public function it_should_update_an_rsvp_when_the_parent_is_not_sent(): void {
		$own_post = $this->log_in_as_author_with_own_post();
		$rsvp_id  = $this->create_rsvp_ticket( $own_post, [ 'post_author' => get_current_user_id() ] );
		$title    = $this->faker->sentence();

		$response = $this->update_request( $rsvp_id, [ 'title' => $title ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $title, get_post_field( 'post_title', $rsvp_id, 'raw' ) );
	}

	private function event_key(): string {
		return tribe( 'tickets.rsvp' )->get_event_key();
	}

	private function create_request( int $parent_id ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/tribe_rsvp_tickets' );
		$request->set_param( 'title', $this->faker->sentence() );
		$request->set_param( 'status', 'publish' );
		$request->set_param( 'meta', [ $this->event_key() => "{$parent_id}" ] );

		return rest_do_request( $request );
	}

	private function update_request( int $rsvp_id, array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', "/wp/v2/tribe_rsvp_tickets/{$rsvp_id}" );

		foreach ( $params as $name => $value ) {
			$request->set_param( $name, $value );
		}

		return rest_do_request( $request );
	}

	/**
	 * @return int[] The IDs of the RSVPs the current user authored.
	 */
	private function rsvps_of_current_user(): array {
		return get_posts(
			[
				'post_type'   => 'tribe_rsvp_tickets',
				'post_status' => 'any',
				'author'      => get_current_user_id(),
				'fields'      => 'ids',
			]
		);
	}
}
