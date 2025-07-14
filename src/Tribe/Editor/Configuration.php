<?php

/**
 * Class Tribe__Tickets__Editor__Configuration
 *
 * Class used to set values into the editor client (browser) via localized variables
 *
 * @since 4.9
 */
class Tribe__Tickets__Editor__Configuration implements Tribe__Editor__Configuration_Interface {

	/**
	 * Add actions / filters into WP
	 *
	 * @since 4.9
	 */
	public function hook() {
		add_filter( 'tribe_editor_config', [ $this, 'editor_config' ] );
	}

	/**
	 * Hook into "tribe_editor_config" to attach new variables for tickets.
	 *
	 * @since 4.9
	 *
	 * @param array $editor_config The editor configuration.
	 *
	 * @return array
	 */
	public function editor_config( $editor_config ) {
		$tickets = empty( $editor_config['tickets'] ) ? [] : $editor_config['tickets'];

		$editor_config = $this->set_defaults( $editor_config );

		$editor_config['common']['rest']['nonce'] = array_merge(
			$editor_config['common']['rest']['nonce'],
			[
				'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
				'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
				'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
				'move_tickets'        => wp_create_nonce( 'move_tickets' ),
			]
		);

		$editor_config['tickets'] = array_merge(
			(array) $tickets,
			$this->localize()
		);

		/**
		 * Filter the default buffer duration between ticket sale start time and end time.
		 *
		 * @since 5.0.4
		 *
		 * @param int $buffer Number in hours to be used.
		 */
		$editor_config['tickets']['end_sale_buffer_duration'] = apply_filters( 'tribe_tickets_editor_end_sale_buffer_duration_hours', 2 );

		/**
		 * Filter the default buffer years between ticket sale start date and end date.
		 *
		 * @since 5.0.4
		 *
		 * @param int $buffer Number in years to be used.
		 */
		$editor_config['tickets']['end_sale_buffer_years'] = apply_filters( 'tribe_tickets_editor_end_sale_buffer_years', 1 );

		return $editor_config;
	}

	/**
	 * Set an initial set of default values to prevent accessing not defined variables
	 *
	 * @since 4.9
	 *
	 * @param array $editor_config
	 *
	 * @return array
	 */
	public function set_defaults( $editor_config ) {
		if ( empty( $editor_config['common']['rest'] ) ) {
			$editor_config['common']['rest'] = [];
		}

		if ( empty( $editor_config['common']['rest']['nonce'] ) ) {
			$editor_config['common']['rest']['nonce'] = [];
		}

		return $editor_config;
	}

