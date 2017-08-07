<?php
class Tribe__Tickets__Assets {
	/**
	 * Enqueue scripts for front end
	 *
	 * @since TBD
	 */
	public function enqueue_scripts() {
		tribe_assets(
			Tribe__Tickets__Main::instance(),
			array(
				array( 'event-tickets-tickets-css', 'tickets.css', array( 'dashicons' ) ),
				array( 'event-tickets-tickets-rsvp-css', 'rsvp.css', array() ),
				array( 'event-tickets-tickets-rsvp-js', 'rsvp.js', array( 'jquery', 'jquery-ui-datepicker' ) ),
				array( 'event-tickets-attendees-list-js', 'attendees-list.js', array( 'jquery', 'bumpdown' ) ),
			),
			'enqueue_scripts'
		);
	}

	/**
	 * Enqueue scripts for admin views
	 *
	 * @since TBD
	 */
	public function admin_enqueue_scripts() {
		global $post;

		/**
		 * Filter the array of module names.
		 *
		 * @since TBD
		 *
		 * @param array the array of modules
		 *
		 * @see event-tickets/src/Tribe/Tickets.php->modules()
		 */
		$modules = apply_filters( 'tribe_events_tickets_modules', null );

		// For the metabox
		if (
			! empty( $post )
			&& ! empty( $modules )
			&& in_array( $post->post_type, Tribe__Tickets__Main::instance()->post_types() )
		 ) {
			// Set up some data for our localize scripts

			$upload_header_data = array(
				'title'  => esc_html__( 'Ticket header image', 'event-tickets' ),
				'button' => esc_html__( 'Set as ticket header', 'event-tickets' ),
			);

			$nonces = array(
				'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
				'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
				'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			);

			$locale  = localeconv();
			$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

			/**
			 * Filter the decimal point character used in the price
			 * @param string $decimal the decimal character to filter
			 *
			 * @since TBD
			 *
			 * @param string $decimal
			 */
			$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

			tribe_assets(
				Tribe__Tickets__Main::instance(),
				array(
					array( 'event-tickets-admin-css', 'tickets.css' ),
					array( 'event-tickets-admin-refresh-css', 'tickets-refresh.css', array( 'event-tickets-admin-css' ) ),
					array( 'event-tickets-admin-tables-css', 'tickets-tables.css', array( 'event-tickets-admin-css' ) ),
					array( 'event-tickets-admin-js', 'tickets.js', array( 'jquery-ui-datepicker' ) ),
					array( 'event-tickets-admin-tables-js', 'tickets-tables.js', array( 'underscore' ) ),
					array( 'event-tickets-admin-accordion-js', 'accordion.js', array() ),
				),
				'admin_enqueue_scripts',
				array(
					'localize' => array(
						array(
							'name' => 'HeaderImageData',
							'data' => $upload_header_data,
						),
						array(
							'name' => 'TribeTickets',
							'data' => $nonces,
						),
						array(
							'name' => 'tribe_ticket_notices',
							'data' => array(
								'confirm_alert' => __( 'Are you sure you want to delete this ticket? This cannot be undone.', 'event-tickets' ),
							),
						),
						array(
							'name' => 'tribe_global_stock_admin_ui',
							'data' => array(
								'nav_away_msg' => __( 'It looks like you have modified your global stock settings but have not saved or updated the post.', 'event-tickets' ),
							),
						),
						array(
							'name' => 'price_format',
							'data' => array(
								'decimal' => $decimal,
								'decimal_error' => __( 'Please enter in without thousand separators and currency symbols.', 'event-tickets' ),
							),
						),
					),
				)
			);
		}
	}
}
