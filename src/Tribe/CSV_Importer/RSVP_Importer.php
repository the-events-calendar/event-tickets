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

	protected function match_existing_post( array $record ) {
		// TODO: Implement match_existing_post() method.
	}

	protected function update_post( $post_id, array $record ) {
		// TODO: Implement update_post() method.
	}

	protected function create_post( array $record ) {
		// TODO: Implement create_post() method.
	}
}