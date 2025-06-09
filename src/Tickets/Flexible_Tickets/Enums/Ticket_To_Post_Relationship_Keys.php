<?php
/**
 * A pseudo-enum class to store the meta keys that relate Tickets to Posts.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Enums;
 */

namespace TEC\Tickets\Flexible_Tickets\Enums;

/**
 * Class Ticket_To_Post_Relationship_Keys.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Enums;
 */
class Ticket_To_Post_Relationship_Keys {
	/**
	 * The meta key relating PayPal Tickets to Posts.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const PAYPAL_TICKETS = '_tribe_tpp_for_event';

	/**
	 * The meta key relating Commerce Tickets to Posts.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const COMMERCE_TICKETS = '_tec_tickets_commerce_event';

	/**
	 * The meta key relating WooCommerce Tickets to Posts.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const WOOCOMMERCE_TICKETS = '_tribe_wooticket_event';

	/**
	 * The meta key relating Easy Digital Downloads Tickets to Posts.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const EDD_TICKETS = '_tribe_eddticket_for_event';

	/**
	 * Returns the list of all the meta keys that relate Tickets to Posts.
	 *
	 * @since 5.8.0
	 *
	 * @return array<string>
	 */
	public static function all(): array {
		return [
			// PayPal Tickets (ET).
			self::PAYPAL_TICKETS,
			// Commerce Tickets (ET).
			self::COMMERCE_TICKETS,
			// WooCommerce Tickets (ET+); the post type is 'product', registered by WooCommerce.
			self::WOOCOMMERCE_TICKETS,
			// Easy Digital Downloads Tickets (ET+); the post type is 'download', registered by EDD.
			self::EDD_TICKETS
		];
	}
}
