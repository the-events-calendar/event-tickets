<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Date_Utils as Dates;

/**
 * Class Order
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Order extends Abstract_Order {

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
	 * Which meta holds the items used to setup this order.
	 *
	 * @since 5.1.9
	 * @since 5.2.0 Updated to use `_tec_tc_order_items` instead of `_tec_tc_order_items`.
	 *
	 * @var string
	 */
	public static $items_meta_key = '_tec_tc_order_items';

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
	 * Meta that holds the hash for this order, which at the time of purchase was unique.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	public static $hash_meta_key = '_tec_tc_order_hash';

	/**
	 * Meta value for placeholder names.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	public static $placeholder_name = 'Unknown';

	/**
	 * Register this Class post type into WP.
	 *
	 * @since 5.1.9
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Orders', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'capabilities'    => [
				'edit_post'              => 'not-allowed-cap', // 'edit_post',
				'read_post'              => 'read_post',
				'delete_post'            => 'not-allowed-cap', // 'delete_post',
				'edit_posts'             => 'edit_posts',
				'edit_others_posts'      => 'edit_others_posts',
				'delete_posts'           => 'not-allowed-cap', // 'delete_posts',
				'publish_posts'          => 'not-allowed-cap', // 'publish_posts',
				'read_private_posts'     => 'read_private_posts',
				'read'                   => 'read',
				'delete_private_posts'   => 'not-allowed-cap', // 'delete_private_posts',
				'delete_published_posts' => 'not-allowed-cap', // 'delete_published_posts',
				'delete_others_posts'    => 'not-allowed-cap', // 'delete_others_posts',
				'edit_private_posts'     => 'not-allowed-cap', // 'edit_private_posts',
				'edit_published_posts'   => 'not-allowed-cap', // 'edit_published_posts',
				'create_posts'           => 'not-allowed-cap', // 'edit_posts',
			],
			'has_archive'     => false,
			'hierarchical'    => false,
		];

		/**
		 * Filter the arguments that craft the order post type.
		 *
		 * @see   register_post_type
		 * @since 5.1.9
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 *
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
	public static function get_status_log_meta_key( Status\Status_Interface $status ) {
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
	public static function get_gateway_payload_meta_key( Status\Status_Interface $status ) {
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
	public static function get_flag_action_marker_meta_key( $flag, Status\Status_Interface $status ) {
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
		$status = tribe( Status\Status_Handler::class )->get_by_slug( $status_slug );

		if ( ! $status ) {
			return false;
		}

		$can_apply = $status->can_apply_to( $order_id, $status );
		if ( ! $can_apply ) {
			return $can_apply;
		}

		$args = array_merge( $extra_args, [ 'status' => $status->get_wp_slug() ] );

		$updated = tec_tc_orders()->by_args(
			[
				'status' => 'any',
				'id'     => $order_id,
			]
		)->set_args( $args )->save();

		// After modifying the status we add a meta to flag when it was modified.
		if ( $updated ) {
			$time = Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT );
			add_post_meta( $order_id, static::get_status_log_meta_key( $status ), $time );
		}

		return (bool) $updated;
	}

	/**
	 * Prepares an array of cart items ready to go into an Order.
	 *
	 * @since 5.3.0
	 *
	 * @param Cart $cart The cart instance to get items from.
	 *
	 * @return array
	 */
	public function prepare_cart_items_for_order( Cart $cart ) {
		return array_map(
			static function ( $item ) {
				/** @var Value $ticket_value */
				$ticket_value = tribe( Ticket::class )->get_price_value( $item['ticket_id'] );

				if ( null === $ticket_value ) {
					return null;
				}

				$item['price']     = (string) $ticket_value->get_decimal();
				$item['sub_total'] = (string) $ticket_value->sub_total( $item['quantity'] )->get_decimal();

				return $item;
			},
			$cart->get_items_in_cart()
		);
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
	public function create_from_cart( Gateway_Interface $gateway, $purchaser = null ) {
		$cart = tribe( Cart::class );

		$items      = $cart->get_items_in_cart();
		$items      = array_filter( array_map(
			static function ( $item ) {
				/** @var Value $ticket_value */
				$ticket_value         = tribe( Ticket::class )->get_price_value( $item['ticket_id'] );
				$ticket_regular_value = tribe( Ticket::class )->get_price_value( $item['ticket_id'], true );

				if ( null === $ticket_value ) {
					return null;
				}

				$item['price']     = $ticket_value->get_decimal();
				$item['sub_total'] = $ticket_value->sub_total( $item['quantity'] )->get_decimal();
				$item['event_id']  = tribe( Ticket::class )->get_related_event_id( $item['ticket_id'] );

				$item['regular_price']     = $ticket_regular_value->get_decimal();
				$item['regular_sub_total'] = $ticket_regular_value->sub_total( $item['quantity'] )->get_decimal();

				return $item;
			},
			$items
		) );
		$total = $this->get_value_total( array_filter( $items ) );

		$order_args = [
			'title'                => $this->generate_order_title( $items, $cart->get_cart_hash() ),
			'total_value'          => $total->get_decimal(),
			'items'                => $items,
			'gateway'              => $gateway::get_key(),
			'hash'                 => $cart->get_cart_hash(),
			'currency'             => Utils\Currency::get_currency_code(),
			'purchaser_user_id'    => $purchaser['purchaser_user_id'],
			'purchaser_full_name'  => $purchaser['purchaser_full_name'],
			'purchaser_first_name' => $purchaser['purchaser_first_name'],
			'purchaser_last_name'  => $purchaser['purchaser_last_name'],
			'purchaser_email'      => $purchaser['purchaser_email'],
		];

		$order = $this->create( $gateway, $order_args );

		// We were unable to create the order bail from here.
		if ( ! $order ) {
			return false;
		}

		return $order;
	}

	/**
	 * Filters the values and creates a new Order with Tickets Commerce.
	 *
	 * @since 5.2.0
	 *
	 * @param Gateway_Interface $gateway
	 * @param array             $args
	 *
	 * @throws \Tribe__Repository__Usage_Error
	 *
	 * @return false|\WP_Post
	 */
	public function create( Gateway_Interface $gateway, $args ) {
		$gateway_key = $gateway::get_key();

		/**
		 * Allows filtering of the order creation arguments for all orders created via Tickets Commerce.
		 *
		 * @since 5.2.0
		 *
		 * @param array             $args
		 * @param Gateway_Interface $gateway
		 */
		$args = apply_filters( "tec_tickets_commerce_order_{$gateway_key}_create_args", $args, $gateway );

		/**
		 * Allows filtering of the order creation arguments for all orders created via Tickets Commerce.
		 *
		 * @since 5.2.0
		 *
		 * @param array             $args
		 * @param Gateway_Interface $gateway
		 */
		$args = apply_filters( 'tec_tickets_commerce_order_create_args', $args, $gateway );

		return tec_tc_orders()->set_args( $args )->create();
	}

	/**
	 * Generates a title based on Cart Hash, items in the cart.
	 *
	 * @since 5.1.9
	 *
	 * @param array $items List of events form.
	 *
	 * @return string
	 */
	public function generate_order_title( $items, $hash = null ) {
		$title = [ 'TEC-TC' ];
		if ( $hash ) {
			$title[] = implode( '-', (array) $hash );
		}
		$title[] = 'T';

		$tickets = array_filter( wp_list_pluck( $items, 'ticket_id' ) );
		$title   = array_merge( $title, $tickets );

		return implode( '-', $title );
	}

	/**
	 * Return payment method label for the order.
	 *
	 * @since 5.2.0
	 *
	 * @param int|\WP_Post $order Order Object.
	 *
	 * @return string
	 */
	public function get_gateway_label( $order ) {
		if ( is_numeric( $order ) ) {
			$order = tec_tc_get_order( $order );
		}

		if ( empty( $order->gateway ) ) {
			return null;
		}

		$gateway = tribe( Gateways\Manager::class )->get_gateway_by_key( $order->gateway );
		if ( empty( $gateway ) ) {
			return null;
		}

		return $gateway::get_label();
	}

	/**
	 * Redirects to the source post after a recoverable (logic) error.
	 *
	 * @todo  Determine if redirecting should be something relegated to some other method, and here we just actually
	 *        generate the order/Attendees.
	 *
	 * @todo  Deprecate tpp_error
	 *
	 * @see   \Tribe__Tickets__Commerce__PayPal__Errors for error codes translations.
	 * @since 5.1.9
	 *
	 * @param int  $post_id    A post ID.
	 *
	 * @param int  $error_code The current error code.
	 * @param bool $redirect   Whether to really redirect or not.
	 */
	protected function redirect_after_error( $error_code, $redirect, $post_id ) {
		$url = add_query_arg( 'tpp_error', $error_code, get_permalink( $post_id ) );
		if ( $redirect ) {
			wp_redirect( esc_url_raw( $url ) );
		}
		tribe_exit();
	}

	/**
	 * Loads an order object with information about its attendees
	 *
	 * @since 5.2.0
	 *
	 * @param \WP_Post $order the order object.
	 *
	 * @return \WP_Post|\WP_Post[]
	 */
	public function get_attendees( \WP_Post $order ) {
		$order->attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );

		if ( empty( $order->attendees ) ) {
			$order->attendees = [
				'name'  => get_post_meta( $order->ID, static::$purchaser_full_name_meta_key, true ),
				'email' => get_post_meta( $order->ID, static::$purchaser_email_meta_key, true ),
			];

			return [ $order ];
		}

		return $order;

	}

	/**
	 * Returns the Ticket ID that is associated with the attendee
	 *
	 * @since 5.2.0
	 *
	 * @param \WP_Post $attendee the attendee object.
	 *
	 * @return mixed
	 */
	public function get_ticket_id( \WP_Post $attendee ) {
		return get_post_meta( $attendee->ID, static::$tickets_in_order_meta_key, true );
	}

	/**
	 * Check if the order is of valid type.
	 *
	 * @since 5.2.0
	 *
	 * @param int|\WP_Post $order The Order object to check.
	 *
	 * @return bool
	 */
	public static function is_valid( $order ) {
		$order = get_post( $order );

		if ( ! $order ) {
			return false;
		}

		return static::POSTTYPE === $order->post_type;
	}

	/**
	 * Get the order associated with a given gateway order id.
	 *
	 * @since 5.3.0
	 *
	 * @param string $gateway_order_id The gateway order id.
	 *
	 * @return mixed|\WP_Post|null
	 */
	public function get_from_gateway_order_id( string $gateway_order_id ) {
		return tec_tc_orders()->by_args( [
			'status'           => 'any',
			'gateway_order_id' => $gateway_order_id,
		] )->first();
	}
}
