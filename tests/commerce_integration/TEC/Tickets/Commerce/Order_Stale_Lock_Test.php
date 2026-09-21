<?php
/**
 * Regression tests for reclaiming order locks left behind by a request that died.
 *
 * An order is locked by writing a lock id into `post_content_filtered`, and lock_order() only ever
 * acquired a lock when that column was empty. Nothing released a lock whose request never reached
 * unlock_order() -- a fatal, a timeout or an exception thrown by a third party filter inside upsert(),
 * which takes its lock outside any transaction. The order was then locked for good: no status change,
 * no recheck and no admin action could ever move it again, so a buyer whose payment was captured
 * stayed stranded in Pending with no attendee.
 *
 * These drive lock_order() directly rather than going through modify_status(), because modify_status()
 * rolls its transaction back when the lock is refused. Under the test harness, which runs each test
 * with autocommit off, that rollback also discards the lock this test wrote, so the column could not
 * be inspected afterwards. Production runs with autocommit on and is unaffected.
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use DateTimeZone;
use ReflectionProperty;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Post;

class Order_Stale_Lock_Test extends WPTestCase {

	use Ticket_Maker;
	use Order_Maker;
	use With_Clock_Mock;

	/**
	 * The moment the clock is frozen at, so a lock's age is exact rather than a race against the
	 * wall clock.
	 */
	private const FROZEN_NOW = '2026-09-15 12:00:00';

	public function setUp(): void {
		parent::setUp();

		$this->freeze_time( $this->frozen_now() );
	}

	/**
	 * A lock held by a request that is still running must keep blocking, which is the whole point of
	 * the lock. Reclaiming stale locks must not weaken that.
	 */
	public function test_fresh_lock_is_not_reclaimed(): void {
		/*
		 * Driven on the real clock rather than the frozen one: this is the only case that needs no
		 * backdating, so the lock is taken by lock_order() itself, whose id carries the real time.
		 */
		$this->unfreeze_time();

		$order = $this->make_pending_order();

		$this->assertTrue(
			tribe( Order::class )->lock_order( $order->ID ),
			'The first holder must get the lock.'
		);

		$held = $this->read_lock( $order->ID );

		$this->assertFalse(
			tribe( Order::class )->lock_order( $order->ID ),
			'A lock taken moments ago must still block a second holder.'
		);

		$this->assertSame(
			$held,
			$this->read_lock( $order->ID ),
			'A refused acquisition must leave the held lock in place.'
		);
	}

	/**
	 * The fix: a lock older than the TTL belonged to a request that is long gone, so the next attempt
	 * takes it over.
	 */
	public function test_lock_older_than_the_ttl_is_reclaimed(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, $this->lock_id_aged( DAY_IN_SECONDS ) );

		$this->assertTrue(
			tribe( Order::class )->lock_order( $order->ID ),
			'A day-old lock must be reclaimable.'
		);

		$this->assertSame(
			tribe( Order::class )->get_lock_id(),
			$this->read_lock( $order->ID ),
			'Reclaiming must leave this request holding the lock.'
		);
	}

	/**
	 * The end to end effect: an order carrying a stale lock can change status again. Without the
	 * reclaim it was frozen at whatever status it was left in.
	 */
	public function test_order_with_a_stale_lock_can_transition_again(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, $this->lock_id_aged( DAY_IN_SECONDS ) );

		$this->assertTrue(
			tribe( Order::class )->modify_status( $order->ID, Completed::SLUG ),
			'An order holding a day-old lock must be transitionable again.'
		);

		$this->assertSame(
			tribe( Completed::class )->get_wp_slug(),
			get_post_status( $order->ID ),
			'The reclaimed order must actually carry the new status.'
		);
	}

	/**
	 * The lock lives in a general purpose WordPress column. Only a value this plugin wrote as a lock
	 * may ever be taken over, so content that is not a lock is left exactly where it is.
	 */
	public function test_content_that_is_not_a_lock_is_never_reclaimed(): void {
		$order   = $this->make_pending_order();
		$content = 'Some filtered content that is not a lock at all.';

		$this->write_lock( $order->ID, $content );

		$this->assertFalse(
			tribe( Order::class )->lock_order( $order->ID ),
			'A column holding something other than a lock must not be treated as a stale lock.'
		);

		$this->assertSame(
			$content,
			$this->read_lock( $order->ID ),
			'Content that is not a lock must be left untouched.'
		);
	}

	/**
	 * A lock id carrying our prefix but not the shape generate_lock_id() produces cannot be dated, so
	 * it is not assumed to be stale.
	 */
	public function test_undatable_lock_is_not_reclaimed(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, '_order_locknot-a-timestamp' );

		$this->assertFalse(
			tribe( Order::class )->lock_order( $order->ID ),
			'A lock whose age cannot be read must not be reclaimed.'
		);
	}

	/**
	 * Sites that legitimately hold an order open for longer can widen the window.
	 */
	public function test_ttl_is_filterable(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, $this->lock_id_aged( 2 * HOUR_IN_SECONDS ) );

		$ttl = static fn() => 3 * HOUR_IN_SECONDS;
		add_filter( 'tec_tickets_commerce_order_lock_ttl', $ttl );

		$reclaimed_with_wide_ttl = tribe( Order::class )->lock_order( $order->ID );

		remove_filter( 'tec_tickets_commerce_order_lock_ttl', $ttl );

		$this->assertFalse(
			$reclaimed_with_wide_ttl,
			'A TTL raised past the lock age must keep the lock in force.'
		);

		$this->assertTrue(
			tribe( Order::class )->lock_order( $order->ID ),
			'Back on the default TTL the same lock is stale and can be reclaimed.'
		);
	}

	/**
	 * The request whose lock was taken over must not be able to release the new holder's lock. Without
	 * a match on the lock id, it clears whatever is there and both requests believe they hold the order.
	 */
	public function test_a_superseded_request_cannot_release_the_new_holders_lock(): void {
		$order = $this->make_pending_order();
		$stale = $this->lock_id_aged( DAY_IN_SECONDS );

		$this->write_lock( $order->ID, $stale );

		$this->assertTrue(
			tribe( Order::class )->lock_order( $order->ID ),
			'The stale lock must be reclaimable.'
		);

		$new_holder = $this->read_lock( $order->ID );

		// The original request resumes, still believing the lock is its own.
		$this->hold_lock_id( $stale );
		tribe( Order::class )->unlock_order( $order->ID );

		$this->assertSame(
			$new_holder,
			$this->read_lock( $order->ID ),
			"A superseded request must not clear the lock another request now holds."
		);
	}

	/**
	 * A filter answering zero must not silently switch locking off.
	 */
	public function test_a_filtered_ttl_of_zero_does_not_disable_locking(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, $this->lock_id_aged( 0 ) );

		$ttl = static fn() => 0;
		add_filter( 'tec_tickets_commerce_order_lock_ttl', $ttl );

		$reclaimed = tribe( Order::class )->lock_order( $order->ID );

		remove_filter( 'tec_tickets_commerce_order_lock_ttl', $ttl );

		$this->assertFalse(
			$reclaimed,
			'A TTL of zero must not make a lock taken moments ago reclaimable.'
		);
	}

	/**
	 * A lock dated in the future must be left alone.
	 *
	 * Lock ids carry the clock of whichever node wrote them, so on a node running even slightly ahead
	 * every brand new lock looks future dated to its neighbours. Reclaiming those would let two requests
	 * mutate one order at once, which is worse than waiting.
	 */
	public function test_a_future_dated_lock_is_not_reclaimed(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, $this->lock_id_aged( -HOUR_IN_SECONDS ) );

		$this->assertFalse(
			tribe( Order::class )->lock_order( $order->ID ),
			'A lock dated in the future belongs to a node whose clock runs ahead, not to a dead request.'
		);
	}

	/**
	 * Leaving future-dated locks alone does not strand the order: the wall clock catches up, and once it
	 * has passed the lock by the lifetime, the lock is reclaimed like any other.
	 */
	public function test_a_future_dated_lock_is_reclaimed_once_the_clock_catches_up(): void {
		$order = $this->make_pending_order();

		$this->write_lock( $order->ID, $this->lock_id_aged( -HOUR_IN_SECONDS ) );

		// An hour and a half later: past the lock's own timestamp, and past the lifetime on top of it.
		$this->freeze_time( $this->frozen_now()->modify( '+90 minutes' ) );

		$this->assertTrue(
			tribe( Order::class )->lock_order( $order->ID ),
			'Once the clock has passed a future-dated lock by the lifetime, it is stale like any other.'
		);
	}

	/**
	 * An upsert whose lock is taken over before it saves must give up, not create a replacement. Its
	 * save pins the lock id, so it matches no rows -- and the order it could not write is the same one
	 * the new holder is writing, so a second one would leave one gateway payment with two orders.
	 */
	public function test_an_upsert_that_loses_its_lock_creates_no_second_order(): void {
		$order  = $this->make_pending_order();
		$before = $this->count_orders();

		$steal = function ( array $args ) use ( $order ): array {
			$this->write_lock( $order->ID, $this->lock_id_aged( 0 ) );

			return $args;
		};

		add_filter( 'tec_tickets_commerce_order_update_args', $steal );

		$result = tribe( Order::class )->upsert( tribe( Gateway::class ), $this->upsert_args( $order->ID ) );

		remove_filter( 'tec_tickets_commerce_order_update_args', $steal );

		$this->assertFalse(
			$result,
			'An upsert that lost its lock must refuse rather than create a second order.'
		);

		$this->assertSame(
			$before,
			$this->count_orders(),
			'The refused upsert must leave the orders it found behind it, and add none.'
		);
	}

	/**
	 * An order deleted while this request held its lock matches no rows on the save and none on the
	 * unlock either, which reads exactly like a lost lock. It is not one: there is no other holder and
	 * no order left to collide with, so the replacement this flow has always created must still happen.
	 */
	public function test_an_upsert_whose_order_is_deleted_creates_a_replacement(): void {
		$order = $this->make_pending_order();

		$delete = static function ( array $args ) use ( $order ): array {
			wp_delete_post( $order->ID, true );

			return $args;
		};

		add_filter( 'tec_tickets_commerce_order_update_args', $delete );

		$result = tribe( Order::class )->upsert( tribe( Gateway::class ), $this->upsert_args( $order->ID ) );

		remove_filter( 'tec_tickets_commerce_order_update_args', $delete );

		$this->assertInstanceOf(
			WP_Post::class,
			$result,
			'An order deleted mid-upsert must be replaced, not answered with a failure.'
		);

		$this->assertNotSame(
			$order->ID,
			$result->ID,
			'The replacement must be a new order, the deleted one being gone.'
		);
	}

	/**
	 * An unlocked order is still locked the ordinary way.
	 */
	public function test_unlocked_order_is_locked_normally(): void {
		$order = $this->make_pending_order();

		$this->assertTrue(
			tribe( Order::class )->lock_order( $order->ID ),
			'An order with no lock must be lockable.'
		);
	}

	/**
	 * Creates a Pending order with one ticket on it.
	 */
	private function make_pending_order(): WP_Post {
		$post   = static::factory()->post->create();
		$ticket = $this->create_tc_ticket( $post, 10 );

		return $this->create_order( [ $ticket => 1 ], [ 'order_status' => Pending::SLUG ] );
	}

	/**
	 * The arguments an upsert of an existing order carries, shaped like the ones create_from_cart()
	 * builds, so the create() fallback has everything it needs when it is the answer.
	 */
	private function upsert_args( int $order_id ): array {
		return [
			'id'                   => $order_id,
			'title'                => 'TEC-TC-T',
			'total_value'          => 10,
			'subtotal'             => 10,
			'items'                => [],
			'gateway'              => Gateway::get_key(),
			'hash'                 => 'stale-lock-upsert',
			'currency'             => 'USD',
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'stale-lock@test.com',
		];
	}

	/**
	 * Counts the orders in the table, past any cached query.
	 */
	private function count_orders(): int {
		return absint(
			DB::get_var(
				DB::prepare(
					'SELECT COUNT(ID) FROM %i WHERE post_type = %s',
					DB::prefix( 'posts' ),
					Order::POSTTYPE
				)
			)
		);
	}

	/**
	 * Writes a value straight into the lock column, modelling a lock left behind by a request that
	 * never reached unlock_order().
	 */
	private function write_lock( int $order_id, string $value ): void {
		DB::query(
			DB::prepare(
				'UPDATE %i SET post_content_filtered = %s WHERE ID = %d',
				DB::prefix( 'posts' ),
				$value,
				$order_id
			)
		);

		clean_post_cache( $order_id );
	}

	/**
	 * Makes the Order class believe this request holds the given lock id.
	 */
	private function hold_lock_id( string $lock_id ): void {
		$property = new ReflectionProperty( Order::class, 'lock_id' );
		$property->setAccessible( true );
		$property->setValue( null, $lock_id );
	}

	/**
	 * Reads the lock column straight from the database, past any cached post object.
	 */
	private function read_lock( int $order_id ): string {
		return DB::get_var(
			DB::prepare(
				'SELECT post_content_filtered FROM %i WHERE ID = %d',
				DB::prefix( 'posts' ),
				$order_id
			)
		) ?? '';
	}

	/**
	 * Builds a lock id of the shape generate_lock_id() produces, dated a given number of seconds
	 * before the frozen clock.
	 */
	private function lock_id_aged( int $seconds_ago ): string {
		return sprintf(
			'_order_lock%08x%05x.%s',
			$this->frozen_now()->getTimestamp() - $seconds_ago,
			0,
			'12345678'
		);
	}

	/**
	 * The instant the clock is frozen at.
	 */
	private function frozen_now(): DateTimeImmutable {
		return new DateTimeImmutable( self::FROZEN_NOW, new DateTimeZone( 'UTC' ) );
	}
}
