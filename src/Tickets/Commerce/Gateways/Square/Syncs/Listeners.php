<?php
/**
 * Listens to events that trigger the scheduling of the syncs.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Tickets\Commerce\Gateways\Square\Settings;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Ticket as Ticket_Data;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use WP_Query;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use Tribe__Settings_Manager as Settings_Manager;

/**
 * Class Listeners
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Listeners extends Controller_Contract {
	/**
	 * The hook to delete synced post types.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const HOOK_SYNC_RESET_SYNCED_POST_TYPE = 'tec_tickets_commerce_square_sync_pre_reset_status';

	/**
	 * The settings.
	 *
	 * @since 5.24.0
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * The regulator.
	 *
	 * @since 5.24.0
	 *
	 * @var Regulator
	 */
	private Regulator $regulator;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Container $container The container.
	 * @param Settings  $settings The settings.
	 * @param Regulator $regulator The regulator.
	 */
	public function __construct( Container $container, Settings $settings, Regulator $regulator ) {
		parent::__construct( $container );
		$this->settings  = $settings;
		$this->regulator = $regulator;
	}

	/**
	 * Register the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->add_tec_settings_listener();

		if ( ! $this->settings->is_inventory_sync_enabled() ) {
			return;
		}

		add_action( 'save_post', [ $this, 'schedule_sync_on_save' ], 10, 2 );
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_sync' ], 10, 2 );
		add_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_sync_on_date_start' ], 10, 4 );
		add_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_sync_on_date_end' ], 10, 4 );
		add_action( 'wp_trash_post', [ $this, 'schedule_sync_on_delete' ] );
		add_action( 'before_delete_post', [ $this, 'schedule_sync_on_delete' ] );
		add_action( 'tec_tickets_commerce_square_ticket_out_of_sync', [ $this, 'schedule_ticket_sync_on_out_of_sync' ], 10, 3 );
		add_action( 'tec_tickets_ticket_stock_changed', [ $this, 'schedule_ticket_sync_on_stock_changed' ] );
		add_action( 'tec_tickets_commerce_square_merchant_disconnected', [ $this, 'unsync' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_tec_settings_listener();

		if ( ! $this->settings->is_inventory_sync_enabled() ) {
			return;
		}

		remove_action( 'save_post', [ $this, 'schedule_sync_on_save' ] );
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_sync' ] );
		remove_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_sync_on_date_start' ] );
		remove_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_sync_on_date_end' ] );
		remove_action( 'wp_trash_post', [ $this, 'schedule_sync_on_delete' ] );
		remove_action( 'before_delete_post', [ $this, 'schedule_sync_on_delete' ] );
		remove_action( 'tec_tickets_commerce_square_ticket_out_of_sync', [ $this, 'schedule_ticket_sync_on_out_of_sync' ] );
		remove_action( 'tec_tickets_ticket_stock_changed', [ $this, 'schedule_ticket_sync_on_stock_changed' ] );
		remove_action( 'tec_tickets_commerce_square_merchant_disconnected', [ $this, 'unsync' ] );
	}

	/**
	 * Add the settings listener.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function add_tec_settings_listener(): void {
		add_action( 'tec_common_settings_manager_pre_set_options', [ $this, 'reset_sync_status' ], 10, 2 );
	}

	/**
	 * Remove the settings listener.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function remove_tec_settings_listener(): void {
		remove_action( 'tec_common_settings_manager_pre_set_options', [ $this, 'reset_sync_status' ] );
	}

	/**
	 * Remove local sync data.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unsync(): void {
		$this->remove_tec_settings_listener();

		Sync_Controller::reset_sync_status( Settings_Manager::get_options() );

		$this->add_tec_settings_listener();
	}

	/**
	 * Reset the sync status.
	 *
	 * @since 5.24.0
	 *
	 * @param array $new_options The new options.
	 * @param array $old_options The old options.
	 *
	 * @return void
	 */
	public function reset_sync_status( array $new_options, array $old_options ): void {
		$this->remove_tec_settings_listener();
		$sync_still_enabled = tribe_is_truthy( $new_options[ Settings::OPTION_INVENTORY_SYNC ] ?? false );

		if ( ! $sync_still_enabled ) {
			// We do a global reset then!
			Sync_Controller::reset_sync_status( $new_options );
			$this->add_tec_settings_listener();
			return;
		}

		$old_ticket_enabled_post_types = (array) ( $old_options['ticket-enabled-post-types'] ?? [] );
		$new_ticket_enabled_post_types = (array) ( $new_options['ticket-enabled-post-types'] ?? [] );

		if ( $old_ticket_enabled_post_types === $new_ticket_enabled_post_types ) {
			return;
		}

		$removed_post_types = array_diff( $old_ticket_enabled_post_types, $new_ticket_enabled_post_types );

		foreach ( $removed_post_types as $post_type ) {
			Sync_Controller::reset_sync_status( $new_options, $post_type );
		}

		$this->add_tec_settings_listener();
	}

	/**
	 * Reset the post type data.
	 *
	 * @since 5.24.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return void
	 */
	public function reset_post_type_data( string $post_type = '' ): void {
		$post_types = array_filter( [ $post_type ] );
		if ( empty( $post_types ) ) {
			$post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		}

		if ( empty( $post_types ) ) {
			return;
		}

		/**
		 * Whether to delete all events at once.
		 *
		 * @since 5.24.0
		 *
		 * @param bool   $all_at_once Whether to delete all events at once.
		 * @param array  $post_types  The post types.
		 * @param string $post_type   The post type.
		 */
		$all_at_once = apply_filters(
			'tec_tickets_commerce_square_sync_reset_post_type_data_all_at_once',
			did_action( 'tec_tickets_commerce_square_merchant_disconnected' ) || doing_action( 'tec_tickets_commerce_square_merchant_disconnected' ),
			$post_types,
			$post_type
		);

		/**
		 * Filter the number of events to delete at once.
		 *
		 * @since 5.24.0
		 *
		 * @param int $number The number of events to delete at once.
		 */
		$schedule_events_to_delete_at_once = max( 1, (int) apply_filters( 'tec_tickets_commerce_square_sync_reset_post_type_data_schedule_events_to_delete_at_once', 1000 ) );

		if ( $all_at_once ) {
			/**
			 * Filter the number of events to delete at once but when all at once is requested.
			 *
			 * @since 5.24.0
			 *
			 * @param int $number The number of events to delete at once.
			 */
			$schedule_events_to_delete_at_once = apply_filters( 'tec_tickets_commerce_square_sync_reset_post_type_data_schedule_events_to_delete_at_once_all_at_once', -1 );
		}

		$args = [
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'post_type'              => $post_types,
			'posts_per_page'         => $schedule_events_to_delete_at_once, // Most servers will be able to handle 1000 ids at a time and at the same time it will cover most cases all in one go.
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => Commerce_Settings::get_key( Item::SQUARE_ID_META ),
					'compare' => 'EXISTS',
				],
			],
		];

		$results = new WP_Query( $args );

		if ( empty( $results->posts ) ) {
			// The action will stop repeating itself here. When no more results are found.
			return;
		}

		if ( ! $all_at_once ) {
			/**
			 * We don't do found_rows so we cant now if there are more posts to delete or not.
			 *
			 * We let the next action decide that instead. Having the found_rows property results in slower queries.
			 *
			 * We want to give some time for the deletions to occur before this runs. So that the query above retrieves diff results because of the deleted meta_keys Item::SQUARE_ID_META.
			 */
			$this->regulator->schedule( self::HOOK_SYNC_RESET_SYNCED_POST_TYPE, [ $post_type ], 15 * MINUTE_IN_SECONDS );
		}

		foreach ( $results->posts as $post_id ) {
			// Remove the event from Square.
			$this->schedule_deletion( $post_id, $all_at_once );
		}
	}

	/**
	 * Schedule the sync on save.
	 *
	 * @since 5.24.0
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function schedule_sync_on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		if ( empty( Sync_Controller::get_sync_able_tickets_of_event( $post_id ) ) ) {
			return;
		}

		$this->schedule_sync( $post_id, $post_id );
	}

	/**
	 * Schedule the sync on delete.
	 *
	 * @since 5.24.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function schedule_sync_on_delete( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$ticket_types = tribe_tickets()->ticket_types();

		$ticket_or_event_types = array_merge(
			(array) tribe_get_option( 'ticket-enabled-post-types', [] ),
			$ticket_types
		);

		if ( ! in_array( $post->post_type, $ticket_or_event_types, true ) ) {
			return;
		}

		if ( ! $this->is_object_syncable( $post_id, true ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, $ticket_types, true ) ) {
			$this->schedule_deletion( $post_id );
			return;
		}

		// This is a ticket - if this is the last "sync-able" ticket we need to schedule deletion for the parent event instead.
		$ticket = Tickets::load_ticket_object( $post_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// It seems like we cant delete its parent...
			$this->schedule_deletion( $post_id );
			return;
		}

		$parent_id = $ticket->get_event_id();

		if ( ! $parent_id ) {
			// The parent is already deleted...
			$this->schedule_deletion( $post_id );
			return;
		}

		$syncable_tickets = Sync_Controller::get_sync_able_tickets_of_event( $parent_id );

		if ( count( $syncable_tickets ) > 1 ) {
			// There are more sync-able tickets for this event, we only need to delete the ticket from Square.
			$this->schedule_deletion( $post_id );
			return;
		}

		// This is the last sync-able ticket for this event, we need to schedule deletion for the parent event instead.
		$this->schedule_deletion( $parent_id );
	}

	/**
	 * Schedule the ticket sync on out of sync.
	 *
	 * @since 5.24.0
	 *
	 * @param int    $ticket_id The ticket ID.
	 * @param int    $quantity  The quantity of tickets.
	 * @param string $state     The state of the inventory.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync_on_out_of_sync( int $ticket_id, int $quantity, string $state ): void {
		$this->regulator->schedule( Inventory_Sync::HOOK_CHECK_TICKET_INVENTORY_SYNC, [ $ticket_id, $quantity, $state ], 2 * MINUTE_IN_SECONDS );
	}

	/**
	 * Schedule the ticket sync on stock changed.
	 *
	 * @since 5.24.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync_on_stock_changed( int $ticket_id ): void {
		$ticket = tribe( Ticket_Data::class )->load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			return;
		}

		$this->schedule_sync( $ticket->ID, $ticket->get_event_id(), MINUTE_IN_SECONDS / 12 );
	}

	/**
	 * Schedule the ticket sync.
	 *
	 * @since 5.24.0
	 *
	 * @param int  $ticket_id     The ticket ID.
	 * @param ?int $parent_id     The parent ID. Null when the event has been deleted...
	 * @param int  $minimum_delay The minimum delay in seconds.
	 *
	 * @return void
	 */
	public function schedule_sync( int $ticket_id, ?int $parent_id = null, int $minimum_delay = 20 ): void {
		if ( ! $parent_id ) {
			// Don't sync the ticket if the event has been deleted...
			return;
		}

		if ( ! $this->is_object_syncable( $ticket_id ) ) {
			return;
		}

		$this->regulator->schedule( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], $minimum_delay );
	}

	/**
	 * Schedule the ticket sync on date start.
	 *
	 * @since 5.24.0
	 *
	 * @param int     $ticket_id     The ticket ID.
	 * @param bool    $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int     $timestamp     The timestamp.
	 * @param WP_Post $post_parent   The parent post.
	 *
	 * @return void
	 */
	public function schedule_sync_on_date_start( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $post_parent ): void {
		if ( ! $this->is_object_syncable( $ticket_id ) ) {
			return;
		}

		$should_sync = $its_happening || time() >= $timestamp - Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $ticket_id );

		if ( ! $should_sync ) {
			return;
		}

		$this->regulator->schedule( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_parent->ID ], MINUTE_IN_SECONDS / 3 );
	}

	/**
	 * Schedule the ticket sync on date end.
	 *
	 * @since 5.24.0
	 *
	 * @param int     $ticket_id     The ticket ID.
	 * @param bool    $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int     $timestamp     The timestamp.
	 * @param WP_Post $post_parent   The parent post.
	 *
	 * @return void
	 */
	public function schedule_sync_on_date_end( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $post_parent ): void {
		if ( ! $this->is_object_syncable( $ticket_id, true ) ) {
			return;
		}

		if ( ! $its_happening ) {
			// Remove the synced tickets going out of sale at the very last moment.
			return;
		}

		$this->regulator->schedule( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_parent->ID ], MINUTE_IN_SECONDS / 3 );
	}

	/**
	 * Schedule the deletion of a ticket from Square.
	 *
	 * @since 5.24.0
	 *
	 * @param int  $post_id         The post ID.
	 * @param bool $only_local_data Whether to only remove local data.
	 *
	 * @return void
	 */
	protected function schedule_deletion( int $post_id, bool $only_local_data = false ): void {
		$remote_objects   = tribe( Remote_Objects::class );
		$remote_object_id = $remote_objects->delete_remote_object_data( $post_id );

		if ( $only_local_data ) {
			/**
			 * Variable `$only_local_data` being true, by default, happens only when the merchant
			 * is being disconnected.
			 *
			 * So there is no point in scheduling a deletion at a later time, since,
			 * we won't be authorized to delete the objects from Square by then.
			 */
			return;
		}

		if ( as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_id ], Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			// Unschedule any possible scheduled syncs.
			as_unschedule_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_id ], Sync_Controller::AS_SYNC_ACTION_GROUP );
		}

		$this->regulator->schedule( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ 0, $remote_object_id ], MINUTE_IN_SECONDS / 3 );
	}

	/**
	 * Whether the object is syncable.
	 *
	 * @since 5.24.0
	 *
	 * @param int  $post_id            The post ID.
	 * @param bool $requires_remote_id Whether the object requires a remote ID.
	 *
	 * @return bool
	 */
	protected function is_object_syncable( int $post_id, bool $requires_remote_id = false ): bool {
		$has_remote_object_id = (bool) Item::get_remote_object_id( $post_id );

		if ( $has_remote_object_id ) {
			return true;
		}

		if ( $requires_remote_id ) {
			return false;
		}

		// If the initial sync is completed we need to sync this object anew.
		return Sync_Controller::is_sync_completed();
	}
}
