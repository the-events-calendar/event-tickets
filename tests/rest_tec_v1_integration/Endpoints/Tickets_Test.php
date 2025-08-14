<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Endpoints;

use TEC\Tickets\REST\TEC\V1\Endpoints\Tickets;
use Tribe__Tickets__Tickets as Tickets_API;
use Closure;

class Tickets_Test extends Ticket_Test {
	protected $endpoint_class = Tickets::class;

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
				// Public ticket - should be accessible to all
				$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id );
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
