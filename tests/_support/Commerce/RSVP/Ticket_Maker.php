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
	protected function create_rsvp_ticket( $post_id, array $overrides = [] ) {
		$factory = $this->factory ?? $this->factory();

		$meta_input = isset( $overrides['meta_input'] ) && \is_array( $overrides['meta_input'] )
			? $overrides['meta_input']
			: array();

		unset( $overrides['meta_input'] );

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$capacity = \Tribe__Utils__Array::get( $meta_input, '_capacity', 100 );
		$stock    = \Tribe__Utils__Array::get( $meta_input, '_stock', $capacity );

		unset( $meta_input['_capacity'], $meta_input['_stock'] );

		$merged_meta_input = array_merge( [
			'_tribe_rsvp_for_event'                          => $post_id,
			'total_sales'                                    => 0,
			'_stock'                                         => $stock,
			tribe( 'tickets.handler' )->key_capacity         => $capacity,
			'_manage_stock'                                  => 'yes',
			'_ticket_start_date'                             => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'_ticket_end_date'                               => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
			\Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE => 'own',
		], $meta_input );

		// if the ticket start and/or end date(s) are set to empty values they should
		// not be set
		foreach ( [ '_ticket_start_date', '_ticket_end_date' ] as $key ) {
			if ( empty( $merged_meta_input[ $key ] ) ) {
				unset( $merged_meta_input[ $key ] );
			}
		}

		$ticket_id = $factory->post->create( array_merge(
				[
					'post_title'   => "Test RSVP ticket for {$post_id}",
					'post_content' => "Test RSVP ticket description for {$post_id}",
					'post_excerpt' => "Ticket RSVP ticket excerpt for {$post_id}",
					'post_type'    => $rsvp->ticket_object,
					'meta_input'   => $merged_meta_input,
				], $overrides )
		);

		return $ticket_id;
	}

	protected function create_many_rsvp_tickets( int $count, int $post_id, array $overrides = [] ) {
		return array_map( function () use ( $post_id, $overrides ) {
			return $this->create_rsvp_ticket( $post_id, $overrides );
		}, range( 1, $count ) );
	}
}
