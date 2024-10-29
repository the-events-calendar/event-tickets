<?php
/**
 * Information repository and handler of post meta data for the plugin.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

/**
 * Class Meta.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */
class Meta {
	/**
	 * The meta key used to store the enabled state of seat layouts and reservations for a post.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const META_KEY_ENABLED = '_tec_slr_enabled';

	/**
	 * The meta key used to store the layout ID of a post.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const META_KEY_LAYOUT_ID = '_tec_slr_layout';

	/**
	 * The meta key used to store the seat type of a Ticket.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const META_KEY_SEAT_TYPE = '_tec_slr_seat_type';

	/**
	 * The meta key used to store the UUID of a post.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const META_KEY_UUID = '_tec_slr_uuid';

	/**
	 * The meta key used to store the seat labels of a Ticket as attendee meta.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const META_KEY_ATTENDEE_SEAT_LABEL = '_tec_slr_seat_label';

	/**
	 * The meta key used to store the reservation ID of an attendee.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public const META_KEY_RESERVATION_ID = '_tec_slr_reservation_id';
}
