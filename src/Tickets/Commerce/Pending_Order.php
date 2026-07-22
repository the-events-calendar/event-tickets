<?php
/**
 * Pending Order implementation.
 *
 * @since 5.29.0.1
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\Monolog\Logger;
use RuntimeException;

/**
 * Pending_Order implementation which is used to store that a specific cart hash,
 * has a pending order to be updated.
 *
 * @since 5.29.0.1
 * @package TEC\Tickets\Commerce
 */
final class Pending_Order {
	/**
	 * The transient name.
	 *
	 * @var string
	 */
	private const TRANSIENT_NAME = 'tec_tickets_commerce_pending_order_%s';

	/**
	 * The cart instance.
	 *
	 * @var Cart
	 */
	private Cart $cart;

	/**
	 * The logger instance.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * The constructor.
	 *
	 * @since 5.29.0.1
	 *
	 * @param Cart   $cart   The cart instance.
	 * @param Logger $logger The logger instance.
	 */
	public function __construct( Cart $cart, Logger $logger ) {
		$this->cart   = $cart;
		$this->logger = $logger;
	}

	/**
	 * Stores a gateway's order id as a pending order for the current Cart hash.
	 *
	 * @since 5.29.0.1
	 *
	 * @param string $gateway_order_id The gateway's order id.
	 *
	 * @return void
	 */
	public function set( string $gateway_order_id ): void {
		try {
			set_transient( $this->get_transient_name(), $gateway_order_id, $this->cart->get_cart_expiration() );
		} catch ( RuntimeException $e ) {
			$this->logger->debug(
				'No cart hash present.',
				[
					'method'  => __METHOD__,
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				]
			);
		}
	}

	/**
	 * Retrieves the gateway's order id for the current cart hash.
	 *
	 * @since 5.29.0.1
	 *
	 * @return string|null
	 */
	public function get(): ?string {
		try {
			$gateway_order_id = get_transient( $this->get_transient_name() );
			if ( ! is_string( $gateway_order_id ) ) {
				return null;
			}

			return $gateway_order_id;
		} catch ( RuntimeException $e ) {
			$this->logger->debug(
				'No cart hash present.',
				[
					'method'  => __METHOD__,
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				]
			);
		}

		return null;
	}

	/**
	 * Clears the stored gateway order id for the current cart hash.
	 *
	 * @since 5.29.0.1
	 *
	 * @return void
	 */
	public function clear(): void {
		try {
			delete_transient( $this->get_transient_name() );
		} catch ( RuntimeException $e ) {
			$this->logger->debug(
				'No cart hash present.',
				[
					'method'  => __METHOD__,
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
				]
			);
		}
	}

	/**
	 * Builds the transient name for the current cart hash.
	 *
	 * @since 5.29.0.1
	 *
	 * @return string
	 *
	 * @throws RuntimeException When there is no cart hash.
	 */
	private function get_transient_name(): string {
		$cart_hash = $this->cart->get_cart_hash( false );
		if ( ! $cart_hash ) {
			throw new RuntimeException( 'No cart hash present.' );
		}

		return sprintf( self::TRANSIENT_NAME, $cart_hash );
	}
}
