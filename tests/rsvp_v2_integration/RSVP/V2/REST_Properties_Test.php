<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use WP_Post;

class REST_Properties_Test extends WPTestCase {
	use Ticket_Maker;

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
	public function it_should_add_show_not_going_property_to_rsvp_ticket_model(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Set show_not_going to enabled.
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		$properties = [
			'type' => Constants::TC_RSVP_TYPE,
		];

		$post            = get_post( $ticket_id );
		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_properties( $properties, $post, 'raw' );

		$this->assertArrayHasKey( 'show_not_going', $result );
		$this->assertTrue( $result['show_not_going'] );
	}

	/**
	 * @test
	 */
	public function it_should_return_false_when_show_not_going_is_disabled(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Set show_not_going to disabled.
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '' );

		$properties = [
			'type' => Constants::TC_RSVP_TYPE,
		];

		$post            = get_post( $ticket_id );
		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_properties( $properties, $post, 'raw' );

		$this->assertArrayHasKey( 'show_not_going', $result );
		$this->assertFalse( $result['show_not_going'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_show_not_going_to_regular_ticket(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$properties = [
			'type' => 'default',
		];

		$post            = get_post( $ticket_id );
		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_properties( $properties, $post, 'raw' );

		$this->assertArrayNotHasKey( 'show_not_going', $result );
	}

	/**
	 * @test
	 */
	public function it_should_read_type_from_meta_when_not_in_properties(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		// Empty properties, should read type from meta.
		$properties = [];

		$post            = get_post( $ticket_id );
		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_properties( $properties, $post, 'raw' );

		$this->assertArrayHasKey( 'show_not_going', $result );
		$this->assertTrue( $result['show_not_going'] );
	}

	/**
	 * @test
	 */
	public function it_should_add_show_not_going_to_rest_properties_list(): void {
		$properties = [
			'type'  => true,
			'price' => true,
		];

		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_rest_properties( $properties );

		$this->assertArrayHasKey( 'show_not_going', $result );
		$this->assertTrue( $result['show_not_going'] );
		// Ensure original properties are preserved.
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'price', $result );
	}

	/**
	 * @test
	 */
	public function it_should_add_show_not_going_to_request_body_docs(): void {
		$properties = new PropertiesCollection();

		$documentation = [
			'allOf' => [
				[ '$ref' => '#/components/schemas/TEC_Post_Entity_Request_Body' ],
				[
					'title'      => 'Ticket Request Body',
					'type'       => 'object',
					'properties' => $properties,
				],
			],
		];

		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_request_body_docs( $documentation, null );

		$result_properties = $result['allOf'][1]['properties'];
		$this->assertInstanceOf( PropertiesCollection::class, $result_properties );

		$property_names = [];
		foreach ( $result_properties as $property ) {
			$property_names[] = $property->get_name();
		}

		$this->assertContains( 'show_not_going', $property_names );
	}

	/**
	 * @test
	 */
	public function it_should_add_show_not_going_to_response_docs(): void {
		$properties = new PropertiesCollection();

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

		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_response_docs( $documentation, null );

		$result_properties = $result['allOf'][1]['properties'];
		$this->assertInstanceOf( PropertiesCollection::class, $result_properties );

		$property_names = [];
		foreach ( $result_properties as $property ) {
			$property_names[] = $property->get_name();
		}

		$this->assertContains( 'show_not_going', $property_names );
	}

	/**
	 * @test
	 */
	public function it_should_not_modify_request_body_docs_without_properties_collection(): void {
		$documentation = [
			'allOf' => [
				[ '$ref' => '#/components/schemas/TEC_Post_Entity_Request_Body' ],
				[
					'title'      => 'Ticket Request Body',
					'type'       => 'object',
					'properties' => [], // Array instead of PropertiesCollection.
				],
			],
		];

		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_request_body_docs( $documentation, null );

		$this->assertEquals( $documentation, $result );
	}

	/**
	 * @test
	 */
	public function it_should_not_modify_response_docs_without_properties_collection(): void {
		$documentation = [
			'allOf' => [
				[ '$ref' => '#/components/schemas/TEC_Post_Entity' ],
				[
					'title'      => 'Ticket',
					'type'       => 'object',
					'properties' => [], // Array instead of PropertiesCollection.
				],
			],
		];

		$rest_properties = tribe( REST_Properties::class );
		$result          = $rest_properties->add_show_not_going_to_response_docs( $documentation, null );

		$this->assertEquals( $documentation, $result );
	}
}
