<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Settings;
use TEC\Tickets\Commerce\Settings as TC_Settings;

/**
 * Square Commerce Settings.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Settings extends Abstract_Settings {

	/**
	 * Client ID option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $option_client_id = 'tickets-commerce-square-client-id';

	/**
	 * Sandbox Client ID option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $option_sandbox_client_id = 'tickets-commerce-square-sandbox-client-id';

	/**
	 * Location ID option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $option_location_id = 'tickets-commerce-square-location-id';

	/**
	 * Sandbox Location ID option key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $option_sandbox_location_id = 'tickets-commerce-square-sandbox-location-id';

	/**
	 * Get all the settings for the Square gateway.
	 *
	 * @since TBD
	 *
	 * @return array The gateway settings.
	 */
	public function get_settings(): array {
		$main_settings = [
			'square-connection-start' => [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-toggle-large">',
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
			 * @since TBD
			 *
			 * @param array $settings     The list of Square Commerce settings.
			 * @param bool  $is_connected Whether or not gateway is connected.
			 */
			return apply_filters( 'tec_tickets_commerce_square_settings', $main_settings, $is_connected );
		}

		$is_sandbox_mode = tec_tickets_commerce_is_sandbox_mode();

		$connected_settings = [
			'square-settings-title' => [
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Square Settings', 'event-tickets' ) . '</h3>',
			],
		];

		// If in sandbox mode, only show the sandbox location settings
		if ( $is_sandbox_mode ) {
			$connected_settings[ static::$option_sandbox_location_id ] = [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Square Test Location', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Select the Square test location to process test payments through.', 'event-tickets' ),
				'validation_type' => 'options',
				'options'         => $this->get_location_options( true ),
				'can_be_empty'    => false,
			];
		} else {
			// In live mode, only show the live location settings
			$connected_settings[ static::$option_location_id ] = [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Square Location', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Select the Square location to process payments through.', 'event-tickets' ),
				'validation_type' => 'options',
				'options'         => $this->get_location_options( false ),
				'can_be_empty'    => false,
			];
		}

		// Add a notice about sandbox mode if active
		if ( $is_sandbox_mode ) {
			$sandbox_notice = [
				'square-sandbox-notice' => [
					'type' => 'html',
					'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-notice-message" id="square-sandbox-notice-message">' .
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
						'</div>',
				],
			];
			$connected_settings = array_merge( $sandbox_notice, $connected_settings );
		}

		/**
		 * Allow filtering the list of Square settings.
		 *
		 * @since TBD
		 *
		 * @param array $settings     The list of Square Commerce settings.
		 * @param bool  $is_connected Whether or not gateway is connected.
		 */
		return apply_filters( 'tec_tickets_commerce_square_settings', array_merge( $main_settings, $connected_settings ), $is_connected );
	}

	/**
	 * Get all available Square locations as an array of options for a dropdown field.
	 *
	 * @since TBD
	 *
	 * @param bool $sandbox Whether to get sandbox locations or production locations.
	 *
	 * @return array The location options array with location IDs as keys and names as values.
	 */
	protected function get_location_options( bool $sandbox = false ): array {
		$merchant = tribe( Merchant::class );

		// Store the original mode
		$original_mode = $merchant->get_mode();

		// Set the merchant mode based on whether we're getting sandbox locations
		if ( $sandbox ) {
			$merchant->set_mode( 'sandbox' );
		} else {
			$merchant->set_mode( 'live' );
		}

		$options = [];

		try {
			// Only try to fetch locations if the merchant is connected in the current mode
			if ( $merchant->is_connected() ) {
				$locations = $merchant->get_locations();

				foreach ( $locations as $location ) {
					if ( empty( $location['id'] ) || empty( $location['name'] ) ) {
						continue;
					}

					$name = $location['name'];

					// Add the location type as additional information
					if ( ! empty( $location['type'] ) ) {
						$name .= ' (' . $location['type'] . ')';
					}

					// Add location status if not active
					if ( ! empty( $location['status'] ) && 'ACTIVE' !== $location['status'] ) {
						$name .= ' - ' . $location['status'];
					}

					$options[ $location['id'] ] = $name;
				}
			}
		} catch ( \Exception $e ) {
			// If there's an error, add a placeholder option that explains the error
			$options[''] = sprintf(
				/* translators: %s: error message */
				__( 'Error loading locations: %s', 'event-tickets' ),
				$e->getMessage()
			);
		}

		// Restore the original mode
		$merchant->set_mode( $original_mode );

		// If there are no locations available, add a placeholder
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
		 * @since TBD
		 *
		 * @param array $options  The location options.
		 * @param bool  $sandbox  Whether getting sandbox locations.
		 */
		return apply_filters( 'tec_tickets_commerce_square_location_options', $options, $sandbox );
	}

	/**
	 * Get the connection settings HTML.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_section_name(): string {
		return 'square-payments';
	}

	/**
	 * Filter the container classes for the settings page when Square is active.
	 *
	 * @since TBD
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
