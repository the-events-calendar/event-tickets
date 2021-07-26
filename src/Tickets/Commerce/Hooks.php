<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Commerce\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.commerce.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Commerce\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.commerce.hooks' ), 'some_method' ] );
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use \tad_DI52_ServiceProvider;
use Tribe\Tickets\Shortcodes\Tribe_Tickets_Checkout;

/**
 * Class Hooks.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce
 */
class Hooks extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_actions() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'tribe_common_loaded', [ $this, 'load_commerce_module' ] );

		add_action( 'event_tickets_attendee_update', [ $this, 'update_attendee_data' ], 10, 3 );
		add_action( 'event_tickets_after_attendees_update', [ $this, 'maybe_send_tickets_after_status_change' ] );

		add_action( 'wp_loaded', [ $this, 'maybe_delete_expired_products' ], 0 );
		add_action( 'wp_loaded', [ $this, 'maybe_redirect_to_attendees_registration_screen' ], 1 );

		add_action( 'tribe_events_tickets_metabox_edit_advanced', [ $this, 'include_metabox_advanced_options' ], 10, 2 );

		add_action( 'tribe_events_tickets_attendees_event_details_top', [ $this, 'setup_attendance_totals' ] );
		add_action( 'trashed_post', [ $this, 'maybe_redirect_to_attendees_report' ] );
		add_action( 'tickets_tpp_ticket_deleted', [ $this, 'update_stock_after_deletion' ], 10, 3 );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tribe_shortcodes', [ $this, 'filter_register_shortcodes' ] );
		add_filter( 'tec_tickets_commerce_settings', [ $this, 'filter_include_commerce_settings' ] );

		add_filter( 'tribe_attendee_registration_form_classes', [ $this, 'filter_registration_form_class' ] );
		add_filter( 'tribe_attendee_registration_cart_provider', [ $this, 'filter_registration_cart_provider' ], 10, 2 );

	}


	public function load_commerce_module() {
		tribe( Module::class );
	}

	public function register_post_types() {
		$this->container->make( Attendee::class )->register_post_type();
		$this->container->make( Order::class )->register_post_type();
		$this->container->make( Ticket::class )->register_post_type();
	}

	public function update_stock_after_deletion( $ticket_id, $post_id, $product_id ) {
		$this->container->make( Ticket::class )->update_stock_after_deletion( $ticket_id, $post_id, $product_id );
	}

	public function setup_attendance_totals() {
		$this->container->make( Reports\Attendance_Totals::class )->integrate_with_attendee_screen();
	}

	public function maybe_redirect_to_attendees_report() {
		$this->container->make( Attendee::class )->maybe_redirect_to_attendees_report();
	}

	public function include_metabox_advanced_options( $post_id, $ticket_id = null ) {
		$this->container->make( Editor\Metabox::class )->include_metabox_advanced_options( $post_id, $ticket_id );
	}

	public function update_attendee_data( $attendee_data, $attendee_id, $post_id ){
		$this->container->make( Attendee::class )->update_attendee_data( $attendee_data, $attendee_id, $post_id );
	}

	public function maybe_send_tickets_after_status_change( $event_id ) {
		$this->container->make( Attendee::class )->maybe_send_tickets_after_status_change( $event_id );
	}

	public function maybe_redirect_to_attendees_registration_screen() {
		$this->container->make( Module::class )->maybe_redirect_to_attendees_registration_screen();
	}

	public function maybe_delete_expired_products() {
		$this->container->make( Cart::class )->maybe_delete_expired_products();
	}

	public function filter_registration_form_class( $classes ) {
		return $this->container->make( Attendee::class )->registration_form_class( $classes );
	}

	public function filter_registration_cart_provider( $provider_obj, $provider ) {
		return $this->container->make( Attendee::class )->registration_cart_provider( $provider_obj, $provider );
	}

	/**
	 * Register shortcodes.
	 *
	 * @see   \Tribe\Shortcode\Manager::get_registered_shortcodes()
	 *
	 * @since 5.1.6
	 *
	 * @param array $shortcodes An associative array of shortcodes in the shape `[ <slug> => <class> ]`.
	 *
	 * @return array
	 */
	public function filter_register_shortcodes( array $shortcodes ) {
		$shortcodes['tribe_tickets_checkout'] = Tribe_Tickets_Checkout::class;

		return $shortcodes;
	}


	/**
	 * Modify the commerce settings completely once we have Tickets Commerce active.
	 *
	 * @since 5.1.6
	 *
	 * @return array
	 */
	public function filter_include_commerce_settings() {
		return $this->container->make( Settings::class )->get_settings();
	}
}