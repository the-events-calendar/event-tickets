<?php

class Tribe__Tickets__CSV_Importer__Column_Names {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * The class singleton constructor.
	 *
	 * @return Tribe__Tickets__CSV_Importer__Column_Names
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds RSVP column names to the importer mapping options.
	 *
	 * @param array $column_names
	 *
	 * @return array
	 */
	public function filter_rsvp_column_names( array $column_names ) {
		$column_names = array_merge( $column_names, [
			'event_name'              => esc_html__( 'Event Name or ID or Slug', 'event-tickets' ),
			'ticket_name'             => esc_html__( 'Ticket Name', 'event-tickets' ),
			'ticket_description'      => esc_html__( 'Ticket Description', 'event-tickets' ),
			'ticket_show_description' => esc_html__( 'Ticket Show Description', 'event-tickets' ),
			'ticket_start_sale_date'  => esc_html__( 'Ticket Start Sale Date', 'event-tickets' ),
			'ticket_start_sale_time'  => esc_html__( 'Ticket Start Sale Time', 'event-tickets' ),
			'ticket_end_sale_date'    => esc_html__( 'Ticket End Sale Date', 'event-tickets' ),
			'ticket_end_sale_time'    => esc_html__( 'Ticket End Sale Time', 'event-tickets' ),
			'ticket_stock'            => esc_html__( 'Ticket Stock', 'event-tickets' ),
			'ticket_capacity'         => esc_html__( 'Ticket Capacity', 'event-tickets' ),
		] );

		return $column_names;
	}

	/**
	 * Adds RSVP column mapping data to the csv_column_mapping array that gets output via JSON
	 *
	 * @param array $mapping Mapping data indexed by CSV import type
	 *
	 * @return array
	 */
	public function filter_rsvp_column_mapping( array $mapping ) {
		$mapping['rsvp_tickets'] = get_option( 'tribe_events_import_column_mapping_rsvp_tickets', [] );

		return $mapping;
	}
}
