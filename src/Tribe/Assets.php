<?php
class Tribe__Tickets__Assets {
	/**
	 * Enqueue scripts for front end
	 *
	 * @since 4.6
	 * @since 4.11.1 Only load if in a tickets-enabled post context.
	 *
	 * @see   \tribe_tickets_is_enabled_post_context()
	 */
	public function enqueue_scripts() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		tribe_assets(
			$tickets_main,
			[
				[ 'event-tickets-reset-css', 'reset.css' ],
				[ 'event-tickets-tickets-css', 'tickets.css', [ 'dashicons', 'event-tickets-reset-css' ] ],
				[ 'event-tickets-tickets-rsvp-css', 'rsvp.css', [] ],
				[ 'event-tickets-tickets-rsvp-js', 'rsvp.js', [ 'jquery', 'jquery-ui-datepicker' ] ],
				[ 'event-tickets-attendees-list-js', 'attendees-list.js', [ 'jquery' ] ],
				[ 'event-tickets-details-js', 'ticket-details.js', [] ],
			],
			'wp_enqueue_scripts',
			[
				'conditionals' => 'tribe_tickets_is_enabled_post_context',
			]
		);

		// Tickets registration page styles
		tribe_asset(
			$tickets_main,
			'event-tickets-registration-page-styles',
			'tickets-registration-page.css',
			[],
			null,
			[]
		);

		// Tickets registration page scripts
		tribe_asset(
			$tickets_main,
			'event-tickets-registration-page-scripts',
			'tickets-registration-page.js',
			[ 'jquery', 'wp-util' ],
			null,
			[]
		);

	}

	/**
	 * Enqueue scripts for admin views.
	 *
	 * @since 4.6
	 * @since 4.10.9 Use customizable ticket name functions.
	 */
	public function admin_enqueue_scripts() {
		// Set up some data for our localize scripts

		$upload_header_data = [
			'title'  => esc_html( sprintf( __( '%s header image', 'event-tickets' ), tribe_get_ticket_label_singular( 'header_image_title' ) ) ),
			'button' => esc_html( sprintf( __( 'Set as %s header', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'header_button' ) ) ),
		];

		$nonces = [
			'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			'ajaxurl'             => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
		];

		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		/**
		 * Filter the decimal point character used in the price.
		 *
		 * @since 4.6
		 *
		 * @param string $decimal The decimal character to filter.
		 */
		$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );
		$global_stock_mode = $tickets_handler->get_default_capacity_mode();
		$assets = [
				[ 'event-tickets-admin-css', 'tickets-admin.css', [ 'tribe-validation-style', 'tribe-jquery-timepicker-css', 'tribe-common-admin' ] ],
				[ 'event-tickets-admin-refresh-css', 'tickets-refresh.css', [ 'event-tickets-admin-css', 'tribe-common-admin' ] ],
				[ 'event-tickets-admin-tables-css', 'tickets-tables.css', [ 'event-tickets-admin-css' ] ],
				[ 'event-tickets-attendees-list-js', 'attendees-list.js', [ 'jquery' ] ],
				[ 'event-tickets-admin-accordion-js', 'accordion.js', [] ],
				[ 'event-tickets-admin-accordion-css', 'accordion.css', [] ],
				[ 'event-tickets-admin-js', 'tickets.js', [ 'jquery-ui-datepicker', 'tribe-bumpdown', 'tribe-attrchange', 'tribe-moment', 'underscore', 'tribe-validation', 'event-tickets-admin-accordion-js', 'tribe-timepicker' ] ],
		];

		tribe_assets(
			Tribe__Tickets__Main::instance(),
			$assets,
			'admin_enqueue_scripts',
			[
				'groups'       => 'event-tickets-admin',
				'conditionals' => [ $this, 'should_enqueue_admin' ],
				'localize'     => [
					[
						'name' => 'HeaderImageData',
						'data' => $upload_header_data,
					],
					[
						'name' => 'TribeTickets',
						'data' => $nonces,
					],
					[
						'name' => 'tribe_ticket_vars',
						'data' => [ 'stock_mode' => $global_stock_mode ],
					],
					[
						'name' => 'tribe_ticket_notices',
						'data' => [
							'confirm_alert' => __( 'Are you sure you want to delete this ticket? This cannot be undone.', 'event-tickets' ),
						],
					],
					[
						'name' => 'tribe_global_stock_admin_ui',
						'data' => [
							'nav_away_msg' => __( 'It looks like you have modified your shared capacity setting but have not saved or updated the post.', 'event-tickets' ),
						],
					],
					[
						'name' => 'price_format',
						'data' => [
							'decimal' => $decimal,
							'decimal_error' => __( 'Please enter in without thousand separators and currency symbols.', 'event-tickets' ),
						],
					],
				],
			]
		);
	}

	/**
	 * Check if we should add the Admin Assets into a Page
	 *
	 * @since  4.6
	 *
	 * @return bool
	 */
	public function should_enqueue_admin() {
		global $post;

		/**
		 * Filter the array of module names.
		 *
		 * @since 4.6
		 *
		 * @param array the array of modules
		 *
		 * @see event-tickets/src/Tribe/Tickets.php->modules()
		 */
		$modules = Tribe__Tickets__Tickets::modules();

		// For the metabox
		return ! empty( $post ) && ! empty( $modules ) && in_array( $post->post_type, tribe( 'tickets.main' )->post_types(), true );
	}

	/**
	 * Whether we are currently editing or creating a ticket-able post.
	 *
	 * @since 4.7
	 *
	 * @return bool
	 */
	protected function is_editing_ticketable_post() {
		/** @var Tribe__Context $context */
		$context = tribe( 'context' );

		/** @var Tribe__Tickets__Main $main */
		$main = tribe( 'tickets.main' );

		return $context->is_editing_post( $main->post_types() );
	}

	/**
	 * Enqueues scripts and styles that might be needed in the post editor area.
	 *
	 * @since 4.7
	 */
	public function enqueue_editor_scripts() {
		if ( $this->is_editing_ticketable_post() ) {
			tribe_asset_enqueue( 'tribe-validation' );
		}
	}

	/**
	 * Add data strings to tribe_l10n_datatables object.
	 *
	 * @param array $data Object data.
	 *
	 * @return array
	 *
	 * @since 4.9.4
	 */
	public function add_data_strings( $data ) {
		$data['registration_prompt'] = __( 'There is unsaved attendee information. Are you sure you want to continue?', 'event-tickets' );

		return $data;
	}
}