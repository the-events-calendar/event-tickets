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
}
