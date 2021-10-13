<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Communications\Email;
use TEC\Tickets\Commerce\Utils\Price;
use Tribe__Date_Utils as Dates;


/**
 * Class Order
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Order {
	/**
	 * Tickets Commerce Order Post Type slug.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tc_order';

	/**
	 * Which meta holds which gateway was used on this order.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $gateway_meta_key = '_tec_tc_order_gateway';

	/**
	 * Which meta holds which gateway order id was used on this order.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $gateway_order_id_meta_key = '_tec_tc_order_gateway_order_id';

	/**
	 * Normally when dealing with the gateways we have a payload from the original creation of the Order on their side
	 * of the API, we should store that whole Payload with this meta key so that this data can be used in the future.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $gateway_payload_meta_key = '_tec_tc_order_gateway_payload';

	/**
	 * Which meta holds the cart items used to setup this order.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $cart_items_meta_key = '_tec_tc_order_cart_items';

	/**
	 * Which meta holds the tickets in a given order, they are added as individual meta items, allowing them to be
	 * selected in a meta query.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $tickets_in_order_meta_key = '_tec_tc_order_tickets_in_order';

	/**
	 * Which meta holds the events in a given order, they are added as individual meta items, allowing them to be
	 * selected in a meta query.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $events_in_order_meta_key = '_tec_tc_order_events_in_order';

	/**
	 * Which meta holds the cart items used to setup this order.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $total_value_meta_key = '_tec_tc_order_total_value';

	/**
	 * Which meta holds the cart items used to setup this order.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $currency_meta_key = '_tec_tc_order_currency';

	/**
	 * Which meta holds the purchaser full name.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_full_name_meta_key = '_tec_tc_order_purchaser_full_name';

	/**
	 * Which meta holds the purchaser first name.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_first_name_meta_key = '_tec_tc_order_purchaser_first_name';

	/**
	 * Which meta holds the purchaser last name.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_last_name_meta_key = '_tec_tc_order_purchaser_last_name';

	/**
	 * Which meta holds the cart items used to setup this order.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_email_meta_key = '_tec_tc_order_purchaser_email';

	/**
	 * Which meta holds the purchaser user id.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_user_id_meta_key = '_tec_tc_order_purchaser_user_id';

	/**
	 * Prefix for the log of when a given status was applied.
	 *
	 * @since 5.1.10
	 *
	 * @var string
	 */
	public static $status_log_meta_key_prefix = '_tec_tc_order_status_log';

	/**
	 * Prefix for the Status Flag Action marker meta key.
	 *
	 * @since 5.1.10
	 *
	 * @var string
	 */
	public static $flag_action_status_marker_meta_key_prefix = '_tec_tc_order_fa_marker';

	/**
	 * Meta that holds the cart hash for this order.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $cart_hash_meta_key = '_tec_tc_order_cart_hash';

	/**
	 * Register this Class post type into WP.
	 *
	 * @since 5.1.9
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Orders', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
		];

		/**
		 * Filter the arguments that craft the order post type.
		 *
		 * @see   register_post_type
		 *
		 * @since 5.1.9
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 */
		$post_type_args = apply_filters( 'tec_tickets_commerce_order_post_type_args', $post_type_args );

		register_post_type( static::POSTTYPE, $post_type_args );
	}

	/**
	 * Gets the meta Key for a given Order Status gateway_payload.
	 *
	 * @since 5.1.9
	 *
	 * @param Status\Status_Interface $status
	 *
	 * @return string
	 */
	public static function get_status_log_meta_key( Commerce\Status\Status_Interface $status ) {
		return static::$status_log_meta_key_prefix . '_' . $status->get_slug();
	}

	/**
	 * Gets the meta Key for a given Order Status gateway_payload.
	 *
	 * @since 5.1.10
	 *
	 * @param Status\Status_Interface $status
	 *
	 * @return string
	 */
	public static function get_gateway_payload_meta_key( Commerce\Status\Status_Interface $status ) {
		return static::$gateway_payload_meta_key . '_' . $status->get_slug();
	}

	/**
	 * Gets the key for a Flag Action marker for given status and flag.
	 *
	 * @since 5.1.10
	 *
	 * @param string $flag   Which flag we are getting the meta key for.
	 * @param string $status Which status ID we are getting the meta key for.
	 *
	 * @return string
	 */
	public static function get_flag_action_marker_meta_key( $flag, Commerce\Status\Status_Interface $status ) {
		$prefix = static::$flag_action_status_marker_meta_key_prefix;

		return "{$prefix}:{$status->get_slug()}:{$flag}";
	}

	/**
	 * Modify the status of a given order based on Slug.
	 *
	 * @since 5.1.9
	 *
	 * @throws \Tribe__Repository__Usage_Error
	 *
	 * @param int    $order_id    Which order ID will be updated.
	 * @param string $status_slug Which Order Status we are modifying to.
	 * @param array  $extra_args  Extra repository arguments.
	 *
	 * @return bool|\WP_Error
	 */
	public function modify_status( $order_id, $status_slug, array $extra_args = [] ) {
		$status = tribe( Commerce\Status\Status_Handler::class )->get_by_slug( $status_slug );

		if ( ! $status ) {
			return false;
		}

		$can_apply = $status->can_apply_to( $order_id );
		if ( ! $can_apply ) {
			return $can_apply;
		}

		$args = array_merge( $extra_args, [ 'status' => $status->get_wp_slug() ] );

		$updated = tec_tc_orders()->by_args( [
			'status' => 'any',
			'id'     => $order_id,
		] )->set_args( $args )->save();

		// After modifying the status we add a meta to flag when it was modified.
		if ( $updated ) {
			$time = Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT );
			add_post_meta( $order_id, static::get_status_log_meta_key( $status ), $time );
		}

		return (bool) $updated;
	}

	/**
	 * Creates a order from the items in the cart.
	 *
	 * @since 5.1.9
	 *
	 * @throws \Tribe__Repository__Usage_Error
	 *
	 * @return false|\WP_Post
	 */
	public function create_from_cart( Commerce\Gateways\Interface_Gateway $gateway, $purchaser = null ) {
		$cart = tribe( Cart::class );

		$items      = $cart->get_items_in_cart();
		$items      = array_map( static function ( $item ) {
			$ticket = \Tribe__Tickets__Tickets::load_ticket_object( $item['ticket_id'] );
			if ( null === $ticket ) {
				return null;
			}

			$item['sub_total'] = Price::sub_total( $ticket->price, $item['quantity'] );
			$item['price']     = $ticket->price;

			return $item;
		}, $items );
		$items      = array_filter( $items );
		$sub_totals = array_filter( wp_list_pluck( $items, 'sub_total' ) );
		$total      = Price::total( $sub_totals );

		$order_args = [
			'title'       => $this->generate_order_title( $items, $cart->get_cart_hash() ),
			'total_value' => $total,
			'cart_items'  => $items,
			'gateway'     => $gateway::get_key(),
			'cart_hash'   => $cart->get_cart_hash(),
		];

		// When purchaser data-set is not passed we pull from the current user.
		if ( empty( $purchaser ) && is_user_logged_in() && $user = wp_get_current_user() ) {
			$order_args['purchaser_user_id']    = $user->ID;
			$order_args['purchaser_full_name']  = $user->first_name . ' ' . $user->last_name;
			$order_args['purchaser_first_name'] = $user->first_name;
			$order_args['purchaser_last_name']  = $user->last_name;
			$order_args['purchaser_email']      = $user->user_email;
		} elseif ( empty( $purchaser ) ) {
			$order_args['purchaser_user_id']    = 0;
			$order_args['purchaser_full_name']  = 'Pending...';
			$order_args['purchaser_first_name'] = 'Pending...';
			$order_args['purchaser_last_name']  = 'Pending...';
			$order_args['purchaser_email']      = '';
		}

		$order = tec_tc_orders()->set_args( $order_args )->create();

		// We were unable to create the order bail from here.
		if ( ! $order ) {
			return false;
		}

		return $order;
	}

	/**
	 * Generates a title based on Cart Hash, items in the cart.
	 *
	 * @since 5.1.9
	 *
	 * @param array $items List of events form
	 *
	 * @return string
	 */
	public function generate_order_title( $items, $hash = null ) {
		$title = [ 'TEC-TC' ];
		if ( $hash ) {
			$title[] = $hash;
		}
		$title[] = 'T';

		$tickets = array_filter( wp_list_pluck( $items, 'ticket_id' ) );
		$title   = array_merge( $title, $tickets );

		return implode( '-', $title );
	}

	/**
	 * Redirects to the source post after a recoverable (logic) error.
	 *
	 * @todo  Determine if redirecting should be something relegated to some other method, and here we just actually
	 *       generate the order/Attendees.
	 *
	 * @see   \Tribe__Tickets__Commerce__PayPal__Errors for error codes translations.
	 * @since 5.1.9
	 *
	 * @param bool $redirect   Whether to really redirect or not.
	 * @param int  $post_id    A post ID
	 *
	 * @param int  $error_code The current error code
	 *
	 */
	protected function redirect_after_error( $error_code, $redirect, $post_id ) {
		$url = add_query_arg( 'tpp_error', $error_code, get_permalink( $post_id ) );
		if ( $redirect ) {
			wp_redirect( esc_url_raw( $url ) );
		}
		tribe_exit();
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 *
	 * @since 5.1.9
	 *
	 * @param string $payment_status The tickets payment status, defaults to completed.
	 * @param bool   $redirect       Whether the client should be redirected or not.
	 *
	 */
	public function generate_order( $payment_status = 'completed', $redirect = true ) {
		/*
		 * This method might run during a POST (IPN) PayPal request hence the
		 * purchasing user ID, if any, will be stored in a custom PayPal var.
		 * Let's fallback on the current user ID for GET requests (PDT); it will be always `0`
		 * during a PayPal POST (IPN) request.
		 */
		$attendee_user_id = ! isset( $custom['user_id'] ) ? get_current_user_id() : absint( $custom['user_id'] );

		$attendee_full_name = empty( $transaction_data['first_name'] ) && empty( $transaction_data['last_name'] )
			? ''
			: sanitize_text_field( "{$transaction_data['first_name']} {$transaction_data['last_name']}" );

		$attendee_email = empty( $transaction_data['payer_email'] ) ? null : sanitize_email( $transaction_data['payer_email'] );
		$attendee_email = is_email( $attendee_email ) ? $attendee_email : null;

		if ( ! empty( $attendee_user_id ) ) {
			$attendee = get_user_by( 'id', $attendee_user_id );

			// Check if the user was found.
			if ( $attendee ) {
				// Check if the user has an email address.
				if ( $attendee->user_email ) {
					$attendee_email = $attendee->user_email;
				}

				$user_full_name = trim( "{$attendee->first_name} {$attendee->last_name}" );

				// Check if the user has first/last name.
				if ( ! empty( $user_full_name ) ) {
					$attendee_full_name = $user_full_name;
				}
			}
		}

		/**
		 * This is an array of tickets IDs for which the user decided to opt-out.
		 *
		 * @see \Tribe__Tickets_Plus__Commerce__PayPal__Attendees::register_optout_choice()
		 */
		$attendee_optouts = \Tribe__Utils__Array::get( $custom, 'oo', [] );

		if ( ! $attendee_email || ! $attendee_full_name ) {
			$this->redirect_after_error( 101, $redirect, $post_id );

			return;
		}

		// Iterate over each product
		foreach ( (array) $transaction_data['items'] as $item ) {
			$order_attendee_id = 0;

			if ( empty( $item['ticket'] ) ) {
				continue;
			}

			/** @var \Tribe__Tickets__Ticket_Object $ticket_type */
			$ticket_type = $item['ticket'];
			$product_id  = $ticket_type->ID;

			// Get the event this tickets is for
			$post = $ticket_type->get_event();

			if ( empty( $post ) ) {
				continue;
			}

			$post_id = $post->ID;

			// if there were no PayPal tickets for the product added to the cart, continue
			if ( empty( $item['quantity'] ) ) {
				continue;
			}

			// get the PayPal status `decrease_stock_by` value
			$status_stock_size = 1;

			$ticket_qty = (int) $item['quantity'];

			// to avoid tickets from not being created on a status stock size of 0
			// let's take the status stock size into account and create a number of tickets
			// at least equal to the number of tickets the user requested
			$ticket_qty = $status_stock_size < 1 ? $ticket_qty : $status_stock_size * $ticket_qty;

			$qty = max( $ticket_qty, 0 );

			// Throw an error if Qty is bigger then Remaining
			if ( $payment_status === tribe( Commerce\Status\Completed::class )->get_wp_slug() && $ticket_type->managing_stock() ) {
				add_action( 'tec_tickets_commerce_pending_stock_ignore', '__return_true' );
				$inventory = (int) $ticket_type->inventory();
				remove_action( 'tec_tickets_commerce_pending_stock_ignore', '__return_true' );

				$inventory_is_not_unlimited = - 1 !== $inventory;

				if ( $inventory_is_not_unlimited && $qty > $inventory ) {
					if ( ! $order->was_pending() ) {
						$this->redirect_after_error( 102, $redirect, $post_id );

						return;
					}

					/** @var \Tribe__Tickets__Commerce__PayPal__Oversell__Policies $oversell_policies */
					$oversell_policies = tribe( 'tickets.commerce.paypal.oversell.policies' );
					$oversell_policy   = $oversell_policies->for_post_ticket_order( $post_id, $ticket_type->ID, $order_id );

					$qty = $oversell_policy->modify_quantity( $qty, $inventory );

					if ( ! $oversell_policy->allows_overselling() ) {
						$oversold_attendees = tribe( Module::class )->get_attendees_by_order_id( $order_id );
						$oversell_policy->handle_oversold_attendees( $oversold_attendees );
						$this->redirect_after_error( 102, $redirect, $post_id );

						return;
					}
				}
			}

			if ( $qty === 0 ) {
				$this->redirect_after_error( 103, $redirect, $post_id );

				return;
			}

			$has_tickets = true;

			/**
			 * PayPal specific action fired just before a PayPal-driven attendee ticket for an event is generated
			 *
			 * @since 4.7
			 *
			 * @param int    $post_id     ID of event
			 * @param string $ticket_type Ticket Type object for the product
			 * @param array  $data        Parsed PayPal transaction data
			 */
			do_action( 'tribe_tickets_tpp_before_attendee_ticket_creation', $post_id, $ticket_type, $transaction_data );

			$existing_attendees = tribe( Module::class )->get_attendees_by_order_id( $order_id );

			$has_generated_new_tickets = false;

			/** @var \Tribe__Tickets__Commerce__Currency $currency */
			$currency        = tribe( 'tickets.commerce.currency' );
			$currency_symbol = $currency->get_currency_symbol( $product_id, true );

			// Iterate over all the amount of tickets purchased (for this product)
			for ( $i = 0; $i < $qty; $i ++ ) {
				$attendee_id       = null;
				$updating_attendee = false;

				/**
				 * Allow filtering the individual attendee name used when creating a new attendee.
				 *
				 * @since 5.0.3
				 *
				 * @param string                   $individual_attendee_name The attendee full name.
				 * @param int|null                 $attendee_number          The attendee number index value from the order, starting with zero.
				 * @param int                      $order_id                 The order ID.
				 * @param int                      $ticket_id                The ticket ID.
				 * @param int                      $post_id                  The ID of the post associated to the ticket.
				 * @param \Tribe__Tickets__Tickets $provider                 The current ticket provider object.
				 */
				$individual_attendee_name = apply_filters( 'tribe_tickets_attendee_create_individual_name', $attendee_full_name, $i, $order_id, $product_id, $post_id, $this );

				/**
				 * Allow filtering the individual attendee email used when creating a new attendee.
				 *
				 * @since 5.0.3
				 *
				 * @param string                   $individual_attendee_email The attendee email.
				 * @param int|null                 $attendee_number           The attendee number index value from the order, starting with zero.
				 * @param int                      $order_id                  The order ID.
				 * @param int                      $ticket_id                 The ticket ID.
				 * @param int                      $post_id                   The ID of the post associated to the ticket.
				 * @param \Tribe__Tickets__Tickets $provider                  The current ticket provider object.
				 */
				$individual_attendee_email = apply_filters( 'tribe_tickets_attendee_create_individual_email', $attendee_email, $i, $order_id, $product_id, $post_id, $this );

				// check if we already have an attendee or not
				$post_title        = $individual_attendee_name . ' | ' . ( $i + 1 );
				$criteria          = [ 'post_title' => $post_title, 'product_id' => $product_id, 'event_id' => $post_id ];
				$existing_attendee = wp_list_filter( $existing_attendees, $criteria );

				if ( ! empty( $existing_attendee ) ) {
					$existing_attendee = reset( $existing_attendee );
					$updating_attendee = true;
					$attendee_id       = $existing_attendee['attendee_id'];
					$attendee          = [];
				} else {
					$attendee = [
						'post_title' => $post_title,
					];

					// since we are creating at least one
					$has_generated_new_tickets = true;
				}

				$attendee_order_status = trim( strtolower( $payment_status ) );

				$repository = tribe_attendees( $this->orm_provider );

				$data = $attendee;

				$data['order_attendee_id'] = $order_attendee_id;
				$data['attendee_status']   = $attendee_order_status;

				if ( Order_Statuses::$refunded === $payment_status ) {
					$refund_order_id = \Tribe__Utils__Array::get( $transaction_data, 'txn_id', '' );

					$data['refund_order_id'] = $refund_order_id;
				}

				if ( ! $updating_attendee ) {
					$optout = \Tribe__Utils__Array::get( $attendee_optouts, 'ticket_' . $product_id, false );
					$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
					$optout = $optout ? 'yes' : 'no';

					$data['ticket_id']      = $product_id;
					$data['post_id']        = $post_id;
					$data['order_id']       = $order_id;
					$data['optout']         = $optout;
					$data['full_name']      = $individual_attendee_name;
					$data['email']          = $individual_attendee_email;
					$data['price_paid']     = get_post_meta( $product_id, '_price', true );
					$data['price_currency'] = $currency_symbol;

					if ( 0 < $attendee_user_id ) {
						$data['user_id'] = $attendee_user_id;
					}

					$attendee_object = tribe( Module::class )->create_attendee( $ticket_type, $data );
					$attendee_id     = $attendee_object->ID;

				} else {
					// Update attendee.
					tribe( Module::class )->update_attendee( $attendee_id, $data );
				}

				$order->add_attendee( $attendee_id );

				$order_attendee_id ++;

				if ( ! empty( $existing_attendee ) ) {
					$existing_attendees = wp_list_filter( $existing_attendees, array( 'attendee_id' => $existing_attendee['attendee_id'] ), 'NOT' );
				}
			}

			if ( ! ( empty( $existing_attendees ) || empty( $oversell_policy ) ) ) {
				// an oversell policy applied: what to do with existing oversold attendees?
				$oversell_policy->handle_oversold_attendees( $existing_attendees );
			}

			if ( $has_generated_new_tickets ) {
				/**
				 * Action fired when a PayPal has had attendee tickets generated for it.
				 *
				 * @since 4.7
				 *
				 * @param int    $product_id PayPal ticket post ID
				 * @param string $order_id   ID of the PayPal order
				 * @param int    $qty        Quantity ordered
				 */
				do_action( 'event_tickets_tpp_tickets_generated_for_product', $product_id, $order_id, $qty );
			}

			/**
			 * Action fired when a PayPal has had attendee tickets updated for it.
			 *
			 * This will fire even when tickets are initially created; if you need to hook on the
			 * creation process only use the 'event_tickets_tpp_tickets_generated_for_product' action.
			 *
			 * @since 4.7
			 *
			 * @param int    $product_id PayPal ticket post ID
			 * @param string $order_id   ID of the PayPal order
			 * @param int    $qty        Quantity ordered
			 */
			do_action( 'event_tickets_tpp_tickets_generated_for_product', $product_id, $order_id, $qty );

			// After Adding the Values we Update the Transient
			\Tribe__Post_Transient::instance()->delete( $post_id, \Tribe__Tickets__Tickets::ATTENDEES_CACHE );
		}

		/**
		 * Fires when an PayPal attendee tickets have been generated.
		 *
		 * @since 4.7
		 *
		 * @param string $order_id ID of the PayPal order
		 * @param int    $post_id  ID of the post the order was placed for
		 */
		do_action( 'event_tickets_tpp_tickets_generated', $order_id, $post_id );

		/**
		 * Filters whether a confirmation email should be sent or not for PayPal tickets.
		 *
		 * This applies to attendance and non attendance emails.
		 *
		 * @since 4.7
		 *
		 * @param bool $send_mail Defaults to `true`.
		 */
		$send_mail = apply_filters( 'tribe_tickets_tpp_send_mail', true );

		if (
			$send_mail
			&& $has_tickets
			&& $attendee_order_status === Order_Statuses::$completed
		) {
			tribe( Email::class )->send_tickets_email( $order_id, $post_id );
		}
	}
}