<?php
/**
 * Controls the basic, common, features of the Flexible Tickets project.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;
use TEC\Events_Pro\Custom_Tables\V1\Series\Provider as Series_Provider;
use TEC\Common\StellarWP\DB\DB;

/**
 * Class Base.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Base extends Controller {
	/**
	 * The action fired to trigger the update of the Attendee > Event meta value for a batch of Attendees.
	 *
	 * @since 5.8.2
	 *
	 * @var string
	 */
	public const AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION = 'tec_tickets_flexible_tickets_update_attendee_event_key';

	/**
	 * Registers the controller services and implementations.
	 *
	 * @since 5.8.0
	 */
	protected function do_register(): void {
		$this->container->singleton( Repositories\Ticket_Groups::class, Repositories\Ticket_Groups::class );
		$this->container->singleton( Repositories\Posts_And_Ticket_Groups::class, Repositories\Posts_And_Ticket_Groups::class );

		tec_asset(
			tribe( 'tickets.main' ),
			'tec-tickets-flexible-tickets-style',
			'flexible-tickets.css',
			[],
			null,
			[
				'groups' => [
					'flexible-tickets',
				],
			],
		);

		// Remove the filter that would prevent Series from appearing among the ticket-able post types.
		$series_provider = $this->container->get( Series_Provider::class );
		remove_action( 'init', [ $series_provider, 'remove_series_from_ticketable_post_types' ] );

		// Remove the filter that would prevent Series from being ticket-able in CT1.
		remove_filter(
			'tribe_tickets_settings_post_types',
			[
				$series_provider,
				'filter_remove_series_post_type',
			]
		);

		$this->handle_first_activation();

		/**
		 * Subscribe to the action fired when the Provisional ID base is updated to set up and start the update
		 * process based on Action Scheduler.
		 */
		$provisional_ids_base_option_name = tribe( ID_Generator::class )->option_name();
		add_action(
			"update_option_{$provisional_ids_base_option_name}",
			[
				$this,
				'dispatch_attendee_event_value_update',
			],
			10,
			2
		);

		/*
		 * Subscribe to the action that will fired by Action Scheduler to update the Attendees following a provisional
		 * ID base update.
		 */
		add_action(
			self::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			[ $this, 'update_attendee_event_value' ],
			10,
			2
		);
	}

	/**
	 * Unregisters the controller services and implementations.
	 *
	 * @since 5.8.0
	 */
	public function unregister(): void {
		// Restore the filter that would prevent Series from appearing among the ticket-able post types.
		$series_provider = $this->container->get( Series_Provider::class );
		if ( ! has_action( 'init', [ $series_provider, 'remove_series_from_ticketable_post_types' ] ) ) {
			add_action( 'init', [ $series_provider, 'remove_series_from_ticketable_post_types' ] );
		}

		// Restore the filter that would prevent Series from being ticket-able in CT1.
		add_filter(
			'tribe_tickets_settings_post_types',
			[
				$series_provider,
				'filter_remove_series_post_type',
			]
		);

		$provisional_ids_base_option_name = tribe( ID_Generator::class )->option_name();
		remove_action(
			"update_option_{$provisional_ids_base_option_name}",
			[
				$this,
				'dispatch_attendee_event_value_update',
			]
		);
		remove_action( self::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION, [ $this, 'update_attendee_event_value' ] );
	}

	/**
	 * If the Flexible Tickets feature has never been activated, then make Series ticketable by default.
	 *
	 * @since 5.8.0
	 *
	 * @return void On first activation, Series are made ticketable by default.
	 */
	private function handle_first_activation(): void {
		$is_first_activation = tribe_get_option( 'flexible_tickets_activated', false ) === false;

		if ( ! $is_first_activation ) {
			return;
		}

		tribe_update_option( 'flexible_tickets_activated', true );
		$ticketable   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = Series::POSTTYPE;
		tribe_update_option(
			'ticket-enabled-post-types',
			array_values( array_unique( $ticketable ) )
		);
	}

	/**
	 * Starts the flow of updates that will re-align the Attendee to Occurrence Provisional IDs
	 * relationships stored in the Attendee to Event meta keys.
	 *
	 * @since 5.8.2
	 *
	 * @param int $old_value The previous provisional IDs base value.
	 * @param int $new_value The new provisional IDs base value.
	 *
	 * @return void Starts the flow of updates that will re-align the Attendee to Occurrence Provisional IDs
	 */
	public function dispatch_attendee_event_value_update( $old_value, $new_value ): void {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		$count = $this->count_attendees_to_update( $old_value, $new_value );

		if ( $count === 0 ) {
			// Nothing to do.
			return;
		}

		as_enqueue_async_action(
			self::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			[
				0,
				$old_value,
			],
			'tec_tickets_flexible_tickets'
		);
	}

	/**
	 * Returns the number of Attendees that need to be updated to the new provisional IDs base value.
	 *
	 * @since 5.8.2
	 *
	 * @param int $old_value The previous provisional IDs base value.
	 * @param int $new_value The new provisional IDs base value.
	 *
	 * @return int The number of Attendees that need to be updated to the new provisional IDs base value.
	 */
	private function count_attendees_to_update( $old_value, $new_value ): int {
		$attendee_to_event_keys = tribe_attendees()->attendee_to_event_keys();
		$attendee_post_types    = tribe_attendees()->attendee_types();

		if ( empty( $attendee_post_types ) || empty( $attendee_to_event_keys ) ) {
			return 0;
		}

		$meta_keys  = "'" . implode( "','", $attendee_to_event_keys ) . "'";
		$post_types = "'" . implode( "','", $attendee_post_types ) . "'";

		global $wpdb;
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( pm.post_id ) FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p
				ON p.ID = pm.post_id
				AND pm.meta_key IN ({$meta_keys})
				AND p.post_type IN ({$post_types})
			WHERE pm.meta_value > %d
			AND pm.meta_value < %d",
				$old_value,
				$new_value
			)
		);

		return (int) $count;
	}

	/**
	 * Updates the value of the meta key relating Attendees to Occurrence Provisional IDs using a direct query.
	 *
	 * @since 5.8.2
	 *
	 * @param int $offset    The offset to update Attendeess from, i.e. the number of already updated Attendees.
	 * @param int $old_value The previous provisional IDs base value, passed as it will not be in the database
	 *                       anymore by the time this method runs.
	 *
	 * @return void Updates the value of the meta key relating Attendees to Occurrence Provisional IDs.
	 */
	public function update_attendee_event_value( int $offset = 0, int $old_value = 0 ): void {
		$attendee_to_event_keys = tribe_attendees()->attendee_to_event_keys();
		$attendee_post_types    = tribe_attendees()->attendee_types();

		if ( empty( $attendee_post_types ) || empty( $attendee_to_event_keys ) ) {
			return;
		}

		$meta_keys  = "'" . implode( "','", $attendee_to_event_keys ) . "'";
		$post_types = "'" . implode( "','", $attendee_post_types ) . "'";
		$new_value  = tribe( ID_Generator::class )->current();

		/**
		 * Filters the batch size used to update the Attendee > Event meta value.
		 *
		 * @since 5.8.2
		 *
		 * @param int $batch_size The batch size used to update the Attendee > Event meta value.
		 */
		$batch_size = apply_filters( 'tec_tickets_flexible_tickets_attendee_event_value_update_batch_size', 250 );

		global $wpdb;

		/*
		 * Pull the list of Attendees that should be updated in this run.
		 * Action Scheduler is granting a lock: this should be thread-safe and not change between this query and the
		 * following one.
		 */
		$attendee_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT attendees.ID from {$wpdb->posts} attendees
				JOIN {$wpdb->postmeta} old_value
					 ON old_value.post_id = attendees.ID
					 AND old_value.meta_key IN ({$meta_keys})
				WHERE attendees.post_type IN ({$post_types})
				AND old_value.meta_value > %d
				AND old_value.meta_value < %d
				ORDER BY attendees.ID DESC
				LIMIT %d",
				$old_value,
				$new_value,
				$batch_size
			)
		);

		$attendee_ids_imploded = implode( ',', $attendee_ids );

		$updated = DB::query(
			DB::prepare(
				"UPDATE {$wpdb->postmeta} new_value
				SET new_value.meta_value = (new_value.meta_value + %d)
				WHERE new_value.meta_value > %d
				AND new_value.meta_value < %d
				AND new_value.meta_key IN ({$meta_keys})
				AND new_value.post_id IN ({$attendee_ids_imploded})",
				$new_value - $old_value,
				$old_value,
				$new_value,
			)
		);

		// Flush the updated Attendees post cache right now.
		foreach ( $attendee_ids as $attendee_id ) {
			clean_post_cache( $attendee_id );
		}

		if ( $updated === false ) {
			do_action(
				'tribe_log',
				'error',
				'Update of Event provisional ID linked from Attendee failed',
				[
					'source'     => __METHOD__,
					'error'      => $wpdb->last_error,
					'offset'     => $offset,
					'batch_size' => $batch_size,
				]
			);
		}

		if ( $this->count_attendees_to_update( $old_value, $new_value ) === 0 ) {
			// We're done.
			return;
		}

		// Enqueue a new async action to process the next batch.
		as_enqueue_async_action(
			self::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			[
				$offset + $batch_size,
				$old_value,
			],
			'tec_tickets_flexible_tickets'
		);
	}
}
