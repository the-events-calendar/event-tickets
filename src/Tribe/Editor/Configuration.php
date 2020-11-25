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
		add_filter( 'tribe_editor_config', array( $this, 'editor_config' ) );
	}

	/**
	 * Hook into "tribe_editor_config" to attach new variables for tickets
	 *
	 * @since 4.9
	 *
	 * @param $editor_config
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
			$editor_config['common']['rest'] = array();
		}

		if ( empty( $editor_config['common']['rest']['nonce'] ) ) {
			$editor_config['common']['rest']['nonce'] = array();
		}

		return $editor_config;
	}

	/**
	 * Variables attached into the group that is used to localize values into the client
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	public function localize() {
		return array(
			'providers'        => $this->get_providers(),
			'default_provider' => Tribe__Tickets__Tickets::get_default_module(),
			'default_currency' => tribe_get_option( 'defaultCurrencySymbol', '$' ),
		);
	}

	/**
	 * Return an array with all the providers used by tickets
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	public function get_providers() {
		$modules                 = Tribe__Tickets__Tickets::modules();
		$class_names             = array_keys( $modules );
		$providers               = array();
		$default_currency_symbol = tribe_get_option( 'defaultCurrencySymbol', '$' );

		foreach ( $class_names as $class ) {
			if ( 'RSVP' === $modules[ $class ] ) {
				continue;
			}

			$currency = tribe( 'tickets.commerce.currency' );

			// Backwards to avoid fatals
			$currency_symbol = $default_currency_symbol;
			if ( is_callable( array( $currency, 'get_provider_symbol' ) ) ) {
				$currency_symbol = $currency->get_provider_symbol( $class, null );
			}

			$currency_position = 'prefix';
			if ( is_callable( array( $currency, 'get_provider_symbol_position' ) ) ) {
				$currency_position = $currency->get_provider_symbol_position( $class, null );
			}

			$providers[] = array(
				'name'              => $modules[ $class ],
				'class'             => $class,
				'currency'          => html_entity_decode( $currency_symbol ),
				'currency_position' => $currency_position,
			);
		}

		return $providers;
	}
}