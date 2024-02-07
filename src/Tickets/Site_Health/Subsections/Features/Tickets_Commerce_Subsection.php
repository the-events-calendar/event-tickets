<?php
/**
 * Class that handles interfacing with core Site Health.
 *
 * @since   5.8.1
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health\Subsections\Features;

use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPal_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Commerce\Repositories\Tickets_Repository;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Site_Health\Abstract_Info_Subsection;

/**
 * Class Tickets_Commerce_Subsection
 *
 * @since   5.8.1
 * @package TEC\Tickets\Site_Health
 */
class Tickets_Commerce_Subsection extends Abstract_Info_Subsection {

	/**
	 * @inheritDoc
	 */
	protected function is_subsection_enabled(): bool {
		return tec_tickets_commerce_is_enabled();
	}

	/**
	 * @inheritDoc
	 */
	protected function generate_subsection(): array {
		return [
			[
				'id'       => 'tickets_commerce_average_order_total',
				'title'    => esc_html__(
					'Tickets Commerce Average Order Total',
					'event-tickets'
				),
				'value'    => $this->get_tickets_commerce_average_order_total(),
				'priority' => 180,
			],
			[
				'id'       => 'tickets_commerce_test_mode',
				'title'    => esc_html__(
					'Tickets Commerce Test Mode',
					'event-tickets'
				),
				'value'    => $this->is_tickets_commerce_test_mode(),
				'priority' => 260,
			],
			[
				'id'       => 'tickets_commerce_stripe_connected',
				'title'    => esc_html__(
					'Tickets Commerce Stripe Connected',
					'event-tickets'
				),
				'value'    => $this->is_tickets_commerce_stripe_connected(),
				'priority' => 270,
			],
			[
				'id'       => 'tickets_commerce_paypal_connected',
				'title'    => esc_html__(
					'Tickets Commerce PayPal Connected',
					'event-tickets'
				),
				'value'    => $this->is_tickets_commerce_paypal_connected(),
				'priority' => 280,
			],
			[
				'id'       => 'tickets_commerce_currency_code',
				'title'    => esc_html__(
					'Tickets Commerce Currency Code',
					'event-tickets'
				),
				'value'    => $this->get_tickets_commerce_currency_code(),
				'priority' => 290,
			],
			[
				'id'       => 'tickets_commerce_currency_position',
				'title'    => esc_html__(
					'Tickets Commerce Currency Position',
					'event-tickets'
				),
				'value'    => $this->get_tickets_commerce_currency_position(),
				'priority' => 300,
			],
			[
				'id'       => 'tickets_commerce_decimal_separator',
				'title'    => esc_html__(
					'Tickets Commerce Decimal Separator',
					'event-tickets'
				),
				'value'    => $this->get_tickets_commerce_decimal_separator(),
				'priority' => 310,
			],
			[
				'id'       => 'tickets_commerce_thousands_separator',
				'title'    => esc_html__(
					'Tickets Commerce Thousands Separator',
					'event-tickets'
				),
				'value'    => $this->get_tickets_commerce_thousands_separator(),
				'priority' => 320,
			],
			[
				'id'       => 'tickets_commerce_number_of_decimals',
				'title'    => esc_html__(
					'Tickets Commerce Number of Decimals',
					'event-tickets'
				),
				'value'    => $this->get_tickets_commerce_number_of_decimals(),
				'priority' => 330,
			],
		];
	}

	/**
	 * Calculates the average order total for tickets commerce.
	 *
	 * @return string Formatted average price.
	 * */
	private function get_tickets_commerce_average_order_total(): string {
		$tickets_commerce_ticket_prices = tribe( Tickets_Repository::class )->per_page( -1 )->pluck( 'price' );
		$total                          = 0;
		$count                          = 0;

		foreach ( $tickets_commerce_ticket_prices as $price ) {
			if ( 'Free' === $price || '' === $price || null === $price ) {
				// Skip free or empty prices for average calculation.
				continue;
			}

			$number = Value::create( $price )->get_float();
			$total += $number;
			++$count;


		}

		// Calculate the average price, avoid division by zero.
		$tickets_commerce_average_price = $count > 0 ? $total / $count : 0;


		return Value::create( $tickets_commerce_average_price )->get_currency();
	}

	/**
	 * Determines if Tickets Commerce is in test mode.
	 *
	 * @return string 'True' if Tickets Commerce is in test mode, 'False' otherwise.
	 */
	private function is_tickets_commerce_test_mode(): string {
		return $this->get_boolean_string(
			tribe_get_option(
				Settings::$option_sandbox,
				false
			)
		);
	}

	/**
	 * Checks if Stripe is connected with Tickets Commerce.
	 *
	 * @return string 'True' if Stripe is connected, 'False' otherwise.
	 */
	private function is_tickets_commerce_stripe_connected(): string {
		return $this->get_boolean_string( tribe( Stripe_Gateway::class )->is_enabled() );
	}

	/**
	 * Determines if PayPal is connected with Tickets Commerce.
	 *
	 * @return string 'True' if PayPal is connected, 'False' otherwise.
	 */
	private function is_tickets_commerce_paypal_connected(): string {
		return $this->get_boolean_string( tribe( PayPal_Gateway::class )->is_enabled() );
	}

	/**
	 * Retrieves the currency code set in Tickets Commerce.
	 *
	 * @return string The currency code.
	 */
	private function get_tickets_commerce_currency_code(): string {
		return tribe_get_option(
			Settings::$option_currency_code,
			Currency::$currency_code_fallback
		);
	}

	/**
	 * Gets the currency position setting from Tickets Commerce.
	 *
	 * @return string The currency position.
	 */
	private function get_tickets_commerce_currency_position(): string {
		return tribe_get_option(
			Settings::$option_currency_position,
			'prefix'
		);
	}


	/**
	 * Obtains the decimal separator setting from Tickets Commerce.
	 *
	 * @return string The decimal separator.
	 */
	private function get_tickets_commerce_decimal_separator(): string {
		return tribe_get_option(
			Settings::$option_currency_decimal_separator,
			Currency::$currency_code_decimal_separator
		);
	}

	/**
	 * Retrieves the thousands separator setting from Tickets Commerce.
	 *
	 * @return string The thousands separator.
	 */
	private function get_tickets_commerce_thousands_separator(): string {
		return tribe_get_option(
			Settings::$option_currency_thousands_separator,
			Currency::$currency_code_thousands_separator
		);
	}

	/**
	 * Gets the number of decimals setting from Tickets Commerce.
	 *
	 * @return string The number of decimals.
	 */
	private function get_tickets_commerce_number_of_decimals(): string {
		return tribe_get_option(
			Settings::$option_currency_number_of_decimals,
			Currency::$currency_code_number_of_decimals
		);
	}
}
