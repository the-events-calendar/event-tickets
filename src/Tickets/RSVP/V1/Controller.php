<?php
/**
 * RSVP V1 Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__RSVP;
use Tribe__Tickets__Promoter__Observer as Promoter_Observer;
use Tribe\Tickets\Promoter\Triggers\Observers\RSVP as RSVP_Observer;
use Tribe__Tickets__CSV_Importer__RSVP_Importer as RSVP_Importer;

/**
 * V1 Controller for RSVP functionality.
 *
 * This controller registers all hooks for the current RSVP implementation.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {

	/**
	 * Stored callbacks for unregistration.
	 *
	 * @since TBD
	 *
	 * @var array<string, callable>
	 */
	private array $callbacks = [];

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// Register singletons.
		$this->register_singletons();

		// Get instances for hook registration.
		$rsvp       = tribe( 'tickets.rsvp' );
		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );

		// Register RSVP hooks.
		$this->register_rsvp_hooks( $rsvp );

		// Register RSVP block hooks.
		$this->register_block_hooks( $rsvp_block );

		// Register Promoter hooks.
		$this->register_promoter_hooks();

		// Register CSV Importer hooks.
		$this->register_csv_importer_hooks();
	}

	/**
	 * Register singleton bindings.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_singletons(): void {
		$this->container->singleton( 'tickets.rsvp', Tribe__Tickets__RSVP::class );

		// Bind the repositories as factories to make sure each instance is different.
		$this->container->bind(
			'tickets.ticket-repository.rsvp',
			'Tribe__Tickets__Repositories__Ticket__RSVP'
		);
		$this->container->bind(
			'tickets.attendee-repository.rsvp',
			'Tribe__Tickets__Repositories__Attendee__RSVP'
		);
	}

	/**
	 * Register RSVP class hooks.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__RSVP $rsvp The RSVP instance.
	 *
	 * @return void
	 */
	private function register_rsvp_hooks( Tribe__Tickets__RSVP $rsvp ): void {
		add_action( 'init', [ $rsvp, 'init' ] );
		add_action( 'init', [ $rsvp, 'set_plugin_name' ], 9 );
		add_action( 'template_redirect', [ $rsvp, 'maybe_generate_tickets' ], 10, 0 );
		add_action( 'event_tickets_attendee_update', [ $rsvp, 'update_attendee_data' ], 10, 3 );
		add_action( 'event_tickets_after_attendees_update', [ $rsvp, 'maybe_send_tickets_after_status_change' ] );
		add_action( 'wp_enqueue_scripts', [ $rsvp, 'register_resources' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $rsvp, 'enqueue_resources' ], 11 );
		add_action( 'trashed_post', [ $rsvp, 'maybe_redirect_to_attendees_report' ] );
		add_filter( 'post_updated_messages', [ $rsvp, 'updated_messages' ] );
		add_action( 'rsvp_checkin', [ $rsvp, 'purge_attendees_transient' ] );
		add_action( 'rsvp_uncheckin', [ $rsvp, 'purge_attendees_transient' ] );
		add_action( 'tribe_events_tickets_attendees_event_details_top', [ $rsvp, 'setup_attendance_totals' ] );
		add_filter( 'tribe_get_cost', [ $rsvp, 'trigger_get_cost' ], 10, 3 );
		add_filter( 'event_tickets_attendees_rsvp_checkin_stati', [ $rsvp, 'filter_event_tickets_attendees_rsvp_checkin_stati' ] );
		add_filter( 'tribe_tickets_rsvp_form_full_name', [ $rsvp, 'rsvp_form_add_full_name' ] );
		add_filter( 'tribe_tickets_rsvp_form_email', [ $rsvp, 'rsvp_form_add_email' ] );
		add_action( 'before_delete_post', [ $rsvp, 'update_stock_from_attendees_page' ] );
		add_action( 'wp_ajax_nopriv_tribe_tickets_rsvp_handle', [ $rsvp, 'ajax_handle_rsvp' ] );
		add_action( 'wp_ajax_tribe_tickets_rsvp_handle', [ $rsvp, 'ajax_handle_rsvp' ] );
		add_filter( 'tec_cache_listener_save_post_types', [ $rsvp, 'filter_cache_listener_save_post_types' ] );
	}

	/**
	 * Register RSVP block hooks.
	 *
	 * @since TBD
	 *
	 * @param \Tribe__Tickets__Editor__Blocks__Rsvp $rsvp_block The RSVP block instance.
	 *
	 * @return void
	 */
	private function register_block_hooks( \Tribe__Tickets__Editor__Blocks__Rsvp $rsvp_block ): void {
		// Register AJAX handlers.
		add_action( 'wp_ajax_rsvp-form', [ $rsvp_block, 'rsvp_form' ] );
		add_action( 'wp_ajax_nopriv_rsvp-form', [ $rsvp_block, 'rsvp_form' ] );
		add_action( 'wp_ajax_rsvp-process', [ $rsvp_block, 'rsvp_process' ] );
		add_action( 'wp_ajax_nopriv_rsvp-process', [ $rsvp_block, 'rsvp_process' ] );

		// Register block.
		add_action( 'tribe_editor_register_blocks', [ $rsvp_block, 'register' ] );
	}

	/**
	 * Register Promoter observer hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_promoter_hooks(): void {
		// RSVP Observer hooks - store callbacks for unregistration.
		$this->callbacks['rsvp_observer_checkin']          = $this->container->callback( RSVP_Observer::class, 'rsvp_checkin' );
		$this->callbacks['rsvp_observer_attendee_created'] = $this->container->callback( RSVP_Observer::class, 'attendee_created' );
		$this->callbacks['rsvp_observer_attendee_updated'] = $this->container->callback( RSVP_Observer::class, 'attendee_updated' );

		add_action( 'rsvp_checkin', $this->callbacks['rsvp_observer_checkin'], 10, 2 );
		add_action( 'event_tickets_rsvp_attendee_created', $this->callbacks['rsvp_observer_attendee_created'], 10, 3 );
		add_action( 'updated_postmeta', $this->callbacks['rsvp_observer_attendee_updated'], 10, 4 );

		// Promoter Observer hooks - store callbacks for unregistration.
		$this->callbacks['promoter_notify_ticket_event']       = $this->container->callback( Promoter_Observer::class, 'notify_ticket_event' );
		$this->callbacks['promoter_notify_event_id_deleted']   = $this->container->callback( Promoter_Observer::class, 'notify_event_id' );
		$this->callbacks['promoter_notify_event_id_generated'] = $this->container->callback( Promoter_Observer::class, 'notify_event_id' );

		add_action( 'save_post_tribe_rsvp_tickets', $this->callbacks['promoter_notify_ticket_event'] );
		add_action( 'tickets_rsvp_ticket_deleted', $this->callbacks['promoter_notify_event_id_deleted'], 10, 2 );
		add_action( 'event_tickets_rsvp_tickets_generated', $this->callbacks['promoter_notify_event_id_generated'], 10, 2 );
	}

	/**
	 * Register CSV Importer hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_csv_importer_hooks(): void {
		if ( ! class_exists( 'Tribe__Events__Importer__File_Importer' ) ) {
			return;
		}

		$this->callbacks['csv_importer_activity'] = $this->container->callback( RSVP_Importer::class, 'register_rsvp_activity' );
		add_action( 'tribe_aggregator_record_activity_wakeup', $this->callbacks['csv_importer_activity'] );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$rsvp       = tribe( 'tickets.rsvp' );
		$rsvp_block = tribe( 'tickets.editor.blocks.rsvp' );

		// Remove RSVP hooks.
		remove_action( 'init', [ $rsvp, 'init' ] );
		remove_action( 'init', [ $rsvp, 'set_plugin_name' ], 9 );
		remove_action( 'template_redirect', [ $rsvp, 'maybe_generate_tickets' ] );
		remove_action( 'event_tickets_attendee_update', [ $rsvp, 'update_attendee_data' ] );
		remove_action( 'event_tickets_after_attendees_update', [ $rsvp, 'maybe_send_tickets_after_status_change' ] );
		remove_action( 'wp_enqueue_scripts', [ $rsvp, 'register_resources' ], 5 );
		remove_action( 'wp_enqueue_scripts', [ $rsvp, 'enqueue_resources' ], 11 );
		remove_action( 'trashed_post', [ $rsvp, 'maybe_redirect_to_attendees_report' ] );
		remove_filter( 'post_updated_messages', [ $rsvp, 'updated_messages' ] );
		remove_action( 'rsvp_checkin', [ $rsvp, 'purge_attendees_transient' ] );
		remove_action( 'rsvp_uncheckin', [ $rsvp, 'purge_attendees_transient' ] );
		remove_action( 'tribe_events_tickets_attendees_event_details_top', [ $rsvp, 'setup_attendance_totals' ] );
		remove_filter( 'tribe_get_cost', [ $rsvp, 'trigger_get_cost' ] );
		remove_filter( 'event_tickets_attendees_rsvp_checkin_stati', [ $rsvp, 'filter_event_tickets_attendees_rsvp_checkin_stati' ] );
		remove_filter( 'tribe_tickets_rsvp_form_full_name', [ $rsvp, 'rsvp_form_add_full_name' ] );
		remove_filter( 'tribe_tickets_rsvp_form_email', [ $rsvp, 'rsvp_form_add_email' ] );
		remove_action( 'before_delete_post', [ $rsvp, 'update_stock_from_attendees_page' ] );
		remove_action( 'wp_ajax_nopriv_tribe_tickets_rsvp_handle', [ $rsvp, 'ajax_handle_rsvp' ] );
		remove_action( 'wp_ajax_tribe_tickets_rsvp_handle', [ $rsvp, 'ajax_handle_rsvp' ] );
		remove_filter( 'tec_cache_listener_save_post_types', [ $rsvp, 'filter_cache_listener_save_post_types' ] );

		// Remove RSVP block hooks.
		remove_action( 'wp_ajax_rsvp-form', [ $rsvp_block, 'rsvp_form' ] );
		remove_action( 'wp_ajax_nopriv_rsvp-form', [ $rsvp_block, 'rsvp_form' ] );
		remove_action( 'wp_ajax_rsvp-process', [ $rsvp_block, 'rsvp_process' ] );
		remove_action( 'wp_ajax_nopriv_rsvp-process', [ $rsvp_block, 'rsvp_process' ] );
		remove_action( 'tribe_editor_register_blocks', [ $rsvp_block, 'register' ] );

		// Remove Promoter hooks using stored callbacks.
		if ( isset( $this->callbacks['rsvp_observer_checkin'] ) ) {
			remove_action( 'rsvp_checkin', $this->callbacks['rsvp_observer_checkin'], 10 );
		}
		if ( isset( $this->callbacks['rsvp_observer_attendee_created'] ) ) {
			remove_action( 'event_tickets_rsvp_attendee_created', $this->callbacks['rsvp_observer_attendee_created'], 10 );
		}
		if ( isset( $this->callbacks['rsvp_observer_attendee_updated'] ) ) {
			remove_action( 'updated_postmeta', $this->callbacks['rsvp_observer_attendee_updated'], 10 );
		}
		if ( isset( $this->callbacks['promoter_notify_ticket_event'] ) ) {
			remove_action( 'save_post_tribe_rsvp_tickets', $this->callbacks['promoter_notify_ticket_event'] );
		}
		if ( isset( $this->callbacks['promoter_notify_event_id_deleted'] ) ) {
			remove_action( 'tickets_rsvp_ticket_deleted', $this->callbacks['promoter_notify_event_id_deleted'], 10 );
		}
		if ( isset( $this->callbacks['promoter_notify_event_id_generated'] ) ) {
			remove_action( 'event_tickets_rsvp_tickets_generated', $this->callbacks['promoter_notify_event_id_generated'], 10 );
		}
		if ( isset( $this->callbacks['csv_importer_activity'] ) ) {
			remove_action( 'tribe_aggregator_record_activity_wakeup', $this->callbacks['csv_importer_activity'] );
		}

		$this->callbacks = [];

		// Reset the registered flag to allow re-registration.
		$this->container->setVar( static::class . '_registered', false );
	}
}
