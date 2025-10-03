<?php
/**
 * Handles registering and setup for RSVP in Tickets Commerce.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP;

use TEC\Tickets\Commerce\REST\Ticket_Endpoint;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\RSVP\REST\Order_Endpoint;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */
class Controller extends Controller_Contract {

	/**
	 * The TC RSVP attendance totals instance.
	 *
	 * @since TBD
	 *
	 * @var Attendance_Totals
	 */
	protected $attendance_totals;

	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail if method belongs to the parent/abstract class.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the controller is active or not.
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * Register the controller.
	 *
	 * @since   TBD
	 *
	 * @uses    Notices::register_admin_notices()
	 */
	public function do_register(): void {
		$this->container->singleton( Ticket_Endpoint::class );
		$this->container->singleton( REST\Order_Endpoint::class );

		$this->register_assets();
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->remove_actions();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider
	 *
	 * @since TBD
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Adds the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'add_meta_boxes', [ $this, 'configure' ] );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'tec_tickets_commerce_after_save_ticket', [ $this, 'save_rsvp' ], 10, 4 );
		add_action( 'tribe_events_tickets_attendees_event_details_top', [ $this, 'setup_attendance_totals' ] );
		add_action( 'tec_event_tickets_rsvp_form__start', [ $this, 'display_rsvp_responses_info' ], 10, 3 );
	}

	/**
	 * Removes the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function remove_actions() {

	}

	/**
	 * Configures the RSVP metabox for the given post type.
	 *
	 * @since TBD
	 *
	 * @param string|null $post_type The post type to configure the metabox for.
	 */
	public function configure( $post_type = null ) {
		$this->container->make( Metabox::class )->configure( $post_type );
	}

	/**
	 * Register the REST API endpoints.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->container->make( Ticket_Endpoint::class )->register();
		$this->container->make( Order_Endpoint::class )->register();
	}

	/**
	 * Saves RSVP data when a ticket is saved.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id      The post ID of the event.
	 * @param object $ticket       The ticket object.
	 * @param array  $raw_data     The raw ticket data.
	 * @param string $ticket_class The ticket class name.
	 */
	public function save_rsvp( $post_id, $ticket, $raw_data, $ticket_class ) {
		$this->container->make( Ticket::class )->save_rsvp( $post_id, $ticket, $raw_data, $ticket_class );
	}

	/**
	 * Adds the actions required by the controller.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_action( 'tec_tickets_commerce_get_ticket_legacy', [ $this, 'filter_rsvp' ], 10, 3 );
		add_filter( 'tec_tickets_front_end_ticket_form_template_content', [ $this, 'render_rsvp_template' ], 10, 5 );
		add_filter( 'tribe_tickets_attendees_table_order_status', [ $this, 'modify_tc_rsvp_status_display' ], 10, 2 );
		add_filter( 'tec_tickets_attendees_table_column_check_in', [ $this, 'modify_tc_rsvp_checkin_display' ], 10, 2 );
		add_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'modify_tc_rsvp_row_actions' ], 10, 2 );
		add_filter( 'tec_tickets_plus_my_tickets_order_list_ticket_type_titles', [ $this, 'add_tc_rsvp_label_for_my_tickets' ], 10, 2 );
		// This is the correct filter for REST API ticket data
		add_filter( 'tribe_tickets_rest_api_ticket_data', [ $this, 'add_rsvp_counts_to_rest_api_data' ], 10, 3 );
	}

	/**
	 * Filters RSVP tickets for legacy compatibility.
	 *
	 * @since TBD
	 *
	 * @param mixed $return    The return value to filter.
	 * @param int   $event_id  The event ID.
	 * @param int   $ticket_id The ticket ID.
	 *
	 * @return mixed The filtered return value.
	 */
	public function filter_rsvp( $return, $event_id, $ticket_id ) {
		return $this->container->make( Ticket::class )->filter_rsvp( $return, $event_id, $ticket_id );
	}

	/**
	 * Renders the RSVP template content for the tickets block.
	 *
	 * @since TBD
	 *
	 * @param string                             $content  The template content to be rendered.
	 * @param Tribe__Tickets__Ticket_Object|null $rsvp     The rsvp object or null.
	 * @param Tribe__Tickets__Editor__Template   $template The template object.
	 * @param WP_Post                            $post     The post object.
	 * @param bool                               $echo     Whether to echo the output.
	 *
	 * @return string The rendered template content.
	 */
	public function render_rsvp_template( $content, $rsvp, $template, $post, $echo ) {
		if ( empty( $rsvp ) || ! $rsvp instanceof \Tribe__Tickets__Ticket_Object ) {
			return $content;
		}

		// Check if the post uses the block editor and has the new Commerce RSVP block.
		// If it does, don't render the template here as the block will handle it.
		if ( has_blocks( $post ) && has_block( 'tec/rsvp', $post ) ) {
			return $content;
		}

		$must_login = ! is_user_logged_in() && $this->login_required();

		// Create the RSVP template args.
		$rsvp_template_args = [
			'rsvp'          => $rsvp,
			'post_id'       => $post->ID,
			'block_html_id' => Constants::TC_RSVP_TYPE . uniqid(),
			'step'          => '',
			'active_rsvps'  => $rsvp && $rsvp->date_in_range() ? [ $rsvp ] : [],
			'must_login'    => ! is_user_logged_in() && $this->login_required(),
		];

		// Render the RSVP template and append to existing content
		$content .= $template->template( 'v2/commerce/rsvp', $rsvp_template_args, $echo );

		return $content;
	}

	/**
	 * Modifies the status display for TC RSVP attendees to show "Going" or "Not Going".
	 *
	 * @since TBD
	 *
	 * @param string $label The current status label.
	 * @param array  $item  The attendee item data.
	 *
	 * @return string The modified status label.
	 */
	public function modify_tc_rsvp_status_display( $label, $item ) {
		// Only modify for TC RSVP attendees.
		if ( empty( $item['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $item['ticket_type'] ) {
			return $label;
		}

		// Check for RSVP status in the item data.
		if ( ! isset( $item['rsvp_status'] ) ) {
			return $label;
		}

		// Determine the display text based on RSVP status.
		$status_text = 'yes' === $item['rsvp_status'] ? __( 'Going', 'event-tickets' ) : __( 'Not Going', 'event-tickets' );

		// Extract the icon from the existing label if present.
		$icon = '';
		if ( preg_match( '/<span class="dashicons[^>]*><\/span>/', $label, $matches ) ) {
			$icon = $matches[0];
		}

		// Build the new label with appropriate CSS classes.
		$classes = [
			'tec-tickets__admin-table-attendees-order-status',
			'tec-tickets__admin-table-attendees-order-status--tc-rsvp',
			'tec-tickets__admin-table-attendees-order-status--' . ( 'yes' === $item['rsvp_status'] ? 'going' : 'not-going' ),
		];

		$new_label = sprintf(
			'<div class="tec-tickets__admin-table-attendees-order-status-wrapper"><span class="%1$s">%2$s%3$s</span></div>',
			implode( ' ', $classes ),
			$icon,
			esc_html( $status_text )
		);

		return $new_label;
	}

	/**
	 * Modifies the check-in display for TC RSVP attendees.
	 * Hides the check-in column content when RSVP status is 'no'.
	 *
	 * @since TBD
	 *
	 * @param string $content The current check-in column content.
	 * @param array  $item    The attendee item data.
	 *
	 * @return string The modified check-in column content.
	 */
	public function modify_tc_rsvp_checkin_display( $content, $item ) {
		// Only modify for TC RSVP attendees.
		if ( empty( $item['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $item['ticket_type'] ) {
			return $content;
		}

		// Hide check-in content if RSVP status is 'no' (Not Going).
		if ( isset( $item['rsvp_status'] ) && 'no' === $item['rsvp_status'] ) {
			return '';
		}

		return $content;
	}

	/**
	 * Modifies the row actions for TC RSVP attendees.
	 * Removes check-in actions when RSVP status is 'no' (Not Going).
	 *
	 * @since TBD
	 *
	 * @param array $actions The current row actions.
	 * @param array $item    The attendee item data.
	 *
	 * @return array The modified row actions.
	 */
	public function modify_tc_rsvp_row_actions( $actions, $item ) {
		// Only modify for TC RSVP attendees.
		if ( empty( $item['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $item['ticket_type'] ) {
			return $actions;
		}

		// Remove check-in actions if RSVP status is 'no' (Not Going).
		if ( isset( $item['rsvp_status'] ) && 'no' === $item['rsvp_status'] ) {
			// Remove the check-in action (key 0 contains check-in/undo check-in links).
			unset( $actions[0] );
		}

		return $actions;
	}

	/**
	 * Adds TC RSVP attendance totals to the summary box of the attendance
	 * screen.
	 *
	 * Expects to fire during 'tribe_events_tickets_attendees_event_details_top'.
	 *
	 * @since TBD
	 *
	 * @param int|null $event_id The event ID.
	 */
	public function setup_attendance_totals( $event_id = null ) {
		$this->attendance_totals( $event_id )->integrate_with_attendee_screen();
	}

	/**
	 * Returns the TC RSVP attendance totals object.
	 *
	 * @since TBD
	 *
	 * @param int|null $event_id The event ID to set for the attendance totals.
	 *
	 * @return Attendance_Totals The TC RSVP attendance totals object.
	 */
	public function attendance_totals( $event_id = null ) {
		if ( empty( $this->attendance_totals ) ) {
			$this->attendance_totals = new Attendance_Totals();
		}

		$this->attendance_totals->set_event_id( $event_id );

		return $this->attendance_totals;
	}

	/**
	 * Displays RSVP responses information in the admin edit panel.
	 * Only shows if RSVP is saved and has responses.
	 *
	 * @since TBD
	 *
	 * @param int      $post_id     The post ID of the post the ticket is attached to.
	 * @param string   $ticket_type The type of ticket the form is being rendered for.
	 * @param int|null $rsvp_id     The post ID of the ticket that is being edited, null if new.
	 */
	public function display_rsvp_responses_info( $post_id, $ticket_type, $rsvp_id = null ) {
		// Only display for TC RSVP tickets that are saved
		if ( Constants::TC_RSVP_TYPE !== $ticket_type || empty( $rsvp_id ) ) {
			return;
		}

		// Get the attendance totals for this event
		$attendance_totals = $this->attendance_totals( $post_id );
		$total_responses = $attendance_totals->get_total_rsvps();

		// Don't display if there are no responses yet
		if ( 0 === $total_responses ) {
			return;
		}

		// Get the tickets for this event to check if "Can't go" is enabled
		$tickets = \Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$cant_go_enabled = false;

		foreach ( $tickets as $ticket ) {
			if ( Constants::TC_RSVP_TYPE === $ticket->type() && (int) $ticket->ID === (int) $rsvp_id ) {
				$cant_go_enabled = ! empty( get_post_meta( $ticket->ID, '_tribe_ticket_show_not_going', true ) );
				break;
			}
		}

		// Build the attendees admin URL
		$attendees_url = add_query_arg(
			[
				'page'     => 'tickets-attendees',
				'event_id' => $post_id,
			],
			admin_url( 'edit.php?post_type=tribe_events' )
		);

		// Render the template
		tribe( 'tickets.admin.views' )->template(
			[ 'editor', 'rsvp', 'panel', 'responses-info' ],
			[
				'post_id'         => $post_id,
				'rsvp_id'         => $rsvp_id,
				'total_responses' => $total_responses,
				'cant_go_enabled' => $cant_go_enabled,
				'attendees_url'   => $attendees_url,
			]
		);
	}

	/**
	 * Indicates if we currently require users to be logged in before they can obtain tickets.
	 *
	 * @since TBD
	 *
	 * @return bool Whether login is required for RSVP tickets.
	 */
	protected function login_required() {
		$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', [] );

		return in_array( 'event-tickets_rsvp', $requirements, true );
	}

	/**
	 * Adds the TC-RSVP label to the My Tickets page ticket type titles.
	 *
	 * @since TBD
	 *
	 * @param array $titles  The list of ticket type titles.
	 * @param int   $post_id The post ID.
	 *
	 * @return array The updated list of ticket type titles.
	 */
	public function add_tc_rsvp_label_for_my_tickets( $titles, $post_id ) {
		$titles[ Constants::TC_RSVP_TYPE ] = tribe_get_rsvp_label_plural( 'order list view' );

		return $titles;
	}

	/**
	 * Add RSVP-specific counts to the REST API ticket data.
	 *
	 * @since TBD
	 *
	 * @param array  $data       The ticket data.
	 * @param int    $ticket_id  The ticket ID.
	 * @param string $context    The context.
	 *
	 * @return array The modified ticket data.
	 */
	public function add_rsvp_counts_to_rest_api_data( $data, $ticket_id, $context ) {
		// Only add counts for TC RSVP tickets
		if ( empty( $data['type'] ) || Constants::TC_RSVP_TYPE !== $data['type'] ) {
			return $data;
		}

		// Get the event ID for this ticket
		$event_id = ! empty( $data['post_id'] ) ? $data['post_id'] : get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );
		if ( ! $event_id ) {
			// Try to get event from ticket provider
			$provider = tribe_tickets_get_ticket_provider( $ticket_id );
			if ( $provider ) {
				$event = $provider->get_event_for_ticket( $ticket_id );
				$event_id = $event ? $event->ID : 0;
			}
		}

		// Calculate the actual RSVP counts using the Attendance_Totals class
		if ( $event_id ) {
			$attendance_totals = new Attendance_Totals( $event_id );
			$data['going_count'] = $attendance_totals->get_total_going();
			$data['not_going_count'] = $attendance_totals->get_total_not_going();
		} else {
			// Fallback to zero if we can't find the event
			$data['going_count'] = 0;
			$data['not_going_count'] = 0;
		}

		// Also add the show_not_going option
		$show_not_going = get_post_meta( $ticket_id, '_tribe_ticket_show_not_going', true );
		// The meta value might be stored as 'yes', '1', or true - use tribe_is_truthy to check
		$data['show_not_going'] = tribe_is_truthy( $show_not_going );

		return $data;
	}
}
