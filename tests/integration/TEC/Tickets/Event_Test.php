<?php

namespace TEC\Tickets;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Traits\With_Ticket_Providers;

/**
 * A ticket belongs to a post only when the ID is a ticket and the ticket is attached to that post.
 */
class Event_Test extends WPTestCase {
	use Attendee_Maker;
	use With_Ticket_Providers;

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_match_a_ticket_to_the_post_it_is_attached_to( string $provider, callable $make_ticket ): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $make_ticket( $post_id );

		$this->assertTrue( Event::ticket_belongs_to_post( $ticket_id, $post_id ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_match_a_ticket_to_another_post( string $provider, callable $make_ticket ): void {
		$ticket_id = $make_ticket( static::factory()->post->create() );

		$this->assertFalse( Event::ticket_belongs_to_post( $ticket_id, static::factory()->post->create() ) );
	}

	/**
	 * @test
	 * @dataProvider provider_ticket_makers
	 */
	public function it_should_not_match_a_ticket_without_a_parent_post( string $provider, callable $make_ticket ): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $make_ticket( $post_id );
		delete_post_meta( $ticket_id, tribe_tickets_get_ticket_provider( $ticket_id )->get_event_key() );

		$this->assertFalse( Event::ticket_belongs_to_post( $ticket_id, $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_match_a_post_that_is_not_a_ticket(): void {
		$post_id = static::factory()->post->create();
		$page_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->create_tc_ticket( $post_id, $this->faker->numberBetween( 1, 100 ) );

		$this->assertFalse( Event::ticket_belongs_to_post( $page_id, $post_id ) );
		$this->assertFalse( Event::ticket_belongs_to_post( $post_id, $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_match_an_attendee_of_the_post(): void {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $post_id, $this->faker->numberBetween( 1, 100 ) );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$this->assertFalse( Event::ticket_belongs_to_post( $attendee_id, $post_id ) );
	}
}
