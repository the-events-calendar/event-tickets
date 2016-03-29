<?php


class Tribe__Tickets__CSV_Importer__RSVP_Importer extends Tribe__Events__Importer__File_Importer {

	/**
	 * @var Tribe__Events__Importer__File_Reader
	 */
	private $file_reader;

	/**
	 * The class constructor proxy method.
	 *
	 * @param                                      $instance
	 * @param Tribe__Events__Importer__File_Reader $file_reader
	 *
	 * @return Tribe__Tickets__CSV_Importer__RSVP_Importer
	 */
	public static function instance( $instance, Tribe__Events__Importer__File_Reader $file_reader ) {
		return new self( $file_reader );
	}

	public function match_existing_post( array $record ) {
		// try to match a ticket by event name and ticket name
		$start_date = $this->get_event_start_date( $record );
		$end_date   = $this->get_event_end_date( $record );
		$all_day    = $this->get_boolean_value_by_key( $record, 'event_all_day' );

		// Base query - only the meta query will be different
		$query_args = array(
			'post_type'      => Tribe__Events__Main::POSTTYPE,
			'post_title'     => $this->get_value_by_key( $record, 'event_name' ),
			'fields'         => 'ids',
			'posts_per_page' => 1,
		);

		// When trying to find matches for all day events, the comparison should only be against the date
		// component only since a) the time is irrelevant and b) the time may have been adjusted to match
		// the eod cutoff setting
		if ( Tribe__Date_Utils::is_all_day( $all_day ) ) {
			$meta_query = array(
				array(
					'key'     => '_EventStartDate',
					'value'   => $this->get_event_start_date( $record, true ),
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_EventAllDay',
					'value'   => 'yes',
				),
			);
			// For regular, non-all day events, use the full date *and* time in the start date comparison
		} else {
			$meta_query = array(
				array(
					'key'   => '_EventStartDate',
					'value' => $start_date,
				),
			);
		}

		// Optionally use the end date/time for matching, where available
		if ( ! empty( $end_date ) && ! $all_day ) {
			$meta_query[] = array(
				'key'   => '_EventEndDate',
				'value' => $end_date,
			);
		}

		$query_args['meta_query'] = $meta_query;

		add_filter( 'posts_search', array( $this, 'filter_query_for_title_search' ), 10, 2 );
		$matches = get_posts( $query_args );
		remove_filter( 'posts_search', array( $this, 'filter_query_for_title_search' ), 10, 2 );

		if ( empty( $matches ) ) {
			return 0;
		}

		return reset( $matches );
	}

	protected function update_post( $post_id, array $record ) {
		// TODO: Implement update_post() method.
	}

	protected function create_post( array $record ) {
		// TODO: Implement create_post() method.
	}
}