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

use \TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce as Base_Commerce;
use TEC\Tickets\Commerce\Admin\Orders_Page;
use TEC\Tickets\Commerce\Admin_Tables\Orders_Table;
use TEC\Tickets\Commerce\Reports\Orders;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Status\Status_Handler;
use WP_Admin_Bar;
use Tribe__Date_Utils;
use WP_Query;
use WP_Post;
use WP_User_Query;
use TEC\Tickets\Hooks as Tickets_Hooks;

/**
 * Class Hooks.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce
 */
class Hooks extends Service_Provider {

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
		add_action( 'init', [ $this, 'register_order_statuses' ], 11 );

		add_action( 'init', [ $this, 'register_order_reports' ] );
		add_action( 'init', [ $this, 'register_attendee_reports' ] );

		// Compatibility Hooks
		add_action( 'init', [ $this, 'register_event_compatibility_hooks' ] );

		add_action( 'template_redirect', [ $this, 'do_cart_parse_request' ] );
		add_action( 'template_redirect', [ $this, 'do_checkout_parse_request' ] );

		add_action( 'event_tickets_attendee_update', [ $this, 'update_attendee_data' ], 10, 3 );
		add_action( 'event_tickets_after_attendees_update', [ $this, 'maybe_send_tickets_after_status_change' ] );

		add_action( 'wp_loaded', [ $this, 'maybe_delete_expired_products' ], 0 );
		add_action( 'wp_loaded', [ $this, 'maybe_redirect_to_attendees_registration_screen' ], 1 );

		add_action( 'tribe_events_tickets_metabox_edit_advanced', [ $this, 'include_metabox_advanced_options', ], 10, 2 );

		add_action( 'tribe_events_tickets_attendees_event_details_top', [ $this, 'setup_attendance_totals' ] );
		add_action( 'trashed_post', [ $this, 'maybe_redirect_to_attendees_report' ] );
		add_action( 'tec_tickets_commerce_attendee_before_delete', [ $this, 'update_stock_after_attendee_deletion' ] );

		add_action( 'transition_post_status', [ $this, 'transition_order_post_status_hooks' ], 10, 3 );

		// This needs to run earlier than our page setup.
		add_action( 'admin_init', [ $this, 'maybe_trigger_process_action' ], 5 );

		add_action( 'tec_tickets_commerce_order_status_transition', [ $this, 'modify_tickets_counters_by_status', ], 15, 3 );

		add_action( 'admin_bar_menu', [ $this, 'include_admin_bar_test_mode' ], 1000, 1 );

		add_action( 'tribe_template_before_include:tickets/v2/commerce/checkout', [ $this, 'include_assets_checkout_shortcode' ] );

		add_action( 'tribe_tickets_ticket_moved', [ $this, 'handle_moved_ticket_updates' ], 10, 6 );

		add_action( 'tribe_tickets_price_input_description', [ $this, 'render_sale_price_fields' ], 10, 3 );

		add_action( 'pre_get_posts', [ $this, 'pre_filter_admin_order_table' ] );

		add_action( 'admin_menu', tribe_callback( Orders_Page::class, 'add_orders_page' ), 15 );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tribe_shortcodes', [ $this, 'filter_register_shortcodes' ] );

		add_filter( 'tribe_attendee_registration_form_classes', [ $this, 'filter_registration_form_class' ] );
		add_filter( 'tribe_attendee_registration_cart_provider', [ $this, 'filter_registration_cart_provider', ], 10, 2 );

		add_filter( 'tribe_tickets_get_default_module', [ $this, 'filter_de_prioritize_module' ], 5, 2 );

		add_filter( 'tribe_tickets_checkout_urls', [ $this, 'filter_js_include_checkout_url' ] );
		add_filter( 'tribe_tickets_cart_urls', [ $this, 'filter_js_include_cart_url' ] );

