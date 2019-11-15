<?php

/**
 * Initialize Gutenberg Event Meta fields
 *
 * @since 4.9
 */
class Tribe__Tickets__Editor__Meta extends Tribe__Editor__Meta {
	/**
	 * Register the required Meta fields for good Gutenberg saving
	 *
	 * @since 4.9
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

		register_meta(
			'post',
			'_tribe_tickets_list',
			$this->numeric_array()
		);

		register_meta(
			'post',
			'_tribe_ticket_has_attendee_info_fields',
			$this->boolean()
		);
	}

	/**
	 * Removes `_edd_button_behavior` key from the REST API where tickets blocks is used
	 *
	 * @since 4.9
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

	/**
	 * Make sure the value of the "virtual" meta is up to date with the correct ticket values
	 * as can be modified by removing or adding a plugin outside of the blocks editor the ticket
	 * can be added by React if is part of the diff of non created blocks
	 *
	 * @since 4.9
	 *
	 * @param mixed $value
	 * @param int $post_id
	 * @param string $meta_key
	 * @param bool $single
	 *
	 * @return array
	 */
	public function register_tickets_list_in_rest( $value, $post_id, $meta_key, $single ) {

		if ( '_tribe_tickets_list' !== $meta_key  ) {
			return $value;
		}

		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$list_of_tickets = array();
		foreach ( $tickets as $ticket ) {
			if ( ! ( $ticket instanceof Tribe__Tickets__Ticket_Object ) || 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
				continue;
			}
			$list_of_tickets[] = $ticket->ID;
		}

		return $list_of_tickets;
	}

	/**
	 * Don't delete virtual meta.
	 *
	 * @param null|bool $delete            Whether to allow metadata deletion of the given type.
	 * @param int       $unused_object_id  Object ID.
	 * @param string    $meta_key          Meta key.
	 * @param mixed     $unused_meta_value Meta value. Must be serializable if non-scalar.
	 * @param bool      $unused_delete_all Whether to delete the matching metadata entries
	 *                              for all objects, ignoring the specified $object_id.
	 *                              Default false.
	 *
	 * @return bool
	 */
	public function delete_tickets_list_in_rest( $delete, $unused_object_id, $meta_key, $unused_meta_value, $unused_delete_all ) {
		if ( '_tribe_tickets_list' === $meta_key ) {
			return true;
		}

		return $delete;
	}

	/**
	 * Don't update virtual meta.
	 *
	 * @param null|bool $check      Whether to allow updating metadata for the given type.
	 * @param int       $object_id  Object ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
	 * @param mixed     $prev_value Optional. If specified, only update existing
	 *                              metadata entries with the specified value.
	 *                              Otherwise, update all entries.
	 *
	 * @return bool
	 */
	public function update_tickets_list_in_rest( $check, $unused_object_id, $meta_key, $unused_meta_value, $unused_prev_value ) {
		if ( '_tribe_tickets_list' === $meta_key ) {
			return true;
		}

		return $check;
	}
}
