<?php

namespace Tribe\Tickets\Test\Commerce\RSVP;


trait Ticket_Maker {

	/**
	 * Generates an RSVP ticket for a post.
	 *
	 * @param       int $post_id   The ID of the post this ticket should be related to.
	 * @param array     $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_ticket( $post_id, array $overrides = [] ) {
		$factory = $this->factory ?? $this->factory();

		$meta_input = isset( $overrides['meta_input'] ) && \is_array( $overrides['meta_input'] )
			? $overrides['meta_input']
			: array();

		unset( $overrides['meta_input'] );

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$capacity = \Tribe__Utils__Array::get( $meta_input, '_capacity', 100 );

		/**
		 * For RSVP tickets the Stock is really the capacity; here we gently
		 * ignore the user input to work around the non-intuitive management
		 * of stock in RSVP tickets.
		 */
		$stock = $capacity;

		unset( $meta_input['_capacity'], $meta_input['_stock'] );

		$ticket_id = $factory->post->create( array_merge(
				[
					'post_title'   => "Test RSVP ticket for {$post_id}",
					'post_content' => "Test RSVP ticket description for {$post_id}",
					'post_excerpt' => "Ticket RSVP ticket excerpt for {$post_id}",
					'post_type'    => $rsvp->ticket_object,
					'meta_input'   => array_merge( [
						'_tribe_rsvp_for_event'                          => $post_id,
						'total_sales'                                    => 0,
						'_stock'                                         => $stock,
						'_capacity'                                      => $capacity,
						'_manage_stock'                                  => 'yes',
						'_ticket_start_date'                             => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
						'_ticket_end_date'                               => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
						\Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE => 'own',
					], $meta_input ),
				], $overrides )
		);

		return $ticket_id;
	}

	protected function create_many_tickets( int $count, int $post_id, array $overrides = [] ) {
		return array_map( function () use ( $post_id, $overrides ) {
			return $this->create_ticket( $post_id, $overrides );
		}, range( 1, $count ) );
	}
}
