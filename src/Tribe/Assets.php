<?php
class Tribe__Tickets__Assets {
	/**
	 * Enqueue scripts for front end
	 *
	 * @since 4.6
	 */
	public function enqueue_scripts() {
		$tickets_main = Tribe__Tickets__Main::instance();

		tribe_assets(
			$tickets_main,
			[
				[ 'event-tickets-tickets-css', 'tickets.css', [ 'dashicons' ] ],
				[ 'event-tickets-tickets-rsvp-css', 'rsvp.css', [] ],
				[ 'event-tickets-tickets-rsvp-js', 'rsvp.js', [ 'jquery', 'jquery-ui-datepicker' ] ],
				[ 'event-tickets-attendees-list-js', 'attendees-list.js', [ 'jquery' ] ],
				[ 'event-tickets-details-js', 'ticket-details.js', [] ],
			],
			'wp_enqueue_scripts'
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
			[],
			null,
			[]
		);

	}

	/**
	 * Enqueue scripts for admin views
	 *
	 * @since 4.6
	 */
	public function admin_enqueue_scripts() {
		// Set up some data for our localize scripts

		$upload_header_data = [
			'title'  => esc_html__( 'Ticket header image', 'event-tickets' ),
			'button' => esc_html__( 'Set as ticket header', 'event-tickets' ),
		];

		$nonces = [
			'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
		];

		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		/**
		 * Filter the decimal point character used in the price
		 * @param string $decimal the decimal character to filter
		 *
		 * @since 4.6
		 *
		 * @param string $decimal
		 */
		$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

		$global_stock_mode = tribe( 'tickets.handler' )->get_default_capacity_mode();

		tribe_assets(
			Tribe__Tickets__Main::instance(),
			[
				[ 'event-tickets-admin-css', 'tickets-admin.css', [ 'tribe-validation-style', 'tribe-jquery-timepicker-css', 'tribe-common-admin' ] ],
				[ 'event-tickets-admin-tables-css', 'tickets-tables.css', [ 'event-tickets-admin-css' ] ],
				[ 'event-tickets-attendees-list-js', 'attendees-list.js', [ 'jquery' ] ],
				[ 'event-tickets-admin-accordion-js', 'accordion.js', [] ],
				[ 'event-tickets-admin-accordion-css', 'accordion.css', [] ],
				[
					'event-tickets-admin-js',
					'tickets.js',
					[
						'jquery-ui-datepicker',
						'tribe-bumpdown',
						'tribe-attrchange',
						'tribe-moment',
						'underscore',
						'tribe-validation',
						'event-tickets-admin-accordion-js',
						'tribe-timepicker'
					]
				],
			],
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
		return ! empty( $post ) && ! empty( $modules ) && in_array( $post->post_type, tribe( 'tickets.main' )->post_types() );
	}

	/**
	 * Whether we are currently editing or creating a ticket-able post.
	 *
	 * @since 4.7
	 *
	 * @return bool
	 */
	protected function is_editing_ticketable_post() {
		$context    = tribe( 'context' );
		$post_types = tribe( 'tickets.main' )->post_types();

		return $context->is_editing_post( $post_types );
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
