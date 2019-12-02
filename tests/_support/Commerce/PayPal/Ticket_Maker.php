<?php

namespace Tribe\Tickets\Test\Commerce\PayPal;

trait Ticket_Maker {

	/**
	 * Generates a PayPal ticket for a post.
	 *
	 * @param       int $post_id   The ID of the post this ticket should be related to.
	 * @param       int $price
	 * @param array     $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_paypal_ticket( int $post_id, int $price, array $overrides = [] ) {
		$factory = $this->factory ?? $this->factory();

		$meta_input = isset( $overrides['meta_input'] ) && \is_array( $overrides['meta_input'] )
			? $overrides['meta_input']
			: array();

		$capacity = \Tribe__Utils__Array::get( $meta_input, '_capacity', 100 );
		$sales = empty( $overrides['meta_input']['total_sales'] ) ? 0 : $overrides['meta_input']['total_sales'];

		// We don't set stock for unlimited tickets, take sales into account when setting stock.
		$calculated_stock = 0 < $capacity ? ( $capacity - $sales ) : null;

		$manage_stock = 0 < $capacity ? 'yes' : 'no';

		$stock    = \Tribe__Utils__Array::get( $meta_input, '_stock', $calculated_stock );

		unset( $overrides['meta_input'] );

		$default_meta_input = [
			'_tribe_tpp_for_event'                           => $post_id,
			'_price'                                         => $price,
			tribe( 'tickets.handler' )->key_capacity         => $capacity,
			'_manage_stock'                                  => $manage_stock,
			'_ticket_start_date'                             => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'_ticket_end_date'                               => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
			\Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE => 'own',
		];

		// We don't set stock for unlimited tickets
		if ( ! is_null( $stock ) ) {
			$default_meta_input['_stock' ] = $stock;
		}

		// if we have sales, set them
		if ( ! empty( $sales ) ) {
			$default_meta_input['total_sales' ] = $sales;
		}

		$defaults = [
			'post_title'   => "Test PayPal ticket for {$post_id}",
			'post_content' => "Test PayPal ticket description for {$post_id}",
			'post_excerpt' => "Ticket PayPal ticket excerpt for {$post_id}",
			'post_type'    => tribe( 'tickets.commerce.paypal' )->ticket_object,
			'meta_input'   => array_merge( $default_meta_input, $meta_input )
		];

		$ticket_id = $factory->post->create( array_merge( $defaults, $overrides ) );

		return $ticket_id;
	}

	protected function create_many_paypal_tickets( int $count, int $post_id, array $overrides = [] ) {
		return array_map( function () use ( $post_id, $overrides ) {
			$price = $overrides['price'] ?? random_int( 1, 5 );

			return $this->create_paypal_ticket( $post_id, $price, $overrides );
		}, range( 1, $count ) );
	}
}
