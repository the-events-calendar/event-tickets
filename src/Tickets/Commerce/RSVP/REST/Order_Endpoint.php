<?php
/**
 * Tickets Commerce: RSVP Order Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP\REST;

use TEC\Tickets\Commerce\Cart\RSVP_Cart;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Success;
use TEC\Tickets\Commerce\RSVP\Constants;

use Tribe__Tickets__Tickets_View;
use Tribe__Tickets__Ticket_Object;
use Tribe__Utils__Array;
use Tribe__Tickets__Editor__Blocks__Rsvp as RSVP_Block;
use Tribe__Tickets__Editor__Template as Template;

use WP_REST_Request;
use WP_REST_Server;


/**
 * Class Order Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/commerce/rsvp/order';

	/**
	 *
	 * @since TBD
	 *
	 * @var Tribe__Tickets__Tickets_View
	 */
	protected $tickets_view;

	/**
	 *
	 * @since TBD
	 *
	 * @var Module
	 */
	protected $module;

	/**
	 * RSVP blocks editor instance.
	 *
	 * @since TBD
	 *
	 * @var RSVP_Block
	 */
	protected $blocks_rsvp;

	/**
	 * Tickets template renderer instance.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	protected $template;

	/**
	 * Class constructor
	 */
	public function __construct( RSVP_Block $block, Template $template ) {
		$this->tickets_view = Tribe__Tickets__Tickets_View::instance();
		$this->module       = tribe( Module::class );
		$this->blocks_rsvp  = $block;
		$this->template     = $template;
	}
	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_steps' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Filters the cart repository, to use RSVP_Cart instead of the default Cart.
	 *
	 * @since TBD
	 *
	 * @param Cart_Interface $cart Instance of the cart repository managing the cart.
	 */
	public function setup_cart( $cart ) {
		return tribe( RSVP_Cart::class );
	}

	public function handle_steps( WP_REST_Request $request ) {
		$response = [
			'success' => false,
			'html' => '',
		];

		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );
		$step      = tribe_get_request_var( 'step', null );

		add_filter( 'tec_tickets_commerce_cart_repository', [ $this, 'setup_cart' ] );

		$render_response = $this->render_rsvp_step( $ticket_id, $step );

		if ( is_string( $render_response ) && '' !== $render_response ) {
			// Return the HTML if it's a string.
			$response['html'] = $render_response;

			wp_send_json_success( $response );
		} elseif ( is_array( $render_response ) && ! empty( $render_response['errors'] ) ) {
			$response['html'] = $this->render_rsvp_error( $render_response['errors'] );

			wp_send_json_error( $response );
		}

		$response['html'] = $this->render_rsvp_error( __( 'Something happened here.', 'event-tickets' ) );

		wp_send_json_error( $response );
		//return new WP_REST_Response( $response );
	}

	/**
	 * Handle RSVP error rendering.
	 *
	 * @since TBD
	 *
	 * @param string|array $error_message The error message(s).
	 *
	 * @return string The error template HTML.
	 */
	public function render_rsvp_error( $error_message ) {
		// Set required template globals.
		$args = [
			'error_message' => $error_message,
		];

		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		// Add the rendering attributes into global context.
		$template->add_template_globals( $args );

		return $template->template( 'v2/commerce/rsvp/messages/error', $args, false );
	}

	/**
	 * Handle processing the RSVP step based on current arguments.
	 *
	 * @since TBD
	 *
	 * @param array $args {
	 *      The list of step template arguments.
	 *
	 *      @type int                           $rsvp_id    The RSVP ticket ID.
	 *      @type int                           $post_id    The ticket ID.
	 *      @type Tribe__Tickets__Ticket_Object $rsvp       The RSVP ticket object.
	 *      @type null|string                   $step       Which step being rendered.
	 *      @type boolean                       $must_login Whether login is required to register.
	 *      @type string                        $login_url  The site login URL.
	 *      @type int                           $threshold  The RSVP ticket threshold.
	 * }
	 *
	 * @return array The process result.
	 */
	public function process_rsvp_step( array $args ) {
		$result = [
			'success' => null,
			'errors'  => [],
		];

		// Process the attendee.
		if ( 'success' === $args['step'] ) {
			$first_attendee = $this->parse_attendee_details();
			$data = [
				'purchaser' => [
					'name'  => $first_attendee['full_name'],
					'email' => $first_attendee['email'],
				],
			];

			$purchaser = tribe( Order::class )->get_purchaser_data( $data );

			if ( is_wp_error( $purchaser ) ) {
				return $purchaser;
			}

			// Get the cart instance.
			$cart = tribe( RSVP_Cart::class );
			$cart->save();

			// Parse the ticket quantity for this RSVP.
			$ticket_id = $args['rsvp_id'];
			$quantity = $this->parse_ticket_quantity( $ticket_id );

			// Add the RSVP ticket to the cart.
			if ( $quantity > 0 ) {
				$extra_args = [
					'type'         => Constants::TC_RSVP_TYPE,
					'order_status' => $first_attendee['order_status'],
					'optout'       => $first_attendee['optout'],
				];

				/**
				 * Filter the extra arguments passed to cart upsert for RSVP tickets.
				 *
				 * This allows Event Tickets Plus to inject attendee meta into the cart item.
				 *
				 * @since TBD
				 *
				 * @param array $extra_args The extra arguments for the cart item.
				 * @param int   $ticket_id  The ticket ID.
				 * @param int   $quantity   The quantity of tickets.
				 * @param array $first_attendee The parsed attendee details.
				 */
				$extra_args = apply_filters( 'tec_tickets_commerce_rsvp_cart_upsert_item_args', $extra_args, $ticket_id, $quantity, $first_attendee );

				$cart->upsert_item(
					$ticket_id,
					$quantity,
					$extra_args
				);

				// Save the cart to persist the items.
				$cart->save();
			}

			$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser, Constants::TC_RSVP_TYPE );

			$created = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

			if ( is_wp_error( $created ) ) {
				return $created;
			}

			$updated = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			tribe( Cart::class )->clear_cart();

			$response['success']      = true;
			$response['id']           = $order->ID;
			$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $order->gateway_order_id ], tribe( Success::class )->get_url() );

			$attendees             = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
			$attendee_ids          = array_column( $attendees, 'attendee_id' );
			$response['attendees'] = $attendee_ids;

			$attendee_ids_flat = implode( ',', $attendee_ids );

			$nonce_action = 'tribe-tickets-rsvp-opt-in-' . md5( $attendee_ids_flat );

			$response['opt_in_args'] = [
				'is_going'     => ! empty( $first_attendee['order_status'] ) ? 'yes' === $first_attendee['order_status'] : false,
				'checked'      => false,
				'attendee_ids' => $attendee_ids_flat,
				'opt_in_nonce' => wp_create_nonce( $nonce_action ),
			];

			return $response;

		} elseif ( 'opt-in' === $args['step'] ) {
			$optout = ! tribe_is_truthy( tribe_get_request_var( 'opt_in', true ) );

			$attendee_ids = Tribe__Utils__Array::list_to_array( tribe_get_request_var( 'attendee_ids', [] ) );
			$attendee_ids = array_map( 'absint', $attendee_ids );

			$attendee_ids_flat = implode( ',', $attendee_ids );

			$nonce_value  = tribe_get_request_var( 'opt_in_nonce', '' );
			$nonce_action = 'tribe-tickets-rsvp-opt-in-' . md5( $attendee_ids_flat );

			if ( false === wp_verify_nonce( $nonce_value, $nonce_action ) ) {
				$result['success']  = false;
				$result['errors'][] = __( 'Unable to verify your opt-in request, please try again.', 'event-tickets' );

				return $result;
			}

			foreach ( $attendee_ids as $attendee_id ) {
				update_post_meta( $attendee_id, $this->module->attendee_optout_key, (int) $optout );
			}

			$result['success']     = true;
			$result['opt_in_args'] = [
				'is_going'     => true,
				'checked'      => ! $optout,
				'attendee_ids' => $attendee_ids_flat,
				'opt_in_nonce' => $nonce_value,
			];
		}

		return $result;
	}

	/**
	 * Handle RSVP processing for the RSVP forms.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket ID.
	 * @param null|string $step      Which step to render.
	 *
	 * @return string The step template HTML.
	 */
	public function render_rsvp_step( $ticket_id, $step = null ) {
		// No ticket.
		if ( 0 === $ticket_id ) {
			return '';
		}

		$post_id = (int) get_post_meta( $ticket_id,  Module::ATTENDEE_EVENT_KEY, true );

		// No post found, something went wrong.
		if ( 0 === $post_id ) {
			return '';
		}

		// Get post status.
		$post_status = get_post_status( $post_id );

		// Check if the post is private and the user can't read it.
		if ( 'private' === $post_status && ! current_user_can( 'read_private_posts' ) ) {
			return '';
		}

		// If post is anything other than private or published, return empty.
		if ( ! in_array( $post_status, [ 'publish', 'private' ] ) ) {
			return '';
		}

		// Check password if one exists.
		if ( post_password_required( $post_id ) ) {
			return '';
		}


		$ticket = $this->module->get_ticket( $post_id, $ticket_id );

		// No ticket found.
		if ( null === $ticket ) {
			return '';
		}

		// Set required template globals.
		$args = [
			'rsvp_id'    => $ticket_id,
			'ticket_id'  => $ticket_id,
			'post_id'    => $post_id,
			'rsvp'       => $ticket,
			'step'       => $step,
			'must_login' => ! is_user_logged_in() && $this->module->login_required(),
			'login_url'  => tribe( Checkout::class )->get_login_url(),
			'threshold'  => $this->blocks_rsvp->get_threshold( $post_id ),
			'going'      => tribe_get_request_var( 'going', 'yes' ),
			'attendees'  => [],
		];

		/**
		 * Allow filtering of the template arguments used prior to processing.
		 *
		 * @since TBD
		 *
		 * @param array $args {
		 *      The list of step template arguments.
		 *
		 *      @type int                           $rsvp_id    The RSVP ticket ID.
		 *      @type int                           $post_id    The ticket ID.
		 *      @type Tribe__Tickets__Ticket_Object $rsvp       The RSVP ticket object.
		 *      @type null|string                   $step       Which step being rendered.
		 *      @type boolean                       $must_login Whether login is required to register.
		 *      @type string                        $login_url  The site login URL.
		 *      @type int                           $threshold  The RSVP ticket threshold.
		 * }
		 */
		$args = apply_filters( 'tec_tickets_commerce_rsvp_render_step_template_args_pre_process', $args );

		$args['process_result'] = $this->process_rsvp_step( $args );

		/**
		 * Allow filtering of the template arguments used.
		 *
		 * @since TBD
		 *
		 * @param array $args {
		 *      The list of step template arguments.
		 *
		 *      @type int                           $rsvp_id        The RSVP ticket ID.
		 *      @type int                           $post_id        The ticket ID.
		 *      @type Tribe__Tickets__Ticket_Object $rsvp           The RSVP ticket object.
		 *      @type null|string                   $step           Which step being rendered.
		 *      @type boolean                       $must_login     Whether login is required to register.
		 *      @type string                        $login_url      The site login URL.
		 *      @type int                           $threshold      The RSVP ticket threshold.
		 *      @type array                         $process_result The processing result.
		 * }
		 */
		$args = apply_filters( 'tec_tickets_commerce_rsvp_render_step_template_args', $args );

		// Return the process result for opt-in.
		if ( false === $args['process_result']['success'] ) {
			return $args['process_result'];
		}

		$args['opt_in_checked']      = false;
		$args['opt_in_attendee_ids'] = '';
		$args['opt_in_nonce']        = '';
		$args['is_going']            = null;

		if ( ! empty( $args['process_result']['opt_in_args'] ) ) {
			// Refresh ticket.
			$args['rsvp']                = $this->module->get_ticket( $post_id, $ticket_id );
			$args['is_going']            = $args['process_result']['opt_in_args']['is_going'];
			$args['opt_in_checked']      = $args['process_result']['opt_in_args']['checked'];
			$args['opt_in_attendee_ids'] = $args['process_result']['opt_in_args']['attendee_ids'];
			$args['opt_in_nonce']        = $args['process_result']['opt_in_args']['opt_in_nonce'];
		}

		if ( ! empty( $args['process_result']['attendees'] ) ) {
			$args['attendees'] = $args['process_result']['attendees'];
		}

		// Handle Event Tickets logic.
		$hide_attendee_list_optout = true;

		/**
		 * Allow filtering of whether to show the opt-in option for attendees.
		 *
		 * @since TBD
		 *
		 * @param bool $hide_attendee_list_optout Whether to hide attendees list opt-out.
		 * @param int  $post_id                   The post ID that the ticket belongs to.
		 * @param int  $ticket_id                 The ticket ID.
		 */
		$hide_attendee_list_optout = apply_filters( 'tec_tickets_commerce_rsvp_hide_attendees_list_optout', $hide_attendee_list_optout, $post_id, $ticket_id );

		if ( false === $args['is_going'] ) {
			$hide_attendee_list_optout = true;
		}

		$args['opt_in_toggle_hidden'] = $hide_attendee_list_optout;

		// Add the rendering attributes into global context.
		$this->template->add_template_globals( $args );

		$html  = $this->template->template( 'v2/components/loader/loader', [ 'classes' => [] ], false );
		$html .= $this->template->template( 'v2/commerce/rsvp/content', $args, false );

		return $html;
	}

	/**
	 * @param $post_id
	 *
	 * @return array|false
	 */
	public function parse_attendee_details() {
		$first_attendee = [];

		if ( ! empty( $_POST['tribe_tickets'] ) ) {
			$first_ticket = current( $_POST['tribe_tickets'] );

			if ( ! empty( $first_ticket['attendees'] ) ) {
				$first_attendee = current( $first_ticket['attendees'] );
			}
		} elseif ( isset( $_POST['attendee'] ) ) {
			$first_attendee = $_POST['attendee'];
		}

		$attendee_email        = empty( $first_attendee['email'] ) ? null : htmlentities( sanitize_email( html_entity_decode( $first_attendee['email'] ) ) );
		$attendee_email        = is_email( $attendee_email ) ? $attendee_email : null;
		$attendee_full_name    = empty( $first_attendee['full_name'] ) ? null : htmlentities( sanitize_text_field( html_entity_decode( $first_attendee['full_name'] ) ) );
		$attendee_optout       = empty( $first_attendee['optout'] ) ? 0 : $first_attendee['optout'];
		$attendee_order_status = empty( $first_attendee['order_status'] ) ? 'yes' : $first_attendee['order_status'];

		$attendee_optout = filter_var( $attendee_optout, FILTER_VALIDATE_BOOLEAN );

		if ( 'going' === $attendee_order_status ) {
			$attendee_order_status = 'yes';
		} elseif ( 'not-going' === $attendee_order_status ) {
			$attendee_order_status = 'no';
		}

		if ( ! $this->tickets_view->is_valid_rsvp_option( $attendee_order_status ) ) {
			$attendee_order_status = 'yes';
		}

		if ( ! $attendee_email || ! $attendee_full_name ) {
			return false;
		}

		$attendee_details = [
			'full_name'    => $attendee_full_name,
			'email'        => $attendee_email,
			'order_status' => $attendee_order_status,
			'optout'       => $attendee_optout,
		];

		return $attendee_details;
	}

	/**
	 * Parses the quantity of tickets requested for a product via the $_POST var.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int Either the requested quantity of tickets or `0` in any other case.
	 */
	public function parse_ticket_quantity( $ticket_id ) {
		$quantity = 0;

		if ( isset( $_POST['tribe_tickets'][ $ticket_id ]['quantity'] ) ) {
			$quantity = absint( $_POST['tribe_tickets'][ $ticket_id ]['quantity'] );
		} elseif ( isset( $_POST["quantity_{$ticket_id}"] ) ) {
			$quantity = absint( $_POST["quantity_{$ticket_id}"] );
		}

		return $quantity;
	}
}
