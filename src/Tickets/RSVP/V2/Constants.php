<?php
/**
 * Constants for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

/**
 * Class Constants
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Constants {
	/**
	 * The ticket type identifier for TC-RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TC_RSVP_TYPE = 'tc-rsvp';

	/**
	 * The key used to filter the repository query args to include or exclude TC-RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TYPE_META_QUERY_KEY = 'tc-rsvp-type';

	/**
	 * Meta key for storing the "show not going" option.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SHOW_NOT_GOING_META_KEY = '_tribe_ticket_show_not_going';

	/**
	 * RSVP status meta key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RSVP_STATUS_META_KEY = '_tec_tickets_commerce_rsvp_status';
}
