<?php
/**
 * Contract for order email senders that handle the `send_email` flag action.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */

namespace TEC\Tickets\Commerce\Emails;

use WP_Post;

/**
 * Interface Order_Email_Sender_Interface.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */
interface Order_Email_Sender_Interface {

	/**
	 * Determines whether this sender should handle the given order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The decorated order post object.
	 *
	 * @return bool
	 */
	public function supports( WP_Post $order ): bool;

	/**
	 * Sends the appropriate email(s) for the given order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The decorated order post object.
	 *
	 * @return void
	 */
	public function send( WP_Post $order ): void;
}
