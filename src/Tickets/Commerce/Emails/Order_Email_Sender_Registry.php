<?php
/**
 * Resolves and delegates order email sending to the appropriate sender.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */

namespace TEC\Tickets\Commerce\Emails;

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
	 * The registered order email senders, in dispatch order.
	 *
	 * @since TBD
	 *
	 * @var Order_Email_Sender_Interface[]
	 */
	private array $senders;

	/**
	 * Order_Email_Sender_Registry constructor.
	 *
	 * @since TBD
	 *
	 * @param Order_Email_Sender_Interface[] $senders The order email senders to dispatch to.
	 */
	public function __construct( array $senders ) {
		$this->senders = $senders;
	}

	/**
	 * Sends the appropriate email for the given order.
	 *
	 * Dispatches to the first registered sender whose `supports()` returns true and stops there —
	 * an order only ever needs one confirmation email, so this is intentionally first-match-wins
	 * rather than a broadcast to every supporting sender.
	 *
	 * @since TBD
	 * @since TBD Checks `tec_tickets_emails_is_enabled()` once here instead of in each sender.
	 *
	 * @param WP_Post $order The decorated order post object.
	 *
	 * @return void
	 */
	public function send( WP_Post $order ): void {
		if ( ! tec_tickets_emails_is_enabled() ) {
			return;
		}

		foreach ( $this->senders as $sender ) {
			if ( ! $sender instanceof Order_Email_Sender_Interface ) {
				continue;
			}

			if ( $sender->supports( $order ) ) {
				$sender->send( $order );

				return;
			}
		}
	}
}
