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
		add_filter( 'tec_tickets_front_end_ticket_form_template_content', [ $this, 'render_rsvp_template' ], 10, 4 );
		add_filter( 'tribe_tickets_attendees_table_order_status', [ $this, 'modify_tc_rsvp_status_display' ], 10, 2 );
		add_filter( 'tec_tickets_attendees_table_column_check_in', [ $this, 'modify_tc_rsvp_checkin_display' ], 10, 2 );
		add_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'modify_tc_rsvp_row_actions' ], 10, 2 );
	}

	public function filter_rsvp( $return, $event_id, $ticket_id ) {
		return $this->container->make( Ticket::class )->filter_rsvp( $return, $event_id, $ticket_id );
	}

	/**
	 * Renders the RSVP template content for the tickets block.
	 *
	 * @since TBD
	 *
	 * @param string                           $content  The template content to be rendered.
	 * @param Tribe__Tickets__Editor__Template $template The template object.
	 * @param WP_Post                          $post     The post object.
	 * @param bool                             $echo     Whether to echo the output.
	 *
	 * @return string The rendered template content.
	 */
	public function render_rsvp_template( $content, $template, $post, $echo ) {
		// Create the RSVP template args
		$rsvp_template_args = [ 'block_html_id' => Constants::TC_RSVP_TYPE . '-' . uniqid(), 'step' => '' ];

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
}
