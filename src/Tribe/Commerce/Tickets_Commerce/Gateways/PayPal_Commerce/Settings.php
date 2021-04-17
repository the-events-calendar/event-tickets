<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Abstract_Settings;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models\MerchantDetail;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Repositories\MerchantDetails;
use Tribe__Languages__Locations;
use Tribe__Tickets__Admin__Views;
use Tribe__Tickets__Main;

/**
 * The PayPal Commerce specific settings.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 */
class Settings extends Abstract_Settings {

	/**
	 * The merchant detail model.
	 *
	 * @since TBD
	 *
	 * @var MerchantDetail
	 */
	private $merchant_model;

	/**
	 * The merchant details repository.
	 *
	 * @since TBD
	 *
	 * @var MerchantDetails
	 */
	private $merchant_repository;

	/**
	 * Set up the things we need for the settings.
	 *
	 * @since TBD
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
	 * @since TBD
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
			'tickets-commerce-paypal-commerce-configure'       => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_introduction_html(),
				'validation_type' => 'html',
			],
			// @todo Define setting as property.
			'tickets-commerce-paypal-commerce-account-country' => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Account Country', 'event-tickets' ),
				'tooltip'         => esc_html__( 'This is the country your site operates from.', 'event-tickets' ),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $countries,
				'required'        => true, // @todo This is not working.
				'can_be_empty'    => false, // @todo This is not working.
			],
			// @todo Define setting as property.
			'tickets-commerce-paypal-commerce-connect'         => [
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
		$account_country = tribe_get_option( 'tickets-commerce-paypal-commerce-account-country' );

		return in_array( $account_country, $north_american_countries, true );
	}

	/**
	 * Get the formatted error HTML.
	 *
	 * @since TBD
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
	 * @since TBD
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

					case 'json':
						$error = sprintf(
							'%1$s<br><code>%2$s</code>',
							$error['message'],
							$error['value']
						);

					default:
						// This is an unrecognized error.
						$error = null;
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

}
