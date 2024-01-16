<?php
/**
 * A pseudo-enum class for the Ticket post types.
 *
 * @since 5.8.0
 */

namespace TEC\Tickets\Flexible_Tickets\Enums;

/**
 * Class Ticket_Post_Types.
 *
 * @since 5.8.0
 */
class Ticket_Post_Types {
	/**
	 * The post type for PayPal Tickets.
	 * The post type is registered by ET.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const PAYPAL_TICKETS = 'tribe_tpp_tickets';

	/**
	 * The post type for Commerce Tickets.
	 * The post type is registered by ET.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const COMMERCE_TICKETS = 'tec_tc_ticket';

	/**
	 * The post type for WooCommerce Tickets.
	 * The post type is registered by WooCommerce.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const WOOCOMMERCE_TICKETS = 'product';

	/**
	 * The post type for Easy Digital Downloads Tickets.
	 * The post type is registered by EDD.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public const EDD_TICKETS = 'download';

	/**
	 * Returns the list of all the Ticket post types.
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