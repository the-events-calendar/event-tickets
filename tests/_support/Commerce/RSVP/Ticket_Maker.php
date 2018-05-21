<?php

namespace Tribe\Tickets\Test\Commerce\RSVP;


trait Ticket_Maker {
	
	/**
	 * Generates a ticket for a post.
	 *
	 * @param       int $post_id
	 * @param       int $capacity The initial ticket capacity and stock, use `meta_input` to fine tune (see method code)
	 * @param array $meta_input An array of values to override the `meta_input` entry (see method code)
	 *
	 * @return mixed
	 */
	protected function make_ticket( $post_id, $capacity = 10, array $overrides = [] ) {
		$factory = ! isset( $this->factory ) ? $this->factory() : $this->factory;

		$meta_input = isset( $overrides['meta_input'] ) && is_array( $overrides['meta_input'] ) ? $overrides['meta_input'] : array();
		unset( $overrides['meta_input'] );

		$hash       = 'rsvp-ticket-' . md5( uniqid( 'rsvp-ticket', true ) );
		$a_week_ago = date( 'Y-m-d H:i:s', strtotime( '-1 week' ) );
		$in_a_week  = date( 'Y-m-d H:i:s', strtotime( '+1 week' ) );

		$ticket_id = $factory->post->create( array_merge(
				[
					'post_type'    => tribe( 'tickets.rsvp' )->ticket_object,
					'post_title'   => "RSVP ticket {$hash}",
					'post_content' => "",
					// typically the content is empty
					'post_excerpt' => "RSVP ticket {$hash} description",
					// this is what we expose in the UI as "description"
					'meta_input'   => array_merge( [
						'_tribe_rsvp_for_event'          => $post_id,
						'_tribe_ticket_show_description' => 'yes',
						'_price'                         => 0, // always 0 for RSVP tickets
						'_stock'                         => $capacity,
						'_tribe_ticket_capacity'         => $capacity,
						'_manage_stock'                  => 'yes',
						'_tribe_ticket_version'          => \Tribe__Tickets__Main::VERSION,
						'_ticket_start_date'             => $a_week_ago,
						'_ticket_end_date'               => $in_a_week,
					], $meta_input ),
				], $overrides )
		);

		return $ticket_id;
	}
}