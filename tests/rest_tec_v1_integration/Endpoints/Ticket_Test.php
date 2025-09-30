<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Endpoints;

use Closure;
use TEC\Common\Tests\Testcases\REST\TEC\V1\Post_Entity_REST_Test_Case;
use TEC\Tickets\Commerce\Models\Ticket_Model as Model;
use TEC\Tickets\Commerce\Repositories\Tickets_Repository;
use TEC\Tickets\Commerce\Ticket as Ticket_Model;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

class Ticket_Test extends Post_Entity_REST_Test_Case {
	use Ticket_Maker;

	protected $endpoint_class = Ticket::class;

	protected function create_test_data(): array {
		wp_set_current_user( 1 );

		// Create ticketable posts and pages.
		$post_1 = self::factory()->post->create(
			[
				'post_title'   => 'Concert Post',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_content' => 'Amazing concert details here',
			]
		);

		$page_1 = self::factory()->post->create(
			[
				'post_title'   => 'Workshop Page',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => 'Workshop registration information',
			]
		);

		// Create private post for testing access control.
		$private_post = self::factory()->post->create(
			[
				'post_title'   => 'Private Event Post',
				'post_status'  => 'private',
				'post_type'    => 'post',
				'post_content' => 'Private event information',
			]
		);

		// Create draft post.
		$draft_post = self::factory()->post->create(
			[
				'post_title'   => 'Draft Event Post',
				'post_status'  => 'draft',
				'post_type'    => 'post',
				'post_content' => 'Draft event details',
			]
		);

		// Create password protected post.
		$password_post = self::factory()->post->create(
			[
				'post_title'    => 'Password Protected Event',
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_password' => 'password123',
				'post_content'  => 'Password protected event details',
			]
		);

		// Create various ticket types.
		$ticket_1 = $this->create_tc_ticket( $post_1, '25.00' );
		update_post_meta( $ticket_1, '_name', 'General Admission' );
		update_post_meta( $ticket_1, '_description', 'Standard ticket for general admission' );
		update_post_meta( $ticket_1, '_tribe_ticket_capacity', 100 );
		wp_update_post( [ 'ID' => $ticket_1, 'menu_order' => 0 ] );

		$ticket_2 = $this->create_tc_ticket( $post_1, '75.00' );
		update_post_meta( $ticket_2, '_name', 'VIP Ticket' );
		update_post_meta( $ticket_2, '_description', 'VIP access with special perks' );
		update_post_meta( $ticket_2, '_tribe_ticket_capacity', -1 );
		wp_update_post( [ 'ID' => $ticket_2, 'menu_order' => 7 ] );

		$ticket_3 = $this->create_tc_ticket( $page_1, '15.00' );
		update_post_meta( $ticket_3, '_name', 'Workshop Registration' );
		update_post_meta( $ticket_3, '_description', 'Basic workshop registration' );
		update_post_meta( $ticket_3, '_tribe_ticket_capacity', 50 );
		wp_update_post( [ 'ID' => $ticket_3, 'menu_order' => 3 ] );

		// Create ticket for private post.
		$private_ticket = $this->create_tc_ticket( $private_post, '50.00' );
		update_post_meta( $private_ticket, '_name', 'Private Event Ticket' );
		wp_update_post( [ 'ID' => $private_ticket, 'menu_order' => 1 ] );

		// Create ticket for draft post.
		$draft_ticket = $this->create_tc_ticket( $draft_post, '30.00' );
		update_post_meta( $draft_ticket, '_name', 'Draft Event Ticket' );
		wp_update_post( [ 'ID' => $draft_ticket, 'menu_order' => 6 ] );

		// Create ticket for password protected post.
		$password_ticket = $this->create_tc_ticket( $password_post, '40.00' );
		update_post_meta( $password_ticket, '_name', 'Password Event Ticket' );
		wp_update_post( [ 'ID' => $password_ticket, 'menu_order' => 2 ] );

		// Create free ticket.
		$free_ticket = $this->create_tc_ticket( $post_1, '0.00' );
		update_post_meta( $free_ticket, '_name', 'Free Entry' );
		update_post_meta( $free_ticket, '_description', 'Free admission ticket' );
		wp_update_post( [ 'ID' => $free_ticket, 'menu_order' => 4 ] );

		// Create ticket with stock management.
		$stock_ticket = $this->create_tc_ticket( $page_1, '35.00' );
		update_post_meta( $stock_ticket, '_name', 'Limited Edition' );
		update_post_meta( $stock_ticket, '_manage_stock', 'yes' );
		update_post_meta( $stock_ticket, '_stock', '100' );
		wp_update_post( [ 'ID' => $stock_ticket, 'menu_order' => 5 ] );

		wp_set_current_user( 0 );

		return [
			[ $post_1, $page_1, $private_post, $draft_post, $password_post ],
			[ $ticket_1, $ticket_2, $ticket_3, $private_ticket, $draft_ticket, $password_ticket, $free_ticket, $stock_ticket ],
		];
	}

