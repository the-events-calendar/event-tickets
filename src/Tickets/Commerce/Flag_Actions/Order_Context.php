<?php
/**
 * Order type contexts used to scope flag actions at registration time.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */

namespace TEC\Tickets\Commerce\Flag_Actions;

/**
 * Class Order_Context.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
final class Order_Context {
	/**
	 * Applies to all Tickets Commerce order types.
	 *
	 * @since 5.30.0
	 *
	 * @var string
	 */
	public const ALL = 'all';

	/**
	 * Applies only to standard ticket (non-RSVP) orders.
	 *
	 * @since 5.30.0
	 *
	 * @var string
	 */
	public const TICKET = 'ticket';

	/**
	 * Applies only to TC-RSVP (RSVP v2) orders.
	 *
	 * @since 5.30.0
	 *
	 * @var string
	 */
	public const RSVP_V2 = 'rsvp_v2';
}
