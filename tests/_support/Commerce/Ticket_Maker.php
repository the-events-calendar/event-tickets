<?php

namespace Tribe\Tickets\Test\Commerce;

use Tribe__Tickets__Global_Stock as Global_Stock;

trait Ticket_Maker {

	/**
	 * Generates ticket.
	 *
	 * @param string $provider  Provider class to use.
	 * @param int    $post_id   The ID of the post this ticket should be related to.
	 * @param int    $price
	 * @param array  $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int|false The new ticket ID or false if not saved.
	 */
	protected function create_ticket( $provider, $post_id, $price = 1, array $overrides = [] ) {
		/** @var \Tribe__Tickets__Tickets $provider_class */
		$provider_class = tribe( $provider );

		$post_id = absint( $post_id );

		$data = [
			'ticket_name'             => "Test ticket for {$post_id}",
			'ticket_description'      => "Test ticket description for {$post_id}",
			'ticket_show_description' => 1,
			'ticket_price'            => absint( $price ),
			'ticket_start_date'       => '2020-01-02',
			'ticket_start_time'       => '08:00:00',
			'ticket_end_date'         => '2050-03-01',
			'ticket_end_time'         => '20:00:00',
			'ticket_sku'              => "TEST-TKT-{$post_id}",
			'tribe-ticket'            => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 100,
			],
		];

		$data = array_merge( $data, $overrides );

		return $provider_class->ticket_add( $post_id, $data );
	}

	/**
	 * Generate multiple tickets for a post - the tickets need not be identical.
	 *
	 * @param string $provider      Provider class to use.
	 * @param int    $post_id       The ID of the post these tickets should be related to.
	 * @param array  $tickets       An array of tickets. Ech ticket must be an array.
	 *                              Any data in the array will override the defaults.
	 *                              This should be in the same format as the "overrides" you
	 *                              would send to create_ticket() above.
	 *
	 * @return array An array of the generated ticket IDs.
	 */
	protected function create_distinct_tickets( $provider, $post_id, array $tickets ) {
		$global_sales       = 0;
		$global_stock       = new Global_Stock( $post_id );
		$has_global_tickets = false;
		$ticket_ids         = [];

		foreach ( $tickets as $ticket ) {
			// Randomize price.
			try {
				$price = $ticket['price'] ?? random_int( 1, 10 );
			} catch ( \Exception $exception ) {
				$price = 5;
			}

			// Create ticket.
			$ticket_ids[] = $this->create_ticket( $provider, $post_id, $price, $ticket );
		}

		return $ticket_ids;
	}

	/**
	 * Generates multiple identical tickets for a post.
	 *
	 * @param string $provider  Provider class to use.
	 * @param int    $count     The number of tickets to create
	 * @param int    $post_id   The ID of the post this ticket should be related to.
	 * @param array  $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return array An array of the generated ticket IDs.
	 */
	protected function create_many_tickets( $provider, $count, $post_id, array $overrides = [] ) {
		$ticket_data = [];

		for ( $i = 0; $i < $count; $i ++ ) {
			$ticket_data[] = $overrides;
		}

		return $this->create_distinct_tickets( $provider, $post_id, $ticket_data );
	}
}
