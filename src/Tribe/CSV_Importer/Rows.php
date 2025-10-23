<?php

/**
 * Class Tribe__Tickets__CSV_Importer__Rows
 *
 * Modifies the CSV Importer import option rows.
 */
class Tribe__Tickets__CSV_Importer__Rows {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * The class singleton constructor.
	 *
	 * @return Tribe__Tickets__CSV_Importer__Rows
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array $import_options
	 *
	 * @return array
	 */
	public function filter_import_options_rows( array $import_options ) {
		$import_options['rsvp'] = esc_html( tribe_get_rsvp_label_plural( 'import_type' ) );

		return $import_options;
	}

	/**
	 * Filters the CSV post types to add RSVP tickets
	 *
	 * @param array $post_types Array of post type objects
	 *
	 * @return array
	 */
	public function filter_csv_post_types( array $post_types ) {
		$post_type = get_post_type_object( 'tec_tc_ticket' );

		if ( empty( $post_type ) ) {
			return $post_types;
		}

		// Create a clone to avoid modifying the original post type object.
		$rsvp_post_type               = clone $post_type;
		$rsvp_post_type->name         = 'rsvp';
		$rsvp_post_type->labels->name = esc_html( tribe_get_rsvp_label_plural( 'post_type_label' ) );
		$post_types[]                 = $rsvp_post_type;

		return $post_types;
	}
}
