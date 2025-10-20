<?php
/**
 * CSV Importer for Tickets Commerce RSVP tickets.
 *
 * @since TBD
 */

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\RSVP\Constants as RSVP_Constants;

/**
 * CSV Importer for Tickets Commerce RSVP tickets.
 *
 * Handles importing RSVP tickets from CSV files with supportf for:
 * - One RSVP per event enforcement
 * - Tickets Commerce RSVP creation
 * - Event Aggregator activity tracking
 *
 * @since TBD
 */
class Tribe__Tickets__CSV_Importer__RSVP_Importer extends Tribe__Events__Importer__File_Importer {

	/**
	 * Required CSV fields for RSVP import.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $required_fields = [ 'event_name', 'ticket_name' ];

	/**
	 * Cache of event names to event objects.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static $event_name_cache = [];

	/**
	 * Cache of ticket name-event ID combinations.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static $ticket_name_cache = [];

	/**
	 * Tickets Commerce Module instance.
	 *
	 * @since TBD
	 *
	 * @var Module
	 */
	protected $tc_module;

	/**
	 * Custom row skip message.
	 *
	 * @since TBD
	 *
	 * @var bool|string
	 */
	protected $row_message = false;

	/**
	 * The class constructor proxy method.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Events__Importer__File_Importer|null $instance    The default instance that would be used for the type.
	 * @param Tribe__Events__Importer__File_Reader        $file_reader The file reader instance.
	 *
	 * @return Tribe__Tickets__CSV_Importer__RSVP_Importer The importer instance.
	 */
	public static function instance( $instance, Tribe__Events__Importer__File_Reader $file_reader ) {
		return new self( $file_reader );
	}

	/**
	 * Resets the class static caches.
	 *
	 * @since TBD
	 */
	public static function reset_cache() {
		self::$event_name_cache  = [];
		self::$ticket_name_cache = [];
	}

