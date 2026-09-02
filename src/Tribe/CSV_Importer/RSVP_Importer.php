<?php
/**
 * RSVP CSV Importer.
 *
 * @since 4.7
 */

use TEC\Tickets\Commerce\Module as Commerce_Module;
use TEC\Tickets\RSVP\Controller as RSVP_Controller;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class Tribe__Tickets__CSV_Importer__RSVP_Importer
 */
class Tribe__Tickets__CSV_Importer__RSVP_Importer extends Tribe__Events__Importer__File_Importer {

	/**
	 * @var array
	 */
	protected $required_fields = [ 'event_name' ];

	/**
	 * @var array
	 */
	protected static $event_name_cache = [];

	/**
	 * @var array
	 */
	protected static $ticket_name_cache = [];

	/**
	 * @var Tribe__Tickets__RSVP
	 */
	protected $rsvp_tickets;

	/**
	 * @var bool|string
	 */
	protected $row_message = false;

	/**
	 * The class constructor proxy method.
	 *
	 * @param Tribe__Events__Importer__File_Importer|null $instance    The default instance that would be used for the type.
	 * @param Tribe__Events__Importer__File_Reader        $file_reader The file reader instance.
	 *
	 * @return Tribe__Tickets__CSV_Importer__RSVP_Importer
	 */
	public static function instance( $instance, Tribe__Events__Importer__File_Reader $file_reader ) {
		return new self( $file_reader );
	}

	/**
	 * Resets that class static caches
	 */
	public static function reset_cache() {
		self::$event_name_cache  = [];
		self::$ticket_name_cache = [];
	}

	/**
	 * Whether the site is on RSVP V2 (Commerce) - i.e. the import should produce a TC-RSVP ticket.
	 *
	 * Version is hook-verifiable via `tec_tickets_rsvp_version`. Tests can control it
	 * without touching the real option:
	 * `add_filter( 'tec_tickets_rsvp_version', fn(): string => get_option( 'test_rsvp_version', RSVP_Controller::VERSION_1 ) );`
	 * then `update_option( 'test_rsvp_version', RSVP_Controller::VERSION_2 )` to force V2.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function is_rsvp_v2(): bool {
		if ( ! function_exists( 'tec_tickets_commerce_is_enabled' ) || ! tec_tickets_commerce_is_enabled() ) {
			return false;
		}

		$default = RSVP_Controller::VERSION_1;
		$version = tribe_get_option( RSVP_Controller::VERSION_OPTION_KEY, $default );
		$version = apply_filters( 'tec_tickets_rsvp_version', $version );

		return RSVP_Controller::VERSION_2 === $version;
	}

	/**
	 * Tribe__Tickets__CSV_Importer__RSVP_Importer constructor.
	 *
	 * @since 5.29.0 Made $featured_image_uploader and $rsvp_tickets explicitly nullable.
	 *
	 * @param Tribe__Events__Importer__File_Reader                  $file_reader             The file reader instance.
	 * @param Tribe__Events__Importer__Featured_Image_Uploader|null $featured_image_uploader The featured image uploader.
	 * @param Tribe__Tickets__RSVP|null                             $rsvp_tickets            The RSVP tickets instance.
	 */
	public function __construct(
		Tribe__Events__Importer__File_Reader $file_reader,
		?Tribe__Events__Importer__Featured_Image_Uploader $featured_image_uploader = null,
		?Tribe__Tickets__RSVP $rsvp_tickets = null
	) {
		parent::__construct( $file_reader, $featured_image_uploader );
		$this->rsvp_tickets = ! empty( $rsvp_tickets ) ? $rsvp_tickets : Tribe__Tickets__RSVP::get_instance();

		// Hook registration is handled by TEC\Tickets\RSVP\V1\Controller.
	}

	/**
	 * Matches an existing post based on the record.
	 *
	 * @since TBD
	 *
	 * @param array $record The record data.
	 *
	 * @return bool
	 */
	public function match_existing_post( array $record ) {
		$event = $this->get_event_from( $record );

		if ( empty( $event ) ) {
			return false;
		}

		return $this->is_rsvp_v2()
			? $this->match_existing_post_v2( $record, $event )
			: $this->match_existing_post_v1( $record, $event );
	}

