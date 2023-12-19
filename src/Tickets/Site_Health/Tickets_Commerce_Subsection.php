<?php
/**
 * Class that handles interfacing with core Site Health.
 *
 * @since   5.6.0.1
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPal_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Commerce\Repositories\Tickets_Repository;

/**
 * Class Tickets_Commerce_Subsection
 *
 * @since   TBD
 * @package TEC\Tickets\Site_Health
 */
class Tickets_Commerce_Subsection extends Abstract_Info_Subsection {

	/**
	 * @inheritDoc
	 */
	protected function is_subsection_enabled(): bool {
		return tribe_get_option(
			'tickets_commerce_enabled',
			false
		);
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
				'title'    => 'Tickets Commerce Test Mode',
				'value'    => $this->is_tickets_commerce_test_mode(),
				'priority' => 260,
			],
			[
				'id'       => 'tickets_commerce_stripe_connected',
				'title'    => 'Tickets Commerce Stripe Connected',
				'value'    => $this->is_tickets_commerce_stripe_connected(),
				'priority' => 270,
			],
			[
				'id'       => 'tickets_commerce_paypal_connected',
				'title'    => 'Tickets Commerce PayPal Connected',
				'value'    => $this->is_tickets_commerce_paypal_connected(),
				'priority' => 280,
			],
			[
				'id'       => 'tickets_commerce_currency_code',
				'title'    => 'Tickets Commerce Currency Code',
				'value'    => $this->get_tickets_commerce_currency_code(),
				'priority' => 290,
			],
			[
				'id'       => 'tickets_commerce_currency_position',
				'title'    => 'Tickets Commerce Currency Position',
				'value'    => $this->get_tickets_commerce_currency_position(),
				'priority' => 300,
			],
			[
				'id'       => 'tickets_commerce_decimal_separator',
				'title'    => 'Tickets Commerce Decimal Separator',
				'value'    => $this->get_tickets_commerce_decimal_separator(),
				'priority' => 310,
			],
			[
				'id'       => 'tickets_commerce_thousands_separator',
				'title'    => 'Tickets Commerce Thousands Separator',
				'value'    => $this->get_tickets_commerce_thousands_separator(),
				'priority' => 320,
			],
			[
				'id'       => 'tickets_commerce_number_of_decimals',
				'title'    => 'Tickets Commerce Number of Decimals',
				'value'    => $this->get_tickets_commerce_number_of_decimals(),
				'priority' => 330,
			],
		];
	}

	/**
	 * Calculates the average order total for tickets commerce.
	 *
	 * @return int Formatted average price.
	 * */
	private function get_tickets_commerce_average_order_total(): int {
		// @todo redscar This logic may be incorrect.
		$tickets_commerce_ticket_prices = tribe( Tickets_Repository::class )->per_page( -1 )->pluck( 'price' );
		$total                          = 0;
		$count                          = 0;

		foreach ( $tickets_commerce_ticket_prices as $price ) {
			if ( $price === 'Free' || $price === '' || $price === null ) {
				// Skip free or empty prices for average calculation.
				continue;
			} else {
				// Match the number with international currency format.
				preg_match(
					'/\d+([,.]\d+)?/',
					$price,
					$matches
				);
				if ( isset( $matches[0] ) ) {
					// Convert to a standard number format (replace comma with period).
					$number = floatval(
						str_replace(
							',',
							'.',
							$matches[0]
						)
					);
					$total  += $number;

					++$count;
				}
			}
		}

		// Calculate the average price, avoid division by zero.
		$tickets_commerce_average_price = $count > 0 ? $total / $count : 0;

		// Format the average price with two decimal points.
		$tickets_commerce_formatted_average_price = number_format(
			$tickets_commerce_average_price,
			2,
			'.',
			','
		);

		return $tickets_commerce_formatted_average_price;
	}

	/**
	 * Determines if Tickets Commerce is in test mode.
	 *
	 * @return string 'True' if Tickets Commerce is in test mode, 'False' otherwise.
	 */
	private function is_tickets_commerce_test_mode(): string {
		return tribe_get_option(
			'tickets-commerce-test-mode',
			false
		) ? 'True' : 'False';
	}

	/**
	 * Checks if Stripe is connected with Tickets Commerce.
	 *
	 * @return string 'True' if Stripe is connected, 'False' otherwise.
	 */
	private function is_tickets_commerce_stripe_connected(): string {
		return tribe( Stripe_Gateway::class )->is_enabled() ? 'True' : 'False';
	}

	/**
	 * Determines if PayPal is connected with Tickets Commerce.
	 *
	 * @return string 'True' if PayPal is connected, 'False' otherwise.
	 */
	private function is_tickets_commerce_paypal_connected(): string {
		return tribe( PayPal_Gateway::class )->is_enabled() ? 'True' : 'False';
	}

	/**
	 * Retrieves the currency code set in Tickets Commerce.
	 *
	 * @return string The currency code.
	 */
	private function get_tickets_commerce_currency_code(): string {
		return tribe_get_option(
			'tickets-commerce-currency-code'
		);
	}

	/**
	 * Gets the currency position setting from Tickets Commerce.
	 *
	 * @return string The currency position.
	 */
	private function get_tickets_commerce_currency_position(): string {
		return tribe_get_option(
			'tickets-commerce-currency-position'
		);
	}


	/**
	 * Obtains the decimal separator setting from Tickets Commerce.
	 *
	 * @return string The decimal separator.
	 */
	private function get_tickets_commerce_decimal_separator(): string {
		return tribe_get_option(
			'tickets-commerce-currency-decimal-separator'
		);
	}

	/**
	 * Retrieves the thousands separator setting from Tickets Commerce.
	 *
	 * @return string The thousands separator.
	 */
	private function get_tickets_commerce_thousands_separator(): string {
		return tribe_get_option(
			'tickets-commerce-currency-thousands-separator'
		);
	}

	/**
	 * Gets the number of decimals setting from Tickets Commerce.
	 *
	 * @return string The number of decimals.
	 */
	private function get_tickets_commerce_number_of_decimals(): string {
		return tribe_get_option(
			'tickets-commerce-currency-number-of-decimals'
		);
	}
}
