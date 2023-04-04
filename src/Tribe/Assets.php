<?php

use Tribe__Utils__Array as Arr;
use Tribe\Tickets\Admin\Settings;

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

		$tickets_deps = [
			'dashicons',
			'event-tickets-reset-css',
		];

		if ( $this->should_enqueue_common_full() ) {
			$tickets_deps[] = 'tribe-common-full-style';
		} else {
			$tickets_deps[] = 'tec-variables-full';
		}

		// Check wether we use v1 or v2. We need to update this when we deprecate tickets v1.
		$tickets_stylesheet = tribe_tickets_new_views_is_enabled() ? 'tickets.css' : 'tickets-v1.css';

		tribe_assets(
			$tickets_main,
			[
				[ 'event-tickets-reset-css', 'reset.css' ],
				[ 'event-tickets-tickets-css', $tickets_stylesheet, $tickets_deps ],
				[ 'event-tickets-tickets-rsvp-css', 'rsvp-v1.css', [ 'tec-variables-full' ] ],
				[ 'event-tickets-tickets-rsvp-js', 'rsvp.js', [ 'jquery' ] ],
				[ 'event-tickets-attendees-list-js', 'attendees-list.js', [ 'jquery' ] ],
				[ 'event-tickets-details-js', 'ticket-details.js', [] ],
			],
			'wp_enqueue_scripts',
			[
				'conditionals' => [ $this, 'should_enqueue_frontend' ],
			]
		);

		tribe_asset(
			$tickets_main,
			'tribe-tickets-forms-style',
			'tickets-forms.css',
			[ 'tec-variables-full' ],
			null,
			[
				'groups' => [
					'tribe-tickets-block-assets',
					'tribe-tickets-rsvp',
					'tribe-tickets-registration-page',
					'tribe-tickets-admin',
					'tribe-tickets-forms',
				],
			]
		);

		// Tickets loader library JS.
		tribe_asset(
			$tickets_main,
			'tribe-tickets-loader',
			'v2/tickets-loader.js',
			[
				'jquery',
				'tribe-common',
			],
			null,
			[
				'conditionals' => [ $this, 'should_enqueue_tickets_loader' ],
				'groups'       => [
					'tribe-tickets-block-assets',
					'tribe-tickets-rsvp',
					'tribe-tickets-registration-page',
				],
			]
		);

		// @todo: Remove this once we solve the common breakpoints vs container based.
		tribe_asset(
			$tickets_main,
			'tribe-common-responsive',
			'common-responsive.css',
			[ 'tribe-common-skeleton-style', 'tec-variables-full' ],
			null,
			[
				'conditionals' => [ $this, 'should_enqueue_tickets_loader' ],
				'groups'       => [
					'tribe-tickets-block-assets',
					'tribe-tickets-rsvp',
					'tribe-tickets-registration-page',
					'tribe-tickets-commerce',
					'tribe-tickets-commerce-checkout',
				],
			]
		);

		tribe_asset(
			$tickets_main,
			'tribe-tickets-orders-style',
			'my-tickets.css',
			[ 'tec-variables-full' ],
			null,
			[
				'groups' => [
					'tribe-tickets-page-assets',
				],
			]
		);

		if ( tribe_tickets_new_views_is_enabled() ) {
			// Tribe tickets utils.
			tribe_asset(
				$tickets_main,
				'tribe-tickets-utils',
				'v2/tickets-utils.js',
				[
					'jquery',
					'tribe-common',
				],
				null,
				[
					'groups' => [
						'tribe-tickets-block-assets',
						'tribe-tickets-rsvp',
						'tribe-tickets-registration-page',
					],
					'localize' => [
						[
							'name' => 'TribeCurrency',
							'data' => [ 'Tribe__Tickets__Tickets', 'get_asset_localize_data_for_currencies' ],
						],
					],
				]
			);

			// Tribe tickets page.
			tribe_asset(
				$tickets_main,
				'tribe-tickets-page',
				'v2/tickets-page.js',
				[
					'jquery',
					'tribe-common',
				],
				null,
				[
					'groups' => [
						'tribe-tickets-page-assets',
					],
				]
			);

		} else {

			// Tickets registration page scripts.
			tribe_asset(
				$tickets_main,
				'tribe-tickets-registration-page-scripts',
				'tickets-registration-page.js',
				[
					'jquery',
					'wp-util',
					'tribe-common',
				],
				null,
				[
					'groups' => [
						'tribe-tickets-registration-page',
					],
				]
			);

			// Tickets registration page styles.
			tribe_asset(
				$tickets_main,
				'tribe-tickets-registration-page-styles',
				'tickets-registration-page.css',
				[ 'tec-variables-full' ],
				null,
				[
					'groups' => [
						'tribe-tickets-registration-page',
					],
				]
			);
		}
	}

	/**
	 * Enqueue scripts for admin views.
	 *
	 * @since 4.6
	 * @since 4.10.9 Use customizable ticket name functions.
	 * @since 5.1.2 Add Ticket Settings assets.
	 */
	public function admin_enqueue_scripts() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		// Set up some data for our localize scripts.
		$upload_header_data = [
			'title'  => esc_html( sprintf( __( '%s header image', 'event-tickets' ), tribe_get_ticket_label_singular( 'header_image_title' ) ) ),
			'button' => esc_html( sprintf( __( 'Set as %s header', 'event-tickets' ), tribe_get_ticket_label_singular_lowercase( 'header_button' ) ) ),
		];

		$nonces = [
			'add_ticket_nonce'       => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'      => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce'    => wp_create_nonce( 'remove_ticket_nonce' ),
			'duplicate_ticket_nonce' => wp_create_nonce( 'duplicate_ticket_nonce' ),
			'ajaxurl'                => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
		];

		$ticket_js_deps = [ 'jquery-ui-datepicker', 'tribe-bumpdown', 'tribe-attrchange', 'tribe-moment', 'underscore', 'tribe-validation', 'event-tickets-admin-accordion-js', 'tribe-timepicker' ];

		// While TEC is active, make sure we are loading TEC admin JS as dependency.
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$ticket_js_deps[] = 'tribe-events-admin';
		}

		$assets = [
			[ 'event-tickets-admin-css', 'tickets-admin.css', [ 'tribe-validation-style', 'tribe-jquery-timepicker-css', 'tribe-common-admin' ] ],
			[ 'event-tickets-admin-refresh-css', 'tickets-refresh.css', [ 'event-tickets-admin-css', 'tribe-common-admin' ] ],
			[ 'event-tickets-admin-tables-css', 'tickets-tables.css', [ 'tec-variables-full', 'event-tickets-admin-css' ] ],
			[ 'event-tickets-attendees-list-js', 'attendees-list.js', [ 'jquery' ] ],
			[ 'event-tickets-admin-accordion-js', 'accordion.js', [] ],
			[ 'event-tickets-admin-accordion-css', 'accordion.css', [] ],
			[ 'event-tickets-admin-js', 'tickets.js', $ticket_js_deps ],
		];

		tribe_assets(
			$tickets_main,
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
						'data' => static function() {
							/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
							$tickets_handler = tribe( 'tickets.handler' );
							$global_stock_mode = $tickets_handler->get_default_capacity_mode();

							return [ 'stock_mode' => $global_stock_mode ];
						},
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
						'data' => static function() {
							$locale  = localeconv();
							$decimal = Arr::get( $locale, 'decimal_point', '.' );

							/**
							 * Filter the decimal point character used in the price.
							 *
							 * @since 4.6
							 *
							 * @param string $decimal The decimal character to filter.
							 */
							$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

							return [
								'decimal'       => $decimal,
								'decimal_error' => __( 'Please enter in without thousand separators and currency symbols.', 'event-tickets' ),
							];
						},
					],
				],
			]
		);

		$admin_manager_js_data = [
			'tribeTicketsAdminManagerNonce' => wp_create_nonce( 'tribe_tickets_admin_manager_nonce' ),
			'ajaxurl'                       => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
		];

		tribe_asset(
			$tickets_main,
			'tribe-tickets-admin-manager',
			'admin/tickets-manager.js',
			[
				'jquery',
				'tribe-common',
			],
			null,
			[
				'localize' => [
					[
						'name' => 'TribeTicketsAdminManager',
						'data' => $admin_manager_js_data,
					],
				],
				'groups'   => [
					'tribe-tickets-admin',
				],
			]
		);

		// Register Ticket Admin Settings page assets.
		$settings_assets = [
			[
				'event-tickets-admin-settings-css',
				'tickets-admin-settings.css',
				[
					'tribe-common-admin',
					'tribe-common-full-style',
					'tribe-common-responsive',
					'tribe-dialog',
				],
			],
		];

		tribe_assets(
			$tickets_main,
			$settings_assets,
			'admin_enqueue_scripts',
			[
				'groups'       => 'event-tickets-admin-settings',
				'conditionals' => [ $this, 'should_enqueue_admin_settings_assets' ],
			]
		);

		tribe_asset(
			$tickets_main,
			'tribe-tickets-admin-attendees',
			'tickets-admin-attendees.css',
			[ 'tec-variables-full', 'wp-components' ],
			null,
			[
				'groups' => [
					'event-tickets-admin-attendees',
				],
			]
		);

		tribe_asset(
			$tickets_main,
			'tickets-report-css',
			'tickets-report.css',
			[],
			null,
			[
				'groups' => [
					'event-tickets-admin-attendees',
				],
			]
		);

		tribe_asset(
			$tickets_main,
			'tickets-report-print-css',
			'tickets-report-print.css',
			[],
			null,
			[
				'media'  => 'print',
				'groups' => [
					'event-tickets-admin-attendees',
				],
			]
		);

		$move_url_args = [
			'dialog'    => \Tribe__Tickets__Main::instance()->move_tickets()->dialog_name(),
			'check'     => wp_create_nonce( 'move_tickets' ),
			'TB_iframe' => 'true',
		];

		$config_data = [
			'nonce'             => wp_create_nonce( 'email-attendee-list' ),
			'required'          => esc_html__( 'You need to select a user or type a valid email address', 'event-tickets' ),
			'sending'           => esc_html__( 'Sending...', 'event-tickets' ),
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			'checkin_nonce'     => wp_create_nonce( 'checkin' ),
			'uncheckin_nonce'   => wp_create_nonce( 'uncheckin' ),
			'cannot_move'       => esc_html__( 'You must first select one or more tickets before you can move them!', 'event-tickets' ),
			'move_url'          => add_query_arg( $move_url_args ),
			'confirmation'      => esc_html__( 'Please confirm that you would like to delete this attendee.', 'event-tickets' ),
			'bulk_confirmation' => esc_html__( 'Please confirm you would like to delete these attendees.', 'event-tickets' ),
		];

		/**
		 * Allow filtering the configuration data for the Attendee objects on Attendees report page.
		 *
		 * @since 5.2.0
		 *
		 * @param array $config_data List of configuration data to be localized.
		 */
		$config_data = apply_filters( 'tribe_tickets_attendees_report_js_config', $config_data );

		tribe_asset(
			$tickets_main,
			'tickets-attendees-js',
			'tickets-attendees.js',
			[ 'jquery' ],
			null,
			[
				'localize' => [
					[
						'name' => 'Attendees',
						'data' => $config_data,
					],
				],
				'groups' => [
					'event-tickets-admin-attendees',
				],
			]
		);

		// WP Admin and admin bar.
		tribe_asset(
			$tickets_main,
			'tec-tickets-admin-wp',
			'tickets-admin-wp.css',
			[ 'dashicons' ],
			[
				'admin_enqueue_scripts',
				'wp_enqueue_scripts',
			],
			[
				'conditionals' => [ $this, 'should_enqueue_admin_wp' ],
			]
		);
	}

	/**
	 * Check if we should add the Admin Assets into a Page
	 *
	 * @since  4.6
	 *
	 * @since 5.2.1 Always enqueue scripts for Ticket settings page.
	 *
	 * @return bool
	 */
	public function should_enqueue_admin() {
		global $post;

		// Should enqueue if Ticket settings page.
		if ( tribe( Settings::class )->is_tec_tickets_settings() ) {
			return true;
		}

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

		// For the metabox.
		return ! empty( $post ) && ! empty( $modules ) && in_array( $post->post_type, tribe( 'tickets.main' )->post_types(), true );
	}

	/**
	 * Check if we should add the Admin Settings Assets onto an admin page.
	 *
	 * @since 5.1.2
	 *
	 * @return bool
	 */
	public function should_enqueue_admin_settings_assets() {
		$admin_helpers = Tribe__Admin__Helpers::instance();
		$admin_pages   = tribe( 'admin.pages' );
		$admin_page    = $admin_pages->get_current_page();

		// Load specifically on Ticket Settings page only.
		$should_enqueue = $admin_helpers->is_screen() && Settings::$settings_page_id === $admin_page;

		/**
		 * Allow filtering of whether the base Admin Settings Assets should be loaded.
		 *
		 * @since 5.1.2
		 *
		 * @param bool $should_enqueue Should enqueue the settings asset or not.
		 */
		return apply_filters( 'event_tickets_should_enqueue_admin_settings_assets', $should_enqueue );
	}

	/**
	 * Check if we should enqueue ET frontend styles
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public function should_enqueue_frontend() {
		$is_on_valid_post_type = tribe_tickets_is_enabled_post_context();

		/**
		 * This Try/Catch is present to deal with a problem on Autoloading from version 5.1.0 ET+ with ET 5.0.3.
		 *
		 * @todo Needs to be revised once proper autoloading rules are done for Common, ET and ET+.
		 */
		try {
			/** @var \Tribe__Tickets__Attendee_Registration__Main $ar_reg */
			$ar_reg = tribe( 'tickets.attendee_registration' );

			$is_on_ar_page = $ar_reg->is_on_page();
		} catch ( \Exception $exception ) {
			$is_on_ar_page = false;
		}

		return $is_on_valid_post_type || $is_on_ar_page;
	}

	/**
	 * Check if we should enqueue the new Tickets Loader script.
	 *
	 * @since 5.1.1
	 *
	 * @return bool
	 */
	public function should_enqueue_tickets_loader() {
		$are_new_views_enabled = tribe_tickets_new_views_is_enabled()
			|| tribe_tickets_rsvp_new_views_is_enabled();

		/**
		 * Allow filtering whether the Tickets Loader script should be enqueued.
		 *
		 * @since 5.1.1
		 *
		 * @param bool $should_enqueue_tickets_loader Whether the Tickets Loader script should be enqueued.
		 */
		return (bool) apply_filters( 'tribe_tickets_assets_should_enqueue_tickets_loader', $are_new_views_enabled );
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

	/**
	 * Check if we should load the common full style assets.
	 * When TEC is not in place, or vies V2 are not enabled, so we have the common
	 * styles we need for our tickets blocks, AR, etc.
	 * If V2 are active, we respect the style option.
	 *
	 * @since  4.11.4
	 *
	 * @return bool
	 */
	public function should_enqueue_common_full() {
		// If TEC isn't there, we need to load common full styles.
		if ( ! class_exists( 'Tribe__Events__Main' ) ) {
			return true;
		}

		// If TEC isn't active or they have a previous version.
		if ( ! function_exists( 'tribe_events_views_v2_is_enabled' ) ) {
			return true;
		}

		// If the views V2 are not enabled, we need to load common full styles.
		if ( ! tribe_events_views_v2_is_enabled() ) {
			return true;
		}

		// If views V2 are in place, we respect the skeleton setting.
		return ! tribe( Tribe\Events\Views\V2\Assets::class )->is_skeleton_style();
	}

	/**
	 * Check if we should enqueue the Event Tickets WP admin assets.
	 *
	 * @since 5.5.10
	 *
	 * @return bool True if we should enqueue the assets, false otherwise.
	 */
	public function should_enqueue_admin_wp(): bool {
		return is_admin() || is_admin_bar_showing();
	}

}
