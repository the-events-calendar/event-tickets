<?php

namespace Tribe\Tickets\Test\Commerce\PayPal;

use Tribe\Tickets\Test\Commerce\Ticket_Maker as Ticket_Maker_Base;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Utils__Array as Utils_Array;

trait Ticket_Maker {

	use Ticket_Maker_Base;

	/**
	 * Get the ticket provider class.
	 *
	 * @return string Ticket provider class.
	 */
	protected function get_paypal_ticket_provider() {
		return 'tickets.commerce.paypal';
	}

	/**
	 * Generates a PayPal ticket for a post.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_paypal_ticket( $post_id, $price = 1, array $overrides = [] ) {
		$post_id = absint( $post_id );

		$data = [
			'ticket_name'        => "Test PayPal ticket for {$post_id}",
			'ticket_description' => "Test PayPal ticket description for {$post_id}",
			'ticket_price'       => $price,
		];

		$data = array_merge( $data, $overrides );

		return $this->create_ticket( $this->get_paypal_ticket_provider(), $post_id, $price, $data );
	}

	/**
	 * Generates multiple identical PayPal tickets for a post.
	 *
	 * @param int   $count     The number of tickets to create
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_many_paypal_tickets( $count, $post_id, array $overrides = [] ) {
		$ticket_data = [];

		for ( $i = 0; $i < $count; $i ++ ) {
			$ticket_data[] = $overrides;
		}

		return $this->create_distinct_tickets( $this->get_paypal_ticket_provider(), $post_id, $ticket_data );
	}

	/**
	 * Generate multiple tickets for a post - the tickets need not be identical.
	 *
	 * @param int   $post_id        The ID of the post these tickets should be related to.
	 * @param array $tickets        An array of tickets. Ech ticket must be an array.
	 *                              Any data in the array will override the defaults.
	 *                              This should be in the same format as the "overrides" you
	 *                              would send to create_paypal_ticket_basic() above.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_distinct_paypal_tickets( $post_id, array $tickets ) {
		return $this->create_distinct_tickets( $this->get_paypal_ticket_provider(), $post_id, $tickets );
	}

	/**
	 * Generates a PayPal ticket for a post.
	 *
	 * @deprecated Use create_paypal_ticket() going forward instead.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param int   $price     Ticket price.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 *
	 */
	protected function create_paypal_ticket_basic( $post_id, $price = 1, array $overrides = [] ) {
		$factory      = $this->factory ?? $this->factory();
		$global_stock = new Global_Stock( $post_id );
		$meta_input   = isset( $overrides['meta_input'] ) && is_array( $overrides['meta_input'] ) ? $overrides['meta_input'] : [];

		$capacity = Utils_Array::get( $meta_input, '_capacity', 100 );
		$sales    = Utils_Array::get( $meta_input, 'total_sales', 0 );

		// We don't set stock for unlimited tickets, take sales into account when setting stock.
		$calculated_stock = - 1 === $capacity ? null : ( $capacity - $sales );
		$manage_stock     = - 1 === $capacity ? 'no' : 'yes';
		$stock            = Utils_Array::get( $meta_input, '_stock', $calculated_stock );

		// Allow overriding the stock mode
		$stock_mode = Utils_Array::get( $meta_input, $global_stock::TICKET_STOCK_MODE, $global_stock::OWN_STOCK_MODE );

		unset( $overrides['meta_input'] );

		$default_meta_input = [
			'_tribe_tpp_for_event' => $post_id,
			'_price'               => $price,
			'_manage_stock'        => $manage_stock,
			'_ticket_start_date'   => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'_ticket_end_date'     => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
		];

		// We don't set stock or stock mode for unlimited tickets
		if ( tribe_is_truthy( $manage_stock ) ) {
			$default_meta_input['_stock']                           = $stock;
			$default_meta_input[ $global_stock::TICKET_STOCK_MODE ] = $stock_mode;
		}

		// We only set capacity for non-shared tickets
		if ( $global_stock::OWN_STOCK_MODE === $stock_mode || - 1 === $capacity ) {
			$default_meta_input[ tribe( 'tickets.handler' )->key_capacity ] = $capacity;
		}

		// if we have sales, set them
		if ( ! empty( $sales ) ) {
			$default_meta_input['total_sales'] = $sales;
		}

		$defaults = [
			'post_title'   => "Test PayPal ticket for " . $post_id,
			'post_content' => "Test PayPal ticket description for " . $post_id,
			'post_excerpt' => "Ticket PayPal ticket excerpt for " . $post_id,
			'post_type'    => tribe( 'tickets.commerce.paypal' )->ticket_object,
			'meta_input'   => array_merge( $default_meta_input, $meta_input ),
		];

		$ticket_id = $factory->post->create( array_merge( $defaults, $overrides ) );

		// Get provider key name.
		$provider_key = tribe( 'tickets.handler' )->key_provider_field;

		// Update provider key for post.
		update_post_meta( $post_id, $provider_key, 'Tribe__Tickets__Commerce__PayPal__Main' );

		return $ticket_id;
	}

