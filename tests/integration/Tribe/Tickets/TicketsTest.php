<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets as Tickets;

class TicketsTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
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
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	public function test_get_ticket_counts_with_no_tickets(): void {
		$post_id = static::factory()->post->create();

		$count = Tickets::get_ticket_counts( $post_id );

		$this->assertEquals( [], $count );
	}

	public function test_get_ticket_counts_with_one_unlimited_ticket(): void {
		$post_id          = static::factory()->post->create();
		$unlimited_ticket = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode' => '',
			],
		] );

		$count = Tickets::get_ticket_counts( $post_id );

		$this->assertEquals( [
			'rsvp'    =>
				[
					'count'     => 0,
					'stock'     => 0,
					'unlimited' => 0,
					'available' => 0,
				],
			'tickets' =>
				[
					'count'     => 1,
					'stock'     => 0,
					'global'    => 0,
					'unlimited' => 1,
					'available' => 1,
				],
		], $count );
	}

	public function test_get_ticket_counts_with_own_capacity_ticket(): void {
		$post_id             = static::factory()->post->create();
		$own_capacity_ticket = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 89,
			],
		] );

		$count = Tickets::get_ticket_counts( $post_id );

		$this->assertEquals( [
			'rsvp'    =>
				[
					'count'     => 0,
					'stock'     => 0,
					'unlimited' => 0,
					'available' => 0,
				],
			'tickets' =>
				[
					'count'     => 1,
					'stock'     => 89,
					'global'    => 0,
					'unlimited' => 0,
					'available' => 89,
				],
		], $count );
	}

	public function test_get_ticket_counts_with_shared_capacity_tickets(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, true );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 89 );
		$shared_capacity_ticket_1 = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 89,
			],
		] );
		$capped_capacity_ticket_2 = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 23,
			],
		] );

		$count = Tickets::get_ticket_counts( $post_id );

		$this->assertEquals( [
			'rsvp'    =>
				[
					'count'     => 0,
					'stock'     => 0,
					'unlimited' => 0,
					'available' => 0,
				],
			'tickets' =>
				[
					'count'     => 2,
					'stock'     => 89,
					'global'    => 1,
					'unlimited' => 0,
					'available' => 89,
				],
		], $count );
	}

	public function test_get_ticket_counts_with_all_capacity_types(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, true );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 89 );
		$shared_capacity_ticket_1 = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 89,
			],
		] );
		$capped_capacity_ticket_2 = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 23,
			],
		] );
		$own_capacity_ticket      = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 113,
			],
		] );
		$unlimited_ticket         = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode' => '',
			],
		] );

		$count = Tickets::get_ticket_counts( $post_id );

		$this->assertEquals( [
			'rsvp'    =>
				[
					'count'     => 0,
					'stock'     => 0,
					'unlimited' => 0,
					'available' => 0,
				],
			'tickets' =>
				[
					'count'     => 4,
					'stock'     => 202,
					'global'    => 1,
					'unlimited' => 1,
					'available' => 203,
				],
		], $count );
	}

	public function test_get_ticket_counts_with_tickets_from_diff_posts(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, true );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 89 );
		$shared_capacity_ticket_1 = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 89,
			],
		] );
		$capped_capacity_ticket_2 = $this->create_tc_ticket( $post_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 23,
			],
		] );
		$post_2_id                = static::factory()->post->create();
		$own_capacity_ticket_1    = $this->create_tc_ticket( $post_2_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 192,
			],
		] );
		$unlimited_ticket         = $this->create_tc_ticket( $post_2_id, 23, [
			'tribe-ticket' => [
				'mode' => '',
			],
		] );
		$post_3_id                = static::factory()->post->create();
		$own_capacity_ticket_2    = $this->create_tc_ticket( $post_3_id, 23, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 34,
			],
		] );
		// Filter the event IDs to include tickets from all the posts.
		add_filter( 'tec_tickets_repository_filter_by_event_id', function () use ( $post_id, $post_2_id, $post_3_id ) {
			return [ $post_id, $post_2_id, $post_3_id ];
		} );

		$count = Tickets::get_ticket_counts( $post_id );

		$this->assertEquals( [
			'rsvp'    =>
				[
					'count'     => 0,
					'stock'     => 0,
					'unlimited' => 0,
					'available' => 0,
				],
			'tickets' =>
				[
					'count'     => 5,
					'stock'     => 315,
					'global'    => 1,
					'unlimited' => 1,
					'available' => 316,
				],
		], $count );
	}
}
