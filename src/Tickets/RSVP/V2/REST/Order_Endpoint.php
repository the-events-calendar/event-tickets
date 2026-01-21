<?php
/**
 * RSVP V2: Order Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */

namespace TEC\Tickets\RSVP\V2\REST;

use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Success;
use TEC\Tickets\RSVP\V2\Constants;
use Tribe__Tickets__Tickets_View as Tickets_View;
use Tribe__Tickets__Ticket_Object;
use Tribe__Utils__Array;
use Tribe__Tickets__Editor__Blocks__Rsvp as RSVP_Block;
use Tribe__Tickets__Editor__Template as Template;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Order_Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {
	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/rsvp/v2/order';

	/**
	 * @since TBD
	 *
	 * @var Tickets_View
	 */
	protected Tickets_View $tickets_view;

	/**
	 * @since TBD
	 *
	 * @var Module
	 */
	protected Module $module;

	/**
	 * RSVP blocks editor instance.
	 *
	 * @since TBD
	 *
	 * @var RSVP_Block
	 */
	protected RSVP_Block $blocks_rsvp;

	/**
	 * Tickets template renderer instance.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	protected Template $template;

	/**
	 * Class constructor.
	 *
	 * @since TBD
	 *
	 * @param RSVP_Block $block    The RSVP block instance.
	 * @param Template   $template The template instance.
	 * @param Module     $module   The Tickets Commerce module instance.
	 */
	public function __construct( RSVP_Block $block, Template $template, Module $module ) {
		$this->tickets_view = Tickets_View::instance();
		$this->module       = $module;
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

				/*
				 * RSVPs are publicly accessible: any site visitor, including guests, can submit an RSVP.
				 * Additional validation (post status, password protection, login requirements) is handled
				 * within the callback.
				 */
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
	 *
	 * @return RSVP_Cart
	 */
	public function setup_cart( $cart ): RSVP_Cart {
		return tribe( RSVP_Cart::class );
	}

	/**
	 * Handles RSVP form step requests via REST API.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return WP_REST_Response The response containing success status and HTML content.
	 */
	public function handle_steps( WP_REST_Request $request ): WP_REST_Response {
		$response = [
			'success' => false,
			'html'    => '',
		];

		$ticket_id = absint( $request->get_param( 'ticket_id' ) ?: 0 );
		$step      = $request->get_param( 'step' );

		add_filter( 'tec_tickets_commerce_cart_repository', [ $this, 'setup_cart' ] );

		$render_response = $this->render_rsvp_step( $ticket_id, $request, $step );

		if ( is_string( $render_response ) && '' !== $render_response ) {
			$response['html'] = $render_response;

			$response['success'] = true;
			return new WP_REST_Response( $response );
		}

		if ( is_array( $render_response ) && ! empty( $render_response['errors'] ) ) {
			$response['html'] = $this->render_rsvp_error( $render_response['errors'] );

			$response['success'] = true;
			return new WP_REST_Response( $response );
		}

		$response['html'] = $this->render_rsvp_error( __( 'Something happened here.', 'event-tickets' ) );

		return new WP_REST_Response( $response );
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
	public function render_rsvp_error( $error_message ): string {
		$args = [
			'error_message' => $error_message,
		];

		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$template->add_template_globals( $args );

		return $template->template( 'v2/commerce/rsvp/messages/error', $args, false );
	}

	/**
	 * Handle processing the RSVP step based on current arguments.
	 *
	 * @since TBD
	 *
	 * @param array           $args    {
	 *     The list of step template arguments.
	 *
	 *     @type int                           $rsvp_id    The RSVP ticket ID.
	 *     @type int                           $post_id    The ticket ID.
	 *     @type Tribe__Tickets__Ticket_Object $rsvp       The RSVP ticket object.
	 *     @type null|string                   $step       Which step being rendered.
	 *     @type boolean                       $must_login Whether login is required to register.
	 *     @type string                        $login_url  The site login URL.
	 *     @type int                           $threshold  The RSVP ticket threshold.
	 * }
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return array The process result.
	 */
	public function process_rsvp_step( array $args, $request ): array {
		$result = [
			'success' => null,
			'errors'  => [],
		];

		if ( 'success' === $args['step'] ) {
			$first_attendee = $this->parse_attendee_details( $request );

			if ( false === $first_attendee ) {
				return [
					'success' => false,
					'errors'  => [
						_x( 'Invalid attendee details', 'error message', 'event-tickets' ),
					],
				];
			}

			$data = [
				'purchaser' => [
					'name'  => $first_attendee['full_name'],
					'email' => $first_attendee['email'],
				],
			];

			$purchaser = tribe( Order::class )->get_purchaser_data( $data );

			if ( is_wp_error( $purchaser ) ) {
				return $this->wp_error_to_result( $purchaser );
			}

			$cart = tribe( RSVP_Cart::class );
			$cart->clear();
			$cart->save();

			$ticket_id = $args['rsvp_id'];
			$quantity  = $this->parse_ticket_quantity( $ticket_id, $request );

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
				 * @param array           $extra_args     The extra arguments for the cart item.
				 * @param int             $ticket_id      The ticket ID.
				 * @param int             $quantity       The quantity of tickets.
				 * @param array           $first_attendee The parsed attendee details.
				 * @param WP_REST_Request $request        The REST API request object.
				 */
				$extra_args = apply_filters( 'tec_tickets_rsvp_v2_cart_upsert_item_args', $extra_args, $ticket_id, $quantity, $first_attendee, $request );

				$cart->upsert_item(
					$ticket_id,
					$quantity,
					$extra_args
				);

				$cart->save();
			}

			$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser, Constants::TC_RSVP_TYPE );

			$created = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

			if ( is_wp_error( $created ) ) {
				return $this->wp_error_to_result( $created );
			}

			$updated = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

			if ( is_wp_error( $updated ) ) {
				return $this->wp_error_to_result( $updated );
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

		}

		if ( 'opt-in' === $args['step'] ) {
			$opt_in_value = $request->get_param( 'opt_in' ) ?? true;
			$optout       = ! tribe_is_truthy( $opt_in_value );

			$attendee_ids_param = $request->get_param( 'attendee_ids' ) ?? [];
			$attendee_ids       = Tribe__Utils__Array::list_to_array( $attendee_ids_param );
			$attendee_ids       = array_map( 'absint', $attendee_ids );

			$attendee_ids_flat = implode( ',', $attendee_ids );

			$nonce_value  = $request->get_param( 'opt_in_nonce' ) ?? '';
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
	 * @param int             $ticket_id The ticket ID.
	 * @param WP_REST_Request $request   The REST API request object.
	 * @param null|string     $step      Which step to render.
	 *
	 * @return string|array<string,mixed> The step template HTML or an array with errors.
	 */
	public function render_rsvp_step( $ticket_id, $request, $step = null ) {
		if ( 0 === $ticket_id ) {
			return '';
		}

		$post_id = (int) get_post_meta( $ticket_id, Module::ATTENDEE_EVENT_KEY, true );

		if ( 0 === $post_id ) {
			return '';
		}

		$post_status = get_post_status( $post_id );

		if ( 'private' === $post_status && ! current_user_can( 'read_private_posts' ) ) {
			return '';
		}

		if ( ! in_array( $post_status, [ 'publish', 'private' ] ) ) {
			return '';
		}

		if ( post_password_required( $post_id ) ) {
			return '';
		}

		$ticket = $this->module->get_ticket( $post_id, $ticket_id );

		if ( null === $ticket ) {
			return '';
		}

		$args = [
			'rsvp_id'    => $ticket_id,
			'ticket_id'  => $ticket_id,
			'post_id'    => $post_id,
			'rsvp'       => $ticket,
			'step'       => $step,
			'must_login' => ! is_user_logged_in() && $this->module->login_required(),
			'login_url'  => tribe( Checkout::class )->get_login_url(),
			'threshold'  => $this->blocks_rsvp->get_threshold( $post_id ),
			'going'      => $request->get_param( 'going' ) ?? 'yes',
			'attendees'  => [],
		];

		/**
		 * Allow filtering of the template arguments used prior to processing.
		 *
		 * @since TBD
		 *
		 * @param array           $args    {
		 *     The list of step template arguments.
		 *
		 *     @type int                           $rsvp_id    The RSVP ticket ID.
		 *     @type int                           $post_id    The ticket ID.
		 *     @type Tribe__Tickets__Ticket_Object $rsvp       The RSVP ticket object.
		 *     @type null|string                   $step       Which step being rendered.
		 *     @type boolean                       $must_login Whether login is required to register.
		 *     @type string                        $login_url  The site login URL.
		 *     @type int                           $threshold  The RSVP ticket threshold.
		 * }
		 * @param WP_REST_Request $request The REST API request object.
		 */
		$args = apply_filters( 'tec_tickets_rsvp_v2_render_step_template_args_pre_process', $args, $request );

		$args['process_result'] = $this->process_rsvp_step( $args, $request );

		/**
		 * Allow filtering of the template arguments used.
		 *
		 * @since TBD
		 *
		 * @param array $args {
		 *     The list of step template arguments.
		 *
		 *     @type int                           $rsvp_id        The RSVP ticket ID.
		 *     @type int                           $post_id        The ticket ID.
		 *     @type Tribe__Tickets__Ticket_Object $rsvp           The RSVP ticket object.
		 *     @type null|string                   $step           Which step being rendered.
		 *     @type boolean                       $must_login     Whether login is required to register.
		 *     @type string                        $login_url      The site login URL.
		 *     @type int                           $threshold      The RSVP ticket threshold.
		 *     @type array                         $process_result The processing result.
		 * }
		 */
		$args = apply_filters( 'tec_tickets_rsvp_v2_render_step_template_args', $args );

		if ( false === $args['process_result']['success'] ) {
			return $args['process_result'];
		}

		$args['opt_in_checked']      = false;
		$args['opt_in_attendee_ids'] = '';
		$args['opt_in_nonce']        = '';
		$args['is_going']            = null;

		if ( ! empty( $args['process_result']['opt_in_args'] ) ) {
			$args['rsvp']                = $this->module->get_ticket( $post_id, $ticket_id );
			$args['is_going']            = $args['process_result']['opt_in_args']['is_going'];
			$args['opt_in_checked']      = $args['process_result']['opt_in_args']['checked'];
			$args['opt_in_attendee_ids'] = $args['process_result']['opt_in_args']['attendee_ids'];
			$args['opt_in_nonce']        = $args['process_result']['opt_in_args']['opt_in_nonce'];
		}

		if ( ! empty( $args['process_result']['attendees'] ) ) {
			$args['attendees'] = $args['process_result']['attendees'];
		}

		$show_attendee_list_optout = false;

		/**
		 * Allow filtering of whether to show the opt-in option for attendees.
		 *
		 * @since TBD
		 *
		 * @param bool $show_attendee_list_optout Whether to show attendees list opt-out.
		 * @param int  $post_id                   The post ID that the ticket belongs to.
		 * @param int  $ticket_id                 The ticket ID.
		 */
		$show_attendee_list_optout = apply_filters( 'tec_tickets_rsvp_v2_show_attendees_list_optout', $show_attendee_list_optout, $post_id, $ticket_id );

		if ( false === $args['is_going'] ) {
			$show_attendee_list_optout = false;
		}

		$args['opt_in_toggle_hidden'] = $show_attendee_list_optout;

		$this->template->add_template_globals( $args );

		$html  = $this->template->template( 'v2/components/loader/loader', [ 'classes' => [] ], false );
		$html .= $this->template->template( 'v2/commerce/rsvp/content', $args, false );

		return $html;
	}

	/**
	 * Parse and validate attendee details from POST data.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 *
	 * @return array|false {
	 *     Array of attendee details on success, false on failure.
	 *
	 *     @type string $full_name    The attendee's full name.
	 *     @type string $email        The attendee's email address.
	 *     @type string $order_status The RSVP status ('yes' or 'no').
	 *     @type bool   $optout       Whether the attendee opted out of the attendee list.
	 * }
	 */
	public function parse_attendee_details( $request ) {
		$first_attendee = [];

		$tribe_tickets = $request->get_param( 'tribe_tickets' );
		$attendee      = $request->get_param( 'attendee' );

		if ( ! empty( $tribe_tickets ) ) {
			$first_ticket = current( $tribe_tickets );

			if ( ! empty( $first_ticket['attendees'] ) ) {
				$first_attendee = current( $first_ticket['attendees'] );
			}
		} elseif ( isset( $attendee ) ) {
			$first_attendee = $attendee;
		}

		$attendee_email        = empty( $first_attendee['email'] ) ?
			null
			: htmlentities(
				sanitize_email(
					html_entity_decode( $first_attendee['email'] )
				),
				ENT_COMPAT
			);
		$attendee_email        = is_email( $attendee_email ) ? $attendee_email : null;
		$attendee_full_name    = empty( $first_attendee['full_name'] ) ?
			null
			: htmlentities(
				sanitize_text_field(
					html_entity_decode( $first_attendee['full_name'] )
				),
				ENT_COMPAT
			);
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

		return [
			'full_name'    => $attendee_full_name,
			'email'        => $attendee_email,
			'order_status' => $attendee_order_status,
			'optout'       => $attendee_optout,
		];
	}

	/**
	 * Parses the quantity of tickets requested for a product via the request.
	 *
	 * @since TBD
	 *
	 * @param int             $ticket_id The ticket ID.
	 * @param WP_REST_Request $request   The REST API request object.
	 *
	 * @return int Either the requested quantity of tickets or `0` in any other case.
	 */
	public function parse_ticket_quantity( $ticket_id, $request ): int {
		$quantity = 0;

		$tribe_tickets = $request->get_param( 'tribe_tickets' );
		if ( ! empty( $tribe_tickets[ $ticket_id ]['quantity'] ) ) {
			$quantity = absint( $tribe_tickets[ $ticket_id ]['quantity'] );
		} else {
			$quantity_param = $request->get_param( "quantity_{$ticket_id}" );
			if ( null !== $quantity_param ) {
				$quantity = absint( $quantity_param );
			}
		}

		return $quantity;
	}

	/**
	 * Converts a WP_Error to the expected result array format.
	 *
	 * @since TBD
	 *
	 * @param WP_Error $error The WP_Error to convert.
	 *
	 * @return array The result array with success and errors keys.
	 */
	private function wp_error_to_result( WP_Error $error ): array {
		return [
			'success' => false,
			'errors'  => $error->get_error_messages(),
		];
	}
}