		add_filter( 'tribe_tickets_tickets_in_cart', [ $this, 'filter_tickets_in_cart' ], 10, 2 );
		add_filter( 'tribe_tickets_commerce_cart_get_tickets_' . Base_Commerce::PROVIDER, [ $this, 'filter_rest_get_tickets_in_cart' ] );
		add_filter( 'tribe_rest_url', [ $this, 'filter_rest_cart_url' ], 15, 4 );

		// @todo @backend We need to revisit the refactoring of this report.
		// add_filter( 'tribe_ticket_filter_attendee_report_link', [ $this, 'filter_attendee_report_link' ], 10, 2 );

		add_filter( 'event_tickets_attendees_tc_checkin_stati', [ $this, 'filter_checkin_statuses' ] );

		// Add a post display state for special Event Tickets pages.
		add_filter( 'display_post_states', [ $this, 'add_display_post_states' ], 10, 2 );

		// Filter the 'View Orders` link from ticket editor.
		add_filter( 'tribe_filter_attendee_order_link', [ $this, 'filter_editor_orders_link' ], 10, 2 );

		$this->provider_meta_sanitization_filters();

		add_filter( 'tribe_template_context:tickets-plus/v2/tickets/submit/button-modal', [ $this, 'filter_showing_cart_button' ] );
		add_filter( 'tec_tickets_commerce_payments_tab_settings', [ $this, 'filter_payments_tab_settings' ] );
		add_filter( 'wp_redirect', [ $this, 'filter_redirect_url' ] );
		add_filter( 'tec_tickets_editor_configuration_localized_data', [ $this, 'filter_block_editor_localized_data' ] );
		add_action( 'tribe_editor_config', [ $this, 'filter_tickets_editor_config' ] );
		add_filter( 'wp_list_table_class_name', [ $this, 'filter_wp_list_table_class_name' ], 10, 2 );
		add_filter( 'tribe_dropdown_tec_tc_order_table_customers', [ $this, 'provide_customers_results_to_ajax' ], 10, 2 );

