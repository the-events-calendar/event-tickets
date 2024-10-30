<?php

namespace Tribe\Tickets\Editor;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;
use Tribe__Tickets__Editor__Meta as Meta;
use Tribe__Tickets__Global_Stock as Global_Stock;

class MetaTest extends \Codeception\TestCase\WPTestCase {
	use Commerce_Ticket_Maker;
	use SnapshotAssertions;

	/**
	 * @before
	 */
	public function ensure_post_ticketables(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
		add_filter(
			'tribe_tickets_post_types',
			static fn( array $post_types ): array => array_values( array_unique( array_merge( $post_types, [ 'post' ] ) ) )
		);
	}

	/**
	 * @before
	 */
	public function ensure_is_admin(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * It should correctly return information for a set of tickets with diff. capacities
	 *
	 * @test
	 */
	public function should_correctly_return_information_for_a_set_of_tickets_with_diff_capacities(): void {
		$meta = tribe( Meta::class );

		$post_id = static::factory()->post->create();
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		// Set a global capacity for the post of 217 tickets.
		update_post_meta( $post_id, $tickets_handler->key_capacity, 217 );
		$unlimited_ticket_id = $this->create_tc_ticket( $post_id, 3, [
			'tribe-ticket' => [
				'mode' => '',
			],
		] );
		// The ticket has been sold 2 times.
		update_post_meta( $unlimited_ticket_id, 'total_sales', 2 );

		$own_cap_ticket_id = $this->create_tc_ticket( $post_id, 5, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 113,
			],
		] );
		// The ticket has been sold 12 times.
		update_post_meta( $own_cap_ticket_id, 'total_sales', 12 );
		// The ticket stock is 113 - 12 = 101.
		update_post_meta( $own_cap_ticket_id, '_stock', 101 );

		$global_cap_ticket_id = $this->create_tc_ticket( $post_id, 8, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 217,
			],
		] );
		// The ticket has been sold 27 times.
		update_post_meta( $global_cap_ticket_id, 'total_sales', 27 );
		// The ticket stock is 217 - 27 = 190.
		update_post_meta( $global_cap_ticket_id, '_stock', 190 );

		$global_capped_ticket_id = $this->create_tc_ticket( $post_id, 13, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 89,
			]
		] );
		// The ticket has been sold 17 times.
		update_post_meta( $global_capped_ticket_id, 'total_sales', 17 );
		// The ticket stock is 89 - 17 = 72.
		update_post_meta( $global_capped_ticket_id, '_stock', 72 );

		// Impose a `menu_order` based position to the Tickets to have consistent order in the snapshot.
		wp_update_post( [ 'ID' => $unlimited_ticket_id, 'menu_order' => 0 ] );
		wp_update_post( [ 'ID' => $own_cap_ticket_id, 'menu_order' => 1 ] );
		wp_update_post( [ 'ID' => $global_cap_ticket_id, 'menu_order' => 2 ] );
		wp_update_post( [ 'ID' => $global_capped_ticket_id, 'menu_order' => 3 ] );

		$tickets_list = $meta->register_tickets_list_in_rest( '[]', $post_id, '_tribe_tickets_list', true );

		// Replace the ticket ids in the JSON string to avoid brittle snapshots.
		$tickets_list = str_replace(
			[ $post_id, $unlimited_ticket_id, $own_cap_ticket_id, $global_cap_ticket_id, $global_capped_ticket_id ],
			[ 1234, 12345, 12346, 12347, 12348 ],
			$tickets_list[0]
		);

		// Decode and encode again to have a pretty version of th JSON for the snapshot.
		$this->assertMatchesJsonSnapshot( json_encode( json_decode( $tickets_list ), JSON_PRETTY_PRINT ) );
	}

	/**
	 * It should correctly return information for tickets of differty types
	 *
	 * @test
	 */
	public function it_should_return_information_for_tickets_of_differty_types() {
		$meta = tribe( Meta::class );

		$post_id = static::factory()->post->create();
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		// Set a global capacity for the post of 217 tickets.
		update_post_meta( $post_id, $tickets_handler->key_capacity, 217 );
		$unlimited_ticket_id = $this->create_tc_ticket( $post_id, 3, [
			'tribe-ticket' => [
				'mode' => '',
			],
		] );
		// The ticket has been sold 2 times.
		update_post_meta( $unlimited_ticket_id, 'total_sales', 2 );

		$own_cap_ticket_id = $this->create_tc_ticket( $post_id, 5, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 113,
			],
		] );
		// The ticket has been sold 12 times.
		update_post_meta( $own_cap_ticket_id, 'total_sales', 12 );
		// The ticket stock is 113 - 12 = 101.
		update_post_meta( $own_cap_ticket_id, '_stock', 101 );
		// This ticket is a `pass`.
		update_post_meta( $own_cap_ticket_id, '_type', 'pass' );

		$global_cap_ticket_id = $this->create_tc_ticket( $post_id, 8, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
				'capacity' => 217,
			],
		] );
		// The ticket has been sold 27 times.
		update_post_meta( $global_cap_ticket_id, 'total_sales', 27 );
		// The ticket stock is 217 - 27 = 190.
		update_post_meta( $global_cap_ticket_id, '_stock', 190 );
		// This ticket too is a `pass`.
		update_post_meta( $global_cap_ticket_id, '_type', 'pass' );

		$global_capped_ticket_id = $this->create_tc_ticket( $post_id, 13, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 89,
			]
		] );
		// The ticket has been sold 17 times.
		update_post_meta( $global_capped_ticket_id, 'total_sales', 17 );
		// The ticket stock is 89 - 17 = 72.
		update_post_meta( $global_capped_ticket_id, '_stock', 72 );
		// This ticket too is a `membership_admission`.
		update_post_meta( $global_capped_ticket_id, '_type','membership_admission' );

		// Impose a `menu_order` based position to the Tickets to have consistent order in the snapshot.
		wp_update_post( [ 'ID' => $unlimited_ticket_id, 'menu_order' => 0 ] );
		wp_update_post( [ 'ID' => $own_cap_ticket_id, 'menu_order' => 1 ] );
		wp_update_post( [ 'ID' => $global_cap_ticket_id, 'menu_order' => 2 ] );
		wp_update_post( [ 'ID' => $global_capped_ticket_id, 'menu_order' => 3 ] );

		$tickets_list = $meta->register_tickets_list_in_rest( '[]', $post_id, '_tribe_tickets_list', true );

		// Replace the ticket ids in the JSON string to avoid brittle snapshots.
		$tickets_list = str_replace(
			[ $post_id, $unlimited_ticket_id, $own_cap_ticket_id, $global_cap_ticket_id, $global_capped_ticket_id ],
			[ 1234, 12345, 12346, 12347, 12348 ],
			$tickets_list[0]
		);

		// Decode and encode again to have a pretty version of th JSON for the snapshot.
		$this->assertMatchesJsonSnapshot( json_encode( json_decode( $tickets_list ), JSON_PRETTY_PRINT ) );
	}
}
