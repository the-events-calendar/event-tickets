<?php
/**
 * Resolves and delegates order email sending to the appropriate sender.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */

namespace TEC\Tickets\Commerce\Emails;

use TEC\Common\StellarWP\ContainerContract\ContainerInterface;
use WP_Post;

/**
 * Class Order_Email_Sender_Registry.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */
class Order_Email_Sender_Registry {

	/**
	 * Container tag for order email sender implementations.
	 *
	 * Third-party code can register additional senders with:
	 * `tribe()->tag( [ My_Sender::class ], self::CONTAINER_TAG );`
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const CONTAINER_TAG = 'tec.tickets.commerce.order_email_senders';

	/**
	 * The DI container.
	 *
	 * @since TBD
	 *
	 * @var ContainerInterface
	 */
	private ContainerInterface $container;

	/**
	 * Order_Email_Sender_Registry constructor.
	 *
	 * @since TBD
	 *
	 * @param ContainerInterface $container The plugin DI container.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Sends the appropriate email for the given order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The decorated order post object.
	 *
	 * @return void
	 */
	public function send( WP_Post $order ): void {
		foreach ( $this->get_senders() as $sender ) {
			if ( ! $sender instanceof Order_Email_Sender_Interface ) {
				continue;
			}

			if ( $sender->supports( $order ) ) {
				$sender->send( $order );

				return;
			}
		}
	}

	/**
	 * Returns the registered senders from the container tag, allowing extensions to add or reorder them.
	 *
	 * @since TBD
	 *
	 * @return Order_Email_Sender_Interface[]
	 */
	private function get_senders(): array {
		/**
		 * Filters the order email senders used when the `send_email` flag action fires.
		 *
		 * Prefer registering senders on the container tag
		 * `Order_Email_Sender_Registry::CONTAINER_TAG` via `tribe()->tag()`.
		 *
		 * @since TBD
		 *
		 * @param Order_Email_Sender_Interface[] $senders Registered senders.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_order_email_senders', $this->container->tagged( self::CONTAINER_TAG ) );
	}
}