	public function test_get_formatted_entity() {
		// Ensure the post type is registered.
		$this->assertTrue( post_type_exists( Ticket_Model::POSTTYPE ), 'Ticket post type should be registered' );

		[ $ticketable_posts, $tickets ] = $this->create_test_data();

		$data = [];
		foreach ( $tickets as $ticket_id ) {
			// Get the ticket post object. directly.
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

	public function test_instance_of_orm() {
		$orm = $this->endpoint->get_orm();
		$this->assertInstanceOf( Tickets_Repository::class, $orm );
	}

	public function test_get_model_class() {
		$model_class = $this->endpoint->get_model_class();
		$this->assertEquals( Model::class, $model_class );
	}

	protected function get_example_create_data(): array {
		$example = parent::get_example_create_data();

		$post_id = self::factory()->post->create();

		$example['event']              = $post_id;
		$example['manage_stock']       = true;
		$example['sale_price_enabled'] = true;
		$example['menu_order']         = 5;
		$example['capacity']           = 100;

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
			// Get the ticket post object.
			$ticket_object = Tickets::load_ticket_object( $ticket_id );
			// Get the parent post to check its status.
			$parent_post_id = $ticket_object->get_event_id();
			$parent_post    = $ticket_object->get_event();

			if ( $parent_post && 'publish' === $parent_post->post_status ) {
				if ( empty( $parent_post->post_password ) ) {
					$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id );
				} else {
					$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', ( is_user_logged_in() ? 403 : 401 ) );
				}
			} else {
				// Private/draft/password-protected parent - check permissions.
				$should_pass = is_user_logged_in() && current_user_can( 'read_post', $parent_post_id );
				$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', $should_pass ? 200 : ( is_user_logged_in() ? 403 : 401 ) );
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
			// Get the ticket post object.
			$ticket_object = Tickets::load_ticket_object( $ticket_id );

			// Get the parent post to check its status.
			$parent_post_id = $ticket_object->get_event_id();
			$parent_post    = $ticket_object->get_event();

			if ( $parent_post && 'publish' === $parent_post->post_status ) {
				// Published parent - try with password.
				$responses[] = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', 200, [ 'password' => 'password123' ] );
			} else {
				// Private/draft parent - check permissions even with password.
				$should_pass = is_user_logged_in() && current_user_can( 'read_post', $parent_post_id );
				$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', $should_pass ? 200 : ( is_user_logged_in() ? 403 : 401 ), [ 'password' => 'password123' ] );
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

	public function test_menu_order_functionality() {
		wp_set_current_user( 1 );

		// Create a test post.
		$post_id = self::factory()->post->create(
			[
				'post_title'  => 'Test Event',
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		// Test creating a ticket with menu_order.
		$create_data = [
			'title'      => 'Test Ticket with Menu Order',
			'price'      => 50.00,
			'event'      => $post_id,
			'menu_order' => 10,
		];

		$orm       = $this->endpoint->get_orm();
		$ticket_id = $orm->set_args( $create_data )->create()->ID;

		// Verify the ticket was created with the correct menu_order.
		$ticket_post = get_post( $ticket_id );
		$this->assertEquals( 10, $ticket_post->menu_order, 'Ticket should be created with the specified menu_order' );

		// Test reading the ticket and verifying menu_order is included.
		$formatted_entity = $this->endpoint->get_formatted_entity( $ticket_post );
		$this->assertArrayHasKey( 'menu_order', $formatted_entity, 'menu_order should be included in the formatted entity' );
		$this->assertEquals( 10, $formatted_entity['menu_order'], 'menu_order should match the created value' );

		// Test updating the ticket's menu_order.
		$update_data = [
			'menu_order' => 20,
		];

		$orm->set_args( $update_data )->save( $ticket_id );

		// Verify the menu_order was updated.
		$updated_ticket_post = get_post( $ticket_id );
		$this->assertEquals( 20, $updated_ticket_post->menu_order, 'Ticket menu_order should be updated' );

		// Test reading the updated ticket.
		$updated_formatted_entity = $this->endpoint->get_formatted_entity( $updated_ticket_post );
		$this->assertEquals( 20, $updated_formatted_entity['menu_order'], 'Updated menu_order should be reflected in the formatted entity' );
	}

	public function test_menu_order_in_create_request() {
		wp_set_current_user( 1 );

		// Create a test post.
		$post_id = self::factory()->post->create(
			[
				'post_title'  => 'Test Event for Create',
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		// Test creating a ticket via REST API with menu_order
		$create_data = [
			'title'      => 'REST Created Ticket',
			'price'      => 75.00,
			'event'      => $post_id,
			'menu_order' => 15,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'menu_order', $response, 'Response should include menu_order' );
		$this->assertEquals( 15, $response['menu_order'], 'Response menu_order should match the request' );

		// Verify the ticket was actually created with the correct menu_order
		$ticket_id   = $response['id'];
		$ticket_post = get_post( $ticket_id );
		$this->assertEquals( 15, $ticket_post->menu_order, 'Created ticket should have the correct menu_order' );
	}

	public function test_menu_order_in_update_request() {
		wp_set_current_user( 1 );

		// Create a test post. and ticket
		$post_id = self::factory()->post->create(
			[
				'post_title'  => 'Test Event for Update',
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		$ticket_id = $this->create_tc_ticket( $post_id, '25.00' );
		wp_update_post( [ 'ID' => $ticket_id, 'menu_order' => 5 ] );

		// Test updating the ticket's menu_order. via REST API
		$update_data = [
			'menu_order' => 25,
		];

		$response = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertArrayHasKey( 'menu_order', $response, 'Response should include menu_order' );
		$this->assertEquals( 25, $response['menu_order'], 'Response menu_order should match the update request' );

		// Verify the ticket was actually updated with the correct menu_order
		$ticket_post = get_post( $ticket_id );
		$this->assertEquals( 25, $ticket_post->menu_order, 'Updated ticket should have the correct menu_order' );
	}

	public function test_create_ticket_with_limited_capacity() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Limited Capacity Ticket',
			'price'    => 25.00,
			'capacity' => 100,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Response should include capacity field' );
		$this->assertEquals( 100, $response['capacity'], 'Capacity should be set to 100' );

		// Verify capacity is stored in database
		$ticket_id       = $response['id'];
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 100, $stored_capacity, 'Capacity should be stored as 100 in database' );
	}

	public function test_create_ticket_with_unlimited_capacity_string() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Unlimited Capacity Ticket',
			'price'    => 50.00,
			'capacity' => 'unlimited',
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Response should include capacity field' );
		$this->assertEquals( -1, $response['capacity'], 'Capacity should be set to -1 for unlimited' );

		// Verify capacity is stored in database
		$ticket_id       = $response['id'];
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( -1, $stored_capacity, 'Capacity should be stored as -1 in database' );
	}

	public function test_create_ticket_with_unlimited_capacity_empty_string() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Unlimited Capacity Ticket',
			'price'    => 50.00,
			'capacity' => '',
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Response should include capacity field' );
		$this->assertEquals( -1, $response['capacity'], 'Capacity should be set to -1 for unlimited' );

		// Verify capacity is stored in database
		$ticket_id       = $response['id'];
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( -1, $stored_capacity, 'Capacity should be stored as -1 in database' );
	}

	public function test_create_ticket_with_unlimited_capacity_minus_one() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Unlimited Capacity Ticket',
			'price'    => 50.00,
			'capacity' => -1,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Response should include capacity field' );
		$this->assertEquals( -1, $response['capacity'], 'Capacity should be set to -1 for unlimited' );

		// Verify capacity is stored in database
		$ticket_id       = $response['id'];
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( -1, $stored_capacity, 'Capacity should be stored as -1 in database' );
	}

	public function test_update_capacity_from_limited_to_unlimited() {
		wp_set_current_user( 1 );
		// Create ticket with limited capacity
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Test Ticket',
			'price'    => 25.00,
			'capacity' => 50,
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];
		$this->assertEquals( 50, $response['capacity'], 'Initial capacity should be 50' );

		// Update to unlimited capacity
		$update_data = [ 'capacity' => 'unlimited' ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertEquals( -1, $response['capacity'], 'Updated capacity should be -1 for unlimited' );

		// Verify capacity is stored in database
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( -1, $stored_capacity, 'Capacity should be stored as -1 in database' );
	}

	public function test_update_capacity_from_unlimited_to_limited() {
		wp_set_current_user( 1 );
		// Create ticket with unlimited capacity
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Test Ticket',
			'price'    => 25.00,
			'capacity' => 'unlimited',
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];
		$this->assertEquals( -1, $response['capacity'], 'Initial capacity should be -1' );

		// Update to limited capacity
		$update_data = [ 'capacity' => 75 ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertEquals( 75, $response['capacity'], 'Updated capacity should be 75' );

		// Verify capacity is stored in database
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 75, $stored_capacity, 'Capacity should be stored as 75 in database' );
	}

	public function test_capacity_field_in_api_responses() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Test Ticket',
			'price'    => 25.00,
			'capacity' => 200,
		];

		// Test in create response
		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Create response should include capacity field' );
		$this->assertEquals( 200, $response['capacity'], 'Create response capacity should be 200' );

		$ticket_id = $response['id'];

		// Test in read response
		$response = $this->assert_endpoint( '/tickets/' . $ticket_id, 'GET', 200 );
		$this->assertArrayHasKey( 'capacity', $response, 'Read response should include capacity field' );
		$this->assertEquals( 200, $response['capacity'], 'Read response capacity should be 200' );

		// Test in update response
		$update_data = [ 'capacity' => 150 ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Update response should include capacity field' );
		$this->assertEquals( 150, $response['capacity'], 'Update response capacity should be 150' );
	}

	public function test_capacity_field_persistence() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Persistence Test Ticket',
			'price'    => 30.00,
			'capacity' => 300,
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];

		// Verify initial capacity is stored
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 300, $stored_capacity, 'Initial capacity should be stored as 300' );

		// Update capacity and verify persistence
		$update_data = [ 'capacity' => 250 ];
		$this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );

		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 250, $stored_capacity, 'Updated capacity should be stored as 250' );

		// Update to unlimited and verify persistence
		$update_data = [ 'capacity' => 'unlimited' ];
		$this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );

		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( -1, $stored_capacity, 'Unlimited capacity should be stored as -1' );
	}

	public function test_create_ticket_with_large_capacity() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Large Capacity Ticket',
			'price'    => 10.00,
			'capacity' => 999999,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Response should include capacity field' );
		$this->assertEquals( 999999, $response['capacity'], 'Capacity should be set to 999999' );

		// Verify capacity is stored in database
		$ticket_id       = $response['id'];
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 999999, $stored_capacity, 'Large capacity should be stored correctly' );
	}

	public function test_create_ticket_with_small_capacity() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Small Capacity Ticket',
			'price'    => 100.00,
			'capacity' => 1,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertArrayHasKey( 'capacity', $response, 'Response should include capacity field' );
		$this->assertEquals( 1, $response['capacity'], 'Capacity should be set to 1' );

		// Verify capacity is stored in database
		$ticket_id       = $response['id'];
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 1, $stored_capacity, 'Small capacity should be stored correctly' );
	}

	public function test_capacity_transitions_limited_unlimited_limited() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Transition Test Ticket',
			'price'    => 25.00,
			'capacity' => 100,
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];
		$this->assertEquals( 100, $response['capacity'], 'Initial capacity should be 100' );

		// Transition 1: limited -> unlimited
		$update_data = [ 'capacity' => 'unlimited' ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertEquals( -1, $response['capacity'], 'Capacity should be unlimited (-1)' );

		// Transition 2: unlimited -> limited
		$update_data = [ 'capacity' => 200 ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertEquals( 200, $response['capacity'], 'Capacity should be 200' );

		// Verify final state in database
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 200, $stored_capacity, 'Final capacity should be 200' );
	}

	public function test_capacity_transitions_unlimited_limited_unlimited() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Transition Test Ticket',
			'price'    => 25.00,
			'capacity' => 'unlimited',
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];
		$this->assertEquals( -1, $response['capacity'], 'Initial capacity should be unlimited (-1)' );

		// Transition 1: unlimited -> limited
		$update_data = [ 'capacity' => 150 ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertEquals( 150, $response['capacity'], 'Capacity should be 150' );

		// Transition 2: limited -> unlimited
		$update_data = [ 'capacity' => '' ];
		$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
		$this->assertEquals( -1, $response['capacity'], 'Capacity should be unlimited (-1)' );

		// Verify final state in database
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( -1, $stored_capacity, 'Final capacity should be unlimited (-1)' );
	}

	public function test_rapid_capacity_updates() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Rapid Update Test Ticket',
			'price'    => 25.00,
			'capacity' => 50,
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];

		// Rapid updates
		$updates         = [ 100, 'unlimited', 200, -1, 75 ];
		$expected_values = [ 100, -1, 200, -1, 75 ];

		foreach ( $updates as $index => $capacity ) {
			$update_data = [ 'capacity' => $capacity ];
			$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 200, $update_data );
			$this->assertEquals( $expected_values[ $index ], $response['capacity'], "Update {$index} should set capacity to {$expected_values[$index]}" );
		}

		// Verify final state in database
		$stored_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 75, $stored_capacity, 'Final capacity should be 75' );
	}

	public function test_capacity_with_different_stock_modes() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		// Test with own stock mode
		$create_data = [
			'event'        => $post_id,
			'title'        => 'Own Stock Ticket',
			'price'        => 25.00,
			'capacity'     => 100,
			'manage_stock' => true,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertEquals( 100, $response['capacity'], 'Capacity should be 100 for own stock mode' );

		// Test with unlimited stock mode
		$create_data = [
			'event'        => $post_id,
			'title'        => 'Unlimited Stock Ticket',
			'price'        => 50.00,
			'capacity'     => 'unlimited',
			'manage_stock' => false,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$this->assertEquals( -1, $response['capacity'], 'Capacity should be -1 for unlimited stock mode' );
	}

	public function test_capacity_field_in_api_schema() {
		wp_set_current_user( 1 );
		$schema = $this->endpoint->get_schema();

		$this->assertArrayHasKey( 'properties', $schema, 'Schema should have properties' );
		$this->assertArrayHasKey( 'capacity', $schema['properties'], 'Schema should include capacity field' );

		$capacity_field = $schema['properties']['capacity'];
		$this->assertEquals( 'integer', $capacity_field['type'], 'Capacity field type should be integer' );
		$this->assertArrayHasKey( 'description', $capacity_field, 'Capacity field should have description' );
		$this->assertArrayHasKey( 'examples', $capacity_field, 'Capacity field should have examples' );

		// Verify examples include both limited and unlimited
		$examples = $capacity_field['examples'];
		$this->assertArrayHasKey( 'limited', $examples, 'Examples should include limited capacity' );
		$this->assertArrayHasKey( 'unlimited', $examples, 'Examples should include unlimited capacity' );
		$this->assertEquals( 100, $examples['limited'], 'Limited example should be 100' );
		$this->assertEquals( -1, $examples['unlimited'], 'Unlimited example should be -1' );
	}

	public function test_capacity_field_in_request_body_schema() {
		wp_set_current_user( 1 );
		$request_body = $this->endpoint->get_schema();

		$this->assertArrayHasKey( 'properties', $request_body, 'Request body should have properties' );
		$this->assertArrayHasKey( 'capacity', $request_body['properties'], 'Request body should include capacity field' );

		$capacity_field = $request_body['properties']['capacity'];
		$this->assertEquals( 'integer', $capacity_field['type'], 'Capacity field type should be integer' );
		$this->assertArrayHasKey( 'description', $capacity_field, 'Capacity field should have description' );
		$this->assertArrayHasKey( 'examples', $capacity_field, 'Capacity field should have examples' );

		// Verify the description mentions unlimited capacity
		$description = $capacity_field['description'];
		$this->assertStringContainsString( 'unlimited', $description, 'Description should mention unlimited capacity' );
		$this->assertStringContainsString( 'positive integer', $description, 'Description should mention positive integer' );
	}

	public function test_api_documentation_examples_work() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		// Test limited capacity example
		$limited_data = [
			'event'    => $post_id,
			'title'    => 'Limited Example Ticket',
			'price'    => 25.00,
			'capacity' => 100, // From schema examples
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $limited_data );
		$this->assertEquals( 100, $response['capacity'], 'Limited capacity example should work' );

		// Test unlimited capacity example
		$unlimited_data = [
			'event'    => $post_id,
			'title'    => 'Unlimited Example Ticket',
			'price'    => 50.00,
			'capacity' => -1, // From schema examples
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 201, $unlimited_data );
		$this->assertEquals( -1, $response['capacity'], 'Unlimited capacity example should work' );
	}

	public function test_capacity_field_in_openapi_documentation() {
		wp_set_current_user( 1 );
		$documentation = $this->endpoint->get_documentation();

		$this->assertArrayHasKey( 'requestBody', $documentation, 'Documentation should have requestBody' );
		$this->assertArrayHasKey( 'content', $documentation['requestBody'], 'Request body should have content' );
		$this->assertArrayHasKey( 'application/json', $documentation['requestBody']['content'], 'Should have JSON content type' );
		$this->assertArrayHasKey( 'schema', $documentation['requestBody']['content']['application/json'], 'Should have schema' );

		$schema = $documentation['requestBody']['content']['application/json']['schema'];
		$this->assertArrayHasKey( 'properties', $schema, 'Schema should have properties' );
		$this->assertArrayHasKey( 'capacity', $schema['properties'], 'Schema should include capacity field' );

		$capacity_field = $schema['properties']['capacity'];
		$this->assertEquals( 'integer', $capacity_field['type'], 'Capacity field type should be integer' );
		$this->assertArrayHasKey( 'description', $capacity_field, 'Capacity field should have description' );
		$this->assertArrayHasKey( 'examples', $capacity_field, 'Capacity field should have examples' );
	}

	public function test_capacity_field_in_response_schema() {
		wp_set_current_user( 1 );
		$post_id     = self::factory()->post->create();
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Schema Test Ticket',
			'price'    => 25.00,
			'capacity' => 150,
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];

		// Get the response schema
		$schema = $this->endpoint->get_schema();

		// Verify capacity field is in the response schema
		$this->assertArrayHasKey( 'properties', $schema, 'Response schema should have properties' );
		$this->assertArrayHasKey( 'capacity', $schema['properties'], 'Response schema should include capacity field' );

		$capacity_field = $schema['properties']['capacity'];
		$this->assertEquals( 'integer', $capacity_field['type'], 'Response capacity field type should be integer' );
		$this->assertArrayHasKey( 'description', $capacity_field, 'Response capacity field should have description' );
	}

	public function test_invalid_capacity_values_return_proper_status_codes() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		// Test zero capacity (should be invalid)
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Invalid Capacity Ticket',
			'price'    => 25.00,
			'capacity' => 0,
		];

		$this->assert_endpoint( '/tickets', 'POST', 400, $create_data );

		// Test negative capacity (other than -1)
		$create_data['capacity'] = -5;
		$this->assert_endpoint( '/tickets', 'POST', 400, $create_data );

		// Test non-numeric string
		$create_data['capacity'] = 'invalid';
		$this->assert_endpoint( '/tickets', 'POST', 400, $create_data );

		// Test null capacity
		$create_data['capacity'] = null;
		$this->assert_endpoint( '/tickets', 'POST', 400, $create_data );
	}

	public function test_invalid_capacity_values_return_proper_error_messages() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		// Test zero capacity error message
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Invalid Capacity Ticket',
			'price'    => 25.00,
			'capacity' => 0,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 400, $create_data );
		$this->assertArrayHasKey( 'message', $response, 'Error response should include message' );
		$this->assertStringContainsString( 'capacity', $response['message'], 'Error message should mention capacity' );

		// Test negative capacity error message
		$create_data['capacity'] = -10;
		$response                = $this->assert_endpoint( '/tickets', 'POST', 400, $create_data );
		$this->assertArrayHasKey( 'message', $response, 'Error response should include message' );
		$this->assertStringContainsString( 'capacity', $response['message'], 'Error message should mention capacity' );
	}

	public function test_validation_error_responses_include_capacity_field() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		$create_data = [
			'event'    => $post_id,
			'title'    => 'Invalid Capacity Ticket',
			'price'    => 25.00,
			'capacity' => 0,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 400, $create_data );

		// Check that the error response includes information about the capacity field
		$this->assertArrayHasKey( 'message', $response, 'Error response should include message' );
		$this->assertArrayHasKey( 'data', $response, 'Error response should include data' );

		// The error should be related to the capacity field validation
		$this->assertStringContainsString( 'capacity', $response['message'], 'Error message should mention capacity field' );
	}

	public function test_invalid_capacity_values_in_updates_return_errors() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		// Create a valid ticket first
		$create_data = [
			'event'    => $post_id,
			'title'    => 'Valid Ticket',
			'price'    => 25.00,
			'capacity' => 100,
		];

		$response  = $this->assert_endpoint( '/tickets', 'POST', 201, $create_data );
		$ticket_id = $response['id'];

		// Test updating with invalid capacity values
		$invalid_capacities = [ 0, -5, 'invalid', null ];

		foreach ( $invalid_capacities as $invalid_capacity ) {
			$update_data = [ 'capacity' => $invalid_capacity ];
			$response    = $this->assert_endpoint( '/tickets/' . $ticket_id, 'PUT', 400, $update_data );
			$this->assertArrayHasKey( 'message', $response, 'Error response should include message' );
			$this->assertStringContainsString( 'capacity', $response['message'], 'Error message should mention capacity' );
		}
	}

	public function test_capacity_field_validation_errors_are_properly_formatted() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		$create_data = [
			'event'    => $post_id,
			'title'    => 'Invalid Capacity Ticket',
			'price'    => 25.00,
			'capacity' => 0,
		];

		$response = $this->assert_endpoint( '/tickets', 'POST', 400, $create_data );

		// Verify error response structure
		$this->assertArrayHasKey( 'code', $response, 'Error response should include code' );
		$this->assertArrayHasKey( 'message', $response, 'Error response should include message' );
		$this->assertArrayHasKey( 'data', $response, 'Error response should include data' );

		// Verify error code indicates validation failure
		$this->assertEquals( 'tec_rest_invalid_capacity_parameter', $response['code'], 'Error code should indicate invalid parameter' );

		// Verify error message is user-friendly
		$this->assertStringContainsString( 'capacity', $response['message'], 'Error message should mention capacity' );
	}

	public function test_multiple_invalid_capacity_values_handled_correctly() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		$invalid_capacities = [
			'zero'     => 0,
			'negative' => -10,
			'string'   => 'not_a_number',
			'null'     => null,
			'array'    => [],
			'object'   => (object) [],
		];

		foreach ( $invalid_capacities as $test_name => $invalid_capacity ) {
			$create_data = [
				'event'    => $post_id,
				'title'    => "Invalid {$test_name} Capacity Ticket",
				'price'    => 25.00,
				'capacity' => $invalid_capacity,
			];

			$response = $this->assert_endpoint( '/tickets', 'POST', 400, $create_data );
			$this->assertArrayHasKey( 'message', $response, "Error response for {$test_name} should include message" );
			$this->assertStringContainsString( 'capacity', $response['message'], "Error message for {$test_name} should mention capacity" );
		}
	}
}
