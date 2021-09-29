<?php
/**
 * Attendees Report
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets\Commerce\Reports;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Admin_Tables;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Utils\Price;

/**
 * Class Reports for Attendees
 *
 * @since TBD
 */
class Attendees extends Report_Abstract {

	/**
	 * Slug of the admin page for attendees
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $page_slug = 'tickets-commerce-attendees';

	/**
	 * Order Pages ID on the menu.
	 *
	 * @since TBD
	 *
	 * @var string The menu slug of the orders page
	 */
	public $attendees_page;

	/**
	 * Gets the Orders Report.
	 *
	 * @since TBD
	 * @return string
	 */
	public function get_title() {
		$post_id = tribe_get_request_var( 'event_id' );

		// translators: The title of an event's Attendee List page in the dashboard. %1$s is the name of the event. %2$d is numeric the event ID.
		return \sprintf( __( 'Attendees for: %1$s [#%2$d]', 'event-tickets' ), esc_html( \get_the_title( $post_id ) ), (int) $post_id );
	}

	/**
	 * Hooks the actions and filter required by the class.
	 *
	 * @since TBD
	 */
	public function hook() {
		// add_filter( 'post_row_actions', [ $this, 'add_orders_row_action' ], 10, 2 );
		add_action( 'admin_menu', [ $this, 'register_attendees_page' ] );
	}

	/**
	 * Registers the Tickets Commerce orders page as a plugin options page.
	 *
	 * @since TBD
	 */
	public function register_attendees_page() {
		$candidate_post_id = tribe_get_request_var( 'post_id', 0 );
		$candidate_post_id = tribe_get_request_var( 'event_id', $candidate_post_id );
		$post_id           = absint( $candidate_post_id );

		if ( $post_id !== (int) $candidate_post_id ) {
			return;
		}

		$cap = 'edit_posts';
		if ( ! current_user_can( 'edit_posts' ) && $post_id ) {
			$post = get_post( $post_id );

			if ( $post instanceof WP_Post && get_current_user_id() === (int) $post->post_author ) {
				$cap = 'read';
			}
		}

		$page_title           = __( 'Tickets Commerce Attendees', 'event-tickets' );
		$this->attendees_page = add_submenu_page(
			null,
			$page_title,
			$page_title,
			$cap,
			static::$page_slug,
			[ $this, 'render_page' ]
		);

		$attendees = tribe( Commerce\Admin_Tables\Attendees::class );

		add_filter( 'tribe_filter_attendee_page_slug', [ $this, 'add_attendee_resources_page_slug' ] );
		add_action( 'admin_enqueue_scripts', [ $attendees, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $attendees, 'load_pointers' ] );
		add_action( 'load-' . $this->attendees_page, [ $this, 'attendees_page_screen_setup' ] );
	}

