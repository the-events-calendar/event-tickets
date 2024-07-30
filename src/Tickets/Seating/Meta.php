<?php
/**
 * Information repository and handler of post meta data for the plugin.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

/**
 * Class Meta.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */
class Meta {
	/**
	 * The meta key used to store the enabled state of seat layouts and reservations for a post.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_ENABLED = '_tec_slr_enabled';

	/**
	 * The meta key used to store the layout ID of a post.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_LAYOUT_ID = '_tec_slr_layout';

	/**
	 * The meta key used to store the seat type of a Ticket.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_SEAT_TYPE = '_tec_slr_seat_type';

	/**
	 * The meta key used to store the UUID of a post.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_UUID = '_tec_slr_uuid';

	/**
	 * The meta key used to store the seat labels of a Ticket as attendee meta.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_ATTENDEE_SEAT_LABEL = '_tec_slr_seat_label';

	/**
	 * The meta key used to store the reservation ID of an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_RESERVATION_ID = '_tec_slr_reservation_id';
}