	/**
	 * Variables attached into the group that is used to localize values into the client
	 *
	 * @since 4.9
	 *
	 * @return array<string,mixed> The data to be localized for the Tickets Block Editor components.
	 */
	public function localize() {
		$localized =  [
			'providers'                 => $this->get_providers(),
			'default_provider'          => Tribe__Tickets__Tickets::get_default_module(),
			'default_currency'          => tribe_get_option( 'defaultCurrencySymbol', '$' ),
			'multiple_providers_notice' => _x(
					'It looks like you have multiple ecommerce plugins active. ' .
					'We recommend running only one at a time. However, if you need to run multiple, please select which ' .
					'one to use to sell tickets for this event. ',
					'The notice displayed when multiple ecommerce plugins are active in Block Editor context.',
					'event-tickets'
				) . '<em> ' . _x(
					'Note: adjusting this setting will only impact new tickets. Existing tickets will not change. We highly recommend that all tickets for one event use the same ecommerce plugin.',
					'The note displayed when multiple ecommerce plugins are active in Block Editor context.',
					'event-tickets'
				) . '</em>',
			'choice_disabled'           => false,
			'ticketTypes' => [
				'default' => [
					'title' => tribe_get_ticket_label_plural('editor-configuration'),
				]
			],
			'ticketLabels'              => [
				'ticket' => [
					'singular'          => tribe_get_ticket_label_singular( 'editor-configuration' ),
					'plural'            => tribe_get_ticket_label_plural( 'editor-configuration' ),
					'singularLowercase' => tribe_get_ticket_label_singular_lowercase( 'editor-configuration' ),
					'pluralLowercase'   => tribe_get_ticket_label_plural_lowercase( 'editor-configuration' ),
				],
			],
			'salePrice'                 => [
				'add_sale_price'   => esc_html_x( 'Add Sale Price', 'Label for adding a sale price in the Ticket Block', 'event-tickets' ),
				'sale_price_label' => esc_html_x( 'Sale Price:', 'The label for the value of the sale price in the Ticket Block', 'event-tickets' ),
				'invalid_price'    => esc_html_x( 'Invalid price', 'Warning when sale price is invalid in the Ticket Block', 'event-tickets' ),
				'on_sale_from'     => esc_html_x( 'On sale from:', 'Label to select the start date of sale in the Ticket Block', 'event-tickets' ),
				'to'               => esc_html_x( 'to', 'Label to select the end date of sale in the Ticket Block', 'event-tickets' ),
				'on_sale'          => esc_html_x( 'On Sale', 'Label that is used for tickets that are on sale in the Ticket Block', 'event-tickets' ),
			],
		];

		/**
		 * Filters the localized data for the editor configuration.
		 *
		 * @since 5.8.0
		 *
		 * @param array<string,mixed> $localized The localized data for the editor configuration.
		 */
		$localized = apply_filters( 'tec_tickets_editor_configuration_localized_data', $localized );

		return $localized;
	}

	/**
	 * Return an array with all the currently-active ticket providers (not RSVP).
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	public function get_providers() {
		$modules                 = Tribe__Tickets__Tickets::modules();
		$providers               = [];
		$default_currency_symbol = tribe_get_option( 'defaultCurrencySymbol', '$' );

		foreach ( $modules as $class_name => $display_name ) {
			if ( Tribe__Tickets__RSVP::class === $class_name ) {
				continue;
			}

			$currency = tribe( 'tickets.commerce.currency' );

			// Backwards to avoid fatals.
			$currency_symbol = $default_currency_symbol;
			if ( is_callable( [ $currency, 'get_provider_symbol' ] ) ) {
				$currency_symbol = $currency->get_provider_symbol( $class_name, null );
			}

			$currency_position = 'prefix';
			if ( is_callable( [ $currency, 'get_provider_symbol_position' ] ) ) {
				$currency_position = $currency->get_provider_symbol_position( $class_name, null );
			}

			$currency_number_of_decimals = 2;
			if ( is_callable( [ $currency, 'get_number_of_decimals' ] ) ) {
				$currency_number_of_decimals = $currency->get_number_of_decimals( $class_name, null );
			}

			$currency_decimal_point = '.';
			if ( is_callable( [ $currency, 'get_currency_decimal_point' ] ) ) {
				$currency_decimal_point = $currency->get_currency_decimal_point( $class_name, null );
			}

			$currency_thousands_sep = ',';
			if ( is_callable( [ $currency, 'get_currency_thousands_sep' ] ) ) {
				$currency_thousands_sep = $currency->get_currency_thousands_sep( $class_name, null );
			}

			$html_safe_class = str_replace( [ '\\' ], [ '_' ], $class_name );

			$providers[] = [
				'class'                       => $class_name,
				'currency'                    => html_entity_decode( $currency_symbol ),
				'currency_decimal_point'      => $currency_decimal_point,
				'currency_number_of_decimals' => $currency_number_of_decimals,
				'currency_position'           => $currency_position,
				'currency_thousands_sep'      => $currency_thousands_sep,
				'html_safe_class'             => sanitize_html_class( $html_safe_class ),
				'name'                        => $modules[ $class_name ],
			];
		}

		return $providers;
	}
}
