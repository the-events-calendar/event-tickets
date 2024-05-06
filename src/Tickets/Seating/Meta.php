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
	public const META_KEY_ENABLED = 'tec_slr_enabled';

	/**
	 * The meta key used to store the layout ID of a post.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY_LAYOUT_ID = 'tec_slr_layout_id';
}