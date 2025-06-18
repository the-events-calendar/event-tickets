<?php
namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;
use TEC\Tickets\Commerce\Module as Module;
use TEC\Tickets\Ticket_Data;
use Tribe__Tickets__Global_Stock as Global_Stock;

trait Ticket_Maker {

	use Ticket_Maker_Base;

	/**
	 * The capacity to use for the ticket.
	 *
	 * @var array
	 */
	protected static array $capacity = [];

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
	 * Set the capacity for the ticket.
	 *
	 * @param int    $capacity The capacity to set.
	 * @param string $mode     The mode to set.
	 *
	 * @return self
	 */
	protected function with_capacity( int $capacity, ?string $mode = null ): self {
		self::$capacity = [
			'tribe-ticket' => [
				'mode'     => $mode ?? Global_Stock::OWN_STOCK_MODE,
				'capacity' => $capacity,
			]
		];

		return $this;
	}

	/**
	 * Create a ticket that is on sale.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_on_sale_tc_ticket( $post_id, $price = 1, array $overrides = [] ) {
		return $this->create_tc_ticket( $post_id, $price, $overrides );
	}

	/**
	 * Create a ticket that is in pre-sale.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_pre_sale_tc_ticket( $post_id, $price = 1, array $overrides = [] ) {
		return $this->create_tc_ticket( $post_id, $price, array_merge( $overrides, [
			'ticket_start_date' => '2150-01-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2150-03-01',
			'ticket_end_time'   => '20:00:00',
		] ) );
	}

	/**
	 * Create a ticket that is about to go on sale.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_about_to_be_on_sale_tc_ticket( $post_id, $price = 1, array $overrides = [] ) {
		return $this->create_tc_ticket( $post_id, $price, array_merge( $overrides, [
			'ticket_start_date' => gmdate( 'Y-m-d' ),
			'ticket_start_time' => gmdate( 'H:i:s', time() + MINUTE_IN_SECONDS - Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $post_id ) ),
			'ticket_end_date'   => '2150-03-01',
			'ticket_end_time'   => '20:00:00',
		] ) );
	}

	/**
	 * Create a ticket that has ended sales.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_after_sales_tc_ticket( $post_id, $price = 1, array $overrides = [] ) {
		return $this->create_tc_ticket( $post_id, $price, array_merge( $overrides, [
			'ticket_start_date' => '1994-01-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '1994-03-01',
			'ticket_end_time'   => '20:00:00',
		] ) );
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

		$data = array_merge( [
			'ticket_name'        => "Test TC ticket for {$post_id}",
			'ticket_description' => "Test TC ticket description for {$post_id}",
			'ticket_price'       => $price,
		], self::$capacity );

		self::$capacity = [];

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