		add_filter( 'tec_tickets_all_tickets_table_provider_options', [ $this, 'filter_all_tickets_table_provider_options' ] );
		add_filter( 'tec_tickets_all_tickets_table_event_meta_keys', [ $this, 'filter_all_tickets_table_event_meta_keys' ] );
	}

	/**
	 * Provides the results for the events dropdown in the Orders table.
	 *
	 * @since 5.13.0
	 * @deprecated 5.20.0
	 *
	 * @param array<string,mixed>  $results The results.
	 * @param array<string,string> $search The search.
	 *
	 * @return array<string,mixed>
	 */
	public function provide_events_results_to_ajax( $results, $search ) {
		// phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped
		_deprecated_function( __METHOD__, '5.20.0', Tickets_Hooks::class . '::provide_events_results_to_ajax' );
		return tribe( Tickets_Hooks::class )->provide_events_results_to_ajax( $results, $search );
	}

	/**
	 * Provides the results for the customers dropdown in the Orders table.
	 *
	 * @since 5.13.0
	 *
	 * @param array $results The results.
	 * @param array $search The search.
	 *
	 * @return array
	 */
	public function provide_customers_results_to_ajax( $results, $search ) {
		if ( empty( $search['term'] ) ) {
			return $results;
		}

		$term = '*' . $search['term'] . '*';

		$args = [
			'count_total'    => false,
			'number'         => 10,
			'search'         => $term,
			'search_columns' => [ 'ID', 'user_login', 'user_email', 'user_nicename', 'display_name' ],
			'fields'         => [ 'ID', 'user_email', 'display_name' ],
		];

		$query = new WP_User_Query( $args );

		$user_results = $query->get_results();

		if ( empty( $user_results ) ) {
			return $results;
		}

		$results = array_map(
			function ( $user ) {
				return [
					'id'   => $user->ID,
					'text' => $user->display_name . ' (' . $user->user_email . ')',
				];
			},
			$user_results
		);

		return [ 'results' => $results ];
	}

	/**
	 * Filters the admin order table to apply filters.
	 *
	 * @since 5.13.0
	 *
	 * @param WP_Query $query The WP_Query instance.
	 * @return void
	 */
	public function pre_filter_admin_order_table( $query ) {
		if ( ! $query->is_main_query() || ! $query->is_admin || Order::POSTTYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( empty( $screen->id ) || 'edit-' . Order::POSTTYPE !== $screen->id ) {
			return;
		}

		$current_status = $query->get( 'post_status' );

		if ( ! $current_status ) {
			$query->set( 'post_status', 'any' );
		} elseif ( is_array( $current_status ) ) {
			$statuses = [];
			foreach ( $current_status as $st ) {
				if ( 'any' === $st ) {
					$statuses = [ 'any' ];
					// No need to continue.
					break;
				}

				$statuses = array_merge( $statuses, tribe( Status_Handler::class )->get_group_of_statuses_by_slug( '', $st ) );
			}

			$query->set( 'post_status', array_unique( $statuses ) );
		} else {
			$query->set( 'post_status', tribe( Status_Handler::class )->get_group_of_statuses_by_slug( '', $current_status ) );
		}

		$date_from = sanitize_text_field( tribe_get_request_var( 'tec_tc_date_range_from', '' ) );
		$date_to   = sanitize_text_field( tribe_get_request_var( 'tec_tc_date_range_to', '' ) );

		$date_from = Tribe__Date_Utils::is_valid_date( $date_from ) ? $date_from : '';
		$date_to   = Tribe__Date_Utils::is_valid_date( $date_to ) ? $date_to : '';

		$date_range_valid = $this->is_valid_date_range( $date_from, $date_to );

		if ( ! $date_range_valid ) {
			// If invalid, adjust the to date to be the same as the from date.
			$date_to = $date_from;
		}

		$date_query = $query->get( 'date_query' );

		if ( empty( $date_query ) || ! is_array( $date_query ) ) {
			$date_query = [];
		}

		if ( ! empty( $date_from ) ) {
			$date_query[] = [
				// We need to pass H:i:s to avoid bug in wp core.
				'after'     => Tribe__Date_Utils::reformat( $date_from, 'Y-m-d 00:00:00' ),
				'inclusive' => true,
			];
		}

		if ( ! empty( $date_to ) ) {
			$date_query[] = [
				// We need to pass H:i:s to avoid bug in wp core.
				'before'    => Tribe__Date_Utils::reformat( $date_to, 'Y-m-d 23:59:59' ),
				'inclusive' => true,
			];
		}

		if ( count( $date_query ) > 1 && empty( $date_query['relation'] ) ) {
			$date_query['relation'] = 'AND';
		}

		$query->set( 'date_query', $date_query );

		$meta_query = $query->get( 'meta_query' );

		if ( empty( $meta_query ) || ! is_array( $meta_query ) ) {
			$meta_query = [];
		}

		$gateway = sanitize_text_field( tribe_get_request_var( 'tec_tc_gateway', '' ) );

		if ( $gateway ) {
			$meta_query[] = [
				'key'     => Order::$gateway_meta_key,
				'value'   => $gateway,
				'compare' => '=',
			];
		}

		$event_filter = absint( tribe_get_request_var( 'tec_tc_events', 0 ) );

		if ( $event_filter ) {
			$meta_query[] = [
				'key'     => Order::$events_in_order_meta_key,
				'value'   => $event_filter,
				'compare' => 'IN',
			];
		}

		$customer_filter = absint( tribe_get_request_var( 'tec_tc_customers', 0 ) );

		if ( $customer_filter ) {
			$meta_query[] = [
				'key'     => Order::$purchaser_user_id_meta_key,
				'value'   => $customer_filter,
				'compare' => '=',
			];
		}

		$search = sanitize_text_field( tribe_get_request_var( 'search', '' ) );

		if ( ! empty( $search ) ) {
			$test_search = false;

			if ( is_numeric( $search ) ) {
				// If the search term is numeric, we could assume they are searching by order id.
				$test_search = get_post( absint( $search ) );
				$test_search = $test_search instanceof WP_Post ? $test_search : null;
				$test_search = $test_search ?
					Order::POSTTYPE === $test_search->post_type && 'trash' !== $test_search->post_status :
					false;

				if ( $test_search ) {
					$query->set( 'post__in', [ absint( $search ) ] );
				}
			}

			if ( ! $test_search ) {
				// In every other case create an OR meta query.
				$meta_query[] = [
					[
						'key'     => Order::$purchaser_email_meta_key,
						'value'   => $search,
						'compare' => 'LIKE',
					],
					[
						'key'     => Order::$purchaser_full_name_meta_key,
						'value'   => $search,
						'compare' => 'LIKE',
					],
					[
						'key'     => Order::$gateway_order_id_meta_key,
						'value'   => $search,
						'compare' => '=',
					],
					'relation' => 'OR',
				];
			}
		}

		if ( count( $meta_query ) > 1 && empty( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		$query->set( 'meta_query', $meta_query );

		return $query;
	}

	/**
	 * Validates a date range.
	 *
	 * @since 5.13.0
	 *
	 * @param string $date_from The start date.
	 * @param string $date_to The end date.
	 *
	 * @return bool
	 */
	protected function is_valid_date_range( string $date_from = '', string $date_to = '' ): bool {
		if ( empty( $date_from ) || empty( $date_to ) ) {
			return true;
		}

		$date_from_ts = strtotime( $date_from );
		$date_to_ts   = strtotime( $date_to );

		return $date_to_ts >= $date_from_ts;
	}

	/**
	 * Filters the WP List Table class name to use the new Orders table.
	 *
	 * @since 5.13.0
	 *
	 * @param string $class_name The class name.
	 * @param array  $args The arguments.
	 * @return string
	 */
	public function filter_wp_list_table_class_name( $class_name, $args ) {
		$screen = get_current_screen();

		if ( empty( $screen->id ) || 'edit-' . Order::POSTTYPE !== $screen->id ) {
			return $class_name;
		}

		return Orders_Table::class;
	}

	/**
	 * Filters the redirect URL to determine whether or not section key needs to be added.
	 *
	 * @since 5.3.0
	 *
	 * @param string $url Redirect URL.
	 *
	 * @return string
	 */
	public function filter_redirect_url( $url ) {
		return $this->container->make( Payments_Tab::class )->filter_redirect_url( $url );
	}

	/**
	 * Filters the Settings for Payments tab to add the Commerce Provider related fields.
	 *
	 * @since 5.2.0
	 *
	 * @param array $settings Settings array data for Payments tab.
	 *
	 * @return array
	 */
	public function filter_payments_tab_settings( $settings ) {
		$settings['fields'] = array_merge( $settings['fields'], tribe( Settings::class )->get_settings() );

		return $settings;
	}

	/**
	 * Initializes the Module Class.
	 *
	 * @since 5.1.9
	 *
	 * @deprecated 5.20.0
	 */
	public function load_commerce_module() {
		_deprecated_function( __METHOD__, '5.20.0' );
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
	 * Register the Orders report.
	 *
	 * @todo  Currently this is attaching the hook method to the init, which is incorrect we should not be attaching
	 *        these filters from the orders class if we can avoid it.
	 *
	 * @since 5.2.0
	 */
	public function register_order_reports() {
		$this->container->make( Reports\Orders::class )->hook();
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
	 * Register the Attendees Report
	 *
	 * @since 5.2.0
	 */
	public function register_attendee_reports() {
		$this->container->make( Reports\Attendees::class )->hook();
	}

	/**
	 * Display admin bar when using the Test Mode for payments.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
	 *
	 * @return bool
	 */
	public function include_admin_bar_test_mode( WP_Admin_Bar $wp_admin_bar ) {
		return $this->container->make( Settings::class )->include_admin_bar_test_mode( $wp_admin_bar );
	}

	/**
	 * Depending on which page, tab and if an action is present we trigger the processing.
	 *
	 * @since 5.1.9
	 */
	public function maybe_trigger_process_action() {
		$page = tribe_get_request_var( 'page' );
		if ( \Tribe\Tickets\Admin\Settings::$settings_page_id !== $page ) {
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
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
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
		$statuses[] = tribe( Completed::class )->get_name();

		return array_unique( $statuses );
	}

	/**
	 * Modify the counters for all the tickets involved on this particular order.
	 *
	 * @since 5.2.0
	 *
	 * @param Status_Interface      $new_status New post status.
	 * @param Status_Interface|null $old_status Old post status.
	 * @param WP_Post               $post       Post object.
	 */
	public function modify_tickets_counters_by_status( $new_status, $old_status, $post ) {
		$this->container->make( Ticket::class )->modify_counters_by_status( $new_status, $old_status, $post );
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
	 * @since 5.1.9
	 *
	 * @since 5.5.10 Updated method to use the new hook from Attendee class.
	 *
	 * @param int $attendee_id the attendee id.
	 */
	public function update_stock_after_attendee_deletion( $attendee_id ) {
		$this->container->make( Ticket::class )->update_stock_after_attendee_deletion( $attendee_id );
	}

	/**
	 * Sets up the Attendance Totals Class report with the Attendee Screen
	 *
	 * @since 5.1.9
	 * @since 5.8.2 Add the `$event_id` parameter.
	 *
	 * @parma int|null $event_id The ID of the post to calculate attendance totals for.
	 */
	public function setup_attendance_totals( $event_id = null ) {
		$attendance_totals = $this->container->make( Reports\Attendance_Totals::class );
		$attendance_totals->set_event_id( $event_id );
		$attendance_totals->integrate_with_attendee_screen();
	}

	/**
	 * Redirect the user after deleting trashing an Attendee to the Reports page.
	 *
	 * @since 5.1.94
	 *
	 * @param int $post_id WP_Post ID.
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

		add_filter(
			"sanitize_post_meta_{$ticket_handler->key_provider_field}",
			[
				$this,
				'filter_modify_sanitization_provider_meta',
			]
		);
	}

	/**
	 * Handle saving of Ticket provider meta data.
	 *
	 * @since 5.1.10
	 *
	 * @param mixed $meta_value Metadata value.
	 *
	 * @return string
	 */
	public function filter_modify_sanitization_provider_meta( $meta_value ) {
		return tribe( Settings::class )->skip_sanitization( $meta_value );
	}

	/**
	 * If an event is using Tickets Commerce, use the new Attendees View URL
	 *
	 * @since 5.2.0
	 *
	 * @param string $url     the current Attendees View url.
	 * @param int    $post_id the event id.
	 *
	 * @return string
	 */
	public function filter_attendee_report_link( $url, $post_id ) {

		if ( Module::class !== Module::get_event_ticket_provider( $post_id ) ) {
			return $url;
		}

		return add_query_arg( [ 'page' => 'tickets-commerce-attendees' ], $url );
	}

	/**
	 * Filters the ticket editor order link for Tickets Commerce Module orders.
	 *
	 * @since 5.2.0
	 *
	 * @param string $url     Url for the order page for ticketed event/post.
	 * @param int    $post_id The post ID for the current event/post.
	 *
	 * @return string
	 */
	public function filter_editor_orders_link( $url, $post_id ) {
		return $this->container->make( Orders::class )->filter_editor_orders_link( $url, $post_id );
	}

	/**
	 * Hide the 'save and view cart` button from AR Modal depending on Cart type.
	 *
	 * @since 5.2.0
	 *
	 * @param array $args Context arraay for the modal template.
	 *
	 * @return array
	 */
	public function filter_showing_cart_button( $args ) {
		if ( ! isset( $args['has_tpp'] ) || ! isset( $args['provider'] ) ) {
			return $args;
		}

		if ( Module::class !== $args['provider']->class_name ) {
			return $args;
		}

		$args['has_tpp'] = 'redirect' === $this->container->make( Cart::class )->get_mode();

		return $args;
	}

	/**
	 * Filters to add Tickets Commerce into the "tickets in cart" array.
	 *
	 * @since 5.2.0
	 *
	 * @param array<int> $tickets  Tickets in cart. Format: [ ticket_id => quantity ].
	 * @param string     $provider Commerce provider.
	 *
	 * @return array
	 */
	public function filter_tickets_in_cart( $tickets, $provider ) {
		if ( \TEC\Tickets\Commerce::PROVIDER !== $provider ) {
			return $tickets;
		}

		$tickets = [];
		$items   = tribe( Cart::class )->get_items_in_cart();

		foreach ( $items as $data ) {
			$tickets[ $data['ticket_id'] ] = $data['quantity'];
		}

		return $tickets;
	}


	/**
	 * Modify the cart contents for the Rest call around TTickets Commerce cart.
	 *
	 * @since 5.2.0
	 *
	 * @param array $tickets
	 *
	 * @return array
	 */
	public function filter_rest_get_tickets_in_cart( $tickets ) {
		$cookie = tribe_get_request_var( Cart::$cookie_query_arg );
		if ( empty( $cookie ) ) {
			return $tickets;
		}

		// We reset the tickets passed.
		$tickets = [];
		/* @var Cart $cart */
		$cart   = tribe( Cart::class );
		$cookie = tribe_get_request_var( Cart::$cookie_query_arg );
		$cart->set_cart_hash( $cookie );
		$items = $cart->get_items_in_cart( true );

		foreach ( $items as $data ) {
			$tickets[] = [
				'ticket_id' => $data['ticket_id'],
				'quantity'  => $data['quantity'],
				'post_id'   => $data['event_id'],
				'optout'    => $data['extra']['optout'],
				'iac'       => $data['extra']['iac'],
				'provider'  => \TEC\Tickets\Commerce::PROVIDER,
			];
		}

		return $tickets;
	}

	/**
	 * Modify the Rest URL for the cart to include the TC Cookie.
	 *
	 * @since 5.2.0
	 *
	 * @param string $url
	 * @param string $path
	 * @param int    $blog_id
	 * @param string $scheme
	 *
	 * @return string
	 */
	public function filter_rest_cart_url( $url, $path, $blog_id, $scheme ) {
		if ( '/cart/' !== $path ) {
			return $url;
		}

		$cookie = tribe_get_request_var( Cart::$cookie_query_arg );
		if ( empty( $cookie ) ) {
			return $url;
		}

		return add_query_arg( [ Cart::$cookie_query_arg => $cookie ], $url );
	}

	/**
	 * Hooks for Compatibility with The Events Calendar
	 *
	 * @since 5.2.0
	 */
	public function register_event_compatibility_hooks() {

		if ( ! tribe( \Tribe__Dependency::class )->is_plugin_active( 'Tribe__Events__Main' ) ) {
			return;
		}

		add_filter( 'wp_redirect', [ tribe( Compatibility\Events::class ), 'prevent_filter_redirect_canonical' ], 1, 2 );
	}

	/**
	 * Includes the Assets to the checkout page shortcode.
	 *
	 * @since 5.2.0
	 */
	public function include_assets_checkout_shortcode() {
		Shortcodes\Checkout_Shortcode::enqueue_assets();
	}

	/**
	 * Hook the attendee data update on moved tickets.
	 *
	 * @since 5.5.9
	 *
	 * @param int $ticket_id                The ticket which has been moved.
	 * @param int $src_ticket_type_id       The ticket type it belonged to originally.
	 * @param int $tgt_ticket_type_id       The ticket type it now belongs to.
	 * @param int $src_event_id             The event/post which the ticket originally belonged to.
	 * @param int $tgt_event_id             The event/post which the ticket now belongs to.
	 * @param int $instigator_id            The user who initiated the change.
	 *
	 * @return void
	 */
	public function handle_moved_ticket_updates( $attendee_id, $src_ticket_type_id, $tgt_ticket_type_id, $src_event_id, $tgt_event_id, $instigator_id ) {
		$this->container->make( Ticket::class )->handle_moved_ticket_updates( $attendee_id, $src_ticket_type_id, $tgt_ticket_type_id, $src_event_id, $tgt_event_id, $instigator_id );
	}

	/**
	 * Renders the sale price fields.
	 *
	 * @since 5.9.0
	 *
	 * @param int   $ticket_id The ticket ID.
	 * @param int   $post_id   The post ID.
	 * @param array $context   The context.
	 *
	 * @return void
	 */
	public function render_sale_price_fields( $ticket_id, $post_id, $context ): void {
		$this->container->make( Editor\Metabox::class )->render_sale_price_fields( $ticket_id, $post_id, $context );
	}

	/**
	 * Filters the block editor localized data.
	 *
	 * @since 5.9.0
	 *
	 * @param array<string,mixed> $localized The localized data.
	 *
	 * @return array<string,mixed> The filtered localized data.
	 */
	public function filter_block_editor_localized_data( $localized ) {

		$localized['salePrice'] = [
			'add_sale_price'   => __( 'Add sale price', 'event-tickets' ),
			'sale_price_label' => __( 'Sale Price', 'event-tickets' ),
			'on_sale_from'     => __( 'On sale from', 'event-tickets' ),
			'to'               => __( 'to', 'event-tickets' ),
			'invalid_price'    => __( 'Sale price must be lower than the regular ticket price.', 'event-tickets' ),
			'on_sale'          => __( 'On Sale', 'event-tickets' ),
		];

		return $localized;
	}

	/**
	 * Filters the data used to render the Tickets Block Editor control.
	 *
	 * @since 5.10.0
	 *
	 * @param array<string,mixed> $data The data used to render the Tickets Block Editor control.
	 *
	 * @return array<string,mixed> The data used to render the Tickets Block Editor control.
	 */
	public function filter_tickets_editor_config( $data ) {
		if ( ! isset( $data['tickets'] ) ) {
			$data['tickets'] = [];
		}

		if ( ! isset( $data['tickets']['commerce'] ) ) {
			$data['tickets']['commerce'] = [];
		}

		$data['tickets']['commerce']['isFreeTicketAllowed'] = tec_tickets_commerce_is_free_ticket_allowed();

		return $data;
	}

	/**
	 * Runs the callbacks registered by the Hooks object on the `init` action.
	 *
	 * This method is useful for a late registration of the Commerce functionality after the `init` action has already
	 * been fired.
	 *
	 * @since 5.16.0
	 */
	public function run_init_hooks(): void {
		$this->register_post_types();
		$this->register_order_statuses();
		$this->register_order_reports();
		$this->register_attendee_reports();
		$this->register_event_compatibility_hooks();
	}

	/*** Filters the options for the provider select in the All Tickets table.
	 *
	 * @since 5.14.0
	 *
	 * @param array $options The options.
	 *
	 * @return array The filtered options.
	 */
	public function filter_all_tickets_table_provider_options( $options ) {
		$options[ Ticket::POSTTYPE ] = tribe( Module::class )->plugin_name;

		return $options;
	}

	/**
	 * Filters the meta keys for the All Tickets table.
	 *
	 * @since 5.14.0
	 *
	 * @param array $meta_keys The event meta keys.
	 *
	 * @return array The filtered event meta keys.
	 */
	public function filter_all_tickets_table_event_meta_keys( $meta_keys ) {
		$meta_keys[ Ticket::POSTTYPE ] = Module::ATTENDEE_EVENT_KEY;

		return $meta_keys;
	}
}
