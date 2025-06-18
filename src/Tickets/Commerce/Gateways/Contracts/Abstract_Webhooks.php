<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use Tribe__Utils__Array as Arr;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Abstract_Webhooks.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Webhooks extends Controller_Contract {

	/**
	 * Option name for the option to store pending webhooks.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const PENDING_WEBHOOKS_KEY = '_tec_tickets_commerce_webhook_pending';

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	protected function do_register(): void {}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {}

	/**
	 * Gets the gateway for this webhook.
	 *
	 * @since 5.3.0
	 *
	 * @return Abstract_Gateway
	 */
	abstract public function get_gateway(): Abstract_Gateway;

	/**
	 * Gets the merchant for this webhook.
	 *
	 * @since 5.3.0
	 *
	 * @return Abstract_Merchant
	 */
	abstract public function get_merchant(): Abstract_Merchant;

	/**
	 * Returns the options key for webhook settings in the merchant mode.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_settings_key() : string {
		$gateway = $this->get_gateway();
		$gateway_key   = $gateway::get_key();
		$merchant_mode = $this->get_merchant()->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_{$merchant_mode}_webhooks_settings";
	}

	/**
	 * Retrieves the settings for the webhooks from the database.
	 *
	 * @since 5.3.0
	 *
	 * @param array|string $key       Specify each nested index in order.
	 *                                Example: array( 'lvl1', 'lvl2' );
	 * @param mixed        $default   Default value if the search finds nothing.
	 *
	 * @return mixed
	 */
	public function get_setting( $key, $default = null ) {
		$settings = get_option( $this->get_settings_key(), null );

		return Arr::get( $settings, $key, $default );
	}

	/**
	 * Saves the webhook settings in the database.
	 *
	 * @since 5.3.0
	 *
	 * @param array $settings []
	 *
	 * @return bool
	 */
	public function update_settings( array $settings = [] ) {
		return update_option( $this->get_settings_key(), $settings );
	}

	/**
	 * Retrieves the settings for the webhooks from the database.
	 *
	 * @since 5.3.0
	 *
	 * @return array|null
	 */
	public function get_settings() {
		$settings = get_option( $this->get_settings_key(), null );

		// Without an ID, the webhook settings are invalid.
		if ( empty( $settings['id'] ) ) {
			return null;
		}

		return $settings;
	}

	/**
	 * Deletes the stored webhook settings.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function delete_settings() {
		return delete_option( $this->get_settings_key() );
	}

	/**
	 * Add a pending webhook to the order.
	 *
	 * @since 5.18.1
	 * @since 5.24.0 Moved to the Abstract_Webhooks class from the Stripe_Webhooks class.
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param array  $metadata   Metadata.
	 *
	 * @return void
	 */
	public function add_pending_webhook( int $order_id, string $new_status, string $old_status, array $metadata = [] ): void {
		add_post_meta(
			$order_id,
			static::PENDING_WEBHOOKS_KEY,
			[
				'new_status' => $new_status,
				'metadata'   => $metadata,
				'old_status' => $old_status,
			]
		);
	}

	/**
	 * Get the pending webhooks for an order.
	 *
	 * @since 5.18.1
	 * @since 5.24.0 Moved to the Abstract_Webhooks class from the Stripe_Webhooks class.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	public function get_pending_webhooks( int $order_id ): array {
		return (array) get_post_meta( $order_id, static::PENDING_WEBHOOKS_KEY );
	}

	/**
	 * Delete the pending webhooks for an order.
	 *
	 * @since 5.18.1
	 * @since 5.24.0 Moved to the Abstract_Webhooks class from the Stripe_Webhooks class.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return void
	 */
	public function delete_pending_webhooks( int $order_id ): void {
		delete_post_meta( $order_id, static::PENDING_WEBHOOKS_KEY );
	}

	/**
	 * Get the max number of retries for the webhooks.
	 *
	 * @since 5.19.3
	 * @since 5.24.0 Moved to the Abstract_Webhooks class from the Stripe_Webhooks class. and added generic filter.
	 *
	 * @return int The number of retries.
	 */
	public function get_max_number_of_retries(): int {
		/**
		 * Filter the maximum number of attempts we will try to retry a webhook process.
		 *
		 * @since 5.24.0
		 *
		 * @param int $max_attempts How many attempts we will try to retry a webhook process. Defaults to 5.
		 *
		 * @return int
		 */
		$max_retries = (int) apply_filters( 'tec_tickets_commerce_gateway_webhook_maximum_attempts', 5 );

		$gateway     = $this->get_gateway();
		$gateway_key = $gateway::get_key();

		/**
		 * Filter the maximum number of attempts we will try to retry a webhook process for a specific gateway.
		 *
		 * @since 5.19.3
		 *
		 * @param int $max_attempts How many attempts we will try to retry a webhook process. Defaults to 5.
		 *
		 * @return int
		 */
		return (int) apply_filters( "tec_tickets_commerce_gateway_{$gateway_key}_webhook_maximum_attempts", $max_retries );
	}
}
