<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

class Ticket_ObjectTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * @before
	 */
	public function ensure_user_can_edit_posts(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * It should not include Attendees injected from other posts into global inventory
	 *
	 * @test
	 */
	public function should_not_include_attendees_injected_from_other_posts_into_global_inventory(): void {
		$capacity_key = Tickets_Handler::instance()->key_capacity;
		// Create a first post and set its shared capacity to 100.
		$post_1 = static::factory()->post->create();
		update_post_meta( $post_1, $capacity_key, 100 );
		// Create a second post and set its shared capacity to 100.
		$post_2 = static::factory()->post->create();
		update_post_meta( $post_2, $capacity_key, 30 );
		// Create two shared capacity tickets for the first post.
		$global_ticket_for_post_1 = $this->create_tc_ticket( $post_1, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 100,
			],
		] );
		// Create 2 shared capcity tickets for the second post.
		$global_ticket_for_post_2 = $this->create_tc_ticket( $post_2, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 30,
			],
		] );

		$get_post_1_ticket = fn( int $id ) => Module::get_instance()->get_ticket( $post_1, $id );
		$get_post_2_ticket = fn( int $id ) => Module::get_instance()->get_ticket( $post_2, $id );

		// Baseline check before adding attendees.
		$this->assertEquals( 100, $get_post_1_ticket( $global_ticket_for_post_1 )->inventory() );
		$this->assertEquals( 30, $get_post_2_ticket( $global_ticket_for_post_2 )->inventory() );

		// Add 2 Attendees to post 1, global ticket inventories should change accordingly.
		$this->create_order( [ $global_ticket_for_post_1 => 2 ] );
		$this->assertEquals( 98, $get_post_1_ticket( $global_ticket_for_post_1 )->inventory() );
		$this->assertEquals( 30, $get_post_2_ticket( $global_ticket_for_post_2 )->inventory() );

		// Filter the query to get post 2 Attendees to pull them from post 1 as well.
		add_filter( 'tec_tickets_attendees_filter_by_event', function ( $post_ids ) use ( $post_1, $post_2 ) {
			if ( (array) $post_ids === [ $post_2 ] ) {
				return [ $post_1, $post_2 ];
			}

			return $post_ids;
		} );

		// Check attendees are the ones we expect: post 1 Attendees are injected into post 2 attendees.
		$this->assertEqualSets(
			tribe_attendees()->where( 'event', $post_1 )->get_ids(),
			tribe_attendees()->where( 'event', $post_2 )->get_ids()
		);

		// No matter the injection of attendees, inventory should not be affected for post 2 global ticket.
		$this->assertEquals( 98, $get_post_1_ticket( $global_ticket_for_post_1 )->inventory() );
		$this->assertEquals( 30, $get_post_2_ticket( $global_ticket_for_post_2 )->inventory() );
	}
}
