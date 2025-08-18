<?php

namespace TEC\Tickets\Commerce\Repositories;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Repository;
use Tribe__Repository__Interface;
use WP_Post;

/**
 * Class Tickets Repository.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Repositories
 */
class Tickets_Repository extends Tribe__Repository {
	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	protected $filter_name = 'tc_tickets';

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	protected $key_name = Commerce::ABBR;

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		// Set the order post type.
		$this->default_args['post_type']   = Ticket::POSTTYPE;
		$this->default_args['post_status'] = 'publish';
		$this->create_args['post_status']  = 'publish';
		$this->create_args['post_type']    = Ticket::POSTTYPE;

		/** @var \Tribe__Tickets__Tickets_Handler $ticket_handler */
		$ticket_handler = tribe( 'tickets.handler' );

		// Add event specific aliases.
		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases,
			[
				'event_id'              => Ticket::$event_relation_meta_key,
				'event'                 => Ticket::$event_relation_meta_key,
				'show_description'      => Ticket::$show_description_meta_key,
				'start_date'            => Ticket::START_DATE_META_KEY,
				'end_date'              => Ticket::END_DATE_META_KEY,
				'start_time'            => Ticket::START_TIME_META_KEY,
				'end_time'              => Ticket::END_TIME_META_KEY,
				'sku'                   => Ticket::$sku_meta_key,
				'stock'                 => Ticket::$stock_meta_key,
				'price'                 => Ticket::$price_meta_key,
				'sales'                 => Ticket::$sales_meta_key,
				'stock_mode'            => Ticket::$stock_mode_meta_key,
				'stock_status'          => Ticket::$stock_status_meta_key,
				'allow_backorders'      => Ticket::$allow_backorders_meta_key,
				'manage_stock'          => Ticket::$should_manage_stock_meta_key,
				'type'                  => Ticket::$type_meta_key,
				'sale_price_enabled'    => Ticket::$sale_price_checked_key,
				'sale_price'            => Ticket::$sale_price_key,
				'sale_price_start_date' => Ticket::$sale_price_start_date_key,
				'sale_price_end_date'   => Ticket::$sale_price_end_date_key,
				'capacity'              => $ticket_handler->key_capacity,
			]
		);

		$this->schema = array_merge(
			$this->schema,
			[
				'event' => [ $this, 'filter_by_event' ],
			],
		);

		$this->add_simple_meta_schema_entry( 'event_id', Ticket::$event_relation_meta_key );
		$this->add_simple_meta_schema_entry( 'start_date', Ticket::START_DATE_META_KEY );
		$this->add_simple_meta_schema_entry( 'end_date', Ticket::END_DATE_META_KEY );
		$this->add_simple_meta_schema_entry( 'start_time', Ticket::START_TIME_META_KEY );
		$this->add_simple_meta_schema_entry( 'end_time', Ticket::END_TIME_META_KEY );
		$this->add_simple_meta_schema_entry( 'sku', Ticket::$sku_meta_key );
		$this->add_simple_meta_schema_entry( 'sale_price_enabled', Ticket::$sale_price_checked_key );
		$this->add_simple_meta_schema_entry( 'stock', Ticket::$stock_meta_key );
		$this->add_simple_meta_schema_entry( 'show_description', Ticket::$show_description_meta_key );
		$this->add_simple_meta_schema_entry( 'price', Ticket::$price_meta_key );
		$this->add_simple_meta_schema_entry( 'sales', Ticket::$sales_meta_key );
		$this->add_simple_meta_schema_entry( 'stock_mode', Ticket::$stock_mode_meta_key );
		$this->add_simple_meta_schema_entry( 'stock_status', Ticket::$stock_status_meta_key );
		$this->add_simple_meta_schema_entry( 'allow_backorders', Ticket::$allow_backorders_meta_key );
		$this->add_simple_meta_schema_entry( 'manage_stock', Ticket::$should_manage_stock_meta_key );
		$this->add_simple_meta_schema_entry( 'type', Ticket::$type_meta_key );
		$this->add_simple_meta_schema_entry( 'sale_price', Ticket::$sale_price_key );
		$this->add_simple_meta_schema_entry( 'sale_price_start_date', Ticket::$sale_price_start_date_key );
		$this->add_simple_meta_schema_entry( 'sale_price_end_date', Ticket::$sale_price_end_date_key );
		$this->add_simple_meta_schema_entry( 'capacity', $ticket_handler->key_capacity );

		/**
		 * Filters the schema for the tickets repository.
		 *
		 * @since 5.26.0
		 *
		 * @param Tribe__Repository $this The repository object.
		 */
		do_action( 'tec_tickets_commerce_repository_ticket_schema', $this );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function format_item( $id ) {
		$formatted = null === $this->formatter
			? tec_tc_get_ticket( $id )
			: $this->formatter->format_item( $id );

		/**
		 * Filters a single formatted ticket result.
		 *
		 * @since 5.1.9
		 *
		 * @param mixed|WP_Post                $formatted  The formatted event result, usually a post object.
		 * @param int                          $id         The formatted post ID.
		 * @param Tribe__Repository__Interface $repository The current repository object.
		 */
		$formatted = apply_filters( 'tec_tickets_commerce_repository_ticket_format', $formatted, $id, $this );

		return $formatted;
	}

	/**
	 * Filters tickets by a specific event.
	 *
	 * @since 5.2.2
	 * @since 5.8.0 Apply the `tec_tickets_repository_filter_by_event_id` filter.
	 *
	 * @param int|array $event_id The post ID or array of post IDs to filter by.
	 */
	public function filter_by_event( $event_id ) {
		/**
		 * Filters the post ID used to filter Commerce tickets.
		 *
		 * By default, only the ticketed post ID is used. This filter allows fetching tickets from related posts.
		 *
		 * @since 5.8.0
		 *
		 * @param int|array          $event_id   The event ID or array of event IDs to filter by.
		 * @param Tickets_Repository $repository The current repository object.
		 */
		$event_id = apply_filters( 'tec_tickets_repository_filter_by_event_id', $event_id, $this );

		if ( is_array( $event_id ) && empty( $event_id ) ) {
			// Bail early if the array is empty.
			return;
		}

		if ( is_numeric( $event_id ) ) {
			$event_id = [ $event_id ];
		}

		$this->by( 'meta_in', Ticket::$event_relation_meta_key, $event_id );
	}
}
