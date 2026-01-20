<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Meta_Fields_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$meta_fields = tribe( Meta_Fields::class );

		$this->assertInstanceOf( Meta_Fields::class, $meta_fields );
	}

	/**
	 * @test
	 */
	public function it_should_save_show_not_going_meta_for_rsvp_ticket(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$ticket     = new Ticket_Object();
		$ticket->ID = $ticket_id;

		$raw_data = [
			'ticket_type'                  => Constants::TC_RSVP_TYPE,
			'ticket_rsvp_enable_cannot_go' => '1',
		];

		$meta_fields = tribe( Meta_Fields::class );
		$meta_fields->save_show_not_going( $post_id, $ticket, $raw_data );

		$this->assertEquals( '1', get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_save_show_not_going_as_no_when_disabled(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$ticket     = new Ticket_Object();
		$ticket->ID = $ticket_id;

		$raw_data = [
			'ticket_type'                  => Constants::TC_RSVP_TYPE,
			'ticket_rsvp_enable_cannot_go' => '0',
		];

		$meta_fields = tribe( Meta_Fields::class );
		$meta_fields->save_show_not_going( $post_id, $ticket, $raw_data );

		$this->assertEquals( '', get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_save_meta_for_non_rsvp_ticket(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$ticket     = new Ticket_Object();
		$ticket->ID = $ticket_id;

		$raw_data = [
			'ticket_type'                  => 'default',
			'ticket_rsvp_enable_cannot_go' => '1',
		];

		$meta_fields = tribe( Meta_Fields::class );
		$meta_fields->save_show_not_going( $post_id, $ticket, $raw_data );

		$this->assertEmpty( get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_save_meta_when_param_not_set(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$ticket     = new Ticket_Object();
		$ticket->ID = $ticket_id;

		$raw_data = [
			'ticket_type' => Constants::TC_RSVP_TYPE,
		];

		$meta_fields = tribe( Meta_Fields::class );
		$meta_fields->save_show_not_going( $post_id, $ticket, $raw_data );

		$this->assertEmpty( get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_detect_rsvp_ticket_from_meta_when_type_not_in_raw_data(): void {
		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$ticket     = new Ticket_Object();
		$ticket->ID = $ticket_id;

		// No ticket_type in raw_data, should read from meta.
		$raw_data = [
			'ticket_rsvp_enable_cannot_go' => '1',
		];

		$meta_fields = tribe( Meta_Fields::class );
		$meta_fields->save_show_not_going( $post_id, $ticket, $raw_data );

		$this->assertEquals( '1', get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
	}
}