	/**
	 * Generates multiple identical PayPal tickets for a post.
	 *
	 * @deprecated Use create_many_paypal_tickets() going forward instead.
	 *
	 * @param int   $count     The number of tickets to create
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_many_paypal_tickets_basic( $count, $post_id, array $overrides = [] ) {
		$ticket_data = [];

		for ( $i = 0; $i < $count; $i ++ ) {
			$ticket_data[] = $overrides;
		}

		return $this->create_distinct_paypal_tickets_basic( $post_id, $ticket_data );
	}

	/**
	 * Generate multiple tickets for a post - the tickets need not be identical.
	 * Handles global stock as well.
	 *
	 * @deprecated Use create_distinct_paypal_tickets() going forward instead.
	 *
	 * @param int   $post_id        The ID of the post these tickets should be related to.
	 * @param array $tickets        An array of tickets. Ech ticket must be an array.
	 *                              Any data in the array will override the defaults.
	 *                              This should be in the same format as the "overrides" you
	 *                              would send to create_paypal_ticket_basic() above.
	 * @param int   $global_qty     The global quantity to set, if needed. Will attempt to set
	 *                              intelligently if not provided when there are shared tickets.
	 *
	 * @return array An array of the generated ticket post IDs.
	 */
	protected function create_distinct_paypal_tickets_basic( $post_id, array $tickets, $global_qty = 0 ) {
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
			$ticket_id    = $this->create_paypal_ticket_basic( $post_id, $price, $ticket );
			$ticket_ids[] = $ticket_id;

			// Handle tickets with global stock
			$mode = $ticket['meta_input'][ $global_stock::TICKET_STOCK_MODE ] ?? $global_stock::OWN_STOCK_MODE;

			if ( in_array( $mode, [ $global_stock::CAPPED_STOCK_MODE, $global_stock::GLOBAL_STOCK_MODE ], true ) ) {
				$has_global_tickets = true;

				// Get ticket capacity.
				$cap = $ticket['meta_input']['_capacity'] ?? 0;

				// Handle passed sales.
				$sales = $ticket['meta_input']['total_sales'] ?? 0;

				if ( ! empty( $sales ) ) {
					$cap          -= $sales;
					$global_sales += $sales;
				}

				if ( $global_qty < $cap ) {
					// ensure we have enough cap to cover all tickets
					$global_qty = $cap;
				}
			}
		}

		// Handle event meta for global stock
		$global_stock->enable( $has_global_tickets );

		if ( $has_global_tickets ) {
			$global_qty = $global_qty ?? 100;

			/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
			$tickets_handler = tribe( 'tickets.handler' );
			update_post_meta( $post_id, $global_stock::TICKET_STOCK_CAP, $global_qty );
			update_post_meta( $post_id, $global_stock::GLOBAL_STOCK_LEVEL, $global_qty - $global_sales );
			update_post_meta( $post_id, $tickets_handler->key_capacity, $global_qty );
		}

		return $ticket_ids;
	}
}
