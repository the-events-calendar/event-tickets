<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Generator;
use TEC\Common\Tests\Testcases\REST\TEC\V1\REST_Test_Case;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

class REST_Properties_Test extends REST_Test_Case {
	use Ticket_Maker;

	protected $endpoint_class = Ticket::class;

	/**
	 * Helper method to create test data for ticket REST API tests.
	 *
	 * @return array{0: int[], 1: int[]} Array containing [post_ids, ticket_ids].
	 */
	protected function create_test_data(): array {
		wp_set_current_user( 1 );

		// Create a published page for ticketing (page is in default ticketable post types).
		$post_id = static::factory()->post->create(
			[
				'post_title'   => 'Test Page',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => 'Test page content',
			]
		);

		// Create RSVP ticket with show_not_going enabled.
		$rsvp_ticket_enabled = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_ticket_enabled, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		// Create RSVP ticket with show_not_going disabled.
		$rsvp_ticket_disabled = $this->create_tc_rsvp_ticket( $post_id );
		update_post_meta( $rsvp_ticket_disabled, Constants::SHOW_NOT_GOING_META_KEY, '' );

		// Create regular ticket.
		$regular_ticket = $this->create_tc_ticket( $post_id, 25 );

		// Flush caches to ensure ticket metadata is refreshed.
		wp_cache_flush();

		wp_set_current_user( 0 );

		return [
			[ $post_id ],
			[ $rsvp_ticket_enabled, $rsvp_ticket_disabled, $regular_ticket ],
		];
	}

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$rest_properties = tribe( REST_Properties::class );

		$this->assertInstanceOf( REST_Properties::class, $rest_properties );
	}

	/**
	 * @test
	 */
	public function it_should_include_show_not_going_in_rsvp_ticket_response(): void {
		[ $post_ids, $ticket_ids ] = $this->create_test_data();
		$rsvp_ticket_enabled       = $ticket_ids[0];

		wp_set_current_user( 1 );

		$response = $this->assert_endpoint( '/tickets/' . $rsvp_ticket_enabled );

		$this->assertArrayHasKey( 'show_not_going', $response );
		$this->assertTrue( $response['show_not_going'] );
	}

	/**
	 * @test
	 */
	public function it_should_return_false_for_show_not_going_when_disabled(): void {
		[ $post_ids, $ticket_ids ] = $this->create_test_data();
		$rsvp_ticket_disabled      = $ticket_ids[1];

		wp_set_current_user( 1 );

		$response = $this->assert_endpoint( '/tickets/' . $rsvp_ticket_disabled );

		$this->assertArrayHasKey( 'show_not_going', $response );
		$this->assertFalse( $response['show_not_going'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_include_show_not_going_in_regular_ticket_response(): void {
		[ $post_ids, $ticket_ids ] = $this->create_test_data();
		$regular_ticket            = $ticket_ids[2];

		wp_set_current_user( 1 );

		$response = $this->assert_endpoint( '/tickets/' . $regular_ticket );

		$this->assertArrayNotHasKey( 'show_not_going', $response );
	}

	/**
	 * @test
	 * @dataProvider different_user_roles_provider
	 */
	public function it_should_include_show_not_going_property_for_different_users( Closure $fixture ): void {
		[ $post_ids, $ticket_ids ] = $this->create_test_data();
		$rsvp_ticket_enabled       = $ticket_ids[0];
		$regular_ticket            = $ticket_ids[2];

		$fixture();

		// RSVP ticket should have show_not_going property.
		$rsvp_response = $this->assert_endpoint( '/tickets/' . $rsvp_ticket_enabled );

		$this->assertArrayHasKey( 'show_not_going', $rsvp_response );
		$this->assertTrue( $rsvp_response['show_not_going'] );

		// Regular ticket should not have show_not_going property.
		$regular_response = $this->assert_endpoint( '/tickets/' . $regular_ticket );

		$this->assertArrayNotHasKey( 'show_not_going', $regular_response );
	}

	/**
	 * @test
	 */
	public function it_should_include_show_not_going_in_documentation(): void {
		// The endpoint documentation is tested in detail by the REST_Test_Case parent class.
		// Here we verify that the show_not_going property filter is registered with the Swagger definition hooks.
		$this->assertTrue(
			has_filter( 'tec_rest_swagger_ticket_definition' ),
			'The tec_rest_swagger_ticket_definition filter should have callbacks registered'
		);

		$this->assertTrue(
			has_filter( 'tec_rest_swagger_ticket_request_body_definition' ),
			'The tec_rest_swagger_ticket_request_body_definition filter should have callbacks registered'
		);

		// Verify the filter actually adds the property by testing the REST_Properties method directly.
		$rest_properties = tribe( REST_Properties::class );

		$properties = new \TEC\Common\REST\TEC\V1\Collections\PropertiesCollection();
		$documentation = [
			'allOf' => [
				[ '$ref' => '#/components/schemas/TEC_Post_Entity' ],
				[
					'title'      => 'Ticket',
					'type'       => 'object',
					'properties' => $properties,
				],
			],
		];

		$result = $rest_properties->add_show_not_going_to_response_docs( $documentation );

		$property_names = [];
		foreach ( $result['allOf'][1]['properties'] as $property ) {
			$property_names[] = $property->get_name();
		}

		$this->assertContains( 'show_not_going', $property_names, 'show_not_going should be added to documentation' );
	}

	/**
	 * Provides different user roles for testing.
	 *
	 * @return Generator
	 */
	public function different_user_roles_provider(): Generator {
		yield 'administrator' => [
			function (): void {
				$user = static::factory()->user->create( [ 'role' => 'administrator' ] );
				wp_set_current_user( $user );
			},
		];
	}
}
