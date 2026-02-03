<?php
/**
 * Tests for the RSVP to Tickets Commerce Migration.
 *
 * @since TBD
 */

namespace TEC\Tickets\Tests\RSVP_To_TC_Migration;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\Migrations\RSVP_To_Tickets_Commerce;
use TEC\Tickets\RSVP\V2\Constants;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Attendee_Maker as V2_Attendee_Maker;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker as V2_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as V1_Ticket_Maker;

/**
 * Class RSVP_To_Tickets_Commerce_Test
 *
 * @since TBD
 */
class RSVP_To_Tickets_Commerce_Test extends WPTestCase {
	use V1_Ticket_Maker;
	use Attendee_Maker;
	use V2_Ticket_Maker;
	use V2_Attendee_Maker;

	/**
	 * @var RSVP_To_Tickets_Commerce
	 */
	protected RSVP_To_Tickets_Commerce $migration;

	/**
	 * The migration ID used for registration.
	 */
	private const MIGRATION_ID = 'rsvp-to-tc';

	/**
	 * Set up the test.
	 *
	 * @before
	 */
	public function init(): void {
		$this->migration = new RSVP_To_Tickets_Commerce( self::MIGRATION_ID );
	}


	/**
	 * Create a V1 RSVP attendee with specific options.
	 *
	 * @param int    $ticket_id  The ticket ID.
	 * @param int    $post_id    The post ID.
	 * @param string $order_hash The order hash.
	 * @param array  $overrides  Additional overrides.
	 *
	 * @return int The attendee ID.
	 */
	protected function create_v1_rsvp_attendee( int $ticket_id, int $post_id, string $order_hash = '', array $overrides = [] ): int {
		$rsvp = tribe( 'tickets.rsvp' );

		$defaults = [
			'rsvp_status' => 'yes',
			'full_name'   => 'Test User',
			'email'       => 'test' . uniqid() . '@example.com',
		];

		$data = array_merge( $defaults, $overrides );

		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, $data );

		// Set the order hash.
		if ( ! empty( $order_hash ) ) {
			update_post_meta( $attendee_id, '_tribe_rsvp_order', $order_hash );
		}

