<?php
/**
 * Tickets tag for the TEC REST API V1.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Tags
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Tags;

use TEC\Common\REST\TEC\V1\Abstracts\Tag;

/**
 * Tickets tag for the TEC REST API V1.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Tags
 */
class Tickets_Tag extends Tag {
	/**
	 * Returns the tag name.
	 *
	 * @since 5.26.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Tickets';
	}

	/**
	 * Returns the tag.
	 *
	 * @since 5.26.0
	 *
	 * @return array
	 */
	public function get(): array {
		return [
			'name'        => $this->get_name(),
			'description' => __( 'These operations are introduced by Event Tickets.', 'event-tickets' ),
		];
	}

	/**
	 * Returns the priority of the tag.
	 *
	 * @since 5.26.0
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10;
	}
}
