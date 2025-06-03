<?php
/**
 * Controller for Square syncs.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Settings;
use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use TEC\Tickets\Commerce\Ticket as Ticket_Data;
use Exception;
use Tribe__Settings_Manager as Settings_Manager;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\NotSyncableItemException;

/**
 * Class Controller
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Controller extends Controller_Contract {
	/**
	 * The group that the sync action belongs to.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const AS_SYNC_ACTION_GROUP = 'tec_tickets_commerce_square_syncs';

	/**
	 * The option that marks the sync action as in progress.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_SYNC_ACTIONS_IN_PROGRESS = 'tickets_commerce_square_sync_ptypes_in_progress_%s_%s';

	/**
	 * The option that marks the sync action as completed.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_SYNC_ACTIONS_COMPLETED = 'tickets_commerce_square_sync_ptypes_completed_%s_%s';

	/**
	 * The merchant.
	 *
	 * @since 5.24.0
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * The settings.
	 *
	 * @since 5.24.0
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Container $container The container.
	 * @param Merchant  $merchant  The merchant.
	 * @param Settings  $settings  The settings.
	 */
	public function __construct( Container $container, Merchant $merchant, Settings $settings ) {
		parent::__construct( $container );
		$this->merchant = $merchant;
		$this->settings = $settings;
	}

	/**
	 * Whether the controller is active or not.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return $this->merchant->is_connected();
	}

	/**
	 * Register the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( Remote_Objects::class );
		$this->container->singleton( Items_Sync::class );
		$this->container->singleton( Inventory_Sync::class );

		$this->container->register( Regulator::class );
		$this->container->register( Listeners::class );

		if ( ! $this->settings->is_inventory_sync_enabled() ) {
			return;
		}

		$this->container->register( Integrity_Controller::class );
		$this->container->register_on_action( 'tec_events_fully_loaded', Tec_Event_Details_Provider::class );

		add_action( 'init', [ $this, 'schedule_batch_sync' ] );
		add_action( 'tribe_log', [ $this, 'mark_action_failed' ], 100, 3 );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Regulator::class )->unregister();
		$this->container->get( Listeners::class )->unregister();

		if ( ! $this->settings->is_inventory_sync_enabled() ) {
			return;
		}

		$this->container->get( Integrity_Controller::class )->unregister();

		if ( $this->container->isBound( Tec_Event_Details_Provider::class ) ) {
			$this->container->get( Tec_Event_Details_Provider::class )->unregister();
		}

		remove_action( 'init', [ $this, 'schedule_batch_sync' ] );
		remove_action( 'tribe_log', [ $this, 'mark_action_failed' ], 100 );
	}

	/**
	 * Schedule the batch sync.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function schedule_batch_sync(): void {
		if ( as_has_scheduled_action( Regulator::HOOK_INIT_SYNC_ACTION, [], self::AS_SYNC_ACTION_GROUP ) ) {
			return;
		}

		if ( self::is_sync_completed() || self::is_sync_in_progress() ) {
			return;
		}

		as_schedule_single_action( time(), Regulator::HOOK_INIT_SYNC_ACTION, [], self::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Mark the action as failed.
	 *
	 * @since 5.24.0
	 *
	 * @param string $level   The level of the log.
	 * @param string $message The message of the log.
	 * @param array  $data    The data of the log.
	 *
	 * @return void
	 * @throws Exception If an action scheduler actions fails.
	 */
	public function mark_action_failed( string $level, string $message = '', array $data = [] ): void {
		if ( 'error' !== $level ) {
			return;
		}

		if ( ! did_action( 'action_scheduler_begin_execute' ) ) {
			return;
		}

		if ( ! empty( $data ) ) {
			$message .= ' with error data: ' . wp_json_encode( $data, JSON_PRETTY_PRINT );
		}

		// We mark action with errors as failed.
		throw new Exception( $message );
	}

	/**
	 * Get the sync-able tickets of an event.
	 *
	 * @since 5.24.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return array The syncable tickets.
	 */
	public static function get_sync_able_tickets_of_event( int $event_id ): array {
		$cache_key = 'tec_tickets_commerce_square_sync_able_tickets_' . $event_id;
		$cache     = tribe_cache();

		if ( ! empty( $cache[ $cache_key ] ) && is_array( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$ticket_data = tribe( Ticket_Data::class );

		$tickets_stats = $ticket_data->get_posts_tickets_data( $event_id );

		if (
			empty( $tickets_stats['tickets_on_sale'] ) &&
			empty( $tickets_stats['tickets_about_to_go_to_sale'] ) &&
			empty( $tickets_stats['tickets_have_ended_sales'] )
		) {
			return [];
		}

		$ticket_ids = array_unique(
			array_merge(
				$tickets_stats['tickets_on_sale'],
				$tickets_stats['tickets_about_to_go_to_sale'],
				$tickets_stats['tickets_have_ended_sales']
			)
		);

		$tickets = array_filter(
			array_map(
				static fn ( $ticket_id ) => $ticket_data->load_ticket_object( $ticket_id ),
				$ticket_ids
			)
		);

		$cache[ $cache_key ] = $tickets;

		return $tickets;
	}

	/**
	 * Get the ticket-able post types to sync.
	 *
	 * @since 5.24.0
	 *
	 * @return array The ticket-able post types to sync.
	 */
	public static function ticket_able_post_types_to_sync(): array {
		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		$ticket_able_to_sync = [];

		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			if ( Commerce_Settings::get( self::OPTION_SYNC_ACTIONS_COMPLETED, [ $ticket_able_post_type ] ) ) {
				continue;
			}

			$ticket_able_to_sync[] = $ticket_able_post_type;
		}

		return $ticket_able_to_sync;
	}

	/**
	 * Whether the sync is completed.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public static function is_sync_completed(): bool {
		return empty( self::ticket_able_post_types_to_sync() );
	}

	/**
	 * Whether the sync is in progress.
	 *
	 * @since 5.24.0
	 *
	 * @return bool
	 */
	public static function is_sync_in_progress(): bool {
		$ticket_able_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		foreach ( $ticket_able_post_types as $ticket_able_post_type ) {
			if ( Commerce_Settings::get( self::OPTION_SYNC_ACTIONS_IN_PROGRESS, [ $ticket_able_post_type ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reset the sync status.
	 *
	 * @since 5.24.0
	 *
	 * @param array  $new_options The new options.
	 * @param string $post_type   The post type.
	 *
	 * @return void
	 */
	public static function reset_sync_status( array $new_options = [], string $post_type = '' ): void {
		/**
		 * Fires before the sync status is reset.
		 *
		 * @since 5.24.0
		 *
		 * @param string $post_type The post type.
		 */
		do_action( Listeners::HOOK_SYNC_RESET_SYNCED_POST_TYPE, $post_type );

		$settings = $new_options;

		$progress_option  = Commerce_Settings::get_key( self::OPTION_SYNC_ACTIONS_IN_PROGRESS, [ $post_type ] );
		$completed_option = Commerce_Settings::get_key( self::OPTION_SYNC_ACTIONS_COMPLETED, [ $post_type ] );

		$keys_to_remove = [];

		foreach ( array_keys( $settings ) as $key ) {
			if ( ! str_starts_with( $key, $progress_option ) && ! str_starts_with( $key, $completed_option ) ) {
				continue;
			}

			$keys_to_remove[ $key ] = true;
		}

		if ( ! $keys_to_remove ) {
			return;
		}

		add_action(
			'tec_shutdown',
			function () use ( $keys_to_remove, $post_type ) {
				$listeners = tribe( Listeners::class );

				/**
				 * We make sure we only remove the keys that we should remove and leave any other changes that took place between
				 * self::reset_sync_status and this callback unaffected.
				 */
				$settings = array_diff_key( Settings_Manager::get_options(), $keys_to_remove );

				$listeners->remove_tec_settings_listener();
				Settings_Manager::set_options( $settings );
				$listeners->add_tec_settings_listener();

				/**
				 * Fires when the sync status is reset.
				 *
				 * @since 5.24.0
				 *
				 * @param string $post_type The post type.
				 */
				do_action( 'tec_tickets_commerce_square_sync_post_reset_status', $post_type );
			}
		);
	}

	/**
	 * Whether the ticket is in sync with the square data.
	 *
	 * @since 5.24.0
	 *
	 * @param Ticket_Object $ticket The ticket object.
	 * @param int           $square_quantity The square quantity.
	 * @param string        $square_state    The square state.
	 *
	 * @return bool Whether the ticket is in sync with the square data.
	 *
	 * @throws NotSyncableItemException If the ticket is not syncable.
	 */
	public static function is_ticket_in_sync_with_square_data( Ticket_Object $ticket, int $square_quantity, string $square_state ): bool {
		if ( ! Item::get_remote_object_id( $ticket->ID ) ) {
			throw new NotSyncableItemException( __( 'Ticket is not sync-able.', 'event-tickets' ) );
		}

		$local_quantity = $ticket->available();

		if ( -1 === $local_quantity && $square_quantity > 900000000 && 'IN_STOCK' === $square_state ) {
			return true;
		}

		if ( $square_quantity === $local_quantity && $square_quantity === 0 && 'SOLD' === $square_state ) {
			return true;
		}

		return $square_quantity === $local_quantity && 'IN_STOCK' === $square_state;
	}
}
