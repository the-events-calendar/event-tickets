<?php
/**
 * Order type contexts used to scope flag actions at registration time.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */

namespace TEC\Tickets\Commerce\Flag_Actions;

/**
 * Class Order_Context.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Flag_Actions
 */
final class Order_Context {
	/**
	 * Applies to all Tickets Commerce order types.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const ALL = 'all';

	/**
	 * Applies only to standard ticket (non-RSVP) orders.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TICKET = 'ticket';

	/**
	 * Applies only to TC-RSVP (RSVP v2) orders.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RSVP_V2 = 'rsvp_v2';
}
