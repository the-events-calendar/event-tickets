<?php
/**
 * Handles the CRUD operations of Series Passes meta.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Tickets\Flexible_Tickets\Enums\Ticket_To_Post_Relationship_Keys;
use WP_Post;

/**
 * Class Meta.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class Meta {

	/**
	 * A reference to the Series Passes repository.
	 *
	 * @since 5.8.0
	 *
	 * @var Repository
	 */
	private Repository $repository;

	/**
	 * Meta constructor.
	 *
	 * since 5.8.0
	 *
	 * @param Repository $repository A reference to the Series Passes repository.
	 */
	public function __construct( Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Updates a Series Pass end meta, end date or time, based on the dynamic flag.
	 *
	 * @since 5.8.0
	 *
	 * @param int    $ticket_id The post ID of the Series Pass.
	 * @param string $meta_key The meta key to update.
	 * @param bool   $dynamic Whether the end date or time meta should be dynamic or not.
	 *
	 * @return void The Series Pass end meta is updated.
	 */
	public function update_end_meta( int $ticket_id, string $meta_key, bool $dynamic ): void {
		$dynamic_meta_key = $meta_key === '_ticket_end_date' ? '_dynamic_end_date' : '_dynamic_end_time';

		if ( ! $dynamic ) {
			update_post_meta( $ticket_id, $dynamic_meta_key, '0' );

			return;
		}

		// Set the end date dynamically from the start date of the last Occurrence in the Series.
		$last       = $this->repository->get_last_occurrence_by_ticket( $ticket_id );
		$format     = $meta_key === '_ticket_end_date' ? 'Y-m-d' : 'H:i:s';
		$meta_value = $last instanceof WP_Post ? $last->dates->start->format( $format ) : '';

		update_post_meta( $ticket_id, $meta_key, $meta_value );
		update_post_meta( $ticket_id, $dynamic_meta_key, '1' );
	}

	/**
	 * Updates a Series Pass end meta, date or time, depending on the meta key.
	 *
	 * @since 5.8.0
	 *
	 * @param int    $ticket_id The post ID of the Series Pass.
	 * @param string $meta_key The meta key to update.
	 * @param        $meta_value The meta value to update.
	 *
	 * @return void The Series Pass end meta is updated.
	 */
	public function update_pass_meta( int $ticket_id, string $meta_key, $meta_value ) {
		$relationship_meta_keys = Ticket_To_Post_Relationship_Keys::all();
		if ( ! in_array(
			$meta_key,
			[ '_ticket_end_date', '_ticket_end_time', ...$relationship_meta_keys ],
			true
		) ) {
			return;
		}

		if ( in_array( $meta_key, $relationship_meta_keys, true ) ) {
			// Refresh the meta when the relationship is created or updated, let the following code work.
			$meta_key   = '_ticket_end_date';
			$meta_value = get_post_meta( $ticket_id, '_ticket_end_date', true );
		}

		if ( $meta_key === '_ticket_end_date' ) {
			// We're updating the end date: if empty it's dynamic.
			$end_date_is_dynamic = empty( $meta_value );
			$this->update_end_meta( $ticket_id, '_ticket_end_date', $end_date_is_dynamic );
			// Also update the end time and it's dynamic flag.
			$this->update_end_meta( $ticket_id, '_ticket_end_time', $end_date_is_dynamic );

			return;
		}

		// We're updating the end time: read the dynamic flag of the end date.
		$end_date_is_dynamic = get_post_meta( $ticket_id, '_dynamic_end_date', true );
		$this->update_end_meta( $ticket_id, $meta_key, $end_date_is_dynamic );
	}
}