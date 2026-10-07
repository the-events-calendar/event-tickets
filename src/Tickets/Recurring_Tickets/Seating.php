<?php
/**
 * Keeps Seating and recurring event tickets apart.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Seating\Meta;

/**
 * An event with a seating layout gets no recurring event ticket, and an event with recurring event tickets gets no
 * seating layout, as Seating already keeps itself off Series.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Seating {
	/**
	 * Whether each event read in this request has templates, by post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,bool>
	 */
	private array $has_templates = [];

	/**
	 * Whether a post has a seating layout.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool Whether the post has a seating layout.
	 */
	public function has_layout( int $post_id ): bool {
		return '' !== (string) get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );
	}

	/**
	 * Turns seating off for an event with recurring event tickets, and for its dates.
	 *
	 * @since TBD
	 *
	 * @param bool $enabled Whether the post has a seating layout.
	 * @param int  $post_id The post ID, an event's or a date's.
	 *
	 * @return bool Whether the post uses seating.
	 */
	public function filter_enabled( $enabled, $post_id ): bool {
		return $enabled && ! $this->has_templates( (int) Occurrence::normalize_id( (int) $post_id ) );
	}

	/**
	 * Refuses a seating layout to an event with recurring event tickets.
	 *
	 * @since TBD
	 *
	 * @param mixed  $check     Null to let the write go on, anything else to short-circuit it.
	 * @param int    $object_id The post ID.
	 * @param string $meta_key  The meta key.
	 *
	 * @return mixed False to refuse the layout, the value passed otherwise.
	 */
	public function refuse_layout( $check, $object_id, $meta_key ) {
		if ( null !== $check || Meta::META_KEY_LAYOUT_ID !== $meta_key || ! $this->has_templates( (int) $object_id ) ) {
			return $check;
		}

		return false;
	}

	/**
	 * Forgets whether a ticket's event has templates, once the ticket was saved or deleted.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ticket's event.
	 *
	 * @return void
	 */
	public function forget( $post_id ): void {
		unset( $this->has_templates[ (int) $post_id ] );
	}

	/**
	 * Forgets whether a deleted ticket's event has templates.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket.
	 * @param int $post_id   The ticket's event.
	 *
	 * @return void
	 */
	public function forget_deleted( $ticket_id, $post_id ): void {
		$this->forget( $post_id );
	}

	/**
	 * Whether a post has a recurring event ticket template, read once per request.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool Whether the post has a template.
	 */
	private function has_templates( int $post_id ): bool {
		if ( $post_id < 1 ) {
			return false;
		}

		if ( ! isset( $this->has_templates[ $post_id ] ) ) {
			$this->has_templates[ $post_id ] = (bool) get_posts(
				[
					'post_type'      => Commerce_Ticket::POSTTYPE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The two keys ET relates a ticket by.
					'meta_query'     => [
						[
							'key'   => Commerce_Ticket::$event_relation_meta_key,
							'value' => $post_id,
						],
						[
							'key'   => '_type',
							'value' => Template_Guard::TICKET_TYPE,
						],
					],
				]
			);
		}

		return $this->has_templates[ $post_id ];
	}
}
