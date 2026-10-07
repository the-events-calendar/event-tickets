<?php
/**
 * Recurring event tickets in the calendar views.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Gives each date with recurring event tickets its own availability in the views, and reads a page's rows at once.
 *
 * The views cache a date's ticket model under its event's ID; a date with rows has availability of its own.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Views {
	/**
	 * The prefix of the key holding an event's version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const VERSION_KEY = 'tec_tickets_recurring_tickets_views_version_';

	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The versions of the events' dates' cached ticket models read in this request, by event.
	 *
	 * @since TBD
	 *
	 * @var array<int,string>
	 */
	private array $versions = [];

	/**
	 * Views constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Caches a date's ticket model under the date's own ID and its event's version, when the date has rows.
	 *
	 * @since TBD
	 *
	 * @param string $cache_id The part of the key that names the post: its event's ID, for a date.
	 * @param int    $post_id  The ID of the post the model is for.
	 *
	 * @return string The part of the key that names the post.
	 */
	public function cache_id( $cache_id, $post_id ): string {
		$post_id  = (int) $post_id;
		$event_id = (int) $cache_id;

		if ( $post_id === $event_id || ! $this->rows->get_by_occurrence( $this->occurrence_id( $post_id ) ) ) {
			return (string) $cache_id;
		}

		return $post_id . '_' . $this->version( $event_id );
	}

	/**
	 * Retires the cached ticket models of an event's dates, once Sync changed their rows.
	 *
	 * One write, whatever the number of dates: the dates' keys carry the event's version, which changes.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event's post ID.
	 *
	 * @return void
	 */
	public function forget_dates( $post_id ): void {
		$post_id = (int) $post_id;
		$version = uniqid( '', true );

		// The dates' entries last a day: so does the version that names them.
		tec_kv_cache()->set( self::VERSION_KEY . $post_id, $version, DAY_IN_SECONDS );
		$this->versions[ $post_id ] = $version;
	}

	/**
	 * Reads, in one query, the rows of every date an events query found, before each event is decorated.
	 *
	 * The views fetch events by ID, then decorate them one by one: without this, each date reads its own rows.
	 *
	 * @since TBD
	 *
	 * @param mixed $results The query's results: post IDs, or posts.
	 *
	 * @return void
	 */
	public function prime_dates( $results ): void {
		if ( ! is_array( $results ) || ! $results ) {
			return;
		}

		$occurrence_ids = [];
		foreach ( $results as $result ) {
			$post_id = is_object( $result ) ? (int) ( $result->ID ?? 0 ) : (int) $result;

			if ( tribe( Provisional_Post::class )->is_provisional_post_id( $post_id ) ) {
				$occurrence_ids[] = $this->occurrence_id( $post_id );
			}
		}

		if ( $occurrence_ids ) {
			$this->rows->prime_occurrences( $occurrence_ids );
		}
	}

	/**
	 * Returns the version of an event's dates' cached ticket models.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event's post ID.
	 *
	 * @return string The version, empty until Sync first changes the event's rows.
	 */
	private function version( int $event_id ): string {
		if ( ! isset( $this->versions[ $event_id ] ) ) {
			$this->versions[ $event_id ] = tec_kv_cache()->get( self::VERSION_KEY . $event_id );
		}

		return $this->versions[ $event_id ];
	}

	/**
	 * Returns the occurrence ID of a date's provisional post ID.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The provisional post ID.
	 *
	 * @return int The occurrence ID, 0 for an ID that is not a date's.
	 */
	private function occurrence_id( int $post_id ): int {
		if ( ! tribe( Provisional_Post::class )->is_provisional_post_id( $post_id ) ) {
			return 0;
		}

		return (int) tribe( ID_Generator::class )->unprovide_id( $post_id );
	}
}
