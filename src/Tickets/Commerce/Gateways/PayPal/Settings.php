<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Abstract_Settings;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\WebhookConfig;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\MerchantDetails;
use Tribe__Languages__Locations;
use Tribe__Tickets__Admin__Views;
use Tribe__Tickets__Main;

/**
 * The PayPal Commerce specific settings.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Settings extends Abstract_Settings {

	/**
	 * The option key for account country.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_account_country = 'tickets-commerce-paypal-commerce-account-country';

	/**
	 * The option key for access token.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_access_token = 'tickets-commerce-paypal-commerce-access-token';

	/**
	 * The option key for partner link detail.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_partner_link_detail = 'tickets-commerce-paypal-commerce-partner-link-detail';

	/**
	 * The option key for webhook config.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_webhook_config = 'tickets-commerce-paypal-commerce-webhook-config';

	/**
	 * The merchant detail model.
	 *
	 * @since 5.1.6
	 *
	 * @var MerchantDetail
	 */
	private $merchant_model;

	/**
	 * The merchant details repository.
	 *
	 * @since 5.1.6
	 *
	 * @var MerchantDetails
	 */
	private $merchant_repository;

	/**
	 * Set up the things we need for the settings.
	 *
	 * @since 5.1.6
	 *
	 * @param MerchantDetail  $merchantDetail
	 * @param MerchantDetails $merchantDetailRepository
	 */
	public function __construct( MerchantDetail $merchantDetail, MerchantDetails $merchantDetailRepository ) {
		$this->merchant_model      = $merchantDetail;
		$this->merchant_repository = $merchantDetailRepository;
	}

	/**
	 * Get the list of settings for the gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for the gateway.
	 */
	public function get_settings() {
		$home_url = home_url();

		/** @var Tribe__Languages__Locations $locations */
		$locations = tribe( 'languages.locations' );
		$countries = $locations->get_countries();

		// Add an initial empty selection to the start.
		$countries = [ '' => __( '-- Please select a country --', 'event-tickets' ) ] + $countries;

		$connect_html = 'Connect to PayPal';

		return [
			'tickets-commerce-paypal-commerce-configure' => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_introduction_html(),
				'validation_type' => 'html',
			],
			$this->option_account_country                => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Account Country', 'event-tickets' ),
				'tooltip'         => esc_html__( 'This is the country your site operates from.', 'event-tickets' ),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $countries,
				'required'        => true, // @todo This is not working.
				'can_be_empty'    => false, // @todo This is not working.
			],
			'tickets-commerce-paypal-commerce-connect'   => [
				'type'            => 'wrapped_html',
				'label'           => esc_html__( 'PayPal Connection', 'event-tickets' ),
				'html'            => $this->get_connect_html(),
				'validation_type' => 'html',
			],
		];
	}

	/**
	 * Get the PayPal Commerce introduction section.
	 *
	 * @since 5.1.6
	 *
	 * @return string The PayPal Commerce introduction section.
	 */
	public function get_introduction_html() {
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'plugin_url' => Tribe__Tickets__Main::instance()->plugin_url,
		];

		$admin_views->add_template_globals( $context );

		return $admin_views->template( 'settings/tickets-commerce/paypal-commerce/introduction', [], false );
	}

	/**
	 * Get the Connect with PayPal HTML.
	 *
	 * @since 5.1.6
	 *
	 * @return string The Connect with PayPal HTML.
	 */
	public function get_connect_html() {
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$account_errors = $this->merchant_repository->getAccountErrors();

		$context = [
			'account_is_connected' => $this->merchant_repository->accountIsConnected(),
			'merchant_id'          => $this->merchant_model->merchantId,
			'formatted_errors'     => $this->get_formatted_error_html( $account_errors ),
			'guidance_html'        => $this->get_guidance_html(),
		];

		$admin_views->add_template_globals( $context );

		return $admin_views->template( 'settings/tickets-commerce/paypal-commerce/connect-with-paypal', [], false );
	}

	/**
	 * Get the guidance HTML.
	 *
	 * @since 5.1.6
	 *
	 * @return string The guidance HTML.
	 */
	public function get_guidance_html() {
		if ( $this->account_is_in_north_america() ) {
			$telephone = sprintf(
				'<a href="tel:%1$s">%1$s</a>',
				'1-855-456-1330'
			);

			$message = sprintf(
				// Translators: %s: The PayPal telephone number.
				esc_html__( 'Please call a PayPal support representative at %s', 'event-tickets' ),
				$telephone
			);
		} else {
			$message = esc_html__( 'Please reach out to PayPal support from your PayPal account Resolution Center', 'event-tickets' );
		}

		$message .= esc_html__( ' and relay the following message:', 'event-tickets' );

		return $message;
	}

	/**
	 * Determine whether the account country is in North America.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether the account country is in North America.
	 */
	private function account_is_in_north_america() {
		// Countries list: https://en.wikipedia.org/wiki/List_of_North_American_countries_by_area#Countries
		$north_american_countries = [
			'CA', // Canada
			'US', // United States
			'MX', // Mexico
			'NI', // Nicaragua
			'HN', // Honduras
			'CU', // Cuba
			'GT', // Guatemala
			'PA', // Panama
			'CR', // Costa Rica
			'DO', // Dominican Republic
			'HT', // Haiti
			'BZ', // Belize
			'SV', // EL Salvador
			'BS', // The Bahamas
			'JM', // Jamaica
			'TT', // Trinidad and Tobago
			'DM', // Dominica
			'LC', // Saint Lucia
			'AG', // Antigua and Barbuda
			'BB', // Barbados
			'VC', // Saint Vincent and the Grenadines
			'GD', // Grenada
			'KN', // Saint Kitts and Nevis
		];

		// @todo Replace the settings name with property.
		$account_country = tribe_get_option( $this->option_account_country );

		return in_array( $account_country, $north_american_countries, true );
	}

	/**
	 * Get the formatted error HTML.
	 *
	 * @since 5.1.6
	 *
	 * @param array $errors The list of errors.
	 *
	 * @return string The formatted error HTML.
	 */
	public function get_formatted_error_html( $errors ) {
		// If there are no errors, return an empty string.
		if ( empty( $errors ) ) {
			return '';
		}

		$formatted_errors = $this->format_errors( $errors );

		// There were no errors to show.
		if ( empty( $formatted_errors ) ) {
			return '';
		}

		$is_single_error = 1 === count( $formatted_errors );

		foreach ( $formatted_errors as &$formatted_error ) {
			$formatted_error = sprintf(
				'<%1$s>%2$s</%1$s>',
				$is_single_error ? 'p' : 'li',
				$formatted_error
			);
		}

		$output = implode( "\n", $formatted_errors );

		// Wrap multiple errors in a ul.
		if ( ! $is_single_error ) {
			$output = sprintf(
				'<ul class="ul-disc">%1$s</ul>',
				$output
			);
		}

		return $output;
	}

	/**
	 * Format the list of errors.
	 *
	 * @since 5.1.6
	 *
	 * @param array $errors The list of errors to format.
	 *
	 * @return array The list of formatted errors.
	 */
	private function format_errors( $errors ) {
		$formatted_errors = [];

		foreach ( $errors as $error ) {
			if ( is_array( $error ) ) {
				switch ( $error['type'] ) {
					case 'url':
						$error = sprintf(
							'%1$s<br><code>%2$s</code>',
							$error['message'],
							urldecode_deep( $error['value'] )
						);
						break;

					case 'json':
						$error = sprintf(
							'%1$s<br><code>%2$s</code>',
							$error['message'],
							$error['value']
						);
						break;

					default:
						// This is an unrecognized error.
						$error = null;
						break;
				}
			}

			// If there is no error, just return empty.
			if ( empty( $error ) ) {
				continue;
			}

			$formatted_errors[] = $error;
		}

		return $formatted_errors;
	}

	/**
	 * Returns the country for the account
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function get_account_country() {
		// @todo Replace this with a constant default value or a filtered value for setting the default country.
		return tribe_get_option( $this->option_account_country, '' );
	}

	/**
	 * Updates the country account
	 *
	 * @since 5.1.6
	 *
	 * @param string $country
	 *
	 * @return bool
	 */
	public function update_account_country( $country ) {
		return tribe_update_option( $this->option_account_country, $country );
	}

	/**
	 * Returns the account access token
	 *
	 * @since 5.1.6
	 *
	 * @return array|null
	 */
	public function get_access_token() {
		$access_token = tribe_get_option( $this->option_access_token );

		if ( ! is_array( $access_token ) ) {
			return null;
		}

		return $access_token;
	}

	/**
	 * Updates the account access token.
	 *
	 * @since 5.1.6
	 *
	 * @param array $token The account access token.
	 *
	 * @return bool
	 */
	public function update_access_token( $token ) {
		return tribe_update_option( $this->option_access_token, (array) $token );
	}

	/**
	 * Deletes the account access token
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function delete_access_token() {
		return tribe_update_option( $this->option_access_token, '' );
	}

	/**
	 * Returns the partner link details
	 *
	 * @since 5.1.6
	 *
	 * @since 5.1.6
	 *
	 * @return string|null
	 */
	public function get_partner_link_details() {
		return tribe_get_option( $this->option_partner_link_detail, null );
	}

	/**
	 * Updates the partner link details
	 *
	 * @since 5.1.6
	 *
	 * @param $linkDetails
	 *
	 * @return bool
	 */
	public function update_partner_link_details( $linkDetails ) {
		return tribe_update_option( $this->option_partner_link_detail, $linkDetails );
	}

	/**
	 * Deletes the partner link details
	 *
	 * @since 5.1.6
	 *
	 * @return bool
	 */
	public function delete_partner_link_details() {
		return tribe_update_option( $this->option_partner_link_detail, '' );
	}

	/**
	 * Returns the webhook config.
	 *
	 * @since 5.1.6
	 *
	 * @param string $mode The mode (live/sandbox).
	 *
	 * @return WebhookConfig|null
	 */
	public function get_webhook_config( $mode ) {
		$config = tribe_get_option( "{$this->option_webhook_config}-{$mode}", null );

		if ( empty( $config ) ) {
			return null;
		}

		return WebhookConfig::fromArray( $config );
	}

	/**
	 * Updates the webhook config.
	 *
	 * @since 5.1.6
	 *
	 * @param string        $mode   The mode (live/sandbox).
	 * @param WebhookConfig $config The webhook config array.
	 *
	 * @return bool
	 */
	public function update_webhook_config( $mode, WebhookConfig $config ) {
		return tribe_update_option( "{$this->option_webhook_config}-{$mode}", $config->toArray() );
	}

	/**
	 * Deletes the webhook config.
	 *
	 * @since 5.1.6
	 *
	 * @param string $mode The mode (live/sandbox).
	 *
	 * @return bool
	 */
	public function delete_webhook_config( $mode ) {
		return tribe_update_option( "{$this->option_webhook_config}-{$mode}", '' );
	}

}