	/**
	 * V2: one RSVP per event - match if any TC-RSVP ticket exists for the event.
	 *
	 * @since TBD
	 *
	 * @param array   $record The record data.
	 * @param WP_Post $event  The event post.
	 *
	 * @return bool
	 */
	private function match_existing_post_v2( array $record, WP_Post $event ): bool {
		$cache_key = 'v2-' . $event->ID;
		$cached    = $this->get_cached_match( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		$match = $this->has_rsvp_for_event_v2( $event );

		return $this->cache_match( $cache_key, $match );
	}

	/**
	 * V1: match by ticket_name + event (legacy).
	 *
	 * @since TBD
	 *
	 * @param array   $record The record data.
	 * @param WP_Post $event  The event post.
	 *
	 * @return bool
	 */
	private function match_existing_post_v1( array $record, WP_Post $event ): bool {
		$ticket_name = $this->get_value_by_key( $record, 'ticket_name' );
		$cache_key   = $ticket_name . '-' . $event->ID;

		$cached = $this->get_cached_match( $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		$ticket_post = ( new WP_Query(
			[
				'post_type'      => $this->rsvp_tickets->ticket_object,
				'post_title'     => $ticket_name,
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		) )->get_posts()[0] ?? false;

		if ( empty( $ticket_post ) ) {
			return $this->cache_match( $cache_key, false );
		}

		$ticket = $this->rsvp_tickets->get_ticket( $event->ID, $ticket_post->ID );

		$match = $ticket instanceof Tribe__Tickets__Ticket_Object && $ticket->get_event() == $event;

		return $this->cache_match( $cache_key, $match );
	}

	/**
	 * Check if an event already has a V2 TC-RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $event The event post.
	 *
	 * @return bool
	 */
	private function has_rsvp_for_event_v2( WP_Post $event ): bool {
		$repo = tribe( 'tickets.ticket-repository.rsvp' );
		if ( $repo && $repo->by( 'event', $event->ID )->found() ) {
			return true;
		}

		// Fallback: direct query if repository not available.
		$found = ( new WP_Query(
			[
				'post_type'      => 'tec_tc_ticket',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One-off fallback lookup, limited to 1 result.
				'meta_query'     => [
					[
						'key'   => '_type',
						'value' => RSVP_V2_Constants::TC_RSVP_TYPE,
					],
					[
						'key'   => '_tec_tickets_commerce_event',
						'value' => $event->ID,
					],
				],
			]
		) )->get_posts();

		return ! empty( $found );
	}

	/**
	 * Get a cached match result.
	 *
	 * @since TBD
	 *
	 * @param string $cache_key Cache key.
	 *
	 * @return bool|null Cached value or null if not cached.
	 */
	private function get_cached_match( string $cache_key ): ?bool {
		return self::$ticket_name_cache[ $cache_key ] ?? null;
	}

	/**
	 * Cache a match result and return it.
	 *
	 * @since TBD
	 *
	 * @param string $cache_key Cache key.
	 * @param bool   $is_match  Match result.
	 *
	 * @return bool The same match value (for chaining).
	 */
	private function cache_match( string $cache_key, bool $is_match ): bool {
		self::$ticket_name_cache[ $cache_key ] = $is_match;

		return $is_match;
	}

	/**
	 * Updates an existing post.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $record  The record data.
	 */
	public function update_post( $post_id, array $record ) {
		// Nothing is updated in existing tickets.
		if ( $this->is_aggregator && ! empty( $this->aggregator_record ) ) {
			$this->aggregator_record->meta['activity']->add( 'tribe_rsvp_tickets', 'skipped', $post_id );
		}
	}

	/**
	 * Creates a new RSVP ticket post.
	 *
	 * @since TBD Added V2 handling via `is_rsvp_v2()` dispatcher.
	 *
	 * @param array $record The record data.
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
		$data = (array) apply_filters( 'tribe_tickets_import_rsvp_data', $data );

		$ticket_id = $this->is_rsvp_v2()
			? $this->create_post_v2( $record, $event, $data )
			: $this->create_post_v1( $record, $event, $data );

		if ( $this->is_aggregator && ! empty( $this->aggregator_record ) ) {
			$this->aggregator_record->meta['activity']->add( 'rsvp_tickets', 'created', $ticket_id );
		}

		return $ticket_id;
	}

	/**
	 * Create via Commerce (V2).
	 *
	 * @since TBD
	 *
	 * @param array   $record The record data.
	 * @param WP_Post $event  The event.
	 * @param array   $data   Ticket data.
	 *
	 * @return int
	 */
	private function create_post_v2( array $record, WP_Post $event, array $data ): int {
		$ticket_id = Commerce_Module::get_instance()->ticket_add( $event->ID, $data );

		if ( $ticket_id ) {
			self::$ticket_name_cache[ 'v2-' . $event->ID ] = true;

			$tickets_handler = tribe( 'tickets.handler' );
			update_post_meta( $event->ID, $tickets_handler->key_provider_field, Commerce_Module::class );
		}

		return (int) $ticket_id;
	}

	/**
	 * Create via legacy RSVP (V1).
	 *
	 * @since TBD
	 *
	 * @param array   $record The record data.
	 * @param WP_Post $event  The event.
	 * @param array   $data   Ticket data.
	 *
	 * @return int
	 */
	private function create_post_v1( array $record, WP_Post $event, array $data ): int {
		$ticket_id   = $this->rsvp_tickets->ticket_add( $event->ID, $data );
		$ticket_name = $this->get_value_by_key( $record, 'ticket_name' );
		self::$ticket_name_cache[ $ticket_name . '-' . $event->ID ] = true;

		return (int) $ticket_id;
	}

	/**
	 * Gets the event from the record.
	 *
	 * @param array $record The record data.
	 *
	 * @return bool|WP_Post
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
	 * Gets the ticket data from the record - dispatcher.
	 *
	 * @since TBD
	 *
	 * @param array $record The record data.
	 *
	 * @return array
	 */
	protected function get_ticket_data_from( array $record ) {
		return $this->is_rsvp_v2()
			? $this->get_ticket_data_from_v2( $record )
			: $this->get_ticket_data_from_v1( $record );
	}

	/**
	 * V2 data shape - Commerce TC-RSVP.
	 *
	 * @since TBD
	 *
	 * @param array $record The record data.
	 *
	 * @return array
	 */
	private function get_ticket_data_from_v2( array $record ): array {
		$data = [
			'ticket_name'        => 'RSVP',
			'ticket_description' => '',
			'ticket_price'       => 0,
			'ticket_type'        => RSVP_V2_Constants::TC_RSVP_TYPE,
			'ticket_provider'    => Commerce_Module::class,
			'show_not_going'     => false,
		];

		$start_date = $this->get_value_by_key( $record, 'ticket_start_sale_date' );
		$end_date   = $this->get_value_by_key( $record, 'ticket_end_sale_date' );

		if ( ! empty( $start_date ) ) {
			$data['ticket_start_date'] = $start_date;
		}
		if ( ! empty( $end_date ) ) {
			$data['ticket_end_date'] = $end_date;
		}

		$ticket_start_sale_time = $this->get_value_by_key( $record, 'ticket_start_sale_time' );
		if ( ! empty( $start_date ) && ! empty( $ticket_start_sale_time ) ) {
			$start                         = new DateTime( $start_date . ' ' . $ticket_start_sale_time );
			$data['ticket_start_meridian'] = $start->format( 'A' );
			$data['ticket_start_time']     = $start->format( 'H:i:00' );
		}

		$ticket_end_sale_time = $this->get_value_by_key( $record, 'ticket_end_sale_time' );
		if ( ! empty( $end_date ) && ! empty( $ticket_end_sale_time ) ) {
			$end                         = new DateTime( $end_date . ' ' . $ticket_end_sale_time );
			$data['ticket_end_meridian'] = $end->format( 'A' );
			$data['ticket_end_time']     = $end->format( 'H:i:00' );
		}

		$stock    = trim( (string) $this->get_value_by_key( $record, 'ticket_stock' ) );
		$capacity = trim( (string) $this->get_value_by_key( $record, 'ticket_capacity' ) );

		if ( '' === $capacity ) {
			$capacity = $stock;
		}

		// Unlimited when blank - keep mode empty (Classic_Editor_Post_Data logic).
		if ( '' === $capacity || '0' === $capacity ) {
			$data['tribe-ticket'] = [ 'mode' => '' ];
		} else {
			$data['tribe-ticket'] = [
				'mode'     => \Tribe__Tickets__Global_Stock::OWN_STOCK_MODE,
				'capacity' => (int) $capacity,
			];
		}

		return $data;
	}

	/**
	 * V1 data shape - legacy tribe_rsvp_tickets.
	 *
	 * @since TBD
	 *
	 * @param array $record The record data.
	 *
	 * @return array
	 */
	private function get_ticket_data_from_v1( array $record ): array {
		$data                       = [];
		$data['ticket_name']        = $this->get_value_by_key( $record, 'ticket_name' );
		$data['ticket_description'] = $this->get_value_by_key( $record, 'ticket_description' );
		$data['ticket_start_date']  = $this->get_value_by_key( $record, 'ticket_start_sale_date' );
		$data['ticket_end_date']    = $this->get_value_by_key( $record, 'ticket_end_sale_date' );

		$show_description = trim( (string) $this->get_value_by_key( $record, 'ticket_show_description' ) );
		if ( tribe_is_truthy( $show_description ) ) {
			$data['ticket_show_description'] = $show_description;
		}

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

		$stock    = $this->get_value_by_key( $record, 'ticket_stock' );
		$capacity = $this->get_value_by_key( $record, 'ticket_capacity' );

		if ( empty( $capacity ) ) {
			$capacity = $stock;
		}

		$data['tribe-ticket']['capacity'] = $capacity;
		$data['tribe-ticket']['stock']    = $stock;

		return $data;
	}

	/**
	 * Checks if the record is valid.
	 *
	 * @param array $record The record data.
	 *
	 * @return bool
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
				// Translators: %s is the event title.
				$this->row_message = sprintf( esc_html__( 'Recurring event tickets are not supported, event %s.', 'event-tickets' ), $event->post_title );
			}

			return ! $is_recurring;
		}
		$this->row_message = false;

		return true;
	}

	/**
	 * @param string|int $row The row number.
	 *
	 * @return string
	 */
	protected function get_skipped_row_message( $row ) {
		return false === $this->row_message ? parent::get_skipped_row_message( $row ) : $this->row_message;
	}

	/**
	 * Registers the RSVP post type as a trackable activity.
	 *
	 * @param Tribe__Events__Aggregator__Record__Activity $activity The activity instance.
	 */
	public static function register_rsvp_activity( $activity ) {
		$activity->register( 'tribe_rsvp_tickets', [ 'rsvp', 'rsvp_tickets' ] );
	}
}
