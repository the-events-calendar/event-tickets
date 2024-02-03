<?php

namespace TEC\Tickets\Site_Health\Fieldset;

use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPal_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Site_Health\Contracts\Fieldset_Abstract;

/**
 * Class Commerce
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Fieldset
 */
class Commerce extends Fieldset_Abstract {

	/**
	 * @inheritdoc
	 */
	protected float $priority = 20.0;

	/**
	 * @inheritdoc
	 */
	protected function get_fields(): array {
		return [
			[
				'id'    => 'tickets_commerce_enabled',
				'label' => esc_html__( 'Tickets Commerce Enabled', 'event-tickets' ),
				'value' => [ $this, 'is_tickets_commerce_enabled' ],
			],
			[
				'id'    => 'tickets_commerce_sandbox_mode',
				'label' => esc_html__( 'Tickets Commerce Sandbox Mode', 'event-tickets' ),
				'value' => [ $this, 'is_tickets_commerce_sandbox_mode' ],
			],
			[
				'id'    => 'tribe_commerce_is_available',
				'label' => esc_html__( 'Tribe Commerce Available', 'event-tickets' ),
				'value' => [ $this, 'is_tribe_commerce_available' ],
			],
			[
				'id'    => 'tickets_commerce_gateway_stripe_active',
				'label' => esc_html__( 'Tickets Commerce gateway Stripe active', 'event-tickets' ),
				'value' => [ $this, 'is_tc_stripe_active' ],
			],
			[
				'id'    => 'tickets_commerce_gateway_paypal_active',
				'label' => esc_html__( 'Tickets Commerce gateway PayPal active', 'event-tickets' ),
				'value' => [ $this, 'is_tc_paypal_active' ],
			],
		];
	}

	/**
	 * Check if Tickets Commerce is enabled.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function is_tickets_commerce_enabled(): string {
		return tec_tickets_commerce_is_enabled() ? static::YES : static::NO;
	}

	/**
	 * Check if Tickets Commerce is in sandbox mode.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function is_tickets_commerce_sandbox_mode(): string {
		return tec_tickets_commerce_is_sandbox_mode() ? static::YES : static::NO;
	}

	/**
	 * Check if Tribe Commerce is available.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function is_tribe_commerce_available(): string {
		return tec_tribe_commerce_is_available() ? static::YES : static::NO;
	}

	/**
	 * Check if the Stripe payment gateway is active.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function is_tc_stripe_active(): string {
		$gateway = tribe( Manager::class )->get_gateway_by_key( Stripe_Gateway::get_key() );

		return tec_tickets_commerce_is_enabled() && $gateway::is_active() && $gateway::is_enabled() ? static::YES : static::NO;
	}

	/**
	 * Check if the PayPal payment gateway is active.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function is_tc_paypal_active(): string {
		$gateway = tribe( Manager::class )->get_gateway_by_key( PayPal_Gateway::get_key() );

		return tec_tickets_commerce_is_enabled() && $gateway::is_active() && $gateway::is_enabled() ? static::YES : static::NO;
	}
}