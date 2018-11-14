<?php

/**
 * Initialize Gutenberg Event Meta fields
 *
 * @since TBD
 */
class Tribe__Tickets__Editor__Meta extends Tribe__Editor__Meta {
	/**
	 * Register the required Meta fields for good Gutenberg saving
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register() {
		
		// That comes from Woo, that is why it's static string
		register_meta(
			'post',
			'_price',
			$this->text()
		);
		
		register_meta(
			'post',
			'_stock',
			$this->text()
		);
		
		// Tickets Hander Keys
		$handler = tribe( 'tickets.handler' );
		
		register_meta(
			'post',
			$handler->key_image_header,
			$this->text()
		);
		
		register_meta(
			'post',
			$handler->key_provider_field,
			$this->text()
		);
		
		register_meta(
			'post',
			$handler->key_capacity,
			$this->text()
		);
		
		register_meta(
			'post',
			$handler->key_start_date,
			$this->text()
		);
		
		register_meta(
			'post',
			$handler->key_end_date,
			$this->text()
		);
		
		register_meta(
			'post',
			$handler->key_show_description,
			$this->text()
		);
		
		/**
		 * @todo  move this into the `tickets.handler` class
		 */
		register_meta(
			'post',
			'_tribe_ticket_show_not_going',
			$this->boolean()
		);
		
		// Global Stock
		register_meta(
			'post',
			Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED,
			$this->text()
		);
		
		register_meta(
			'post',
			Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL,
			$this->text()
		);
		
		register_meta(
			'post',
			Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE,
			$this->text()
		);
		
		register_meta(
			'post',
			Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP,
			$this->text()
		);
		
		// Fetch RSVP keys
		$rsvp = tribe( 'tickets.rsvp' );
		
		register_meta(
			'post',
			$rsvp->event_key,
			$this->text()
		);
		
		// "Ghost" Meta fields
		register_meta(
			'post',
			'_tribe_ticket_going_count',
			$this->text()
		);
		
		register_meta(
			'post',
			'_tribe_ticket_not_going_count',
			$this->text()
		);
	}
	
	/**
	 * Removes `_edd_button_behavior` key from the REST API where tickets blocks is used
	 *
	 * @since TBD
	 *
	 * @param array  $args
	 * @param string $defaults
	 * @param string $object_type
	 * @param string $meta_key
	 *
	 * @return array
	 */
	public function register_meta_args( $args = array(), $defaults = '', $object_type = '', $meta_key = '' ) {
		if ( $meta_key === '_edd_button_behavior' ) {
			$args['show_in_rest'] = false;
		}
		
		return $args;
	}
}
