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
use TEC\Tickets\Commerce\Status\Completed;
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
		add_action( 'tribe_settings_do_tabs', [ $this, 'register_payments_tab' ], 15 );

		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_order_statuses' ], 11 );
		add_action( 'tribe_common_loaded', [ $this, 'load_commerce_module' ] );

		add_action( 'template_redirect', [ $this, 'do_cart_parse_request' ] );
		add_action( 'template_redirect', [ $this, 'do_checkout_parse_request' ] );

		add_action( 'event_tickets_attendee_update', [ $this, 'update_attendee_data' ], 10, 3 );
		add_action( 'event_tickets_after_attendees_update', [ $this, 'maybe_send_tickets_after_status_change' ] );

		add_action( 'wp_loaded', [ $this, 'maybe_delete_expired_products' ], 0 );
		add_action( 'wp_loaded', [ $this, 'maybe_redirect_to_attendees_registration_screen' ], 1 );

		add_action( 'tribe_events_tickets_metabox_edit_advanced', [ $this, 'include_metabox_advanced_options' ], 10, 2 );

		add_action( 'tribe_events_tickets_attendees_event_details_top', [ $this, 'setup_attendance_totals' ] );
		add_action( 'trashed_post', [ $this, 'maybe_redirect_to_attendees_report' ] );
		add_action( 'tickets_tpp_ticket_deleted', [ $this, 'update_stock_after_deletion' ], 10, 3 );

		add_action( 'transition_post_status', [ $this, 'transition_order_post_status_hooks' ], 10, 3 );

		// This needs to run earlier than our page setup.
		add_action( 'admin_init', [ $this, 'maybe_trigger_process_action' ], 5 );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tribe_shortcodes', [ $this, 'filter_register_shortcodes' ] );

		add_filter( 'tribe_attendee_registration_form_classes', [ $this, 'filter_registration_form_class' ] );
		add_filter( 'tribe_attendee_registration_cart_provider', [ $this, 'filter_registration_cart_provider' ], 10, 2 );

		add_filter( 'tribe_tickets_get_default_module', [ $this, 'filter_de_prioritize_module' ], 5, 2 );

		add_filter( 'tribe_tickets_checkout_urls', [ $this, 'filter_js_include_checkout_url' ] );
		add_filter( 'tribe_tickets_cart_urls', [ $this, 'filter_js_include_cart_url' ] );

		add_filter( 'event_tickets_attendees_tc_checkin_stati', [ $this, 'filter_checkin_statuses' ] );

		// Add a post display state for special Event Tickets pages.
		add_filter( 'display_post_states', [ $this, 'add_display_post_states' ], 10, 2 );

		$this->provider_meta_sanitization_filters();
	}

	/**
	 * Initializes the Module Class.
	 *
	 * @since 5.1.9
	 */
	public function load_commerce_module() {
		$this->container->make( Module::class );
	}

	public function register_payments_tab() {
		$this->container->make( Settings::class )->register_tab();
	}

	/**
	 * Register all Commerce Post Types in WordPress.
	 *
	 * @since 5.1.9
	 */
	public function register_post_types() {
		$this->container->make( Attendee::class )->register_post_type();
		$this->container->make( Order::class )->register_post_type();
		$this->container->make( Ticket::class )->register_post_type();
	}

	/**
	 * Register all Order Statuses with WP.
	 *
	 * @since 5.1.9
	 */
	public function register_order_statuses() {
		$this->container->make( Status\Status_Handler::class )->register_order_statuses();
	}

	/**
	 * Depending on which page, tab and if an action is present we trigger the processing.
	 *
	 * @since 5.1.9
	 */
	public function maybe_trigger_process_action() {
		$page = tribe_get_request_var( 'page' );
		if ( \Tribe__Settings::instance()->adminSlug !== $page ) {
			return;
		}

		$tab = tribe_get_request_var( 'tab' );
		if ( 'payments' !== $tab ) {
			return;
		}

		$action = (string) tribe_get_request_var( 'tc-action' );
		if ( empty( $action ) ) {
			return;
		}

		/**
		 * Process Tickets Commerce actions when in the Payments Tab.
		 *
		 * @since 5.1.9
		 *
		 * @param string $action Which action we are processing.
		 */
		do_action( 'tec_tickets_commerce_admin_process_action', $action );

		/**
		 * Process Tickets Commerce actions when in the Payments Tab.
		 *
		 * @since 5.1.9
		 */
		do_action( "tec_tickets_commerce_admin_process_action:{$action}" );
	}

	/**
	 * Fires when a post is transitioned from one status to another so that we can make another hook that is namespaced.
	 *
	 * @since 5.1.9
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function transition_order_post_status_hooks( $new_status, $old_status, $post ) {
		$this->container->make( Status\Status_Handler::class )->transition_order_post_status_hooks( $new_status, $old_status, $post );
	}

	/**
	 * Filters the array of statuses that will mark an ticket attendee as eligible for check-in.
	 *
	 * @todo  TribeCommerceLegacy: Move this into a Check In Handler class.
	 *
	 * @since 5.1.9
	 *
	 * @param array $statuses An array of statuses that should mark an ticket attendee as
	 *                        available for check-in.
	 *
	 * @return array The original array plus the 'yes' status.
	 */
	public function filter_checkin_statuses( array $statuses = [] ) {
		$statuses[] = tribe( Completed::class )->get_wp_slug();

		return array_unique( $statuses );
	}

	/**
	 * Parse the cart request, and possibly redirect, so it happens on `template_redirect`.
	 *
	 * @since 5.1.9
	 */
	public function do_cart_parse_request() {
		$this->container->make( Cart::class )->parse_request();
	}

	/**
	 * Parse the checkout request.
	 *
	 * @since 5.1.9
	 */
	public function do_checkout_parse_request() {
		$this->container->make( Checkout::class )->parse_request();
	}

	/**
	 * Backwards compatibility to update stock after deletion of Ticket.
	 *
	 * @todo  Determine if this is still required.
	 *
	 * @since 5.1.9
	 *
	 * @param int $ticket_id  the attendee id being deleted
	 * @param int $post_id    the post or event id for the attendee
	 * @param int $product_id the ticket-product id in Tribe Commerce
	 */
	public function update_stock_after_deletion( $ticket_id, $post_id, $product_id ) {
		$this->container->make( Ticket::class )->update_stock_after_deletion( $ticket_id, $post_id, $product_id );
	}

	/**
	 * Sets up the Attendance Totals Class report with the Attendee Screen
	 *
	 * @since 5.1.9
	 */
	public function setup_attendance_totals() {
		$this->container->make( Reports\Attendance_Totals::class )->integrate_with_attendee_screen();
	}

	/**
	 * Redirect the user after deleting trashing an Attendee to the Reports page.
	 *
	 * @since 5.1.94
	 *
	 * @param int $post_id WP_Post ID
	 */
	public function maybe_redirect_to_attendees_report( $post_id ) {
		$this->container->make( Attendee::class )->maybe_redirect_to_attendees_report( $post_id );
	}

	/**
	 * Includes the metabox advanced options for Tickets Commerce.
	 *
	 * @since 5.1.9
	 *
	 * @param int      $post_id   Which post we are attaching the metabox to.
	 * @param null|int $ticket_id Ticket we are rendering the metabox for.
	 */
	public function include_metabox_advanced_options( $post_id, $ticket_id = null ) {
		$this->container->make( Editor\Metabox::class )->include_metabox_advanced_options( $post_id, $ticket_id );
	}

	/**
	 * Updates the Attendee metadata after insertion.
	 *
	 * @since 5.1.9
	 *
	 * @param array $attendee_data Information that we are trying to save.
	 * @param int   $attendee_id   The attendee ID.
	 * @param int   $post_id       The event/post ID.
	 *
	 */
	public function update_attendee_data( $attendee_data, $attendee_id, $post_id ) {
		$this->container->make( Attendee::class )->update_attendee_data( $attendee_data, $attendee_id, $post_id );
	}

	/**
	 * Fully here for compatibility initially to reduce complexity on the Module.
	 *
	 * @since 5.1.9
	 *
	 * @param int $event_id Which ID we are triggering changes to.
	 *
	 */
	public function maybe_send_tickets_after_status_change( $event_id ) {
		$this->container->make( Attendee::class )->maybe_send_tickets_after_status_change( $event_id );
	}

	/**
	 * Redirect to the Attendees registration page when trying to add tickets.
	 *
	 * @todo  Needs to move to the Checkout page and out of the module.
	 *
	 * @since 5.1.9
	 */
	public function maybe_redirect_to_attendees_registration_screen() {
		$this->container->make( Module::class )->maybe_redirect_to_attendees_registration_screen();
	}

	/**
	 * Delete expired cart items.
	 *
	 * @todo  Needs to move to the Cart page and out of the module.
	 *
	 * @since 5.1.9
	 */
	public function maybe_delete_expired_products() {
		$this->container->make( Cart::class )->maybe_delete_expired_products();
	}

	/**
	 * Add the  HTML Classes to the registration form for this module.
	 *
	 * @todo  Determine what this is used for.
	 *
	 * @since 5.1.9
	 *
	 * @param $classes
	 *
	 * @return array
	 */
	public function filter_registration_form_class( $classes ) {
		return $this->container->make( Attendee::class )->registration_form_class( $classes );
	}

	/**
	 * Included here for Event Tickets Plus compatibility.
	 *
	 * @since 5.1.9
	 *
	 * @param object $provider_obj
	 * @param string $provider
	 *
	 * @return object
	 */
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
		$shortcodes[ Shortcodes\Checkout_Shortcode::get_wp_slug() ] = Shortcodes\Checkout_Shortcode::class;
		$shortcodes[ Shortcodes\Success_Shortcode::get_wp_slug() ]  = Shortcodes\Success_Shortcode::class;

		return $shortcodes;
	}

	/**
	 * If other modules are active, we should de prioritize this one (we want other commerce
	 * modules to take priority over this one).
	 *
	 * @todo  Determine if this is still needed.
	 *
	 * @since 5.1.9
	 *
	 * @param string   $default_module
	 * @param string[] $available_modules
	 *
	 * @return string
	 */
	public function filter_de_prioritize_module( $default_module, array $available_modules ) {
		$tribe_commerce_module = get_class( $this );

		// If this isn't the default (or if there isn't a choice), no need to de prioritize.
		if (
			$default_module !== $tribe_commerce_module
			|| count( $available_modules ) < 2
			|| reset( $available_modules ) !== $tribe_commerce_module
		) {
			return $default_module;
		}

		return next( $available_modules );
	}

	public function filter_js_include_cart_url( $urls ) {
		$urls[ Module::class ] = tribe( Cart::class )->get_url();

		return $urls;
	}

	public function filter_js_include_checkout_url( $urls ) {
		// Note the checkout needs to pass by the cart URL first for AR modal.
		$urls[ Module::class ] = tribe( Cart::class )->get_url();

		return $urls;
	}

	/**
	 * Add a post display state for special Event Tickets pages in the page list table.
	 *
	 * @since 5.1.10
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 *
	 * @return array  $post_states An array of post display states.
	 */
	public function add_display_post_states( $post_states, $post ) {

		$post_states = tribe( Checkout::class )->maybe_add_display_post_states( $post_states, $post );
		$post_states = tribe( Success::class )->maybe_add_display_post_states( $post_states, $post );

		return $post_states;
	}

	/**
	 * Add the filter for provider meta sanitization.
	 *
	 * @since 5.1.10
	 */
	public function provider_meta_sanitization_filters() {

		if ( ! tribe()->offsetExists( 'tickets.handler' ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				'tickets.handler - is not registered.',
				'5.1.10'
			);

			return;
		}

		/**
		 * @var \Tribe__Tickets__Tickets_Handler $ticket_handler
		 */
		$ticket_handler = tribe( 'tickets.handler' );

		add_filter( "sanitize_post_meta_{$ticket_handler->key_provider_field}" , [ $this, 'filter_modify_sanitization_provider_meta' ] );
	}

	/**
	 * Handle saving of Ticket provider meta data.
	 *
	 * @since 5.1.10
	 *
	 * @param mixed  $meta_value Metadata value.
	 *
	 * @return string
	 */
	public function filter_modify_sanitization_provider_meta( $meta_value ) {
		return tribe( Settings::class )->skip_sanitization( $meta_value );
	}
}