<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Endpoints;

use TEC\Tickets\Commerce\Ticket as Ticket_CPT;
use TEC\Tickets\REST\TEC\V1\Endpoints\Tickets;
use Tribe__Tickets__Tickets as Tickets_API;
use Closure;
use Generator;

class Tickets_Test extends Ticket_Test {
	protected $endpoint_class = Tickets::class;

	/**
	 * Roles that hold `create_posts` for tickets but cannot edit a post owned by someone else.
	 *
	 * @return Generator
	 */
	public function roles_without_others_edit_provider(): Generator {
		yield 'contributor' => [ 'contributor' ];
		yield 'author'      => [ 'author' ];
	}

	/**
	 * A user who cannot edit the target event must not be able to create a ticket on it, nor
	 * change the event's stock/capacity through the create request. SVUL-133.
	 *
	 * @dataProvider roles_without_others_edit_provider
	 */
	public function test_create_denied_when_user_cannot_edit_event( string $role ): void {
		$event = self::factory()->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => 1,
			]
		);

		wp_set_current_user( $this->factory()->user->create( [ 'role' => $role ] ) );

		$this->assert_endpoint(
			'/tickets',
			'POST',
			403,
			[ 'event' => $event, 'title' => 'Sneaky ticket', 'price' => 5, 'event_capacity' => 999 ]
		);

		$attached = get_posts(
			[
				'post_type'   => Ticket_CPT::POSTTYPE,
				'post_status' => 'any',
				'meta_key'    => Ticket_CPT::$event_relation_meta_key,
				'meta_value'  => $event,
				'fields'      => 'ids',
			]
		);
		$this->assertEmpty( $attached, 'No ticket should be attached to an event the user cannot edit.' );
		$this->assertEmpty( get_post_meta( $event, '_tribe_ticket_global_stock_level', true ), 'The event stock must not be changed.' );
	}

	/**
	 * The author of the event can create a ticket on it. SVUL-133.
	 */
	public function test_create_allowed_for_author_of_the_event(): void {
		$author = $this->factory()->user->create( [ 'role' => 'author' ] );
		$event  = self::factory()->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $author,
			]
		);

		wp_set_current_user( $author );

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, [ 'event' => $event, 'title' => 'Legit ticket', 'price' => 5 ] );

		$this->assertSame( $event, (int) get_post_meta( $response['id'], Ticket_CPT::$event_relation_meta_key, true ) );

		wp_delete_post( $response['id'], true );
	}

	/**
	 * An editor holds `edit_others_posts`, so may create a ticket on another user's event. SVUL-133.
	 */
	public function test_create_allowed_for_editor_on_others_event(): void {
		$event = self::factory()->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => 1,
			]
		);

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, [ 'event' => $event, 'title' => 'Editor ticket', 'price' => 5 ] );

		$this->assertSame( $event, (int) get_post_meta( $response['id'], Ticket_CPT::$event_relation_meta_key, true ) );

		wp_delete_post( $response['id'], true );
	}

	/**
	 * @dataProvider status_scale_back_provider
	 */
	public function test_create_scales_back_status_to_user_capabilities( string $role, ?string $status, string $expected_status ) {
		$this->grant_event_edit_for_current_user();

		parent::test_create_scales_back_status_to_user_capabilities( $role, $status, $expected_status );
	}

	/**
	 * @dataProvider author_scale_back_provider
	 */
	public function test_create_scales_back_author_to_user_capabilities( string $role, bool $can_set_author ) {
		$this->grant_event_edit_for_current_user();

		parent::test_create_scales_back_author_to_user_capabilities( $role, $can_set_author );
	}

	/**
	 * @dataProvider different_user_roles_provider
	 */
	public function test_create_responses( Closure $fixture ) {
		$this->grant_event_edit_for_current_user();

		parent::test_create_responses( $fixture );
	}

	public function test_get_formatted_entity() {
		[ $ticketable_posts, $tickets ] = $this->create_test_data();

		$data = [];
		foreach ( $tickets as $ticket ) {
			// Get the ticket post object directly
			$ticket_post = tec_tc_get_ticket( $ticket );
			$data[] = $this->endpoint->get_formatted_entity( $ticket_post );
		}

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace( $ticketable_posts, '{POST_ID}', $json );
		$json = str_replace( $tickets, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}

	/**
	 * @dataProvider different_user_roles_provider
	 */
	public function test_read_responses( Closure $fixture ) {
		[ $ticketable_posts, $tickets ] = $this->create_test_data();
		$fixture();

		$responses = [];
		foreach ( $tickets as $ticket_id ) {
			// Get the ticket post object
			$ticket_object = Tickets_API::load_ticket_object( $ticket_id );

			// Get the parent post to check its status
			$parent_post_id = $ticket_object->get_event_id();
			$parent_post = $ticket_object->get_event();

			if ( $parent_post && 'publish' === $parent_post->post_status ) {
				if ( empty( $parent_post->post_password ) ) {
					$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id );
				} else {
					$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', ( is_user_logged_in() ? 403 : 401 ) );
				}
			} else {
				// Private/draft/password-protected parent - check permissions
				$should_pass = is_user_logged_in() && current_user_can( 'read_post', $parent_post_id );
				$response = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', $should_pass ? 200 : ( is_user_logged_in() ? 403 : 401 ) );
				if ( $should_pass ) {
					$responses[] = $response;
				}
			}
		}

		$json = wp_json_encode( $responses, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace( $ticketable_posts, '{POST_ID}', $json );
		$json = str_replace( $tickets, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}

	/**
	 * @dataProvider different_user_roles_provider
	 */
	public function test_read_responses_with_password( Closure $fixture ) {
		[ $ticketable_posts, $tickets ] = $this->create_test_data();
		$fixture();

		$responses = [];
		foreach ( $tickets as $ticket_id ) {
			// Get the ticket post object
			$ticket_object = Tickets_API::load_ticket_object( $ticket_id );

			// Get the parent post to check its status
			$parent_post_id = $ticket_object->get_event_id();
			$parent_post = $ticket_object->get_event();

			if ( $parent_post && 'publish' === $parent_post->post_status ) {
				// Published parent - try with password
				$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', 200, [ 'password' => 'password123' ] );
			} else {
				// Private/draft parent - check permissions even with password
				$should_pass = is_user_logged_in() && current_user_can( 'read_post', $parent_post_id );
				$response = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', $should_pass ? 200 : ( is_user_logged_in() ? 403 : 401 ), [ 'password' => 'password123' ] );
				if ( $should_pass ) {
					$responses[] = $response;
				}
			}
		}

		$json = wp_json_encode( $responses, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace( $ticketable_posts, '{POST_ID}', $json );
		$json = str_replace( $tickets, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}
}
