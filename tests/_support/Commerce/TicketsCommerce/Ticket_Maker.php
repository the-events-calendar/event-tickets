<?php
namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;
use TEC\Tickets\Commerce\Module as Module;

trait Ticket_Maker {

	use Ticket_Maker_Base;

	/**
	 * Get the ticket provider class.
	 *
	 * @since 5.5.1
	 *
	 * @return string Ticket provider class.
	 */
	protected function get_ticket_provider() {
		return tribe( Module::class );
	}

	/**
	 * Generates a ticket for a post.
	 *
	 * @since 5.5.1
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_tc_ticket( $post_id, $price = 1, array $overrides = [] ) {
		$post_id = absint( $post_id );

		$data = [
			'ticket_name'        => "Test TC ticket for {$post_id}",
			'ticket_description' => "Test TC ticket description for {$post_id}",
			'ticket_price'       => $price,
		];

		$data = array_merge( $data, $overrides );

		return $this->create_ticket( $this->get_ticket_provider(), $post_id, $price, $data );
	}

	/**
	 * Generates multiple identical TC tickets for a post.
	 *
	 * @since 5.5.1
	 *
	 * @param int   $count     The number of tickets to create
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_many_tc_tickets( $count, $post_id, array $overrides = [] ) {
		return $this->create_many_tickets( $this->get_ticket_provider(), $count, $post_id, $overrides );
	}

	/**
	 * Generate multiple tickets for a post - the tickets need not be identical.
	 *
	 * @since 5.5.1
	 *
	 * @param int   $post_id        The ID of the post these tickets should be related to.
	 * @param array $tickets        An array of tickets. Each ticket must be an array.
	 *                              Any data in the array will override the defaults.
	 *                              This should be in the same format as the "overrides" you
	 *                              would send to create_tc_ticket() above.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_distinct_tc_tickets( $post_id, array $tickets ) {
		return $this->create_distinct_tickets( $this->get_ticket_provider(), $post_id, $tickets );
	}
}
