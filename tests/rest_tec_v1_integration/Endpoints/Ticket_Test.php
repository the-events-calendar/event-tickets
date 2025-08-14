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

		$ticket_1 = $this->create_tc_ticket( $post_1, '25.00' );
		$ticket_2 = $this->create_tc_ticket( $post_1, '75.00' );
		$ticket_3 = $this->create_tc_ticket( $page_1, '15.00' );

		return [
			[ $post_1, $page_1 ],
			[ $ticket_1, $ticket_2, $ticket_3 ],
		];
	}

	public function test_get_formatted_entity() {
		// Ensure the post type is registered
		$this->assertTrue( post_type_exists( Ticket_Model::POSTTYPE ), 'Ticket post type should be registered' );

		[ $ticketable_posts, $tickets ] = $this->create_test_data();

		// Get the ticket post object directly
		$ticket_post = tec_tc_get_ticket( $tickets[0] );
		$this->assertInstanceOf( WP_Post::class, $ticket_post, 'Ticket post should exist' );
		$this->assertEquals( Ticket_Model::POSTTYPE, $ticket_post->post_type, 'Post should be of ticket type' );

		$data = $this->endpoint->get_formatted_entity( $ticket_post );

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
}
