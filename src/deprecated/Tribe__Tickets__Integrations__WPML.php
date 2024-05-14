<?php

class Tribe__Tickets__Integrations__WPML {

	public function hook() {
		add_filter( 'tribe_tickets_get_tickets_query_args', $this->aggregate_translations( '_tribe_rsvp_for_event_in' ) );
		add_filter( 'tribe_tickets_get_tickets_query_args', $this->aggregate_translations( '_tribe_wooticket_for_event_in' ) );
		add_filter( 'tribe_tickets_get_tickets_query_args', $this->aggregate_translations( '_tribe_eddticket_for_event_in' ) );
		add_filter( 'tribe_tickets_get_tickets_query_args', $this->aggregate_translations( '_tec_tickets_commerce_event_in' ) );
	}

	public function aggregate_translations( $key ) {
		return function( $args ) use ( $key ) {
			if ( ! isset( $args['meta_query'][ $key ]['value'] ) ) {
				return $args;
			}

			$event_id = $args['meta_query'][ $key ]['value'];

			if ( is_array( $event_id ) ) {
				return $args;
			}

			$type = apply_filters( 'wpml_element_type', get_post_type( $event_id ) );
			$args['meta_query'][ $key ]['value'] = wp_list_pluck(
				apply_filters(
					'wpml_get_element_translations',
					$event_id,
					apply_filters( 'wpml_element_trid', false, $event_id, $type ),
					$type
				),
				'element_id'
			);

			return $args;
		};
	}

}

