<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Endpoints;

use TEC\Common\Tests\Testcases\REST\TEC\V1\Post_Entity_REST_Test_Case;
use TEC\Tickets\Commerce\Repositories\Tickets_Repository;
use TEC\Tickets\Commerce\Ticket as Ticket_Model;
use TEC\Tickets\Commerce\Models\Ticket_Model as Model;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;
use Closure;

class Ticket_Test extends Post_Entity_REST_Test_Case {
	use Ticket_Maker;

	protected $endpoint_class = Ticket::class;


	protected function create_test_data(): array {
		wp_set_current_user( 1 );

		// Create ticketable posts and pages
		$post_1 = self::factory()->post->create(
			[
				'post_title' => 'Concert Post',
				'post_status' => 'publish',
				'post_type' => 'post',
				'post_content' => 'Amazing concert details here',
			]
		);

		$page_1 = self::factory()->post->create(
			[
				'post_title' => 'Workshop Page',
				'post_status' => 'publish',
				'post_type' => 'page',
				'post_content' => 'Workshop registration information',
			]
		);

		// Create private post for testing access control
		$private_post = self::factory()->post->create(
			[
				'post_title' => 'Private Event Post',
				'post_status' => 'private',
				'post_type' => 'post',
				'post_content' => 'Private event information',
			]
		);

		// Create draft post
		$draft_post = self::factory()->post->create(
			[
				'post_title' => 'Draft Event Post',
				'post_status' => 'draft',
				'post_type' => 'post',
				'post_content' => 'Draft event details',
			]
		);

		// Create password protected post
		$password_post = self::factory()->post->create(
			[
				'post_title' => 'Password Protected Event',
				'post_status' => 'publish',
				'post_type' => 'post',
				'post_password' => 'password123',
				'post_content' => 'Password protected event details',
			]
		);

		// Create various ticket types
		$ticket_1 = $this->create_tc_ticket( $post_1, '25.00' );
		update_post_meta( $ticket_1, '_name', 'General Admission' );
		update_post_meta( $ticket_1, '_description', 'Standard ticket for general admission' );

		$ticket_2 = $this->create_tc_ticket( $post_1, '75.00' );
		update_post_meta( $ticket_2, '_name', 'VIP Ticket' );
		update_post_meta( $ticket_2, '_description', 'VIP access with special perks' );

		$ticket_3 = $this->create_tc_ticket( $page_1, '15.00' );
		update_post_meta( $ticket_3, '_name', 'Workshop Registration' );
		update_post_meta( $ticket_3, '_description', 'Basic workshop registration' );

		// Create ticket for private post
		$private_ticket = $this->create_tc_ticket( $private_post, '50.00' );
		update_post_meta( $private_ticket, '_name', 'Private Event Ticket' );

		// Create ticket for draft post
		$draft_ticket = $this->create_tc_ticket( $draft_post, '30.00' );
		update_post_meta( $draft_ticket, '_name', 'Draft Event Ticket' );

		// Create ticket for password protected post
		$password_ticket = $this->create_tc_ticket( $password_post, '40.00' );
		update_post_meta( $password_ticket, '_name', 'Password Event Ticket' );

		// Create free ticket
		$free_ticket = $this->create_tc_ticket( $post_1, '0.00' );
		update_post_meta( $free_ticket, '_name', 'Free Entry' );
		update_post_meta( $free_ticket, '_description', 'Free admission ticket' );

		// Create ticket with stock management
		$stock_ticket = $this->create_tc_ticket( $page_1, '35.00' );
		update_post_meta( $stock_ticket, '_name', 'Limited Edition' );
		update_post_meta( $stock_ticket, '_manage_stock', 'yes' );
		update_post_meta( $stock_ticket, '_stock', '100' );

		wp_set_current_user( 0 );

		return [
			[ $post_1, $page_1, $private_post, $draft_post, $password_post ],
			[ $ticket_1, $ticket_2, $ticket_3, $private_ticket, $draft_ticket, $password_ticket, $free_ticket, $stock_ticket ],
		];
	}

	public function test_get_formatted_entity() {
		// Ensure the post type is registered
		$this->assertTrue( post_type_exists( Ticket_Model::POSTTYPE ), 'Ticket post type should be registered' );

		[ $ticketable_posts, $tickets ] = $this->create_test_data();

		$data = [];
		foreach ( $tickets as $ticket_id ) {
			// Get the ticket post object directly
			$ticket_post = tec_tc_get_ticket( $ticket_id );
			$this->assertInstanceOf( WP_Post::class, $ticket_post, 'Ticket post should exist' );
			$this->assertEquals( Ticket_Model::POSTTYPE, $ticket_post->post_type, 'Post should be of ticket type' );
			$data[] = $this->endpoint->get_formatted_entity( $ticket_post );
		}

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace( $ticketable_posts, '{POST_ID}', $json );
		$json = str_replace( $tickets, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}

	/**
	 * Test that the endpoint returns the correct ORM instance.
	 */
	public function test_instance_of_orm() {
		$orm = $this->endpoint->get_orm();
		$this->assertInstanceOf( Tickets_Repository::class, $orm );
	}

	/**
	 * Test that the endpoint returns the correct model class.
	 */
	public function test_get_model_class() {
		$model_class = $this->endpoint->get_model_class();
		$this->assertEquals( Model::class, $model_class );
	}

	protected function get_example_create_data(): array {
		$example = parent::get_example_create_data();

		$post_id = self::factory()->post->create();

		$example['event'] = $post_id;
		$example['manage_stock'] = true;
		$example['sale_price_enabled'] = true;

		return $example;
	}

	public function test_update_handles_save_failure() {
		if ( ! $this->is_updatable() ) {
			return;
		}

		$this->set_class_fn_return( Tickets::class, 'ticket_add', false );

		$example = $this->get_example_create_data();
		unset( $example['id'] );

		$orm = $this->endpoint->get_orm();

		wp_set_current_user( 1 );
		$entity_id = $orm->set_args( $example )->create()->ID;
		$this->assert_endpoint( sprintf( $this->endpoint->get_base_path(), $entity_id ), 'PUT', 500, [ 'title' => 'Updated Title' ] );
	}

	/**
	 * @dataProvider different_user_roles_provider
	 */
	public function test_read_responses( Closure $fixture ) {
		if ( ! $this->is_readable() ) {
			return;
		}

		[ $ticketable_posts, $tickets ] = $this->create_test_data();
		$fixture();

		$responses = [];
		foreach ( $tickets as $ticket_id ) {
			// Get the ticket post object
			$ticket_object = Tickets::load_ticket_object( $ticket_id );
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
		if ( ! $this->is_readable() ) {
			return;
		}

		[ $ticketable_posts, $tickets ] = $this->create_test_data();
		$fixture();

		$responses = [];
		foreach ( $tickets as $ticket_id ) {
			// Get the ticket post object
			$ticket_object = Tickets::load_ticket_object( $ticket_id );

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