	/**
	 * Sets the browser title for the Attendees admin page.
	 * Uses the event title.
	 *
	 * @since TBD
	 *
	 * @param string $admin_title The page title in the admin.
	 *
	 * @return string
	 */
	public function filter_admin_title( $admin_title ) {
		if ( ! empty( (int) $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$event = get_post( (int) $_GET['event_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// translators: The title of an event's Attendee List page in the dashboard. %1$s is the name of the event.
			$admin_title = sprintf( __( '%1$s - Attendee list', 'event-tickets' ), $event->post_title );
		}

		return $admin_title;
	}

	/**
	 * Filter the page slugs that the attendee resources will load to add the order page
	 *
	 * @since TBD
	 *
	 * @param array $slugs an array of admin slugs.
	 *
	 * @return array
	 */
	public function add_attendee_resources_page_slug( $slugs ) {
		$slugs[] = $this->attendees_page;

		return $slugs;
	}

	/**
	 * Sets up the attendees page screen.
	 *
	 * @since TBD
	 */
	public function attendees_page_screen_setup() {
		$orders_table = tribe( Commerce\Admin_Tables\Attendees::class );
		$orders_table->prepare_items();

		wp_enqueue_script( 'jquery-ui-dialog' );

		add_filter( 'admin_title', [ $this, 'filter_admin_title' ] );
	}

	/**
	 * Renders the order page
	 *
	 * @since TBD
	 */
	public function render_page() {
		$this->get_template()->template( 'attendees', $this->get_template_vars() );
	}

	/**
	 * @inheritDoc
	 */
	public function setup_template_vars() {
		$post_id = tribe_get_request_var( 'post_id' );
		$post_id = tribe_get_request_var( 'event_id', $post_id );
		$post    = get_post( $post_id );

		$post_type_object    = get_post_type_object( $post->post_type );
		$post_singular_label = $post_type_object->labels->singular_name;

		$tickets    = \Tribe__Tickets__Tickets::get_event_tickets( $post_id );
		$ticket_ids = tribe_get_request_var( 'product_ids', false );

		if ( false !== $ticket_ids ) {
			$ticket_ids = array_map( 'absint', explode( ',', $ticket_ids ) );
			$ticket_ids = array_filter(
				$ticket_ids,
				static function ( $ticket_id ) {
					return get_post_type( $ticket_id ) === Commerce\Ticket::POSTTYPE;
				}
			);
			$tickets    = array_map( [ tribe( Commerce\Ticket::class ), 'get_ticket' ], $ticket_ids );
		}

		$tickets = array_filter(
			$tickets,
			static function ( $ticket ) {
				return Module::class === $ticket->provider_class;
			}
		);

		$event_data   = [];
		$tickets_data = [];

		foreach ( $tickets as $ticket ) {
			$quantities      = tribe( Commerce\Ticket::class )->get_status_quantity( $ticket->ID );
			$total_by_status = [];
			foreach ( $quantities as $status_slug => $status_count ) {
				if ( ! isset( $event_data['qty_by_status'][ $status_slug ] ) ) {
					$event_data['qty_by_status'][ $status_slug ] = 0;
				}
				if ( ! isset( $event_data['total_by_status'][ $status_slug ] ) ) {
					$event_data['total_by_status'][ $status_slug ] = [];
				}

				$total_by_status[ $status_slug ]                 = Price::sub_total( $ticket->price, $status_count );
				$event_data['total_by_status'][ $status_slug ][] = $total_by_status[ $status_slug ];

				$event_data['qty_by_status'][ $status_slug ] += (int) $status_count;
			}
			$tickets_data[ $ticket->ID ] = [
				'total_by_status' => $total_by_status,
				'qty_by_status'   => $quantities,
			];
		}

		$event_data['total_by_status'] = array_map(
			static function ( $sub_totals ) {
				return Price::total( $sub_totals );
			},
			$event_data['total_by_status']
		);

		$this->template_vars = [
			'title'               => $this->get_title(),
			'table'               => tribe( Admin_Tables\Attendees::class ),
			'report'              => tribe( $this ),
			'post'                => $post,
			'post_id'             => $post_id,
			'post_type_object'    => $post_type_object,
			'post_singular_label' => $post_singular_label,
			'tickets'             => $tickets,
			'tickets_data'        => $tickets_data,
			'event_data'          => $event_data,
			'tooltip'             => tribe( 'tooltip.view' ),
		];

		return $this->template_vars;
	}

	/**
	 * Determines if the "export" button will be displayed by the the title
	 *
	 * @param int $event_id The event whose attendees may be exported.
	 *
	 * @return bool
	 */
	public function can_export_attendees( $event_id ) {

		if ( tribe_get_request_var( 'page' ) !== static::$page_slug ) {
			return false;
		}

		if ( ! tribe( Admin_Tables\Attendees::class )->has_items() ) {
			return false;
		}

		if ( ! $this->user_can_manage_attendees( \get_current_user_id(), $event_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if the current user (or an ID-specified one) is allowed to delete, check-in, and
	 * undo check-in attendees.
	 *
	 * @param int    $user_id  Optional. The ID of the user whose access we're checking.
	 * @param string $event_id Optional. The ID of the event the user is managing.
	 *
	 * @return boolean
	 * @since TBD
	 */
	public function user_can_manage_attendees( $user_id = 0, $event_id = '' ) {
		$user_id  = 0 === $user_id ? get_current_user_id() : $user_id;
		$user_can = true;

		// bail quickly here as we don't have a user to check.
		if ( empty( $user_id ) ) {
			return false;
		}

		/**
		 * Allows customizing the caps a user must have to be allowed to manage attendees.
		 *
		 * @since TBD
		 *
		 * @param int   $user_id      The ID of the user whose capabilities are being checked.
		 *
		 * @param array $default_caps The caps a user must have to be allowed to manage attendees.
		 */
		$required_caps = apply_filters(
			'tribe_tickets_caps_can_manage_attendees',
			[
				'edit_others_posts',
			],
			$user_id
		);

		// Next make sure the user has proper caps in their role.
		foreach ( $required_caps as $cap ) {
			if ( ! user_can( $user_id, $cap ) ) {
				$user_can = false;
				// break on first fail.
				break;
			}
		}

		/**
		 * Filter our return value to let other plugins hook in and alter things
		 *
		 * @param bool $user_can return value, user can or can't
		 * @param int  $user_id  id of the user we're checking
		 * @param int  $event_id id of the event we're checking (matter for checks on event authorship)
		 *
		 * @since TBD
		 */
		$user_can = apply_filters( 'tribe_tickets_user_can_manage_attendees', $user_can, $user_id, $event_id );

		return $user_can;
	}
}
