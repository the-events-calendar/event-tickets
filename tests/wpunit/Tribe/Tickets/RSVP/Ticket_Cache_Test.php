<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use ReflectionClass;
use RuntimeException;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets as Tickets;

class Ticket_Cache_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * It should fetch ticket from cache if possible
	 *
	 * @test
	 */
	public function should_fetch_ticket_from_cache_if_possible(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$no_cache_ticket = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$no_cache_ticket->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The first fetch should have set the cache.'
		);

		// Modify the cached value to inject a different price: we'll use this to test that the cache is used.
		$cached_value          = wp_cache_get( $ticket_id, 'tec_tickets' );
		$cached_value['price'] = 89;
		wp_cache_set( $ticket_id, $cached_value, 'tec_tickets' );

		$cached_ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$cached_value,
			$cached_ticket_1->to_array(),
			'The second fetch should have returned the cached ticket.'
		);

		$cached_ticket_2 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$cached_value,
			$cached_ticket_2->to_array(),
			'The third fetch should have returned the cached ticket.'
		);
		$this->assertInstanceOf( Ticket_Object::class, $no_cache_ticket );
		$this->assertInstanceOf( Ticket_Object::class, $cached_ticket_1 );
		$this->assertInstanceOf( Ticket_Object::class, $cached_ticket_2 );
		$this->assertNotSame( $no_cache_ticket, $cached_ticket_1 );
		$this->assertNotSame( $cached_ticket_1, $cached_ticket_2 );
	}

	/**
	 * It should flush ticket cache when ticket is saved
	 *
	 * @test
	 */
	public function should_flush_ticket_cache_when_ticket_is_saved(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		// Fetch the ticket a 2nd time, it should hit the cached value.
		$ticket_2 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_1->to_array(),
			$ticket_2->to_array(),
			'The second fetch should have returned the cached ticket.'
		);

		// Update the ticket price and save the ticket.
		$ticket_1->price = 89;
		$ticket->save_ticket( $post_id, $ticket_1 );

		$ticket_3 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertNotEquals(
			$ticket_1->to_array(),
			$ticket_3->to_array(),
			'The third fetch should have returned the updated ticket.'
		);

		$ticket_4 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_3->to_array(),
			$ticket_4->to_array(),
			'The fourth fetch should have returned the updated, cached, ticket.'
		);
	}

	/**
	 * It should not flush the ticket cache on update of the post the ticket is attached to
	 *
	 * @test
	 */
	public function should_not_flush_the_ticket_cache_on_update_of_the_post_the_ticket_is_attached_to(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		// Fetch the ticket a 2nd time, it should hit the cached value.
		$ticket_2 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_1->to_array(),
			$ticket_2->to_array(),
			'The second fetch should have returned the cached ticket.'
		);

		// Modify the cached value to inject a different price: we'll use this to test that the cache is used.
		$cached_value          = wp_cache_get( $ticket_id, 'tec_tickets' );
		$cached_value['price'] = 89;
		wp_cache_set( $ticket_id, $cached_value, 'tec_tickets' );

		// Update the post the ticket is attached to.
		if ( ! wp_update_post( [
			'ID'         => $post_id,
			'post_title' => 'New title',
		] ) ) {
			throw new RuntimeException( 'Failed to update post' );
		}

		// Fetch the ticket a third time.
		$ticket_3 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$cached_value,
			$ticket_3->to_array(),
			'The third fetch should have returned the cached ticket.'
		);
	}

	/**
	 * It should flush the ticket cache on post cache flush
	 *
	 * @test
	 */
	public function should_flush_the_ticket_cache_on_post_cache_flush(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEquals(
			$ticket_1->to_array(),
			$cached,
			'The ticket should be cached.'
		);

		clean_post_cache( $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEmpty(
			$cached,
			'The ticket cache should have been flushed.'
		);
	}

	/**
	 * It should flush the ticket cache on post trashing
	 *
	 * @test
	 */
	public function should_flush_the_ticket_cache_on_post_trashing(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEquals(
			$ticket_1->to_array(),
			$cached,
			'The ticket should be cached.'
		);

		wp_delete_post( $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEmpty(
			$cached,
			'The ticket cache should have been flushed.'
		);
	}

	/**
	 * It should flush the ticket cache on post deletion
	 *
	 * @test
	 */
	public function should_flush_the_ticket_cache_on_post_deletion(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEquals(
			$ticket_1->to_array(),
			$cached,
			'The ticket should be cached.'
		);

		wp_delete_post( $ticket_id, true );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEmpty(
			$cached,
			'The ticket cache should have been flushed.'
		);
	}

	/**
	 * It should only cache primitive values
	 *
	 * @test
	 */
	public function should_only_cache_primitive_values(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );

		$assert = function ( $value, $key = null ) use ( &$assert ): void {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					$assert( $v, $key . '.' . $k );
				}
				return;
			}

			$this->assertTrue(
				is_scalar( $value )
				|| is_null( $value )
				|| ( is_object( $value ) && ( new ReflectionClass( $value ) )->isInternal() ),
				"Value of $key is not scalar"
			);
		};

		$assert( $cached );
	}

	/**
	 * It should invalidate the ticket cache when its meta is added, updated, deleted
	 *
	 * @test
	 */
	public function should_invalidate_the_ticket_cache_when_its_meta_is_added_updated_deleted(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( RSVP::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertEquals(
			$ticket_1->to_array(),
			$cached,
			'The ticket should be cached.'
		);

		// Add a meta field to the ticket.
		add_post_meta( $ticket_id, 'foo', 'bar' );

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the meta was added.'
		);

		// Fetch the ticket, the cache should be populated again.
		$ticket_2 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_2->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);

		// Update the meta field from the ticket.
		update_post_meta( $ticket_id, 'foo', 'baz' );

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the meta was updated.'
		);

		// Fetch the ticket, the cache should be populated again.
		$ticket_3 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_3->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);

		// Delete the meta field from the ticket.
		delete_post_meta( $ticket_id, 'foo' );

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the meta was deleted.'
		);
	}
}