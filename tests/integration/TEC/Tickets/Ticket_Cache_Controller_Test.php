<?php

namespace TEC\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee as Commerce_Attendee;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Ticket_Cache_Controller_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Tickets_Commerce;

	public function tearDown(): void {
		unset( $GLOBALS['post'] );
		parent::tearDown();
	}

	public function save_post_arguments(): array {
		return [
			'the post ID and the post' => [ static fn( int $post_id ) => [ $post_id, get_post( $post_id ) ] ],
			'the post ID alone'        => [ static fn( int $post_id ) => [ $post_id ] ],
			'the post ID and null'     => [ static fn( int $post_id ) => [ $post_id, null ] ],
		];
	}

	/**
	 * Some plugins fire `save_post` with the post ID alone, or WordPress hands it a `null` post.
	 *
	 * @test
	 * @dataProvider save_post_arguments
	 */
	public function it_should_clean_the_ticket_cache_of_a_saved_attendee_whatever_save_post_passes( callable $arguments ): void {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $post_id, 10 );
		$attendee_id = static::factory()->post->create( [ 'post_type' => Commerce_Attendee::POSTTYPE ] );
		add_post_meta( $attendee_id, Commerce_Attendee::$ticket_relation_meta_key, $ticket_id );
		wp_cache_set( $ticket_id, 'cached', 'tec_tickets' );

		tribe( Ticket_Cache_Controller::class )->clean_ticket_cache_from_related_post( ...$arguments( $attendee_id ) );

		$this->assertFalse( wp_cache_get( $ticket_id, 'tec_tickets' ) );
	}

	public function invalid_post_ids(): array {
		return [
			'a non-numeric ID' => [ 'not-a-post-id' ],
			'zero'             => [ 0 ],
			'a negative ID'    => [ -1 ],
		];
	}

	/**
	 * `get_post()` answers an empty ID with the global post, which must not be acted on in its place.
	 *
	 * @test
	 * @dataProvider invalid_post_ids
	 */
	public function it_should_not_clean_the_ticket_cache_of_the_global_post_for_an_invalid_post_id( $post_id ): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_tc_ticket( $event_id, 10 );
		$attendee_id = static::factory()->post->create( [ 'post_type' => Commerce_Attendee::POSTTYPE ] );
		add_post_meta( $attendee_id, Commerce_Attendee::$ticket_relation_meta_key, $ticket_id );
		$GLOBALS['post'] = get_post( $attendee_id );
		wp_cache_set( $ticket_id, 'cached', 'tec_tickets' );

		tribe( Ticket_Cache_Controller::class )->clean_ticket_cache_from_related_post( $post_id, 'not-a-post' );

		$this->assertSame( 'cached', wp_cache_get( $ticket_id, 'tec_tickets' ) );
	}

	/**
	 * @test
	 */
	public function it_should_do_nothing_when_save_post_passes_something_that_is_not_a_post(): void {
		tribe( Ticket_Cache_Controller::class )->clean_ticket_cache_from_related_post( 'not-a-post-id', 'not-a-post' );

		$this->addToAssertionCount( 1 );
	}
}
