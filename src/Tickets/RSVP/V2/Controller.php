<?php
/**
 * RSVP V2 Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use WP_REST_Request;
use WP_Error;

/**
 * Controller for RSVP V2 functionality.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {

	/**
	 * Constant name for disabling RSVP V2.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_RSVP_V2_DISABLED';

	/**
	 * Action fired when V2 is registered.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_rsvp_v2_registered';

	/**
	 * Stored callbacks for proper unregistration.
	 *
	 * @since TBD
	 *
	 * @var array<string,callable>
	 */
	private array $callbacks = [];

	/**
	 * Feature flag check.
	 *
	 * @since TBD
	 *
	 * @return bool Whether V2 is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			return false;
		}

		return (bool) apply_filters( 'tec_tickets_rsvp_v2_enabled', true );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// Register singletons for V2 classes.
		$this->container->singleton( Meta::class );
		$this->container->singleton( Ticket::class );
		$this->container->singleton( Attendee::class );
		$this->container->singleton( Order::class );
		$this->container->singleton( Metabox::class );
		$this->container->singleton( Attendance_Totals::class );
		$this->container->singleton( Cart\RSVP_Cart::class );

		// Register REST endpoint singletons.
		$this->container->singleton( REST\Order_Endpoint::class );
		$this->container->singleton( REST\Ticket_Endpoint::class );

		// Register sub-providers.
		$this->container->register( Assets::class );
		$this->container->register( Hooks::class );

		// Register hooks using helper methods.
		$this->register_metabox_hooks();
		$this->register_ticket_hooks();
		$this->register_ajax_hooks();
		$this->register_attendance_hooks();

		// Register legacy bindings for backwards compatibility.
		// These allow existing code that uses 'tickets.rsvp' to continue working.
		$this->container->singleton( 'tickets.rsvp', \Tribe__Tickets__RSVP::class );

		// Bind the repositories as factories for backwards compatibility.
		$this->container->bind(
			'tickets.ticket-repository.rsvp',
			'Tribe__Tickets__Repositories__Ticket__RSVP'
		);
		$this->container->bind(
			'tickets.attendee-repository.rsvp',
			'Tribe__Tickets__Repositories__Attendee__RSVP'
		);

		// Fire action for Plus integration.
		do_action( static::$registration_action );
	}

	/**
	 * Registers metabox hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_metabox_hooks(): void {
		$metabox = $this->container->get( Metabox::class );

		$this->callbacks['register_metabox'] = [ $metabox, 'register_metabox' ];
		add_action( 'add_meta_boxes', $this->callbacks['register_metabox'] );

		$this->callbacks['save_metabox'] = [ $metabox, 'save_metabox' ];
		add_action( 'save_post', $this->callbacks['save_metabox'], 10, 2 );
	}

	/**
	 * Registers ticket hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_ticket_hooks(): void {
		$ticket = $this->container->get( Ticket::class );

		// Filter TC ticket list to exclude RSVP tickets.
		$this->callbacks['filter_out_rsvp_tickets'] = [ $ticket, 'filter_out_rsvp_tickets' ];
		add_filter( 'tec_tickets_commerce_get_tickets', $this->callbacks['filter_out_rsvp_tickets'], 10, 2 );

		// Cache listener.
		$this->callbacks['add_post_type_to_cache'] = [ $ticket, 'add_post_type_to_cache' ];
		add_filter( 'tec_cache_listener_save_post_types', $this->callbacks['add_post_type_to_cache'] );
	}

	/**
	 * Registers AJAX hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_ajax_hooks(): void {
		// AJAX handlers for frontend RSVP form.
		// Delegates to REST\Order_Endpoint for actual processing.
		$this->callbacks['ajax_handle_rsvp'] = [ $this, 'ajax_handle_rsvp' ];
		add_action( 'wp_ajax_tribe_tickets_rsvp_v2_handle', $this->callbacks['ajax_handle_rsvp'] );
		add_action( 'wp_ajax_nopriv_tribe_tickets_rsvp_v2_handle', $this->callbacks['ajax_handle_rsvp'] );
	}

	/**
	 * AJAX handler for RSVP form submissions.
	 *
	 * Validates nonce, extracts params, delegates to REST\Order_Endpoint.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_handle_rsvp(): void {
		check_ajax_referer( 'tribe_tickets_rsvp_v2', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$step = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 'going';

		$order_endpoint = $this->container->get( REST\Order_Endpoint::class );

		// Create mock WP_REST_Request and delegate.
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/order' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$request->set_body_params( wp_unslash( $_POST ) );

		switch ( $step ) {
			case 'going':
			case 'not-going':
				$response = $order_endpoint->create_item( $request );
				break;
			case 'opt-in':
				$response = $order_endpoint->update_item( $request );
				break;
			default:
				$response = new WP_Error( 'invalid_step', __( 'Invalid RSVP step.', 'event-tickets' ) );
				break;
		}

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message(), 400 );
		}

		wp_send_json( $response->get_data(), $response->get_status() );
	}

	/**
	 * Registers attendance hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_attendance_hooks(): void {
		$attendance = $this->container->get( Attendance_Totals::class );

		$this->callbacks['render_totals'] = [ $attendance, 'render_totals' ];
		add_action( 'tribe_events_tickets_attendees_event_details_top', $this->callbacks['render_totals'] );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// Remove metabox hooks.
		if ( isset( $this->callbacks['register_metabox'] ) ) {
			remove_action( 'add_meta_boxes', $this->callbacks['register_metabox'] );
		}
		if ( isset( $this->callbacks['save_metabox'] ) ) {
			remove_action( 'save_post', $this->callbacks['save_metabox'], 10 );
		}

		// Remove ticket hooks.
		if ( isset( $this->callbacks['filter_out_rsvp_tickets'] ) ) {
			remove_filter( 'tec_tickets_commerce_get_tickets', $this->callbacks['filter_out_rsvp_tickets'], 10 );
		}
		if ( isset( $this->callbacks['add_post_type_to_cache'] ) ) {
			remove_filter( 'tec_cache_listener_save_post_types', $this->callbacks['add_post_type_to_cache'] );
		}

		// Remove AJAX hooks.
		if ( isset( $this->callbacks['ajax_handle_rsvp'] ) ) {
			remove_action( 'wp_ajax_tribe_tickets_rsvp_v2_handle', $this->callbacks['ajax_handle_rsvp'] );
			remove_action( 'wp_ajax_nopriv_tribe_tickets_rsvp_v2_handle', $this->callbacks['ajax_handle_rsvp'] );
		}

		// Remove attendance hooks.
		if ( isset( $this->callbacks['render_totals'] ) ) {
			remove_action( 'tribe_events_tickets_attendees_event_details_top', $this->callbacks['render_totals'] );
		}

		// Clear callbacks.
		$this->callbacks = [];
	}
}
