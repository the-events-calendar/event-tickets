<?php
/**
 * Tickets Commerce Order
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Reversed;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Date_Utils as Dates;
use WP_Post;

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
	 * Meta key for the checkout status of the order.
	 *
	 * @since 5.18.1
	 *
	 * @var string
	 */
	protected const CHECKOUT_COMPLETED_META = '_tec_tc_checkout_completed';

	/**
	 * Meta key for the order lock status.
	 *
	 * @since 5.18.1
	 *
	 * @var string
	 */
	protected const ORDER_LOCK_KEY = 'post_content_filtered';

	/**
	 * Keeping track of the lock id generated during a request.
	 *
	 * Enables to determine if the order has also been locked by current request, so that we can allow edit operations while the order is locked.
	 *
	 * @since 5.18.1
	 *
	 * @var string
	 */
	protected static string $lock_id = '';

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
	 * @since 5.18.0
	 *
	 * @var string
	 */
	public static $subtotal_value_meta_key = '_tec_tc_order_subtotal_value';

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
			'capability_type' => 'order',
			'map_meta_cap'    => true,
			'capabilities'    => [
				// Meta capabilities.
				'edit_post'          => 'edit_post',
				'read_post'          => 'read_post',
				'delete_post'        => 'delete_post',
				// Primitive capabilities used outside of map_meta_cap().
				'edit_posts'         => 'edit_posts',
				'create_posts'       => 'edit_tc-orders',
				'edit_others_posts'  => 'edit_others_posts',
				'delete_posts'       => 'delete_posts',
				'publish_posts'      => 'publish_tc-orders', // 'publish_posts',
				'read_private_posts' => 'read_private_posts',
			],
			'has_archive'     => false,
			'hierarchical'    => false,
			'supports'        => [ 'title' ],
			'labels'          => [
				'name'                     => __( 'Orders', 'event-tickets' ),
				'singular_name'            => __( 'Order', 'event-tickets' ),
				'add_new'                  => __( 'Add New Order', 'event-tickets' ),
				'add_new_item'             => __( 'Add New Order', 'event-tickets' ),
				'edit_item'                => __( 'Edit Order', 'event-tickets' ),
				'new_item'                 => __( 'New Order', 'event-tickets' ),
				'view_item'                => __( 'View Order', 'event-tickets' ),
				'view_items'               => __( 'View Orders', 'event-tickets' ),
				'search_items'             => __( 'Search Orders', 'event-tickets' ),
				'not_found'                => __( 'No orders found.', 'event-tickets' ),
				'not_found_in_trash'       => __( 'No orders found in Trash.', 'event-tickets' ),
				'all_items'                => __( 'All Orders', 'event-tickets' ),
				'archives'                 => __( 'Order Archives', 'event-tickets' ),
				'attributes'               => __( 'Order Attributes', 'event-tickets' ),
				'insert_into_item'         => __( 'Insert into order', 'event-tickets' ),
				'uploaded_to_this_item'    => __( 'Uploaded to this order', 'event-tickets' ),
				'filter_items_list'        => __( 'Filter orders list', 'event-tickets' ),
				'filter_by_date'           => __( 'Filter by date', 'event-tickets' ),
				'items_list_navigation'    => __( 'Orders list navigation', 'event-tickets' ),
				'items_list'               => __( 'Orders list', 'event-tickets' ),
				'item_published'           => __( 'Order published.', 'event-tickets' ),
				'item_published_privately' => __( 'Order published privately.', 'event-tickets' ),
				'item_reverted_to_draft'   => __( 'Order reverted to draft.', 'event-tickets' ),
				'item_trashed'             => __( 'Order trashed.', 'event-tickets' ),
				'item_scheduled'           => __( 'Order scheduled.', 'event-tickets' ),
				'item_updated'             => __( 'Order updated.', 'event-tickets' ),
				'item_link'                => _x( 'Order Link', 'navigation link block title', 'event-tickets' ),
				'item_link_description'    => _x( 'A link to an order.', 'navigation link block description', 'event-tickets' ),
			],
		];

		/**
		 * Filter the arguments that craft the order post type.
		 *
		 * @since 5.1.9
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 *
		 * @see   register_post_type
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
	 * @since 5.18.1 Wrap status update in a transaction to prevent race conditions.
	 *
	 * @param int    $order_id        Which order ID will be updated.
	 * @param string $new_status_slug Which Order Status we are modifying to.
	 * @param array  $extra_args      Extra repository arguments.
	 *
	 * @return bool|\WP_Error
	 */
	public function modify_status( $order_id, $new_status_slug, array $extra_args = [] ) {
		$new_status = tribe( Status\Status_Handler::class )->get_by_slug( $new_status_slug );

		if ( ! $new_status ) {
			return false;
		}

		DB::beginTransaction();

		// During this operations - the order should be locked!
		$locked = $this->lock_order( $order_id );

		// If we were unable to lock the order, bail.
		if ( ! $locked ) {
			DB::rollback();

			return false;
		}

		$can_transition = $this->can_transition_to( $new_status, $order_id );

		if ( ! $can_transition ) {
			DB::rollback();

			return $can_transition;
		}

		$args = array_merge( $extra_args, [ 'status' => $new_status->get_wp_slug() ] );

		$updated = tec_tc_orders()
			->by_args(
				[
					'id'                 => $order_id,
					'status'             => 'any',
					self::ORDER_LOCK_KEY => $this->get_lock_id(),
				]
			)
			->set_args( $args )
			->save();

		$this->unlock_order( $order_id );

		DB::commit();

		// After modifying the status we add a meta to flag when it was modified.
		if ( $updated ) {
			$time = Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT );
			add_post_meta( $order_id, static::get_status_log_meta_key( $new_status ), $time );
		}

		return (bool) $updated;
	}

	/**
	 * Whether an order's status be transitioned to another status.
	 *
	 * @since 5.18.1
	 *
	 * @param Status_Interface $new_status The new status to transition to.
	 * @param int              $order_id   The order ID to check the transition for.
	 *
	 * @return bool
	 */
	public function can_transition_to( Status_Interface $new_status, int $order_id ): bool {
		$lock_key = self::ORDER_LOCK_KEY;

		try {
			/**
			 * We want to have the fresher current status supporting the lock system if enabled.
			 *
			 * Overcoming object cache and refreshing even if we had retrieved it already during the request.
			 */
			$current_status_wp_slug = DB::get_var(
				DB::prepare(
					"SELECT post_status FROM %i WHERE ID = %d AND $lock_key=%s",
					DB::prefix( 'posts' ),
					$order_id,
					$this->get_lock_id()
				)
			);
		} catch ( DatabaseQueryException $e ) {
			// The query should be failing silently.
			return false;
		}

		if ( ! $current_status_wp_slug ) {
			return false;
		}

		$current_status = tribe( Status\Status_Handler::class )->get_by_wp_slug( $current_status_wp_slug );

		return $current_status->can_change_to( $new_status );
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
	 * @since 5.18.1 Now it will only create one order per cart hash. Every next time it will update the existing order.
	 *
	 * @return false|WP_Post
	 * @throws \Tribe__Repository__Usage_Error
	 */
	public function create_from_cart( Gateway_Interface $gateway, $purchaser = null ) {
		$cart = tribe( Cart::class );

		$items = $cart->get_items_in_cart();
		$items = array_filter(
			array_map(
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
			)
		);

		$subtotal = $this->get_value_total( $items );

		$original_cart_items = $items;

		/**
		 * Filters the cart items before creating an order.
		 *
		 * Allows modification of the cart items array before creating an order,
		 * passing the current subtotal, payment gateway, and purchaser details.
		 *
		 * @since 5.18.0
		 *
		 * @param array             $items     The items in the cart.
		 * @param Value             $subtotal  The calculated subtotal of the cart items.
		 * @param Gateway_Interface $gateway   The payment gateway used for the order.
		 * @param ?array            $purchaser { Purchaser details.
		 *    @type ?int    $purchaser_user_id    The purchaser user ID.
		 *    @type ?string $purchaser_full_name  The purchaser full name.
		 *    @type ?string $purchaser_first_name The purchaser first name.
		 *    @type ?string $purchaser_last_name  The purchaser last name.
		 *    @type ?string $purchaser_email      The purchaser email.
		 * }
		 */
		$items = apply_filters(
			'tec_tickets_commerce_create_order_from_cart_items',
			$items,
			$subtotal,
			$gateway,
			$purchaser
		);

		$total = $this->get_value_total( array_filter( $items ) );

		$hash              = $cart->get_cart_hash();
		$existing_order_id = null;
		
		$order_args = [
			'title'                => $this->generate_order_title( $original_cart_items, $hash ),
			'total_value'          => $total->get_decimal(),
			'subtotal'             => $subtotal->get_decimal(),
			'items'                => $items,
			'gateway'              => $gateway::get_key(),
			'hash'                 => $hash,
			'currency'             => Utils\Currency::get_currency_code(),
			'purchaser_user_id'    => $purchaser['purchaser_user_id'],
			'purchaser_full_name'  => $purchaser['purchaser_full_name'],
			'purchaser_first_name' => $purchaser['purchaser_first_name'],
			'purchaser_last_name'  => $purchaser['purchaser_last_name'],
			'purchaser_email'      => $purchaser['purchaser_email'],
			'gateway_order_id'     => $this->generate_order_key( $hash ?? '', $purchaser['purchaser_email'] ),
		];

		if ( $hash ) {
			$existing_order_id = tec_tc_orders()->by_args(
				[
					'status' => tribe( Pending::class )->get_wp_slug(),
					'hash'   => $hash,
				]
			)->first_id();

			if ( ! $existing_order_id || ! is_int( $existing_order_id ) ) {
				$existing_order_id = null;
			}
		}

		$order_args['id'] = $existing_order_id;

		$order = $this->upsert( $gateway, $order_args );

		// We were unable to create the order bail from here.
		if ( ! $order ) {
			return false;
		}

		return $order;
	}

	/**
	 * Filters the values and creates a new Order with Tickets Commerce.
	 *
	 * @since    5.2.0
	 *
	 * @param Gateway_Interface $gateway
	 * @param array             $args
	 *
	 * @return false|WP_Post
	 * @throws \Tribe__Repository__Usage_Error
	 *
	 * @internal Use `upsert` instead.
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
	 * Filters the values and creates a new Order with Tickets Commerce or updates an existing one.
	 *
	 * @since 5.18.1
	 *
	 * @param Gateway_Interface $gateway The gateway to use to create the order.
	 * @param array             $args    The arguments to create the order.
	 *
	 * @return false|WP_Post WP_Post instance on success or false on failure.
	 */
	public function upsert( Gateway_Interface $gateway, array $args ) {
		$gateway_key = $gateway::get_key();

		$existing_order_id = (int) $args['id'] ?? 0;
		unset( $args['id'] );

		/**
		 * Allows filtering of the order upsert arguments for all orders created via Tickets Commerce.
		 *
		 * @since 5.18.1
		 *
		 * @param array             $args    The arguments to create the order.
		 * @param Gateway_Interface $gateway The gateway to use to create the order.
		 */
		$args = apply_filters( "tec_tickets_commerce_order_{$gateway_key}_upsert_args", $args, $gateway );

		/**
		 * Allows filtering of the order upsert arguments for all orders created via Tickets Commerce.
		 *
		 * @since 5.18.1
		 *
		 * @param array             $args    The arguments to create the order.
		 * @param Gateway_Interface $gateway The gateway to use to create the order.
		 */
		$args = apply_filters( 'tec_tickets_commerce_order_upsert_args', $args, $gateway );

		/**
		 * Allows filtering of the existing order ID before "upserting" an order.
		 *
		 * @since TDB
		 *
		 * @param int $existing_order_id The existing order ID.
		 */
		$existing_order_id = (int) apply_filters( 'tec_tickets_commerce_order_upsert_existing_order_id', $existing_order_id );

		if ( ! $existing_order_id || 0 >= $existing_order_id ) {
			return $this->create( $gateway, $args );
		}

		$locked = $this->lock_order( $existing_order_id );

		if ( ! $locked ) {
			return false;
		}

		/**
		 * Allows filtering of the order update arguments for all orders created via Tickets Commerce.
		 *
		 * @since 5.18.1
		 *
		 * @param array             $args
		 * @param Gateway_Interface $gateway
		 */
		$update_args = apply_filters( "tec_tickets_commerce_order_{$gateway_key}_update_args", $args, $gateway );

		/**
		 * Allows filtering of the order update arguments for all orders created via Tickets Commerce.
		 *
		 * @since 5.18.1
		 *
		 * @param array             $args
		 * @param Gateway_Interface $gateway
		 */
		$update_args = apply_filters( 'tec_tickets_commerce_order_update_args', $update_args, $gateway );

		$updated = tec_tc_orders()
			->by_args(
				[
					'id'                 => $existing_order_id,
					'status'             => 'any',
					self::ORDER_LOCK_KEY => $this->get_lock_id(),
				]
			)
			->set_args( $update_args )
			->save();

		$this->unlock_order( $existing_order_id );

		if ( empty( $updated[ $existing_order_id ] ) ) {
			/**
			 * It seems like the $existing_order_id no longer exists or failed to be updated. Let's create a new one instead.
			 *
			 * BE AWARE: The `$args` variable is not passed through the update filters here since it's going to pass through the create filters.
			 */
			return $this->create( $gateway, $args );
		}

		$order = tec_tc_get_order( $existing_order_id );

		if ( ! $order instanceof WP_Post ) {
			return false;
		}

		return $order;
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
	 * @param int|WP_Post $order Order Object.
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
	 * @since 5.1.9
	 *
	 * @param int  $error_code The current error code.
	 * @param bool $redirect   Whether to really redirect or not.
	 * @param int  $post_id    A post ID.
	 *
	 * @todo  Deprecate tpp_error
	 *
	 * @see   \Tribe__Tickets__Commerce__PayPal__Errors for error codes translations.
	 * @todo  Determine if redirecting should be something relegated to some other method, and here we only generate
	 *        generate the order/Attendees.
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
	 * @param WP_Post $order the order object.
	 *
	 * @return WP_Post|WP_Post[]
	 */
	public function get_attendees( WP_Post $order ) {
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
	 * Returns the events associated with the order.
	 *
	 * @since 5.13.3
	 *
	 * @param WP_Post|int $order The order object or ID.
	 *
	 * @return WP_Post[]
	 */
	public function get_events( $order ): array {
		$order = tec_tc_get_order( $order );

		if ( ! $order instanceof WP_Post ) {
			return [];
		}

		$events = $order->events_in_order ?? [];

		if ( empty( $events ) ) {
			return [];
		}

		return array_filter(
			array_map( 'get_post', $events ),
			function ( $event ) {
				return $event instanceof WP_Post;
			}
		);
	}

	/**
	 * Returns the total value of the order.
	 *
	 * @since 5.13.3
	 *
	 * @param WP_Post|int $order    The order object or ID.
	 * @param bool        $original Whether to get the original value or the current value.
	 *
	 * @return ?string
	 */
	public function get_value( $order, $original = false ): ?string {
		$order = tec_tc_get_order( $order );

		if ( ! $order instanceof WP_Post ) {
			return null;
		}

		$reversed = tribe( Reversed::class )->get_wp_slug();
		$refunded = tribe( Refunded::class )->get_wp_slug();
		if ( ! in_array( $order->post_status, [ $reversed, $refunded ], true ) ) {
			$regular = 0;
			$total   = 0;

			foreach ( $order->items as $cart_item ) {
				$cart_item_type = $cart_item['type'] ?? 'ticket';
				if ( 'ticket' !== $cart_item_type ) {
					continue;
				}

				$regular += $cart_item['regular_sub_total'] ?? 0;
				$total   += $cart_item['sub_total'] ?? 0;
			}

			// Backwards compatible. We didn't use to store regular, so in most installs this is going to be diff cause regular is gonna be 0 mostly.
			if ( $total !== $regular && $regular > $total ) {
				return Value::create( $original ? $regular : $total )->get_currency();
			}

			return $order->total_value->get_currency();
		}

		if ( empty( $order->gateway_payload['refunded'] ) ) {
			// The item was refunded but we don't know anything about it.
			return $order->total_value->get_currency();
		}

		$refunds  = $order->gateway_payload['refunded'];
		$refunded = max( wp_list_pluck( $refunds, 'amount_refunded' ) );
		$total    = max( wp_list_pluck( $refunds, 'amount_captured' ) );

		$total_value = $total - $refunded;

		return Value::create( ( $original ? $total : $total_value ) / 100 )->get_currency();
	}

	/**
	 * Returns the total value of an order item.
	 *
	 * @since 5.13.3
	 *
	 * @param array $item     The order object or ID.
	 * @param bool  $original Whether to get the original value or the current value.
	 *
	 * @return ?string
	 */
	public function get_item_value( $item, $original = false ): ?string {
		$current = $item['price'];
		$regular = $item['regular_price'] ?? $current;

		return $original ? Value::create( $regular )->get_currency() : Value::create( $current )->get_currency();
	}

	/**
	 * Returns the Ticket ID that is associated with the attendee
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post $attendee the attendee object.
	 *
	 * @return mixed
	 */
	public function get_ticket_id( WP_Post $attendee ) {
		return get_post_meta( $attendee->ID, static::$tickets_in_order_meta_key, true );
	}

	/**
	 * Check if the order is of valid type.
	 *
	 * @since 5.2.0
	 *
	 * @param int|WP_Post $order The Order object to check.
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
	 * @since 5.14.0 Update to fetch latest order in the case of multiple orders with the same gateway order id.
	 *
	 * @param string $gateway_order_id The gateway order id.
	 *
	 * @return mixed|WP_Post|null
	 */
	public function get_from_gateway_order_id( $gateway_order_id ) {
		return tec_tc_orders()->by_args(
			[
				'order_by'         => 'ID',
				'order'            => 'DESC',
				'status'           => 'any',
				'gateway_order_id' => $gateway_order_id,
			] 
		)->first();
	}

	/**
	 * Lock an order to prevent it from being modified.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return bool Whether the order was locked.
	 */
	public function lock_order( int $order_id ): bool {
		$this->generate_lock_id();

		try {
			$lock_key = self::ORDER_LOCK_KEY;

			$result = (bool) DB::query(
				DB::prepare(
					"UPDATE %i set $lock_key = %s where ID = $order_id and $lock_key = ''",
					DB::prefix( 'posts' ),
					$this->get_lock_id()
				)
			);

			/**
			 * Fires after an order is attempted to be locked.
			 *
			 * @since 5.18.1
			 *
			 * @param bool   $result   Whether the order was locked.
			 * @param int    $order_id The order ID.
			 * @param string $lock_id  The lock ID.
			 */
			do_action( 'tec_tickets_commerce_order_locked', $result, $order_id, $this->get_lock_id() );

			return $result;
		} catch ( DatabaseQueryException $e ) {
			return false;
		}
	}

	/**
	 * Unlock an order to allow it to be modified.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return bool Whether the order was unlocked.
	 */
	public function unlock_order( int $order_id ): bool {
		$lock_key = self::ORDER_LOCK_KEY;
		try {
			$result = (bool) DB::query(
				DB::prepare(
					"UPDATE %i set $lock_key = '' where ID = $order_id",
					DB::prefix( 'posts' )
				)
			);

			/**
			 * Fires after an order is attempted to be unlocked.
			 *
			 * @since 5.18.1
			 *
			 * @param bool   $result   Whether the order was unlocked.
			 * @param int    $order_id The order ID.
			 * @param string $lock_id  The lock ID.
			 */
			do_action( 'tec_tickets_commerce_order_unlocked', $result, $order_id, $this->get_lock_id() );

			$this->reset_lock_id();

			return $result;
		} catch ( DatabaseQueryException $e ) {
			return false;
		}
	}

	/**
	 * Get the lock ID.
	 *
	 * @since 5.18.1
	 *
	 * @return string The lock ID.
	 */
	public function get_lock_id(): string {
		return self::$lock_id;
	}

	/**
	 * Reset the lock ID.
	 *
	 * Usually after unlocking an order.
	 *
	 * @since 5.18.1
	 */
	public function reset_lock_id(): void {
		self::$lock_id = '';
	}

	/**
	 * Generate a lock ID.
	 *
	 * @since 5.18.1
	 *
	 * @return string The lock ID.
	 */
	public function generate_lock_id(): string {
		self::$lock_id = uniqid( '_order_lock', true );

		return self::$lock_id;
	}

	/**
	 * Get whether the order is locked.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return bool Whether the order is locked.
	 */
	public function is_order_locked( int $order_id ): bool {
		$lock_key = self::ORDER_LOCK_KEY;

		try {
			/**
			 * Filters whether the order is locked.
			 *
			 * @since 5.18.1
			 *
			 * @param bool $is_locked Whether the order is locked.
			 * @param int  $order_id  The order ID.
			 */
			return apply_filters(
				'tec_tickets_commerce_order_is_locked',
				(bool) DB::get_var(
					DB::prepare(
						"SELECT $lock_key FROM %i WHERE ID = $order_id",
						DB::prefix( 'posts' )
					)
				),
				$order_id
			);
		} catch ( DatabaseQueryException $e ) {
			return false;
		}
	}

	/**
	 * Get whether the order has its checkout completed.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return bool Whether the checkout is completed.
	 */
	public function is_checkout_completed( int $order_id ): bool {
		try {
			/**
			 * Filters whether the checkout is completed.
			 *
			 * Does a direct query to overcome object cache.
			 *
			 * @since 5.18.1
			 *
			 * @param bool $is_completed Whether the checkout is completed.
			 * @param int  $order_id     The order ID.
			 */
			return apply_filters(
				'tec_tickets_commerce_order_is_checkout_completed',
				(bool) DB::get_var(
					DB::prepare(
						'SELECT meta_value FROM %i WHERE post_id = %d AND meta_key = %s',
						DB::prefix( 'postmeta' ),
						$order_id,
						static::CHECKOUT_COMPLETED_META
					)
				),
				$order_id
			);
		} catch ( DatabaseQueryException $e ) {
			return false;
		}
	}

	/**
	 * Mark an order's checkout as completed.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return bool Whether the checkout was marked as completed and the async action was scheduled.
	 */
	public function checkout_completed( int $order_id ): bool {
		$result = (bool) update_post_meta( $order_id, static::CHECKOUT_COMPLETED_META, true );

		if ( ! $result ) {
			return false;
		}

		/**
		 * Fires after an order's checkout is marked as completed.
		 *
		 * @since 5.18.1
		 *
		 * @param int $order_id The order ID.
		 */
		do_action( 'tec_tickets_commerce_order_checkout_completed', $order_id );

		return (bool) as_enqueue_async_action(
			'tec_tickets_commerce_async_webhook_process',
			[ 'order_id' => $order_id ],
			'tec-tickets-commerce-stripe-webhooks'
		);
	}
	
	/**
	 * Generate a hashed key for the order for public view.
	 *
	 * @since 5.19.1
	 *
	 * @param string $hash The order cart hash.
	 * @param string $email The email of the purchaser.
	 *
	 * @return string The order hash key.
	 */
	public function generate_order_key( string $hash, string $email ): string {
		$time  = time();
		$email = sanitize_email( $email );
		$hash  = empty( $hash ) ? wp_generate_password() : $hash;
		
		return substr( md5( $hash . $email . $time ), 0, 12 );
	}
}