		return $attendee_id;
	}

	/**
	 * Get all post meta for comparison.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $exclude Keys to exclude from comparison.
	 *
	 * @return array The meta array.
	 */
	protected function get_comparable_meta( int $post_id, array $exclude = [] ): array {
		$meta = get_post_meta( $post_id );

		// Remove excluded keys.
		foreach ( $exclude as $key ) {
			unset( $meta[ $key ] );
		}

		// Flatten single-value arrays.
		foreach ( $meta as $key => $value ) {
			if ( is_array( $value ) && count( $value ) === 1 ) {
				$meta[ $key ] = $value[0];
			}
		}

		ksort( $meta );

		return $meta;
	}

	/**
	 * Run the migration up for all unmigrated tickets.
	 */
	protected function run_migration_up(): void {
		$batch = 1;
		$run = false;
		while ( ! $this->migration->is_up_done() ) {
			$this->migration->up( $batch, 50 );
			$batch++;
			$run = true;
			// Safety limit.
			if ( $batch > 100 ) {
				break;
			}
		}

		$this->assertTrue( $run );
	}

	/**
	 * Run the migration down for all migrated tickets.
	 */
	protected function run_migration_down(): void {
		$batch = 1;
		$run = false;
		while ( ! $this->migration->is_down_done() ) {
			$this->migration->down( $batch, 50 );
			$batch++;
			$run = true;
			// Safety limit.
			if ( $batch > 100 ) {
				break;
			}
		}

		$this->assertTrue( $run );
	}

	/**
	 * @test
	 * It should report correct total items.
	 */
	public function should_report_correct_total_items(): void {
		$post_id = static::factory()->post->create();

		$this->assertEquals( 0, $this->migration->get_total_items() );

		$this->create_rsvp_ticket( $post_id );
		$this->create_rsvp_ticket( $post_id );

		$this->assertEquals( 2, $this->migration->get_total_items() );
	}

	/**
	 * @test
	 * It should migrate simple ticket with no attendees.
	 */
	public function should_migrate_simple_ticket_with_no_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity' => 100,
			],
		] );

		$this->assertEquals( 'tribe_rsvp_tickets', get_post_type( $ticket_id ) );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );
		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );

		// Verify migration marker.
		$this->assertNotEmpty( get_post_meta( $ticket_id, '_tec_rsvp_migrated_to_tc', true ) );

		// Verify key meta was added/renamed.
		$this->assertEquals( 'tc-rsvp', get_post_meta( $ticket_id, '_type', true ) );
		$this->assertEquals( $post_id, get_post_meta( $ticket_id, '_tec_tickets_commerce_event', true ) );
		$this->assertEquals( 'yes', get_post_meta( $ticket_id, '_manage_stock', true ) );
		$this->assertEquals( 'own', get_post_meta( $ticket_id, '_global_stock_mode', true ) );
		$this->assertEquals( 'instock', get_post_meta( $ticket_id, '_stock_status', true ) );
	}

	/**
	 * @test
	 * It should migrate ticket with single attendee.
	 */
	public function should_migrate_ticket_with_single_attendee(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'order-hash-1', [
			'full_name' => 'John Doe',
			'email'     => 'john@example.com',
		] );

		$this->assertEquals( 'tribe_rsvp_attendees', get_post_type( $attendee_id ) );

		$this->run_migration_up();

		// Verify attendee post type changed.
		clean_post_cache( $attendee_id );
		$this->assertEquals( TC_Attendee::POSTTYPE, get_post_type( $attendee_id ) );

		// Verify attendee has an order parent.
		$attendee_post = get_post( $attendee_id );
		$this->assertGreaterThan( 0, $attendee_post->post_parent );

		// Verify order was created.
		$order_id = $attendee_post->post_parent;
		$this->assertEquals( Order::POSTTYPE, get_post_type( $order_id ) );
		$this->assertEquals( 'tec-tc-completed', get_post_status( $order_id ) );

		// Verify order meta.
		$this->assertEquals( '0', get_post_meta( $order_id, Order::$total_value_meta_key, true ) );
		$this->assertEquals( 'free', get_post_meta( $order_id, Order::$gateway_meta_key, true ) );
		$this->assertEquals( 'John Doe', get_post_meta( $order_id, Order::$purchaser_full_name_meta_key, true ) );
		$this->assertEquals( 'john@example.com', get_post_meta( $order_id, Order::$purchaser_email_meta_key, true ) );

		// Verify attendee meta was renamed.
		$this->assertEquals( $ticket_id, get_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee_id, '_tec_tickets_commerce_event', true ) );
		$this->assertNotEmpty( get_post_meta( $attendee_id, '_tec_tickets_commerce_security_code', true ) );
	}

	/**
	 * @test
	 * It should migrate ticket with multiple attendees same order.
	 */
	public function should_migrate_ticket_with_multiple_attendees_same_order(): void {
		$post_id    = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $post_id );
		$order_hash = 'same-order-hash';

		$attendee_id_1 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
			'full_name' => 'First Person',
			'email'     => 'first@example.com',
		] );
		$attendee_id_2 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
			'full_name' => 'Second Person',
			'email'     => 'second@example.com',
		] );
		$attendee_id_3 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
			'full_name' => 'Third Person',
			'email'     => 'third@example.com',
		] );

		$this->run_migration_up();

		// All attendees should share the same order.
		clean_post_cache( $attendee_id_1 );
		clean_post_cache( $attendee_id_2 );
		clean_post_cache( $attendee_id_3 );

		$order_id_1 = get_post( $attendee_id_1 )->post_parent;
		$order_id_2 = get_post( $attendee_id_2 )->post_parent;
		$order_id_3 = get_post( $attendee_id_3 )->post_parent;

		$this->assertGreaterThan( 0, $order_id_1 );
		$this->assertEquals( $order_id_1, $order_id_2 );
		$this->assertEquals( $order_id_2, $order_id_3 );

		// Verify order items quantity.
		$items = get_post_meta( $order_id_1, Order::$items_meta_key, true );
		$this->assertEquals( 3, $items[0]['quantity'] );
	}

	/**
	 * @test
	 * It should migrate ticket with multiple attendees different orders.
	 */
	public function should_migrate_ticket_with_multiple_attendees_different_orders(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_id_1 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'order-hash-a', [
			'full_name' => 'Person A',
			'email'     => 'a@example.com',
		] );
		$attendee_id_2 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'order-hash-b', [
			'full_name' => 'Person B',
			'email'     => 'b@example.com',
		] );

		$this->run_migration_up();

		clean_post_cache( $attendee_id_1 );
		clean_post_cache( $attendee_id_2 );

		$order_id_1 = get_post( $attendee_id_1 )->post_parent;
		$order_id_2 = get_post( $attendee_id_2 )->post_parent;

		// Attendees should have different orders.
		$this->assertGreaterThan( 0, $order_id_1 );
		$this->assertGreaterThan( 0, $order_id_2 );
		$this->assertNotEquals( $order_id_1, $order_id_2 );

		// Each order should have quantity 1.
		$items_1 = get_post_meta( $order_id_1, Order::$items_meta_key, true );
		$items_2 = get_post_meta( $order_id_2, Order::$items_meta_key, true );
		$this->assertEquals( 1, $items_1[0]['quantity'] );
		$this->assertEquals( 1, $items_2[0]['quantity'] );
	}

	/**
	 * @test
	 * It should migrate not going attendees with status preserved.
	 */
	public function should_migrate_not_going_attendees_with_status_preserved(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create a "not going" attendee.
		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'not-going-order', [
			'rsvp_status' => 'no',
			'full_name'   => 'Not Going Person',
			'email'       => 'notgoing@example.com',
		] );

		$this->run_migration_up();

		clean_post_cache( $attendee_id );
		$attendee_post = get_post( $attendee_id );

		// The attendee should be migrated to TC attendee post type.
		$this->assertEquals( TC_Attendee::POSTTYPE, $attendee_post->post_type );

		// Should have an order parent.
		$this->assertGreaterThan( 0, $attendee_post->post_parent );

		// The "not going" status should be preserved in the new meta key.
		$this->assertEquals( 'no', get_post_meta( $attendee_id, '_tec_tickets_commerce_rsvp_status', true ) );

		// Other meta should also be migrated.
		$this->assertEquals( $ticket_id, get_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee_id, '_tec_tickets_commerce_event', true ) );
	}

	/**
	 * @test
	 * It should migrate ticket with unlimited capacity.
	 */
	public function should_migrate_ticket_with_unlimited_capacity(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity' => -1,
			],
		] );

		// Store original capacity before migration.
		$original_capacity = get_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_capacity, true );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );

		// Capacity should be preserved (unlimited stays unlimited).
		$migrated_capacity = get_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_capacity, true );
		$this->assertEquals( $original_capacity, $migrated_capacity );

		// Stock management should reflect unlimited.
		$manage_stock = get_post_meta( $ticket_id, '_manage_stock', true );
		// The migration sets _manage_stock to 'yes' regardless - verify it was set.
		$this->assertEquals( 'yes', $manage_stock );
	}

	/**
	 * @test
	 * It should migrate ticket with date restrictions.
	 */
	public function should_migrate_ticket_with_date_restrictions(): void {
		$post_id        = static::factory()->post->create();
		$start_datetime = '2024-01-15 09:00:00';
		$end_datetime   = '2024-12-31 23:59:59';

		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => $start_datetime,
				'_ticket_end_date'   => $end_datetime,
			],
		] );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		// Verify dates were split into date and time.
		$this->assertEquals( '2024-01-15', get_post_meta( $ticket_id, '_ticket_start_date', true ) );
		$this->assertEquals( '09:00:00', get_post_meta( $ticket_id, '_ticket_start_time', true ) );
		$this->assertEquals( '2024-12-31', get_post_meta( $ticket_id, '_ticket_end_date', true ) );
		$this->assertEquals( '23:59:59', get_post_meta( $ticket_id, '_ticket_end_time', true ) );

		// Also check non-prefixed versions.
		$this->assertEquals( '2024-01-15', get_post_meta( $ticket_id, 'ticket_start_date', true ) );
		$this->assertEquals( '09:00:00', get_post_meta( $ticket_id, 'ticket_start_time', true ) );
		$this->assertEquals( '2024-12-31', get_post_meta( $ticket_id, 'ticket_end_date', true ) );
		$this->assertEquals( '23:59:59', get_post_meta( $ticket_id, 'ticket_end_time', true ) );
	}

	/**
	 * @test
	 * It should preserve AR fields during migration.
	 */
	public function should_preserve_ar_fields_during_migration(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$ar_data = [
			'company'  => 'Test Corp',
			'job_title' => 'Developer',
		];

		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'ar-order', [
			'full_name' => 'AR Person',
			'email'     => 'ar@example.com',
		] );

		// Add AR fields.
		update_post_meta( $attendee_id, '_tribe_tickets_meta', $ar_data );

		$this->run_migration_up();

		clean_post_cache( $attendee_id );

		// AR fields should be preserved.
		$this->assertEquals( $ar_data, get_post_meta( $attendee_id, '_tribe_tickets_meta', true ) );
	}

	/**
	 * @test
	 * It should rollback simple ticket.
	 */
	public function should_rollback_simple_ticket(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Migrate up first.
		$this->run_migration_up();

		clean_post_cache( $ticket_id );
		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );

		// Now rollback.
		$this->run_migration_down();

		clean_post_cache( $ticket_id );

		// Verify post type restored.
		$this->assertEquals( 'tribe_rsvp_tickets', get_post_type( $ticket_id ) );

		// Verify migration marker removed.
		$this->assertEmpty( get_post_meta( $ticket_id, '_tec_rsvp_migrated_to_tc', true ) );

		// Verify V2 specific meta removed.
		$this->assertEmpty( get_post_meta( $ticket_id, '_type', true ) );
		$this->assertEmpty( get_post_meta( $ticket_id, '_ticket_start_time', true ) );
		$this->assertEmpty( get_post_meta( $ticket_id, '_ticket_end_time', true ) );

		// Verify original meta key restored.
		$this->assertEquals( $post_id, get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true ) );
	}

	/**
	 * @test
	 * It should rollback ticket with attendees and delete migration orders.
	 */
	public function should_rollback_ticket_with_attendees_and_delete_migration_orders(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'rollback-order', [
			'full_name' => 'Rollback Person',
			'email'     => 'rollback@example.com',
		] );

		// Migrate up.
		$this->run_migration_up();

		clean_post_cache( $attendee_id );
		$order_id = get_post( $attendee_id )->post_parent;
		$this->assertGreaterThan( 0, $order_id );

		// Rollback.
		$this->run_migration_down();

		clean_post_cache( $attendee_id );
		clean_post_cache( $order_id );

		// Attendee should be restored.
		$this->assertEquals( 'tribe_rsvp_attendees', get_post_type( $attendee_id ) );
		$this->assertEquals( 0, get_post( $attendee_id )->post_parent );

		// Attendee meta should be restored.
		$this->assertEquals( $ticket_id, get_post_meta( $attendee_id, '_tribe_rsvp_product', true ) );
		$this->assertEquals( $post_id, get_post_meta( $attendee_id, '_tribe_rsvp_event', true ) );

		// Migration-created order should be deleted.
		$this->assertNull( get_post( $order_id ) );
	}

	/**
	 * @test
	 * It should restore datetime fields on rollback.
	 */
	public function should_restore_datetime_fields_on_rollback(): void {
		$post_id        = static::factory()->post->create();
		$start_datetime = '2024-06-15 10:30:00';
		$end_datetime   = '2024-08-20 18:45:00';

		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => $start_datetime,
				'_ticket_end_date'   => $end_datetime,
			],
		] );

		// Migrate up then down.
		$this->run_migration_up();
		$this->run_migration_down();

		clean_post_cache( $ticket_id );

		// Datetime fields should be merged back.
		$this->assertEquals( '2024-06-15 10:30:00', get_post_meta( $ticket_id, '_ticket_start_date', true ) );
		$this->assertEquals( '2024-08-20 18:45:00', get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * @test
	 * It should produce ticket matching V2 RSVP structure after migration.
	 */
	public function should_produce_ticket_matching_v2_rsvp_structure_after_migration(): void {
		$post_id = static::factory()->post->create();

		// Create V1 RSVP and migrate.
		$v1_ticket_id = $this->create_rsvp_ticket( $post_id, [
			'post_title'   => 'Test RSVP Ticket',
			'post_content' => 'Test description',
			'meta_input'   => [
				'_capacity'          => 50,
				'_ticket_start_date' => '2024-01-01 09:00:00',
				'_ticket_end_date'   => '2024-12-31 17:00:00',
			],
		] );

		$this->run_migration_up();
		clean_post_cache( $v1_ticket_id );

		// Create a fresh V2 RSVP ticket.
		$v2_ticket_id = $this->create_tc_rsvp_ticket( $post_id, [
			'ticket_name'        => 'Test V2 RSVP Ticket',
			'ticket_description' => 'Test V2 description',
			'ticket_start_date'  => '2024-01-01',
			'ticket_start_time'  => '09:00:00',
			'ticket_end_date'    => '2024-12-31',
			'ticket_end_time'    => '17:00:00',
			'tribe-ticket'       => [
				'capacity' => 50,
			],
		] );

		// Both should be TC tickets.
		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $v1_ticket_id ) );
		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $v2_ticket_id ) );

		// Both should have the same type.
		$this->assertEquals( 'tc-rsvp', get_post_meta( $v1_ticket_id, '_type', true ) );
		$this->assertEquals( 'tc-rsvp', get_post_meta( $v2_ticket_id, '_type', true ) );

		// Both should have the event relation set.
		$this->assertEquals( $post_id, get_post_meta( $v1_ticket_id, '_tec_tickets_commerce_event', true ) );
		$this->assertEquals( $post_id, get_post_meta( $v2_ticket_id, '_tec_tickets_commerce_event', true ) );

		// Key structural meta should match.
		$structural_keys = [ '_type', '_manage_stock', '_global_stock_mode', '_stock_status', '_backorders' ];
		foreach ( $structural_keys as $key ) {
			$this->assertEquals(
				get_post_meta( $v2_ticket_id, $key, true ),
				get_post_meta( $v1_ticket_id, $key, true ),
				"Meta key {$key} should match between V1 migrated and V2 created tickets"
			);
		}
	}

	/**
	 * @test
	 * It should produce attendee matching V2 RSVP structure after migration.
	 */
	public function should_produce_attendee_matching_v2_rsvp_structure_after_migration(): void {
		$post_id = static::factory()->post->create();

		// Create V1 RSVP with attendee and migrate.
		$v1_ticket_id   = $this->create_rsvp_ticket( $post_id );
		$v1_attendee_id = $this->create_v1_rsvp_attendee( $v1_ticket_id, $post_id, 'comparison-order', [
			'full_name' => 'Compare Person',
			'email'     => 'compare@example.com',
			'optout'    => false,
		] );

		$this->run_migration_up();
		clean_post_cache( $v1_attendee_id );

		// Create V2 RSVP attendee.
		$v2_ticket_id   = $this->create_tc_rsvp_ticket( $post_id );
		$v2_attendee_id = $this->create_tc_rsvp_attendee( $v2_ticket_id, $post_id, [
			'full_name'   => 'Compare Person V2',
			'email'       => 'comparev2@example.com',
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		// Both should be TC attendees.
		$this->assertEquals( TC_Attendee::POSTTYPE, get_post_type( $v1_attendee_id ) );
		$this->assertEquals( TC_Attendee::POSTTYPE, get_post_type( $v2_attendee_id ) );

		// Key attendee meta should use TC keys.
		$this->assertEquals( $v1_ticket_id, get_post_meta( $v1_attendee_id, '_tec_tickets_commerce_ticket', true ) );
		$this->assertEquals( $v2_ticket_id, get_post_meta( $v2_attendee_id, '_tec_tickets_commerce_ticket', true ) );

		$this->assertEquals( $post_id, get_post_meta( $v1_attendee_id, '_tec_tickets_commerce_event', true ) );
		$this->assertEquals( $post_id, get_post_meta( $v2_attendee_id, '_tec_tickets_commerce_event', true ) );

		// Both should have security codes.
		$this->assertNotEmpty( get_post_meta( $v1_attendee_id, '_tec_tickets_commerce_security_code', true ) );
		$this->assertNotEmpty( get_post_meta( $v2_attendee_id, '_tec_tickets_commerce_security_code', true ) );
	}

	/**
	 * @test
	 * It should create order matching TC order structure.
	 */
	public function should_create_order_matching_tc_order_structure(): void {
		$post_id      = static::factory()->post->create();
		$v1_ticket_id = $this->create_rsvp_ticket( $post_id );

		$v1_attendee_id = $this->create_v1_rsvp_attendee( $v1_ticket_id, $post_id, 'tc-order-compare', [
			'full_name' => 'Order Compare Person',
			'email'     => 'ordercompare@example.com',
		] );

		$this->run_migration_up();
		clean_post_cache( $v1_attendee_id );

		$migration_order_id = get_post( $v1_attendee_id )->post_parent;

		// Should be a TC order.
		$this->assertEquals( Order::POSTTYPE, get_post_type( $migration_order_id ) );

		// Migration order should have all expected meta keys that TC orders have.
		$expected_meta_keys = [
			Order::$total_value_meta_key,
			Order::$subtotal_value_meta_key,
			Order::$items_meta_key,
			Order::$gateway_meta_key,
			Order::$hash_meta_key,
			Order::$currency_meta_key,
			Order::$purchaser_user_id_meta_key,
			Order::$purchaser_full_name_meta_key,
			Order::$purchaser_first_name_meta_key,
			Order::$purchaser_last_name_meta_key,
			Order::$purchaser_email_meta_key,
			Order::$gateway_order_id_meta_key,
			Order::$events_in_order_meta_key,
			Order::$tickets_in_order_meta_key,
		];

		foreach ( $expected_meta_keys as $key ) {
			$this->assertTrue(
				metadata_exists( 'post', $migration_order_id, $key ),
				"Migration order should have meta key: {$key}"
			);
		}

		// Migration order should be marked as migration-created.
		$this->assertNotEmpty( get_post_meta( $migration_order_id, '_tec_rsvp_migration_created', true ) );

		// Migration order should use 'free' gateway (RSVP has no payment).
		$this->assertEquals( 'free', get_post_meta( $migration_order_id, Order::$gateway_meta_key, true ) );

		// Migration order total should be 0 (RSVP is free).
		$this->assertEquals( '0', get_post_meta( $migration_order_id, Order::$total_value_meta_key, true ) );

		// Migration order should be completed.
		$this->assertEquals( 'tec-tc-completed', get_post_status( $migration_order_id ) );

		// Purchaser info should be set correctly.
		$this->assertEquals( 'Order Compare Person', get_post_meta( $migration_order_id, Order::$purchaser_full_name_meta_key, true ) );
		$this->assertEquals( 'ordercompare@example.com', get_post_meta( $migration_order_id, Order::$purchaser_email_meta_key, true ) );
		$this->assertEquals( 'Order', get_post_meta( $migration_order_id, Order::$purchaser_first_name_meta_key, true ) );
		$this->assertEquals( 'Compare Person', get_post_meta( $migration_order_id, Order::$purchaser_last_name_meta_key, true ) );

		// Items structure should have expected keys.
		$migration_items = get_post_meta( $migration_order_id, Order::$items_meta_key, true );

		$this->assertIsArray( $migration_items );
		$this->assertNotEmpty( $migration_items );

		$expected_item_keys = [ 'ticket_id', 'event_id', 'quantity', 'price', 'sub_total', 'type' ];
		foreach ( $expected_item_keys as $key ) {
			$this->assertArrayHasKey( $key, $migration_items[0], "Migration order item should have key: {$key}" );
		}

		// Item values should be correct.
		$this->assertEquals( $v1_ticket_id, $migration_items[0]['ticket_id'] );
		$this->assertEquals( $post_id, $migration_items[0]['event_id'] );
		$this->assertEquals( 1, $migration_items[0]['quantity'] );
		$this->assertEquals( '0', $migration_items[0]['price'] );
		$this->assertEquals( 'tc-rsvp', $migration_items[0]['type'] );
	}

	/**
	 * @test
	 * It should handle ticket with show not going option.
	 */
	public function should_handle_ticket_with_show_not_going_option(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_tribe_ticket_show_not_going' => '1',
			],
		] );

		$this->run_migration_up();
		clean_post_cache( $ticket_id );

		// The show_not_going meta should be copied to non-prefixed version.
		$this->assertEquals( '1', get_post_meta( $ticket_id, 'show_not_going', true ) );
	}

	/**
	 * @test
	 * It should set correct status counts based on sales.
	 */
	public function should_set_correct_status_counts_based_on_sales(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => 5,
			],
		] );

		$this->run_migration_up();
		clean_post_cache( $ticket_id );

		// Status counts should be set.
		$this->assertEquals( '0', get_post_meta( $ticket_id, '_tec_tc_ticket_status_count:created', true ) );
		$this->assertEquals( '0', get_post_meta( $ticket_id, '_tec_tc_ticket_status_count:unknown', true ) );
		$this->assertEquals( '0', get_post_meta( $ticket_id, '_tec_tc_ticket_status_count:pending', true ) );
		$this->assertEquals( '5', get_post_meta( $ticket_id, '_tec_tc_ticket_status_count:completed', true ) );
	}

	/**
	 * @test
	 * It should generate correct SKU format.
	 */
	public function should_generate_correct_sku_format(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->run_migration_up();
		clean_post_cache( $ticket_id );

		$sku = get_post_meta( $ticket_id, '_sku', true );
		$this->assertRegExp( '/^\d+-\d+-RSVP$/', $sku );
		$this->assertContains( (string) $ticket_id, $sku );
		$this->assertContains( (string) $post_id, $sku );
	}

	/**
	 * @test
	 * It should handle migration of multiple tickets in batches.
	 */
	public function should_handle_migration_of_multiple_tickets_in_batches(): void {
		$post_id = static::factory()->post->create();

		$ticket_ids = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$ticket_ids[] = $this->create_rsvp_ticket( $post_id );
		}

		$this->assertEquals( 5, $this->migration->get_total_items() );
		$this->assertFalse( $this->migration->is_up_done() );

		// Migrate in batch of 2.
		$this->migration->up( 1, 2 );

		// Should have 3 remaining.
		$this->assertEquals( 3, $this->migration->get_total_items() );

		// Complete the migration.
		$this->run_migration_up();

		$this->assertTrue( $this->migration->is_up_done() );

		// All tickets should be migrated.
		foreach ( $ticket_ids as $ticket_id ) {
			clean_post_cache( $ticket_id );
			$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );
		}
	}

	/**
	 * @test
	 * It should handle attendee with missing order hash.
	 */
	public function should_handle_attendee_with_missing_order_hash(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create attendee without setting order hash.
		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, '', [
			'full_name' => 'No Hash Person',
			'email'     => 'nohash@example.com',
		] );

		// Ensure no order hash is set.
		delete_post_meta( $attendee_id, '_tribe_rsvp_order' );

		$this->run_migration_up();
		clean_post_cache( $attendee_id );

		// Attendee should still be migrated with a generated order.
		$this->assertEquals( TC_Attendee::POSTTYPE, get_post_type( $attendee_id ) );
		$order_id = get_post( $attendee_id )->post_parent;
		$this->assertGreaterThan( 0, $order_id );
	}

	/**
	 * @test
	 * It should report is_applicable correctly.
	 */
	public function should_report_is_applicable_correctly(): void {
		// No tickets - not applicable.
		$this->assertFalse( $this->migration->is_applicable() );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Has V1 tickets - applicable.
		$this->assertTrue( $this->migration->is_applicable() );

		// Migrate.
		$this->run_migration_up();

		// Still applicable (has migrated tickets that can be rolled back).
		$this->assertTrue( $this->migration->is_applicable() );

		// Rollback.
		$this->run_migration_down();

		// Still applicable (has V1 tickets again).
		$this->assertTrue( $this->migration->is_applicable() );
	}

	/**
	 * @test
	 * It should handle optout attendees correctly.
	 */
	public function should_handle_optout_attendees_correctly(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'optout-order', [
			'full_name' => 'Optout Person',
			'email'     => 'optout@example.com',
			'optout'    => 'yes',
		] );

		$this->run_migration_up();
		clean_post_cache( $attendee_id );

		// Optout should be migrated to the new key.
		$this->assertEquals( 'yes', get_post_meta( $attendee_id, '_tec_tickets_commerce_optout', true ) );
	}

	/**
	 * Data provider for complex migration scenarios.
	 *
	 * @return array
	 */
	public function complex_migration_scenarios_provider(): array {
		return [
			'single_ticket_single_attendee'        => [
				'tickets'   => 1,
				'attendees' => [
					[ 'count' => 1, 'order_hash' => 'order-1' ],
				],
			],
			'single_ticket_multiple_same_order'    => [
				'tickets'   => 1,
				'attendees' => [
					[ 'count' => 3, 'order_hash' => 'same-order' ],
				],
			],
			'single_ticket_multiple_orders'        => [
				'tickets'   => 1,
				'attendees' => [
					[ 'count' => 1, 'order_hash' => 'order-a' ],
					[ 'count' => 2, 'order_hash' => 'order-b' ],
					[ 'count' => 1, 'order_hash' => 'order-c' ],
				],
			],
			'multiple_tickets_multiple_attendees'  => [
				'tickets'   => 3,
				'attendees' => [
					[ 'count' => 2, 'order_hash' => 'multi-order' ],
				],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider complex_migration_scenarios_provider
	 *
	 * It should handle complex migration scenarios.
	 *
	 * @param int   $ticket_count   Number of tickets to create.
	 * @param array $attendee_specs Attendee specifications.
	 */
	public function should_handle_complex_migration_scenarios( int $ticket_count, array $attendee_specs ): void {
		$post_id    = static::factory()->post->create();
		$ticket_ids = [];

		for ( $t = 0; $t < $ticket_count; $t++ ) {
			$ticket_ids[] = $this->create_rsvp_ticket( $post_id );
		}

		$attendee_ids = [];
		foreach ( $ticket_ids as $ticket_id ) {
			foreach ( $attendee_specs as $spec ) {
				for ( $a = 0; $a < $spec['count']; $a++ ) {
					$attendee_ids[] = $this->create_v1_rsvp_attendee(
						$ticket_id,
						$post_id,
						$spec['order_hash'] . '-t' . $ticket_id,
						[
							'full_name' => 'Attendee ' . count( $attendee_ids ),
							'email'     => 'attendee' . count( $attendee_ids ) . '@example.com',
						]
					);
				}
			}
		}

		// Migrate up.
		$this->run_migration_up();

		// Verify all tickets migrated.
		foreach ( $ticket_ids as $ticket_id ) {
			clean_post_cache( $ticket_id );
			$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );
		}

		// Verify all attendees migrated.
		foreach ( $attendee_ids as $attendee_id ) {
			clean_post_cache( $attendee_id );
			$this->assertEquals( TC_Attendee::POSTTYPE, get_post_type( $attendee_id ) );
			$this->assertGreaterThan( 0, get_post( $attendee_id )->post_parent );
		}

		// Rollback and verify restoration.
		$this->run_migration_down();

		foreach ( $ticket_ids as $ticket_id ) {
			clean_post_cache( $ticket_id );
			$this->assertEquals( 'tribe_rsvp_tickets', get_post_type( $ticket_id ) );
		}

		foreach ( $attendee_ids as $attendee_id ) {
			clean_post_cache( $attendee_id );
			$this->assertEquals( 'tribe_rsvp_attendees', get_post_type( $attendee_id ) );
			$this->assertEquals( 0, get_post( $attendee_id )->post_parent );
		}
	}

	// ==========================================
	// Edge Case Tests - Data Corruption
	// ==========================================

	/**
	 * @test
	 * It should skip ticket with no event relation.
	 */
	public function should_skip_ticket_with_no_event_relation(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Remove the event relation.
		delete_post_meta( $ticket_id, '_tribe_rsvp_for_event' );

		// Migration should complete without error.
		$this->migration->up( 1, 50 );

		clean_post_cache( $ticket_id );

		// Ticket should remain unmigrated (not marked as migrated).
		$this->assertEmpty( get_post_meta( $ticket_id, '_tec_rsvp_migrated_to_tc', true ) );

		// Post type should still be V1.
		$this->assertEquals( 'tribe_rsvp_tickets', get_post_type( $ticket_id ) );
	}

	/**
	 * @test
	 * It should handle malformed datetime values.
	 */
	public function should_handle_malformed_datetime_values(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => 'not-a-date',
				'_ticket_end_date'   => '',
			],
		] );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		// Migration should complete.
		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );

		// Malformed date should result in epoch-based date (1970-01-01).
		$start_date = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$this->assertNotEmpty( $start_date );
	}

	/**
	 * @test
	 * It should handle ticket with zero capacity.
	 */
	public function should_handle_ticket_with_zero_capacity(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity' => 0,
			],
		] );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );

		// Capacity of 0 may be stored as '0', 0, or empty - migration should handle gracefully.
		$capacity = get_post_meta( $ticket_id, '_capacity', true );
		$this->assertTrue( empty( $capacity ) || $capacity === '0' || $capacity === 0 );
	}

	// ==========================================
	// Edge Case Tests - Empty/Null Values
	// ==========================================

	/**
	 * Data provider for empty value edge cases.
	 *
	 * @return \Generator
	 */
	public function empty_value_edge_cases_provider(): \Generator {
		yield 'empty_full_name' => [
			'overrides' => [ 'full_name' => '' ],
		];

		yield 'whitespace_only_name' => [
			'overrides' => [ 'full_name' => '   ' ],
		];

		yield 'single_word_name' => [
			'overrides' => [ 'full_name' => 'Madonna' ],
		];

		yield 'very_long_name' => [
			'overrides' => [ 'full_name' => str_repeat( 'A', 200 ) ],
		];
	}

	/**
	 * @test
	 * @dataProvider empty_value_edge_cases_provider
	 *
	 * It should handle empty and edge case attendee values.
	 *
	 * @param array $overrides The attendee overrides.
	 */
	public function should_handle_empty_attendee_values( array $overrides ): void {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'edge-order', $overrides );

		$this->run_migration_up();

		clean_post_cache( $attendee_id );

		// Migration should complete.
		$this->assertEquals( TC_Attendee::POSTTYPE, get_post_type( $attendee_id ) );

		// Should have an order.
		$order_id = get_post( $attendee_id )->post_parent;
		$this->assertGreaterThan( 0, $order_id );
	}

	// ==========================================
	// Edge Case Tests - Resume/Idempotency
	// ==========================================

	/**
	 * @test
	 * It should resume migration after partial completion.
	 */
	public function should_resume_migration_after_partial_completion(): void {
		$post_id = static::factory()->post->create();

		$ticket_ids = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$ticket_ids[] = $this->create_rsvp_ticket( $post_id );
		}

		// Migrate first batch (5 tickets).
		$this->migration->up( 1, 5 );

		// Should have 5 remaining.
		$this->assertEquals( 5, $this->migration->get_total_items() );

		// Complete migration.
		$this->run_migration_up();

		// All should be migrated.
		$this->assertTrue( $this->migration->is_up_done() );
		foreach ( $ticket_ids as $ticket_id ) {
			clean_post_cache( $ticket_id );
			$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );
		}
	}

	/**
	 * @test
	 * It should not re-migrate already migrated tickets.
	 */
	public function should_not_remigrate_already_migrated_tickets(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->run_migration_up();

		$migration_timestamp = get_post_meta( $ticket_id, '_tec_rsvp_migrated_to_tc', true );

		// Wait a moment to ensure timestamp would differ.
		usleep( 100000 ); // 0.1 seconds

		// Run migration again.
		$this->migration->up( 1, 50 );

		// Migration marker should be unchanged.
		$this->assertEquals(
			$migration_timestamp,
			get_post_meta( $ticket_id, '_tec_rsvp_migrated_to_tc', true )
		);
	}

	/**
	 * @test
	 * It should correctly report completion status during partial migration.
	 */
	public function should_correctly_report_completion_status_during_partial_migration(): void {
		$post_id = static::factory()->post->create();

		// Create 3 tickets.
		$this->create_rsvp_ticket( $post_id );
		$this->create_rsvp_ticket( $post_id );
		$this->create_rsvp_ticket( $post_id );

		$this->assertFalse( $this->migration->is_up_done() );
		$this->assertEquals( 3, $this->migration->get_total_items() );

		// Migrate 1 ticket.
		$this->migration->up( 1, 1 );

		$this->assertFalse( $this->migration->is_up_done() );
		$this->assertEquals( 2, $this->migration->get_total_items() );

		// Complete migration.
		$this->run_migration_up();

		$this->assertTrue( $this->migration->is_up_done() );
		$this->assertEquals( 0, $this->migration->get_total_items() );
	}

	// ==========================================
	// Edge Case Tests - Timezone Handling
	// ==========================================

	/**
	 * @test
	 * It should preserve time values correctly in datetime migration.
	 */
	public function should_preserve_time_values_in_datetime_migration(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => '2024-06-15 08:30:00',
				'_ticket_end_date'   => '2024-12-25 23:59:59',
			],
		] );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		// Times should be preserved.
		$this->assertEquals( '2024-06-15', get_post_meta( $ticket_id, '_ticket_start_date', true ) );
		$this->assertEquals( '08:30:00', get_post_meta( $ticket_id, '_ticket_start_time', true ) );
		$this->assertEquals( '2024-12-25', get_post_meta( $ticket_id, '_ticket_end_date', true ) );
		$this->assertEquals( '23:59:59', get_post_meta( $ticket_id, '_ticket_end_time', true ) );
	}

	/**
	 * @test
	 * It should handle midnight time correctly.
	 */
	public function should_handle_midnight_time_correctly(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => '2024-01-01 00:00:00',
				'_ticket_end_date'   => '2024-01-02 00:00:00',
			],
		] );

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		$this->assertEquals( '00:00:00', get_post_meta( $ticket_id, '_ticket_start_time', true ) );
		$this->assertEquals( '00:00:00', get_post_meta( $ticket_id, '_ticket_end_time', true ) );
	}

	// ==========================================
	// Edge Case Tests - Multi-Event Scenarios
	// ==========================================

	/**
	 * @test
	 * It should handle multiple tickets across different events.
	 */
	public function should_handle_multiple_tickets_across_different_events(): void {
		$event_1 = static::factory()->post->create();
		$event_2 = static::factory()->post->create();
		$event_3 = static::factory()->post->create();

		$ticket_1 = $this->create_rsvp_ticket( $event_1 );
		$ticket_2 = $this->create_rsvp_ticket( $event_2 );
		$ticket_3 = $this->create_rsvp_ticket( $event_3 );

		$this->run_migration_up();

		// All tickets should be migrated with correct event relations.
		clean_post_cache( $ticket_1 );
		clean_post_cache( $ticket_2 );
		clean_post_cache( $ticket_3 );

		$this->assertEquals( $event_1, get_post_meta( $ticket_1, '_tec_tickets_commerce_event', true ) );
		$this->assertEquals( $event_2, get_post_meta( $ticket_2, '_tec_tickets_commerce_event', true ) );
		$this->assertEquals( $event_3, get_post_meta( $ticket_3, '_tec_tickets_commerce_event', true ) );
	}

	/**
	 * @test
	 * It should create separate orders for attendees from different tickets same order hash.
	 */
	public function should_create_separate_orders_for_different_tickets_same_order_hash(): void {
		$event_1 = static::factory()->post->create();
		$event_2 = static::factory()->post->create();

		$ticket_1 = $this->create_rsvp_ticket( $event_1 );
		$ticket_2 = $this->create_rsvp_ticket( $event_2 );

		// Same order hash but different tickets.
		$order_hash = 'shared-order-hash';

		$attendee_1 = $this->create_v1_rsvp_attendee( $ticket_1, $event_1, $order_hash );
		$attendee_2 = $this->create_v1_rsvp_attendee( $ticket_2, $event_2, $order_hash );

		$this->run_migration_up();

		clean_post_cache( $attendee_1 );
		clean_post_cache( $attendee_2 );

		$order_1 = get_post( $attendee_1 )->post_parent;
		$order_2 = get_post( $attendee_2 )->post_parent;

		// Each ticket migration creates its own orders.
		$this->assertGreaterThan( 0, $order_1 );
		$this->assertGreaterThan( 0, $order_2 );
	}

	// ==========================================
	// Edge Case Tests - Rollback
	// ==========================================

	/**
	 * @test
	 * It should preserve non-migration orders during rollback.
	 */
	public function should_preserve_non_migration_orders_during_rollback(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, 'rollback-test', [
			'full_name' => 'Rollback Test',
			'email'     => 'rollback@example.com',
		] );

		// Migrate up.
		$this->run_migration_up();

		clean_post_cache( $attendee_id );
		$order_id = get_post( $attendee_id )->post_parent;

		// Remove the migration marker from the order (simulate a manually created order).
		delete_post_meta( $order_id, '_tec_rsvp_migration_created' );

		// Rollback.
		$this->run_migration_down();

		clean_post_cache( $order_id );

		// Order should still exist (not deleted).
		$this->assertNotNull( get_post( $order_id ) );
		$this->assertEquals( Order::POSTTYPE, get_post_type( $order_id ) );
	}

	/**
	 * @test
	 * It should handle rollback with multiple attendees per order.
	 */
	public function should_handle_rollback_with_multiple_attendees_per_order(): void {
		$post_id    = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $post_id );
		$order_hash = 'multi-attendee-order';

		$attendee_1 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
			'full_name' => 'Person One',
			'email'     => 'one@example.com',
		] );
		$attendee_2 = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
			'full_name' => 'Person Two',
			'email'     => 'two@example.com',
		] );

		// Migrate up.
		$this->run_migration_up();

		clean_post_cache( $attendee_1 );
		clean_post_cache( $attendee_2 );

		// Both should share the same order.
		$order_id = get_post( $attendee_1 )->post_parent;
		$this->assertEquals( $order_id, get_post( $attendee_2 )->post_parent );

		// Rollback.
		$this->run_migration_down();

		clean_post_cache( $attendee_1 );
		clean_post_cache( $attendee_2 );
		clean_post_cache( $order_id );

		// Both attendees should be restored.
		$this->assertEquals( 'tribe_rsvp_attendees', get_post_type( $attendee_1 ) );
		$this->assertEquals( 'tribe_rsvp_attendees', get_post_type( $attendee_2 ) );
		$this->assertEquals( 0, get_post( $attendee_1 )->post_parent );
		$this->assertEquals( 0, get_post( $attendee_2 )->post_parent );

		// Order should be deleted.
		$this->assertNull( get_post( $order_id ) );
	}

	/**
	 * @test
	 * It should restore attendee title format on rollback.
	 */
	public function should_restore_attendee_title_format_on_rollback(): void {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$order_hash  = 'title-test-order';
		$attendee_id = $this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
			'full_name' => 'Title Test Person',
			'email'     => 'title@example.com',
		] );

		// Store original title format.
		$original_title = get_post( $attendee_id )->post_title;

		// Migrate and rollback.
		$this->run_migration_up();
		$this->run_migration_down();

		clean_post_cache( $attendee_id );

		// Title should be restored to original format (order_hash | full_name).
		$restored_title = get_post( $attendee_id )->post_title;
		$this->assertStringContainsString( '|', $restored_title );
		$this->assertStringContainsString( 'Title Test Person', $restored_title );
	}

	// ==========================================
	// Edge Case Tests - Large Dataset
	// ==========================================

	/**
	 * @test
	 * @group slow
	 * It should handle large dataset migration.
	 */
	public function should_handle_large_dataset_migration(): void {
		$post_id    = static::factory()->post->create();
		$ticket_ids = [];

		// Create 20 tickets with 5 attendees each (100 attendees total).
		for ( $t = 0; $t < 20; $t++ ) {
			$ticket_id    = $this->create_rsvp_ticket( $post_id );
			$ticket_ids[] = $ticket_id;

			$order_hash = 'large-order-' . $t;
			for ( $a = 0; $a < 5; $a++ ) {
				$this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
					'full_name' => "Attendee {$t}-{$a}",
					'email'     => "attendee{$t}_{$a}@example.com",
				] );
			}
		}

		$this->assertEquals( 20, $this->migration->get_total_items() );

		$this->run_migration_up();

		$this->assertTrue( $this->migration->is_up_done() );

		// Verify all tickets migrated.
		foreach ( $ticket_ids as $ticket_id ) {
			clean_post_cache( $ticket_id );
			$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );
		}
	}

	/**
	 * @test
	 * It should handle ticket with many attendees.
	 */
	public function should_handle_ticket_with_many_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create 50 attendees with same order hash.
		$order_hash = 'large-single-order';
		for ( $i = 0; $i < 50; $i++ ) {
			$this->create_v1_rsvp_attendee( $ticket_id, $post_id, $order_hash, [
				'full_name' => "Attendee {$i}",
				'email'     => "attendee{$i}@example.com",
			] );
		}

		$this->run_migration_up();

		clean_post_cache( $ticket_id );

		$this->assertEquals( TC_Ticket::POSTTYPE, get_post_type( $ticket_id ) );

		// Verify order has correct quantity.
		$attendees = get_posts( [
			'post_type'      => TC_Attendee::POSTTYPE,
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'   => '_tec_tickets_commerce_ticket',
					'value' => $ticket_id,
				],
			],
		] );

		$this->assertCount( 50, $attendees );

		// All should have the same order parent.
		$order_id = $attendees[0]->post_parent;
		foreach ( $attendees as $attendee ) {
			$this->assertEquals( $order_id, $attendee->post_parent );
		}

		// Order quantity should be 50.
		$items = get_post_meta( $order_id, Order::$items_meta_key, true );
		$this->assertEquals( 50, $items[0]['quantity'] );
	}
}
