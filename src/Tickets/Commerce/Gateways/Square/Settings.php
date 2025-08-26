<?php
/**
 * Settings for the Square gateway.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Settings;
use TEC\Tickets\Commerce\Settings as TC_Settings;

/**
 * Square Commerce Settings.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Settings extends Abstract_Settings {

	/**
	 * Client ID option key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const OPTION_CLIENT_ID = 'tickets-commerce-square-client-id';

	/**
	 * Sandbox Client ID option key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const OPTION_SANDBOX_CLIENT_ID = 'tickets-commerce-square-sandbox-client-id';

	/**
	 * Location ID option key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const OPTION_LOCATION_ID = 'tickets-commerce-square-location-id';

	/**
	 * Sandbox Location ID option key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const OPTION_SANDBOX_LOCATION_ID = 'tickets-commerce-square-sandbox-location-id';

	/**
	 * Inventory sync option key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const OPTION_INVENTORY_SYNC = 'tickets-commerce-square-inventory-sync';

	/**
	 * Get all the settings for the Square gateway.
	 *
	 * @since 5.24.0
	 *
	 * @return array The gateway settings.
	 */
	public function get_settings(): array {
		$is_connected = tribe( Merchant::class )->is_connected();

		// Add the fee message similar to Stripe.
		$plus_link_faq = sprintf(
			'<a href="https://evnt.is/1b3u" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$square_message = sprintf(
			// Translators: %1$s: The Event Tickets Plus link - reads "Learn More".
			esc_html__( 'You are using the free Square payment gateway integration. This includes an additional 2%% fee for processing ticket sales. This fee is removed if you have an active subscription to Event Tickets Plus. %1$s.', 'event-tickets' ),
			$plus_link_faq
		);

		$container_class = [
			'tec-settings-form__element--full-width',
			'tec-settings-form__element--no-spacing' => $is_connected,
			'tec-settings-form__element--no-row-gap' => ! $is_connected,
		];

		$main_settings = [
			'tickets-commerce-square-commerce-description' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__element--full-width tec-settings-form__content-section"><div class="tec-tickets__admin-settings-tickets-commerce-description">' . $square_message . '</div></div>',
			],
			'square-connection-start' => [
				'type' => 'html',
				'html' => '<div ' . tec_get_classes_attr( $container_class ) . '>',
			],
			'square-signup'           => [
				'type' => 'html',
				'html' => $this->get_connection_settings_html(),
			],
			'square-connection-end'   => [
				'type' => 'html',
				'html' => '</div>',
			],
		];

		// If gateway isn't connected/active, only show the connection settings.
		$is_connected = tribe( Merchant::class )->is_connected();
		if ( ! $is_connected ) {
			/**
			 * Allow filtering the list of Square settings.
			 *
			 * @since 5.24.0
			 *
			 * @param array $settings     The list of Square Commerce settings.
			 * @param bool  $is_connected Whether or not gateway is connected.
			 */
			return apply_filters( 'tec_tickets_commerce_square_settings', $main_settings, $is_connected );
		}

		$is_sandbox_mode = tec_tickets_commerce_is_sandbox_mode();

		$connected_settings = [
			'square-general-section-start' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__content-section">',
			],
			'tickets-commerce-gateway-settings-group-header-general' => [
				'type' => 'html',
				'html' => '<h3 class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . __( 'General', 'event-tickets' ) . '</h3>',
			],
			static::OPTION_INVENTORY_SYNC  => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Inventory Sync', 'event-tickets' ),
				'tooltip'         => esc_html__( 'If this option is selected, your Posts with Tickets on sale will be kept in sync with your Square inventory.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
		];

		// If in sandbox mode, only show the sandbox location settings.
		if ( $is_sandbox_mode ) {
			$connected_settings[ static::OPTION_SANDBOX_LOCATION_ID ] = [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Square Test Web Location', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Select the Square test location to process test payments.', 'event-tickets' ),
				'validation_type' => 'options',
				'options'         => $this->get_location_options( true ),
				'can_be_empty'    => false,
			];
		} else {
			// In live mode, only show the live location settings.
			$connected_settings[ static::OPTION_LOCATION_ID ] = [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Square Web Location', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Select the Square location to process payments.', 'event-tickets' ),
				'validation_type' => 'options',
				'options'         => $this->get_location_options( false ),
				'can_be_empty'    => false,
			];
		}

		$connected_settings['square-general-section-end'] = [
			'type' => 'html',
			'html' => '</div>',
		];

		// Add a notice about sandbox mode if active.
		if ( $is_sandbox_mode ) {
			$sandbox_notice = [
				'square-sandbox-notice' => [
					'type' => 'html',
					'html' => '<div class="tec-settings-form__element--full-width tec-settings-form__element--no-spacing tec-settings-form__element--with-border-bottom"><div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-notice-message" id="square-sandbox-notice-message">' .
						'<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>' .
						'<span>' .
						wp_kses(
							sprintf(
								/* translators: %1$s: opening link tag, %2$s: closing link tag */
								__( 'You are currently in Test Mode. Square will process test payments only. To use live payments, %1$sdisable Test Mode in Tickets Commerce settings%2$s.', 'event-tickets' ),
								'<a href="' . esc_url( tribe( TC_Settings::class )->get_url() ) . '">',
								'</a>'
							),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						) .
						'</span>' .
						'</div></div>',
				],
			];

			// Merge the sandbox notice with the connected settings.
			$connected_settings = array_merge( $sandbox_notice, $connected_settings );
		}

		/**
		 * Allow filtering the list of Square settings.
		 *
		 * @since 5.24.0
		 *
		 * @param array $settings     The list of Square Commerce settings.
		 * @param bool  $is_connected Whether or not gateway is connected.
		 */
		return apply_filters( 'tec_tickets_commerce_square_settings', array_merge( $main_settings, $connected_settings ), $is_connected );
	}

	/**
	 * Check if inventory sync is enabled.
	 *
	 * @since 5.24.0
	 *
	 * @return bool Whether inventory sync is enabled.
	 */
	public function is_inventory_sync_enabled(): bool {
		return tribe_is_truthy( tribe_get_option( static::OPTION_INVENTORY_SYNC ) );
	}

	/**
	 * Get all available Square locations as an array of options for a dropdown field.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $sandbox Whether to get sandbox locations or production locations.
	 *
	 * @return array The location options array with location IDs as keys and names as values.
	 */
	protected function get_location_options( bool $sandbox = false ): array {
		$merchant = tribe( Merchant::class );

		// Store the original mode.
		$original_mode = $merchant->get_mode();

		// Set the merchant mode based on whether we're getting sandbox locations.
		if ( $sandbox ) {
			$merchant->set_mode( 'sandbox' );
		} else {
			$merchant->set_mode( 'live' );
		}

		$options = [];

		try {
			// Only try to fetch locations if the merchant is connected in the current mode.
			if ( $merchant->is_connected() ) {
				$locations = $merchant->get_locations();

				foreach ( $locations as $location ) {
					if ( empty( $location['id'] ) || empty( $location['name'] ) ) {
						continue;
					}

					$name = $location['name'];

					// Add the location type as additional information.
					if ( ! empty( $location['type'] ) ) {
						$name .= ' (' . $location['type'] . ')';
					}

					// Add location status if not active.
					if ( ! empty( $location['status'] ) && 'ACTIVE' !== $location['status'] ) {
						$name .= ' - ' . $location['status'];
					}

					$options[ $location['id'] ] = $name;
				}
			}
		} catch ( \Exception $e ) {
			// If there's an error, add a placeholder option that explains the error.
			$options[''] = sprintf(
				/* translators: %s: error message */
				__( 'Error loading locations: %s', 'event-tickets' ),
				$e->getMessage()
			);
		}

		// Restore the original mode.
		$merchant->set_mode( $original_mode );

		if ( ! empty( $options ) ) {
			$options = [ '' => __( 'Select a location', 'event-tickets' ) ] + $options;
		}

		// If there are no locations available, add a placeholder.
		if ( empty( $options ) ) {
			if ( $sandbox ) {
				$options[''] = __( 'No test locations available. Connect your sandbox account.', 'event-tickets' );
			} else {
				$options[''] = __( 'No locations available. Check your Square account.', 'event-tickets' );
			}
		}

		/**
		 * Filter the Square location options.
		 *
		 * @since 5.24.0
		 *
		 * @param array $options  The location options.
		 * @param bool  $sandbox  Whether getting sandbox locations.
		 */
		return apply_filters( 'tec_tickets_commerce_square_location_options', $options, $sandbox );
	}

	/**
	 * Get the connection settings HTML.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_connection_settings_html(): string {
		$merchant        = tribe( Merchant::class );
		$is_connected    = $merchant->is_connected();
		$signup_template = 'signup';

		if ( $is_connected ) {
			$signup_template = 'connected';
		}

		$admin_views = tribe( 'tickets.admin.views' );

		// Configure variables for the template.
		$template_vars = [
			'merchant'         => $merchant,
			'gateway'          => tribe( Gateway::class ),
			'settings_url'     => tribe( Payments_Tab::class )->get_url(),
			'disconnect_nonce' => wp_create_nonce( $merchant->get_disconnect_action() ),
			'is_connected'     => $is_connected,
		];

		// Only add these if we're connected.
		if ( $is_connected && false === true ) {
			$connected_vars = [
				'merchant_name'     => $merchant->get_merchant_name(),
				'merchant_email'    => $merchant->get_merchant_email(),
				'merchant_id'       => $merchant->get_merchant_id(),
				'merchant_currency' => $merchant->get_merchant_currency(),
			];

			$template_vars = array_merge( $template_vars, $connected_vars );
		}

		return $admin_views->template( 'settings/tickets-commerce/square/' . $signup_template, $template_vars, false );
	}

	/**
	 * Get section name for the gateway.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_section_name(): string {
		return 'square-payments';
	}

	/**
	 * Filter the container classes for the settings page when Square is active.
	 *
	 * @since 5.24.0
	 *
	 * @param array  $container_classes Container classes.
	 * @param string $section           Section name.
	 *
	 * @return array
	 */
	public function filter_settings_container_classes( array $container_classes, string $section ): array {
		if ( $this->get_section_name() === $section ) {
			$container_classes = array_merge(
				$container_classes,
				[
					'tribe-tickets__admin-container--square-payments',
					'tribe-tickets__admin-container',
					'tribe-common',
				]
			);
		}

		return $container_classes;
	}
}