	/**
	 * Gets the first Tickets Commerce RSVP for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID to check.
	 *
	 * @return WP_Post|false The RSVP post object or false if none found.
	 */
	protected function get_event_tc_rsvp( $event_id ) {
		$existing_rsvp = new WP_Query(
			[
				'post_type'      => Ticket::POSTTYPE,
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => Ticket::$event_relation_meta_key,
						'value' => $event_id,
					],
					[
						'key'   => '_type',
						'value' => RSVP_Constants::TC_RSVP_TYPE,
					],
				],
			]
		);

		$posts = $existing_rsvp->get_posts();
		return ! empty( $posts ) ? $posts[0] : false;
	}

	/**
	 * Tribe__Tickets__CSV_Importer__RSVP_Importer constructor.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Events__Importer__File_Reader                  $file_reader             The file reader instance.
	 * @param Tribe__Events__Importer__Featured_Image_Uploader|null $featured_image_uploader The image uploader instance.
	 * @param Module|null                                           $tc_module               The Tickets Commerce module instance.
	 */
	public function __construct(
		Tribe__Events__Importer__File_Reader $file_reader,
		Tribe__Events__Importer__Featured_Image_Uploader $featured_image_uploader = null,
		Module $tc_module = null
	) {
		parent::__construct( $file_reader, $featured_image_uploader );
		$this->tc_module = ! empty( $tc_module ) ? $tc_module : tribe( Module::class );

		add_action( 'tribe_aggregator_record_activity_wakeup', [ $this, 'register_rsvp_activity' ] );
		add_filter( 'tribe_aggregator_csv_post_types', [ $this, 'provide_rsvp_post_type_for_aggregator' ], 20 );

		// Register post type immediately if init has already fired, otherwise hook into init.
		if ( did_action( 'init' ) ) {
			$this->register_rsvp_post_type_for_display();
		} else {
			add_action( 'init', [ $this, 'register_rsvp_post_type_for_display' ], 5 );
		}
	}

	/**
	 * Matches an existing RSVP ticket post from a CSV record.
	 *
	 * Gets the first TC RSVP for the event (one per event).
	 *
	 * @since TBD
	 *
	 * @param array $record The CSV record data.
	 *
	 * @return int|bool The ticket post ID if found, false otherwise.
	 */
	public function match_existing_post( array $record ) {
		$event = $this->get_event_from( $record );

		if ( empty( $event ) ) {
			return false;
		}

		$cache_key = 'event-' . $event->ID;

		if ( isset( self::$ticket_name_cache[ $cache_key ] ) ) {
			return self::$ticket_name_cache[ $cache_key ];
		}

		$existing_rsvp = $this->get_event_tc_rsvp( $event->ID );

		if ( empty( $existing_rsvp ) ) {
			self::$ticket_name_cache[ $cache_key ] = false;
			return false;
		}

		self::$ticket_name_cache[ $cache_key ] = $existing_rsvp->ID;

		return $existing_rsvp->ID;
	}

	/**
	 * Updates an existing RSVP ticket post or skips if data matches.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id The post ID.
	 * @param array $record  The CSV record data.
	 */
	public function update_post( $post_id, array $record ) {
		$event    = $this->get_event_from( $record );
		$new_data = $this->get_ticket_data_from( $record );

		// Get existing ticket data.
		$existing_ticket = $this->tc_module->get_ticket( $event->ID, $post_id );

		if ( empty( $existing_ticket ) ) {
			return;
		}

		// Check if data matches existing.
		$data_matches = $this->ticket_data_matches( $existing_ticket, $new_data );

		if ( $data_matches ) {
			// Skip - data is the same.
			$this->track_activity( 'skipped', $post_id );
			return;
		}

		// Update the ticket.
		$new_data['ticket_id'] = $post_id;

		/**
		 * Filters the data for updating an RSVP via CSV import.
		 *
		 * @since TBD
		 *
		 * @param array $new_data The new ticket data.
		 * @param int $post_id The ticket post ID.
		 * @param array $record The CSV record.
		 */
		$new_data = apply_filters( 'tribe_tickets_import_rsvp_update_data', $new_data, $post_id, $record );

		$this->tc_module->ticket_add( $event->ID, $new_data );

		$this->track_activity( 'updated', $post_id );
	}

	/**
	 * Creates a new RSVP ticket from a CSV record.
	 *
	 * @since TBD
	 *
	 * @param array $record The CSV record data.
	 *
	 * @return int|bool Either the new RSVP ticket post ID or `false` on failure.
	 */
	public function create_post( array $record ) {
		$event = $this->get_event_from( $record );
		$data  = $this->get_ticket_data_from( $record );

		/**
		 * Add an opportunity to change the data for the RSVP created via a CSV file
		 *
		 * @since 4.7.3
		 *
		 * @param array
		 */
		$data      = (array) apply_filters( 'tribe_tickets_import_rsvp_data', $data );
		$ticket_id = $this->tc_module->ticket_add( $event->ID, $data );

		$ticket_name = $this->get_value_by_key( $record, 'ticket_name' );
		$cache_key   = $ticket_name . '-' . $event->ID;

		self::$ticket_name_cache[ $cache_key ] = true;

		$this->track_activity( 'created', $ticket_id );

		return $ticket_id;
	}

	/**
	 * Gets the event from a CSV record by title, slug, or ID.
	 *
	 * @since TBD
	 *
	 * @param array $record The CSV record data.
	 *
	 * @return bool|WP_Post The event post object or false if not found.
	 */
	protected function get_event_from( array $record ) {
		$event_name = $this->get_value_by_key( $record, 'event_name' );

		if ( empty( $event_name ) ) {
			return false;
		}

		if ( isset( self::$event_name_cache[ $event_name ] ) ) {
			return self::$event_name_cache[ $event_name ];
		}

		// By title.
		global $wpdb;
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE post_type = %s AND post_title = %s LIMIT 1',
				$wpdb->posts,
				Tribe__Events__Main::POSTTYPE,
				$event_name
			)
		);
		$event = $row ? get_post( $row ) : false;

		if ( empty( $event ) ) {
			// By slug.
			$event = get_page_by_path( $event_name, OBJECT, Tribe__Events__Main::POSTTYPE );
		}
		if ( empty( $event ) ) {
			// By ID.
			$event = get_post( $event_name );
		}

		$event = ! empty( $event ) ? $event : false;

		self::$event_name_cache[ $event_name ] = $event;

		return $event;
	}

	/**
	 * Extracts and formats ticket data from a CSV record.
	 *
	 * @since TBD
	 *
	 * @param array $record The CSV record data.
	 *
	 * @return array The formatted ticket data array.
	 */
	protected function get_ticket_data_from( array $record ) {
		$data                      = [];
		$data['ticket_name']       = $this->get_value_by_key( $record, 'ticket_name' );
		$data['ticket_start_date'] = $this->get_value_by_key( $record, 'ticket_start_sale_date' );
		$data['ticket_end_date']   = $this->get_value_by_key( $record, 'ticket_end_sale_date' );

		// Add TC RSVP specific fields.
		$data['ticket_type']     = RSVP_Constants::TC_RSVP_TYPE;
		$data['ticket_provider'] = Module::class;
		$data['ticket_price']    = 0; // RSVPs are always free.

		$ticket_start_sale_time = $this->get_value_by_key( $record, 'ticket_start_sale_time' );

		if ( ! empty( $data['ticket_start_date'] ) && ! empty( $ticket_start_sale_time ) ) {
			$start_date = new DateTime( $data['ticket_start_date'] . ' ' . $ticket_start_sale_time );

			$data['ticket_start_meridian'] = $start_date->format( 'A' );
			$data['ticket_start_time']     = $start_date->format( 'H:i:00' );
		}

		$ticket_end_sale_time = $this->get_value_by_key( $record, 'ticket_end_sale_time' );

		if ( ! empty( $data['ticket_end_date'] ) && ! empty( $ticket_end_sale_time ) ) {
			$end_date = new DateTime( $data['ticket_end_date'] . ' ' . $ticket_end_sale_time );

			$data['ticket_end_meridian'] = $end_date->format( 'A' );
			$data['ticket_end_time']     = $end_date->format( 'H:i:00' );
		}

		$capacity = $this->get_value_by_key( $record, 'ticket_capacity' );

		$data['tribe-ticket']['capacity'] = $capacity;
		$data['tribe-ticket']['stock']    = $capacity;

		return $data;
	}

	/**
	 * Validates a CSV record before processing.
	 *
	 * Checks for recurring events.
	 *
	 * @since TBD
	 *
	 * @param array $record The CSV record data.
	 *
	 * @return bool True if the record is valid, false otherwise.
	 */
	public function is_valid_record( array $record ) {
		$valid = parent::is_valid_record( $record );
		if ( empty( $valid ) ) {
			return false;
		}

		$event = $this->get_event_from( $record );

		if ( empty( $event ) ) {
			return false;
		}

		if ( function_exists( 'tribe_is_recurring_event' ) ) {
			$is_recurring = tribe_is_recurring_event( $event->ID );

			if ( $is_recurring ) {
				$this->row_message = sprintf(
					// translators: %s: Event title.
					esc_html__( 'Recurring event tickets are not supported, event %s.', 'event-tickets' ),
					$event->post_title
				);
			}

			return ! $is_recurring;
		}
		$this->row_message = false;

		return true;
	}

	/**
	 * Compares ticket data to determine if it matches existing ticket.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $existing_ticket The existing ticket object.
	 * @param array                         $new_data The new ticket data from CSV.
	 *
	 * @return bool True if data matches, false otherwise.
	 */
	protected function ticket_data_matches( $existing_ticket, $new_data ) {
		// Compare ticket name.
		if ( $existing_ticket->name !== $new_data['ticket_name'] ) {
			return false;
		}

		// Compare capacity.
		$existing_capacity = $existing_ticket->capacity();
		$new_capacity      = $new_data['tribe-ticket']['capacity'];
		if ( $existing_capacity != $new_capacity ) {
			return false;
		}

		// Compare start date.
		$existing_start = $existing_ticket->start_date;
		$new_start_date = ! empty( $new_data['ticket_start_date'] ) ? $new_data['ticket_start_date'] : '';
		$new_start_time = ! empty( $new_data['ticket_start_time'] ) ? $new_data['ticket_start_time'] : '';

		if ( ! empty( $new_start_date ) ) {
			$new_start = $new_start_date;
			if ( ! empty( $new_start_time ) ) {
				$new_start .= ' ' . $new_start_time;
			}
			$new_start_timestamp      = strtotime( $new_start );
			$existing_start_timestamp = strtotime( $existing_start );

			if ( $existing_start_timestamp !== $new_start_timestamp ) {
				return false;
			}
		}

		// Compare end date.
		$existing_end = $existing_ticket->end_date;
		$new_end_date = ! empty( $new_data['ticket_end_date'] ) ? $new_data['ticket_end_date'] : '';
		$new_end_time = ! empty( $new_data['ticket_end_time'] ) ? $new_data['ticket_end_time'] : '';

		if ( ! empty( $new_end_date ) ) {
			$new_end = $new_end_date;
			if ( ! empty( $new_end_time ) ) {
				$new_end .= ' ' . $new_end_time;
			}
			$new_end_timestamp      = strtotime( $new_end );
			$existing_end_timestamp = strtotime( $existing_end );

			if ( $existing_end_timestamp !== $new_end_timestamp ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets the message for a skipped row.
	 *
	 * @since TBD
	 *
	 * @param string|int $row The row number.
	 *
	 * @return string The skip message.
	 */
	protected function get_skipped_row_message( $row ) {
		return $this->row_message === false ? parent::get_skipped_row_message( $row ) : $this->row_message;
	}

	/**
	 * Registers the RSVP post type as a trackable activity.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Events__Aggregator__Record__Activity $activity The activity tracker instance.
	 */
	public function register_rsvp_activity( $activity ) {
		$activity->register( Ticket::POSTTYPE, [ 'rsvp', 'rsvp_tickets' ] );
	}

	/**
	 * Tracks activity for the import record.
	 *
	 * @since TBD
	 *
	 * @param string $type The activity type (created, updated, skipped).
	 * @param int    $post_id The post ID.
	 */
	protected function track_activity( $type, $post_id ) {
		if ( ! $this->is_aggregator || empty( $this->aggregator_record ) ) {
			// Track manually since aggregator properties aren't set.
			$this->log( "Activity: {$type} - Post ID: {$post_id}" );

			// Increment our internal counters.
			switch ( $type ) {
				case 'created':
					++$this->created;
					break;
				case 'updated':
					++$this->updated;
					break;
				case 'skipped':
					$this->skipped[] = $post_id;
					break;
			}
			return;
		}

		$this->aggregator_record->meta['activity']->add( 'rsvp', $type, $post_id );
	}

	/**
	 * Provides a mock post type object for 'rsvp' content type in aggregator.
	 *
	 * When the aggregator tries to get post type labels for 'rsvp', it expects a real
	 * WordPress post type object. Since 'rsvp' is just our import type identifier,
	 * we need to provide a mock object based on the actual ticket post type.
	 *
	 * @since TBD
	 *
	 * @param array $post_types Array of post type objects.
	 *
	 * @return array Modified array with RSVP mock object.
	 */
	public function provide_rsvp_post_type_for_aggregator( $post_types ) {
		// Check if we already have an rsvp post type with correct properties.
		foreach ( $post_types as $post_type ) {
			if ( isset( $post_type->name ) && 'rsvp' === $post_type->name ) {
				// Update it with proper labels if needed.
				if ( ! isset( $post_type->labels->singular_name ) ) {
					$post_type->labels  = (object) [
						'name'                    => tribe_get_rsvp_label_plural(),
						'singular_name'           => tribe_get_rsvp_label_singular(),
						'singular_name_lowercase' => tribe_get_rsvp_label_singular_lowercase(),
						'plural_name_lowercase'   => tribe_get_rsvp_label_plural_lowercase(),
					];
					$post_type->show_ui = true;
				}
				return $post_types;
			}
		}

		// Create a mock post type object for display purposes.
		$rsvp_display_type = (object) [
			'name'    => 'rsvp',
			'labels'  => (object) [
				'name'                    => tribe_get_rsvp_label_plural(),
				'singular_name'           => tribe_get_rsvp_label_singular(),
				'singular_name_lowercase' => tribe_get_rsvp_label_singular_lowercase(),
				'plural_name_lowercase'   => tribe_get_rsvp_label_plural_lowercase(),
			],
			'show_ui' => true,
		];

		$post_types[] = $rsvp_display_type;
		return $post_types;
	}

	/**
	 * Registers 'rsvp' as a hidden post type for display purposes.
	 *
	 * This allows get_post_type_object('rsvp') to return a valid object
	 * when the aggregator display logic tries to get labels. The post type
	 * is hidden (public => false, show_ui => true) so it only exists for
	 * internal WordPress post type registry purposes without affecting the
	 * admin UI or front-end queries.
	 *
	 * @since TBD
	 */
	public function register_rsvp_post_type_for_display() {
		// Only register if not already registered.
		if ( post_type_exists( 'rsvp' ) ) {
			return;
		}

		register_post_type(
			'rsvp',
			[
				'labels'            => [
					'name'                    => tribe_get_rsvp_label_plural(),
					'singular_name'           => tribe_get_rsvp_label_singular(),
					'singular_name_lowercase' => tribe_get_rsvp_label_singular_lowercase(),
					'plural_name_lowercase'   => tribe_get_rsvp_label_plural_lowercase(),
				],
				'public'            => false,
				'show_ui'           => false,
				'show_in_menu'      => false,
				'show_in_nav_menus' => false,
				'show_in_admin_bar' => false,
				'rewrite'           => false,
				'query_var'         => false,
				'can_export'        => false,
				'has_archive'       => false,
			]
		);
	}
}
