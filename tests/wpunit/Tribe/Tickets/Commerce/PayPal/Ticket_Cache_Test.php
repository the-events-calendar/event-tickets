<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Commerce__PayPal__Stati as Stati;
use Tribe__Tickets__Commerce__PayPal__Order as Order;
use Tribe__Events__Main as TEC;

class Ticket_Cache_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @before
	 */
	public function ensure_paypal_module_active(): void {
		// Let's avoid confirmation emails.
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		// Enable PayPal module.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ PayPal::class ] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = 'page';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * It should fetch ticket from cache if possible
	 *
	 * @test
	 */
	public function should_fetch_ticket_from_cache_if_possible(): void {
		$post_id   = static::factory()->post->create();
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
			throw new \RuntimeException( 'Failed to update post' );
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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );

		$this->assertEquals(
			[ $ticket_id ],
			array_map( static fn( Ticket_Object $t ) => $t->ID, Tickets::get_all_event_tickets( $post_id ) )
		);
		$ticket = tribe( PayPal::class );

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

	/**
	 * It should invalidate the cache when an order for the ticket is created, updated, deleted
	 *
	 * @test
	 */
	public function should_invalidate_the_cache_when_an_order_for_the_ticket_is_created_updated_deleted(): void {
		$post_id   = static::factory()->post->create();
		// Ensure the post ticket provider is PayPal.
		update_post_meta( $post_id, '_tribe_default_ticket_provider', PayPal::class );
		$ticket_id = $this->create_paypal_ticket( $post_id, 23 );
		$ticket    = tribe( PayPal::class );

		// Flush the cache to start fresh.
		wp_cache_flush();

		// Fetch the ticket, the cache should be populated.
		$ticket_1 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_1->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);

		// Place a Pending order for 3 tickets.
		$order_ids     = $this->create_paypal_orders( $post_id, [ $ticket_id ], 3, 1, Stati::$pending );
		$order_id      = $order_ids[0]['Order ID'];
		$order_post_id = Order::find_by_order_id( $order_id );

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the order including the ticket was created.'
		);

		// Fetch the ticket, the cache should be populated again.
		$ticket_2 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_2->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);

		// Update the order to Completed.
		/** @var Order $order */
		$order = Order::from_order_id( $order_post_id, true );
		$order->set_meta( 'payment_status', Stati::$completed );
		$order->update();

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the order including the ticket was updated.'
		);

		// Fetch the ticket, the cache should be populated again.
		$ticket_3 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_3->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);

		// Set the order status to Refunded.
		$order->set_meta( 'payment_status', Stati::$refunded );
		$order->update();

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the order including the ticket was updated.'
		);

		// Fetch the ticket, the cache should be populated again.
		$ticket_4 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_4->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);

		// Delete the order.
		wp_delete_post( $order_post_id );

		$this->assertEmpty(
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket cache should have been invalidated when the order including the ticket was deleted.'
		);

		// Fetch the ticket, the cache should be populated again.
		$ticket_5 = $ticket->get_ticket( $post_id, $ticket_id );

		$this->assertEquals(
			$ticket_5->to_array(),
			wp_cache_get( $ticket_id, 'tec_tickets' ),
			'The ticket should be cached.'
		);
	}
}