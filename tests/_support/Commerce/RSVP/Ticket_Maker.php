<?php

namespace Tribe\Tickets\Test\Commerce\RSVP;

use Tribe__Utils__Array as Utils_Array;

trait Ticket_Maker {

	/**
	 * Generates an RSVP ticket for a post.
	 *
	 * @param int   $post_id   The ID of the post this ticket should be related to.
	 * @param array $overrides An array of values to override the default and random generation arguments.
	 *
	 * @return int The generated ticket post ID.
	 */
	protected function create_rsvp_ticket( $post_id, array $overrides = [] ) {
		$factory = $this->factory ?? $this->factory();

		$meta_input = isset( $overrides['meta_input'] ) && \is_array( $overrides['meta_input'] )
			? $overrides['meta_input']
			: [];

		unset( $overrides['meta_input'] );

		/** @var \Tribe__Tickets__RSVP $rsvp */
		$rsvp             = tribe( 'tickets.rsvp' );
		$capacity         = Utils_Array::get( $meta_input, '_capacity', 100 );
		$sales            = Utils_Array::get( $meta_input, 'total_sales', 0 );
		$calculated_stock = -1 === $capacity ? null : ( $capacity - $sales );
		$manage_stock     = -1 === $capacity ? 'no' : 'yes';

		// Unlike tickets, we don't store '-1' for unlimited RSVP.
		if ( -1 === $capacity || '' === $capacity ) {
			$capacity = '';
		}

		unset( $meta_input['_capacity'], $meta_input['_stock'] );

		$merged_meta_input = array_merge(
			[
				'_tribe_rsvp_for_event'                          => $post_id,
				tribe( 'tickets.handler' )->key_capacity         => $capacity,
				'_manage_stock'                                  => 'yes',
				'_ticket_start_date'                             => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
				'_ticket_end_date'                               => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
			],
			$meta_input
		);

		// We don't set stock for unlimited rsvps
		if ( tribe_is_truthy( $manage_stock ) ) {
			$merged_meta_input[ '_stock' ] = $calculated_stock;
		}

		// if we have sales, set them
		if ( ! empty( $calculated_stock ) ) {
			$merged_meta_input['total_sales' ] = $calculated_stock;
		}

		// if the ticket start and/or end date(s) are set to empty values they should
		// not be set
		foreach ( [ '_ticket_start_date', '_ticket_end_date' ] as $key ) {
			if ( empty( $merged_meta_input[ $key ] ) ) {
				unset( $merged_meta_input[ $key ] );
			}
		}

		$ticket_id = $factory->post->create( array_merge(
				[
					'post_title'   => "Test RSVP ticket for " . $post_id,
					'post_content' => "Test RSVP ticket description for " . $post_id,
					'post_excerpt' => "Ticket RSVP ticket excerpt for " . $post_id,
					'post_type'    => $rsvp->ticket_object,
					'meta_input'   => $merged_meta_input,
				], $overrides )
		);

		bdump(
			'set capacity',
			get_post_meta( $ticket_id, tribe( 'tickets.handler' )->key_capacity, true )
		);

		return $ticket_id;
	}

	protected function create_many_rsvp_tickets( int $count, int $post_id, array $overrides = [] ) {
		return array_map( function () use ( $post_id, $overrides ) {
			return $this->create_rsvp_ticket( $post_id, $overrides );
		}, range( 1, $count ) );
	}
}
