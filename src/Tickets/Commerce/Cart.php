<?php
// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE, WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\Commerce\Traits\Cart as Cart_Trait;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use Tribe__Tickets_Plus__Meta__Storage as Meta_Storage;
use Tribe__Utils__Array as Arr;
use TEC\Common\StellarWP\DB\DB;

/**
 * Class Cart
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Cart {

	use Cart_Trait;

	/**
	 * Which URL param we use to identify a given page as the cart.
	 * Keep in mind this is not the only way, please use `is_current_page()` to determine that.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $url_query_arg = 'tec-tc-cart';

	/**
	 * Which URL param we use to tell the checkout page to set a cookie, since you cannot set a cookie on a 302
	 * redirect.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $cookie_query_arg = 'tec-tc-cookie';

	/**
	 * Redirect mode string, which will be used to determine which kind of cart the repository might be.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const REDIRECT_MODE = 'redirect';

	/**
	 * Which URL param we use to identify a given page as the cart.
	 * Keep in mind this is not the only way, please use `is_current_page()` to determine that.
	 *
	 * @since 5.1.9
	 *
	 * @var string[]
	 */
	protected $available_modes = [ self::REDIRECT_MODE ];

	/**
	 * Which cookie we will store the cart hash.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $cart_hash_cookie_name = 'tec-tickets-commerce-cart';

	/**
	 * Gets the current instance of cart handling that we are using.
	 * Most of the pieces should be handled in the Repository for the cart, only piece fully handled by the
	 * parent class is the cookie handling.
	 *
	 * @since 5.1.9
	 * @since 5.21.0 Updated to use Cart_Interface instead of Unmanaged_Cart.
	 *
	 * @return Cart_Interface
	 */
	public function get_repository() {
		$default_cart = tribe( Cart_Interface::class );

		/**
		 * Filters the cart repository, by default we use Unmanaged Cart.
		 *
		 * @since 5.1.9
		 * @since 5.21.0 Updated to use Cart_Interface instead of Unmanaged_Cart.
		 *
		 * @param Cart_Interface $cart Instance of the cart repository managing the cart.
		 */
		return apply_filters( 'tec_tickets_commerce_cart_repository', $default_cart );
	}

	/**
	 * From the current active cart repository we fetch it's mode.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_mode() {
		return $this->get_repository()->get_mode();
	}

	/**
	 * Gets the list of available modes we can use for the cart.
	 *
	 * @since 5.1.9
	 *
	 * @return string[]
	 */
	public function get_available_modes() {
		return $this->available_modes;
	}

	/**
	 * If a given string is a valid and available mode.
	 *
	 * @since 5.1.9
	 *
	 * @param string $mode Which mode we are testing.
	 *
	 * @return bool
	 */
	public function is_available_mode( $mode ) {
		return in_array( $mode, $this->get_available_modes(), true );
	}

	/**
	 * If the current page is the cart page or not.
	 *
	 * @since 5.1.9
	 * @since 5.21.1 Ensure it method returns false if tickets commerce is disabled.
	 *
	 * @return bool
	 */
	public function is_current_page() {
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return false;
		}

		$cart_mode = tribe_get_request_var( static::$url_query_arg, false );

		if ( ! $this->is_available_mode( $cart_mode ) ) {
			return false;
		}

		// When the current cart doesn't use this mode we fail the page check.
		if ( $this->get_mode() !== $cart_mode ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine the Current cart Transient Key based on invoice number.
	 *
	 * @since 5.1.9
	 *
	 * @return string|null
	 */
	public function get_current_cart_transient() {
		$cart_hash = $this->get_cart_hash();

		if ( empty( $cart_hash ) ) {
			return null;
		}

		return $this->get_transient_key( $cart_hash );
	}

	/**
	 * Determine the Current cart URL.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );

		$url = add_query_arg( [ static::$url_query_arg => $this->get_mode() ], $url );

		/**
		 * Allows modifications to the cart url for Tickets Commerce.
		 *
		 * @since 5.1.9
		 *
		 * @param string $url URL for the cart.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_cart_url', $url );
	}

	/**
	 * Generates a unique version of the cart hash, used to enforce idempotency in REST API requests.
	 *
	 * @since 5.4.0.2
	 *
	 * @param string $salt An optional value to make sure the generated hash is not directly translatable to the cart
	 *                     hash.
	 *
	 * @return string
	 */
	public function generate_cart_order_hash( $salt = '' ): string {
		$cart_hash = $this->get_cart_hash();

		/**
		 * Allows modifications to the cart/order hash for Tickets Commerce.
		 *
		 * @since 5.4.0.2
		 *
		 * @param string $cart_order_hash The md5-hashed cart hash.
		 * @param string $cart_hash       The current cart hash.
		 * @param string $salt            The salt value.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_cart_order_hash', md5( $cart_hash . $salt ), $cart_hash, $salt );
	}

	/**
	 * Reads the cart hash from the cookies.
	 *
	 * @since 5.1.9
	 *
	 * @return string|null The cart hash or `null` if not found.
	 */
	public function get_cart_hash( $generate = false ) {
		$cart_hash_length = 12;
		$cart_hash        = $this->get_repository()->get_hash();
		$hash_from_cookie = sanitize_text_field( $_COOKIE[ static::get_cart_hash_cookie_name() ] ?? '' );

		if ( strlen( $hash_from_cookie ) === $cart_hash_length ) {
			$cart_hash           = $hash_from_cookie;
			$cart_hash_transient = get_transient( $this->get_transient_key( $cart_hash ) );

			if ( empty( $cart_hash_transient ) ) {
				$cart_hash = null;
			}
		}

		if ( empty( $cart_hash ) && $generate ) {
			$tries     = 1;
			$max_tries = 20;

			// While we dont find an empty transient to store this cart we loop, but avoid more than 20 tries.
			while (
				( ! empty( $cart_hash_transient ) || empty( $cart_hash ) )
				&& $max_tries >= $tries
			) {
				$cart_hash           = wp_generate_password( $cart_hash_length, false );
				$cart_hash_transient = get_transient( $this->get_transient_key( $cart_hash ) );

				// Make sure we increment.
				++$tries;
			}
		}

		$this->set_cart_hash( $cart_hash );

		return $this->get_repository()->get_hash();
	}

	/**
	 * Configures the Cart hash on the class object
	 *
	 * @since 5.2.0
	 *
	 * @param string $cart_hash Cart hash value.
	 *
	 */
	public function set_cart_hash( $cart_hash ) {
		$this->get_repository()->set_hash( $cart_hash );
	}

	/**
	 * Determine if the cart has items.
	 *
	 * @since 5.19.3
	 *
	 * @return bool Whether the cart has items.
	 */
	public function has_items(): bool {
		return $this->get_repository()->has_items();
	}

	/**
	 * Clear the cart.
	 *
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function clear_cart() {
		// Release any stock reservations before clearing cart.
		$this->release_cart_stock_reservations();

		$this->set_cart_hash_cookie( null );
		$this->get_repository()->clear();

		unset( $_COOKIE[ static::get_cart_hash_cookie_name() ] );

		return delete_transient( static::get_current_cart_transient() );
	}

	/**
	 * Based on the current time when is the cart going to expire.
	 *
	 * @since 5.19.3
	 *
	 * @return int
	 */
	public function get_cart_expiration(): int {
		/**
		 * Filters the life span of the Cart Cookie.
		 *
		 * @depecated
		 * @since 5.1.9
		 * @since 5.21.0 Deprecated in favor of `tec_tickets_commerce_cart_cookie_expiration`.
		 *
		 * @param int $expire The expiry time, as passed to setcookie().
		 */
		$expire = (int) apply_filters_deprecated(
			'tec_tickets_commerce_cart_expiration',
			[ time() + HOUR_IN_SECONDS ],
			'5.21.0',
			'tec_tickets_commerce_cart_cookie_expiration'
		);

		/**
		 * Filters the life span of the Cart Cookie.
		 *
		 * @since 5.21.0
		 *
		 * @param int $expire The expiry time, as passed to setcookie().
		 */
		return (int) apply_filters( 'tec_tickets_commerce_cart_cookie_expiration', $expire );
	}

	/**
	 * Sets the cart hash cookie or resets the cookie.
	 *
	 * @since 5.1.9
	 *
	 * @param string $value Value used for the cookie or empty to purge the cookie.
	 *
	 * @return boolean
	 */
	public function set_cart_hash_cookie( $value = '' ) {
		if ( headers_sent() ) {
			return false;
		}

		$expire = $this->get_cart_expiration();

		// When null means we are deleting.
		if ( null === $value ) {
			$expire = 1;
		}

		$is_cookie_set = setcookie( static::get_cart_hash_cookie_name(), $value ?? '', $expire, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );

		if ( $is_cookie_set ) {
			// Overwrite local variable, so we can use it right away.
			$_COOKIE[ static::get_cart_hash_cookie_name() ] = $value;
		}

		return $is_cookie_set;
	}

	/**
	 * Get the tickets currently in the cart for a given provider.
	 *
	 * @since 5.1.9
	 * @since 5.21.0 Added the $type parameter.
	 *
	 * @param bool   $full_item_params Determines all the item params, including event_id, sub_total, and obj.
	 * @param string $type             The type of item to get from the cart. Default is 'ticket'. Use 'all' to get all items.
	 *
	 * @return array List of items.
	 */
	public function get_items_in_cart( $full_item_params = false, string $type = 'ticket' ) {
		return $this->get_repository()->get_items_in_cart( $full_item_params, $type );
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since 5.1.9
	 *
	 * @param int   $ticket_id  Ticket ID.
	 * @param int   $quantity   Ticket quantity to add.
	 * @param array $extra_data Extra data to send to the cart item.
	 */
	public function add_ticket( $ticket_id, $quantity = 1, array $extra_data = [] ) {
		$cart = $this->get_repository();

		// Ensure quantity is an integer.
		$quantity = (int) $quantity;

		// Add to / update quantity in cart.
		$cart->upsert_item( $ticket_id, $quantity, $extra_data );
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since 5.1.9
	 *
	 * @param int  $ticket_id Ticket ID.
	 * @param ?int $quantity  Ticket quantity to remove. If null, the item will be removed.
	 */
	public function remove_ticket( $ticket_id, ?int $quantity = null ) {
		$cart = $this->get_repository();

		// If the quantity is null, we remove the item.
		if ( null === $quantity ) {
			$cart->remove_item( $ticket_id );
			return;
		}

		// Make sure the cart actually has the item.
		if ( ! $cart->has_item( $ticket_id ) ) {
			return;
		}

		$current_quantity = $cart->get_item_quantity( $ticket_id );
		$new_quantity     = max( 0, $current_quantity - $quantity );

		$cart->upsert_item( $ticket_id, $new_quantity );
	}

	/**
	 * If product cache parameter is found, delete saved products from temporary cart.
	 *
	 * @filter wp_loaded 0
	 *
	 * @since 5.1.9
	 */
	public function maybe_delete_expired_products() {
		$delete = tribe_get_request_var( 'clear_product_cache', null );

		if ( empty( $delete ) ) {
			return;
		}

		$transient_key = $this->get_current_cart_transient();

		// Bail if we have no data key.
		if ( empty( $transient_key ) ) {
			return;
		}

		// Bail if ET+ is not in place.
		if ( ! class_exists( Meta_Storage::class ) ) {
			return;
		}

		$storage = new Meta_Storage();

		// Bail if we have no data to delete.
		$transient = get_transient( $transient_key );
		if ( empty( $transient ) ) {
			return;
		}

		foreach ( $transient as $ticket_id => $data ) {
			$storage->delete_cookie( $ticket_id );
		}
	}

	/**
	 * Prepare the data for cart processing.
	 *
	 * Note that most of the data that is processed here is legacy, so you will see very weird and wonky naming.
	 * Make sure when you are making modifications you consider:
	 * - Event Tickets without ET+ additional data
	 * - Event Ticket Plus IAC
	 * - Event Tickets Plus Attendee Registration
	 *
	 * @since 5.1.9
	 *
	 * @param array $request_data Request Data to be prepared.
	 *
	 * @return array
	 */
	public function prepare_data( $request_data ) {
		/**
		 * Filters the Cart data before sending to the prepare method.
		 *
		 * @since 5.1.9
		 *
		 * @param array $request_data The cart data before processing.
		 */
		$request_data = apply_filters( 'tec_tickets_commerce_cart_pre_prepare_data', $request_data );

		if ( empty( $request_data['tribe_tickets_ar_data'] ) ) {
			return [];
		}

		$raw_data = $request_data['tribe_tickets_ar_data'];

		// Attempt to JSON decode data if needed.
		if ( ! is_array( $raw_data ) ) {
			$raw_data = stripslashes( $raw_data );
			$raw_data = json_decode( $raw_data, true );
		}

		// Set up the raw data from the request.
		$raw_data = array_merge( $request_data, $raw_data );

		// Set the initial data array.
		$data = [
			'post_id'  => absint( Arr::get( $raw_data, 'tribe_tickets_post_id' ) ),
			'provider' => sanitize_text_field( Arr::get( $raw_data, 'tribe_tickets_provider', Module::class ) ),
			'tickets'  => Arr::get( $raw_data, 'tribe_tickets_tickets' ),
			'meta'     => Arr::get( $raw_data, 'tribe_tickets_meta', [] ),
		];

		// Set up the ticket data and metadata.
		$tickets_meta    = Arr::get( $raw_data, 'tribe_tickets', [] );
		$data['tickets'] = array_filter( $this->map_ticket_data( $data['tickets'], $tickets_meta ) );

		/**
		 * Filters the Meta on the Data before processing.
		 *
		 * @since 5.1.9
		 *
		 * @param array $meta Meta information on the cart.
		 * @param array $data Data used for the cart.
		 */
		$data['meta'] = apply_filters( 'tec_tickets_commerce_cart_prepare_data_meta', $data['meta'], $data );

		/**
		 * Filters the Cart data before sending to to the Cart Repository.
		 *
		 * @since 5.1.9
		 *
		 * @param array $data The cart data after processing.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_cart_prepare_data', $this->get_repository()->prepare_data( $data ) );
	}

	/**
	 * Prepares the data from the Tickets form.
	 *
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function parse_request() {
		// When it's not the current page we just bail.
		if ( ! $this->is_current_page() ) {
			return false;
		}

		$data = $this->prepare_data( $_POST );

		/**
		 * Hook to inject behavior before cart is processed, if you need to change the data that will be used, you
		 * should look into `tec_tickets_commerce_cart_prepare_data`.
		 *
		 * @since 5.1.9
		 *
		 * @param array $data Data used to process the cart.
		 */
		do_action( 'tec_tickets_commerce_cart_before_process', $data );

		$processed = $this->process( $data );

		// Cart-level stock reservation system.
		if ( $processed && $this->has_items() ) {
			try {
				$this->reserve_cart_stock();
			} catch ( \TEC\Tickets\Commerce\Exceptions\Insufficient_Stock_Exception $e ) {
				// Handle insufficient stock at cart level.
				$this->clear_cart();
				
				/**
				 * Fires when cart-level stock validation fails.
				 *
				 * @since TBD
				 *
				 * @param string $cart_hash The cart hash.
				 * @param array  $data      The cart data that failed validation.
				 * @param string $error     The error message.
				 */
				do_action( 'tec_tickets_commerce_cart_stock_validation_failed', $this->get_cart_hash(), $data, $e->getMessage() );
				
				// If we're in redirect mode, redirect back with error.
				if ( static::REDIRECT_MODE === $this->get_mode() ) {
					$redirect_url = add_query_arg(
						[
							'tec-tc-cart-error'       => 'insufficient_stock',
							'tec-tc-cart-msg'         => urlencode( $e->get_user_friendly_message() ),
							'tec-tc-has-reservations' => $this->has_reserved_items( $e->get_stock_errors() ) ? '1' : '0',
						],
						wp_get_referer() ?: home_url()
					);

					wp_safe_redirect( $redirect_url );
					tribe_exit();
				}
				
				return false;
			}
		}

		/**
		 * Hook to inject behavior after cart is processed.
		 *
		 * @since 5.1.9
		 *
		 * @param array $data      Data used to process the cart.
		 * @param bool  $processed Whether or not we processed the data.
		 */
		do_action( 'tec_tickets_commerce_cart_after_process', $data, $processed );

		if ( static::REDIRECT_MODE === $this->get_mode() ) {
			$redirect_url = tribe( Checkout::class )->get_url();

			/**
			 * Filter the base redirect URL for cart to checkout.
			 *
			 * @since 5.2.0
			 *
			 * @param string $redirect_url Redirect URL.
			 * @param array  $data         Data that we just processed on the cart.
			 */
			$redirect_url = apply_filters( 'tec_tickets_commerce_cart_to_checkout_redirect_url_base', $redirect_url, $data );

			if (
				! isset( $_COOKIE[ $this->get_cart_hash() ] )
				|| ! $_COOKIE[ $this->get_cart_hash() ]
			) {
				$redirect_url = add_query_arg( [ static::$cookie_query_arg => $this->get_cart_hash() ], $redirect_url );
			}

			/**
			 * Which url it redirects after the processing of the cart.
			 *
			 * @since 5.1.9
			 *
			 * @param string $redirect_url Which url we will direct after processing the cart. Defaults to Checkout page.
			 * @param array  $data         Data that we just processed on the cart.
			 */
			$redirect_url = apply_filters( 'tec_tickets_commerce_cart_to_checkout_redirect_url', $redirect_url, $data );

			if ( null !== $redirect_url ) {
				wp_safe_redirect( $redirect_url );
				tribe_exit();
			}
		}

		return true;
	}

	/**
	 * Process a given cart data into this cart instance.
	 *
	 * @since 5.1.9
	 *
	 * @param array $data
	 *
	 * @return array|boolean Boolean true when it was a success or an array of errors.
	 */
	public function process( array $data = [] ) {
		if ( empty( $data ) ) {
			return false;
		}

		// Before we start we clear the existing cart.
		return $this->get_repository()->process( $data );
	}

	/**
	 * Get the total of the cart.
	 *
	 * @since 5.10.0
	 *
	 * @return null|float
	 */
	public function get_cart_total() {
		return $this->get_repository()->get_cart_total();
	}

	/**
	 * Get the subtotal of the cart.
	 *
	 * @since 5.18.0
	 *
	 * @return float
	 */
	public function get_cart_subtotal(): float {
		return $this->get_repository()->get_cart_subtotal();
	}

	/**
	 * Get cart has cookie name.
	 *
	 * @since 5.18.1
	 *
	 * @return string
	 */
	public static function get_cart_hash_cookie_name(): string {
		/**
		 * Filters the cart hash cookie name.
		 *
		 * @since 5.18.1
		 *
		 * @param string $cart_hash_cookie_name The cart hash cookie name.
		 *
		 * @return string
		 */
		$filtered_cookie_name = apply_filters( 'tec_tickets_commerce_cart_hash_cookie_name', static::$cart_hash_cookie_name );

		return sanitize_title( $filtered_cookie_name );
	}

	/**
	 * Map the ticket data to a more usable format.
	 *
	 * @since 5.21.0
	 *
	 * @param array $ticket_data  Array of raw ticket data.
	 * @param array $tickets_meta Array of ticket meta data.
	 *
	 * @return array Array of mapped ticket data.
	 */
	protected function map_ticket_data( array $ticket_data, array $tickets_meta ): array {
		/** @var Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		return array_map(
			static function ( $ticket ) use ( $handler, $tickets_meta ) {
				// Merge the ticket data with the default ticket data.
				$ticket = array_merge(
					[
						'ticket_id' => 0,
						'quantity'  => 0,
						'optout'    => false,
						'iac'       => 'none',
						'extra'     => [],
					],
					$ticket
				);

				// Normalize and validate the quantity.
				$ticket['quantity'] = (int) $ticket['quantity'];
				if ( $ticket['quantity'] < 1 ) {
					return false;
				}

				// Normalize and validate the ticket ID.
				$ticket['ticket_id'] = (int) $ticket['ticket_id'];
				if ( true !== $handler->is_ticket_readable( $ticket['ticket_id'] ) ) {
					return false;
				}

				// Add attendee data.
				if ( ! empty( $tickets_meta[ $ticket['ticket_id'] ]['attendees'] ) ) {
					$ticket['extra']['attendees'] = $tickets_meta[ $ticket['ticket_id'] ]['attendees'];
				}

				// Add the optout and IAC data.
				$ticket['extra']['optout'] = tribe_is_truthy( $ticket['optout'] );
				$ticket['extra']['iac']    = sanitize_text_field( $ticket['iac'] );
				unset( $ticket['optout'], $ticket['iac'] );

				// Add the ticket object.
				$ticket['obj'] = Tickets::load_ticket_object( $ticket['ticket_id'] );

				return $ticket;
			},
			$ticket_data
		);
	}

	/**
	 * Returns the name of the transient used by the cart.
	 *
	 * @since 5.1.9
	 * @depecated Use `get_transient_key()` via the Cart trait instead.
	 *
	 * @param string $id The cart id.
	 *
	 * @return string The transient name.
	 */
	public static function get_transient_name( $id ) {
		_deprecated_function( __METHOD__, '5.21.0', 'TEC\Tickets\Commerce\Traits\Cart::get_transient_key' );
		return Commerce::ABBR . '-cart-' . md5( $id ?? '' );
	}

	/**
	 * Reserves stock for items in the cart to prevent overselling.
	 *
	 * This creates temporary stock reservations using database locks and transients.
	 *
	 * @since TBD
	 *
	 * @throws \TEC\Tickets\Commerce\Exceptions\Insufficient_Stock_Exception If stock cannot be reserved.
	 * @throws \Exception If database operations fail.
	 */
	private function reserve_cart_stock(): void {
		global $wpdb;

		$cart_items = $this->get_items_in_cart( true );
		if ( empty( $cart_items ) ) {
			return;
		}

		$cart_hash = $this->get_cart_hash();
		if ( empty( $cart_hash ) ) {
			return;
		}

		$reservation_minutes      = $this->get_stock_reservation_minutes();
		$insufficient_stock_items = [];
		$reservations_to_create   = [];

		// Start database transaction for atomic operations.
		DB::beginTransaction();

		try {
			foreach ( $cart_items as $ticket_id => $item ) {
				$ticket = $item['obj'] ?? null;
				if ( ! $ticket || ! $ticket->manage_stock() ) {
					continue;
				}

				// Skip unlimited capacity tickets.
				if ( -1 === tribe_tickets_get_capacity( $ticket->ID ) ) {
					continue;
				}

				// Skip seated tickets - they have their own reservation system.
				if ( get_post_meta( $ticket->ID, \TEC\Tickets\Seating\Meta::META_KEY_SEAT_TYPE, true ) ) {
					continue;
				}

				$requested_quantity = (int) $item['quantity'];
				
				// Get current stock with database lock to prevent race conditions.
				$current_stock   = $this->get_locked_ticket_stock( $ticket->ID );
				$reserved_stock  = $this->get_existing_reservations_for_ticket( $ticket->ID, $cart_hash );
				$available_stock = $current_stock - $reserved_stock;

				if ( $available_stock < $requested_quantity ) {
					// Determine if this is due to reservations or actual stock shortage.
					$is_reserved_stock = $reserved_stock > 0 && $current_stock >= $requested_quantity;
					
					$insufficient_stock_items[] = [
						'ticket_id'      => $ticket->ID,
						'ticket_name'    => $ticket->name,
						'requested'      => $requested_quantity,
						'available'      => max( 0, $available_stock ),
						'current_stock'  => $current_stock,
						'reserved_stock' => $reserved_stock,
						'is_reserved'    => $is_reserved_stock,
					];
				} else {
					// Stock is available, prepare reservation.
					$reservations_to_create[] = [
						'ticket_id'  => $ticket->ID,
						'quantity'   => $requested_quantity,
						'cart_hash'  => $cart_hash,
						'expires_at' => time() + ( $reservation_minutes * MINUTE_IN_SECONDS ),
					];
				}
			}

			// If any items have insufficient stock, roll back and throw exception.
			if ( ! empty( $insufficient_stock_items ) ) {
				DB::rollback();
				throw new \TEC\Tickets\Commerce\Exceptions\Insufficient_Stock_Exception( 
					$insufficient_stock_items,
					$this->build_reservation_aware_error_message( $insufficient_stock_items, $reservation_minutes )
				);
			}

			// Create all reservations atomically.
			foreach ( $reservations_to_create as $reservation ) {
				$this->create_stock_reservation( $reservation );
			}

			// Commit the transaction.
			DB::commit();

			// Store cart reservation metadata.
			$this->store_cart_reservation_metadata( $cart_hash, $reservations_to_create, $reservation_minutes );

		} catch ( \Exception $e ) {
			// Roll back on any error.
			DB::rollback();
			throw $e;
		}
	}

	/**
	 * Gets the current stock for a ticket with database row locking.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The current stock quantity.
	 */
	private function get_locked_ticket_stock( int $ticket_id ): int {
		// Use FOR UPDATE to lock the row and prevent race conditions.
		$stock = DB::get_var( 
			DB::prepare(
				'SELECT meta_value FROM ' . DB::prefix( 'postmeta' ) . ' 
				 WHERE post_id = %d AND meta_key = \'_stock\' 
				 FOR UPDATE',
				$ticket_id
			)
		);

		return (int) ( $stock ?? 0 );
	}

	/**
	 * Gets existing reservations for a ticket, excluding current cart.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id The ticket ID.
	 * @param string $exclude_cart_hash Cart hash to exclude from results.
	 *
	 * @return int Total reserved quantity.
	 */
	private function get_existing_reservations_for_ticket( int $ticket_id, string $exclude_cart_hash = '' ): int {
		$reservation_pattern = $this->get_reservation_transient_pattern( $ticket_id );
		$total_reserved      = 0;

		// Get all reservation transients for this ticket.
		$transients = get_transient( $reservation_pattern . '_index' ) ?: [];

		foreach ( $transients as $cart_hash => $transient_key ) {
			if ( $cart_hash === $exclude_cart_hash ) {
				continue;
			}

			$reservation = get_transient( $transient_key );
			if ( $reservation && $reservation['expires_at'] > time() ) {
				$total_reserved += (int) $reservation['quantity'];
			} else {
				// Clean up expired reservation.
				delete_transient( $transient_key );
				unset( $transients[ $cart_hash ] );
			}
		}

		// Update the index after cleanup.
		set_transient( $reservation_pattern . '_index', $transients, DAY_IN_SECONDS );

		return $total_reserved;
	}

	/**
	 * Creates a stock reservation using transients.
	 *
	 * @since TBD
	 *
	 * @param array $reservation Reservation data.
	 */
	private function create_stock_reservation( array $reservation ): void {
		$transient_key = $this->get_reservation_transient_key( 
			$reservation['ticket_id'], 
			$reservation['cart_hash'] 
		);

		$reservation_data = [
			'ticket_id'  => $reservation['ticket_id'],
			'quantity'   => $reservation['quantity'], 
			'cart_hash'  => $reservation['cart_hash'],
			'created_at' => time(),
			'expires_at' => $reservation['expires_at'],
		];

		// Store the reservation.
		set_transient( $transient_key, $reservation_data, $this->get_stock_reservation_minutes() * MINUTE_IN_SECONDS );

		// Update the reservation index for this ticket.
		$this->update_reservation_index( $reservation['ticket_id'], $reservation['cart_hash'], $transient_key );
	}

	/**
	 * Stores cart reservation metadata for cleanup and tracking.
	 *
	 * @since TBD
	 *
	 * @param string $cart_hash Cart hash.
	 * @param array  $reservations Array of reservations created.
	 * @param int    $minutes Reservation duration in minutes.
	 */
	private function store_cart_reservation_metadata( string $cart_hash, array $reservations, int $minutes ): void {
		$cart_transient_key = $this->get_transient_key( $cart_hash );
		$metadata = [
			'reserved_at'  => time(),
			'expires_at'   => time() + ( $minutes * MINUTE_IN_SECONDS ),
			'reservations' => array_map(
				function ( $res ) {
					return [
						'ticket_id' => $res['ticket_id'],
						'quantity'  => $res['quantity'],
					];
				},
				$reservations
			),
		];

		set_transient( $cart_transient_key . '_reservations', $metadata, $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Gets the stock reservation duration in minutes.
	 *
	 * @since TBD
	 *
	 * @return int Duration in minutes.
	 */
	private function get_stock_reservation_minutes(): int {
		/**
		 * Filters the stock reservation duration for cart items.
		 *
		 * @since TBD
		 *
		 * @param int $minutes Duration in minutes. Default 10.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_cart_stock_reservation_minutes', 10 );
	}

	/**
	 * Gets the transient key for a stock reservation.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $cart_hash Cart hash.
	 *
	 * @return string Transient key.
	 */
	private function get_reservation_transient_key( int $ticket_id, string $cart_hash ): string {
		return Commerce::ABBR . '_stock_res_' . $ticket_id . '_' . substr( md5( $cart_hash ), 0, 8 );
	}

	/**
	 * Gets the transient pattern for stock reservations by ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return string Transient pattern.
	 */
	private function get_reservation_transient_pattern( int $ticket_id ): string {
		return Commerce::ABBR . '_stock_res_' . $ticket_id;
	}

	/**
	 * Updates the reservation index for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $cart_hash Cart hash.
	 * @param string $transient_key Transient key.
	 */
	private function update_reservation_index( int $ticket_id, string $cart_hash, string $transient_key ): void {
		$index_key           = $this->get_reservation_transient_pattern( $ticket_id ) . '_index';
		$index               = get_transient( $index_key ) ?: [];
		$index[ $cart_hash ] = $transient_key;
		set_transient( $index_key, $index, DAY_IN_SECONDS );
	}

	/**
	 * Releases stock reservations for the current cart.
	 *
	 * Called when cart is cleared, order is completed, or reservation expires.
	 *
	 * @since TBD
	 */
	public function release_cart_stock_reservations(): void {
		$cart_hash = $this->get_cart_hash();
		if ( empty( $cart_hash ) ) {
			return;
		}

		$cart_transient_key = $this->get_transient_key( $cart_hash );
		$metadata           = get_transient( $cart_transient_key . '_reservations' );

		if ( ! $metadata || ! isset( $metadata['reservations'] ) ) {
			return;
		}

		foreach ( $metadata['reservations'] as $reservation ) {
			$transient_key = $this->get_reservation_transient_key( 
				$reservation['ticket_id'], 
				$cart_hash 
			);
			
			// Remove the reservation.
			delete_transient( $transient_key );

			// Update the index.
			$index_key = $this->get_reservation_transient_pattern( $reservation['ticket_id'] ) . '_index';
			$index     = get_transient( $index_key ) ?: [];
			unset( $index[ $cart_hash ] );
			set_transient( $index_key, $index, DAY_IN_SECONDS );
		}

		// Remove the cart reservation metadata.
		delete_transient( $cart_transient_key . '_reservations' );

		/**
		 * Fires when cart stock reservations are released.
		 *
		 * @since TBD
		 *
		 * @param string $cart_hash The cart hash.
		 * @param array  $metadata  The reservation metadata.
		 */
		do_action( 'tec_tickets_commerce_cart_stock_reservations_released', $cart_hash, $metadata );
	}

	/**
	 * Cleans up expired stock reservations.
	 *
	 * This method can be called manually or via WordPress cron to remove expired reservations
	 * and free up reserved stock for other customers.
	 *
	 * @since TBD
	 *
	 * @return int Number of expired reservations cleaned up.
	 */
	public static function cleanup_expired_stock_reservations(): int {
		$cleanup_count = 0;
		$pattern       = Commerce::ABBR . '_stock_res_*';

		// Get all transients that match our reservation pattern.
		$transients = DB::get_results( 
			DB::prepare(
				'SELECT option_name FROM ' . DB::prefix( 'options' ) . ' 
				 WHERE option_name LIKE %s 
				 AND option_name NOT LIKE %s',
				'_transient_' . str_replace( '*', '%', $pattern ),
				'%_index'
			)
		);

		foreach ( $transients as $transient_row ) {
			$transient_key = str_replace( '_transient_', '', $transient_row->option_name );
			$reservation   = get_transient( $transient_key );

			if ( ! $reservation || ! isset( $reservation['expires_at'] ) ) {
				continue;
			}

			// Check if reservation has expired.
			if ( $reservation['expires_at'] <= time() ) {
				delete_transient( $transient_key );
				++$cleanup_count;

				// Also clean up from the ticket index.
				$ticket_id = $reservation['ticket_id'] ?? 0;
				$cart_hash = $reservation['cart_hash'] ?? '';

				if ( $ticket_id && $cart_hash ) {
					$index_key = Commerce::ABBR . '_stock_res_' . $ticket_id . '_index';
					$index     = get_transient( $index_key ) ?: [];
					unset( $index[ $cart_hash ] );
					set_transient( $index_key, $index, DAY_IN_SECONDS );
				}

				/**
				 * Fires when an expired stock reservation is cleaned up.
				 *
				 * @since TBD
				 *
				 * @param array $reservation The expired reservation data.
				 */
				do_action( 'tec_tickets_commerce_expired_stock_reservation_cleaned', $reservation );
			}
		}

		/**
		 * Fires after expired stock reservations cleanup completes.
		 *
		 * @since TBD
		 *
		 * @param int $cleanup_count Number of expired reservations cleaned up.
		 */
		do_action( 'tec_tickets_commerce_stock_reservations_cleanup_completed', $cleanup_count );

		return $cleanup_count;
	}

	/**
	 * Registers the WordPress cron job for cleaning up expired stock reservations.
	 *
	 * This should be called during plugin activation or initialization.
	 *
	 * @since TBD
	 */
	public static function register_cleanup_cron(): void {
		if ( ! wp_next_scheduled( 'tec_tickets_commerce_cleanup_expired_stock_reservations' ) ) {
			// Schedule cleanup to run every 15 minutes.
			wp_schedule_event( time(), 'tec_15_minutes', 'tec_tickets_commerce_cleanup_expired_stock_reservations' );
		}

		// Add custom cron interval if it doesn't exist.
		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				if ( ! isset( $schedules['tec_15_minutes'] ) ) {
					$schedules['tec_15_minutes'] = [
						'interval' => 15 * MINUTE_IN_SECONDS,
						'display'  => __( 'Every 15 Minutes', 'event-tickets' ),
					];
				}
				return $schedules;
			}
		);

		// Hook the cleanup function to the cron event.
		add_action( 'tec_tickets_commerce_cleanup_expired_stock_reservations', [ __CLASS__, 'cleanup_expired_stock_reservations' ] );
	}

	/**
	 * Unregisters the WordPress cron job for cleaning up expired stock reservations.
	 *
	 * This should be called during plugin deactivation.
	 *
	 * @since TBD
	 */
	public static function unregister_cleanup_cron(): void {
		wp_clear_scheduled_hook( 'tec_tickets_commerce_cleanup_expired_stock_reservations' );
	}

	/**
	 * Builds a user-friendly error message that explains reservation vs sold-out scenarios.
	 *
	 * @since TBD
	 *
	 * @param array $insufficient_stock_items Array of items with insufficient stock.
	 * @param int   $reservation_minutes      Reservation duration in minutes.
	 *
	 * @return string User-friendly error message.
	 */
	private function build_reservation_aware_error_message( array $insufficient_stock_items, int $reservation_minutes ): string {
		$reserved_items = [];
		$sold_out_items = [];

		// Categorize items by whether they're reserved or actually sold out.
		foreach ( $insufficient_stock_items as $item ) {
			if ( $item['is_reserved'] ) {
				$reserved_items[] = $item;
			} else {
				$sold_out_items[] = $item;
			}
		}

		$messages = [];

		// Handle reserved items with encouraging message.
		if ( ! empty( $reserved_items ) ) {
			if ( count( $reserved_items ) === 1 ) {
				$item       = $reserved_items[0];
				$messages[] = sprintf(
					/* translators: %1$s: ticket name, %2$d: requested quantity, %3$d: reservation minutes */
					__( 'The %1$s tickets you want (quantity: %2$d) are currently being held by another customer completing their purchase. Please try again in a few minutes, as these reservations expire after %3$d minutes.', 'event-tickets' ),
					$item['ticket_name'],
					$item['requested'],
					$reservation_minutes
				);
			} else {
				$ticket_list = array_map( 
					function ( $item ) {
						return sprintf(
							/* translators: %1$s: ticket name, %2$d: requested quantity */
							__( '%1$s (quantity: %2$d)', 'event-tickets' ),
							$item['ticket_name'],
							$item['requested']
						);
					}, 
					$reserved_items 
				);

				$messages[] = sprintf(
					/* translators: %1$s: comma-separated list of tickets, %2$d: reservation minutes */
					__( 'These tickets are currently being held by other customers completing their purchases: %1$s. Please try again in a few minutes, as these reservations expire after %2$d minutes.', 'event-tickets' ),
					implode( ', ', $ticket_list ),
					$reservation_minutes
				);
			}
		}

		// Handle truly sold out items.
		if ( ! empty( $sold_out_items ) ) {
			foreach ( $sold_out_items as $item ) {
				if ( $item['available'] <= 0 ) {
					$messages[] = sprintf(
						/* translators: %s: ticket name */
						__( '%s is completely sold out.', 'event-tickets' ),
						$item['ticket_name']
					);
				} else {
					$messages[] = sprintf(
						/* translators: %1$s: ticket name, %2$d: available quantity, %3$d: requested quantity */
						__( '%1$s: Only %2$d available (you requested %3$d).', 'event-tickets' ),
						$item['ticket_name'],
						$item['available'],
						$item['requested']
					);
				}
			}
		}

		// Add helpful suggestion if there are reservations.
		if ( ! empty( $reserved_items ) ) {
			$messages[] = __( '💡 Tip: Try refreshing this page in a few minutes if the other customer doesn\'t complete their purchase.', 'event-tickets' );
		}

		return implode( ' ', $messages );
	}

	/**
	 * Checks if any of the stock errors are due to reservations.
	 *
	 * @since TBD
	 *
	 * @param array $stock_errors Array of stock error items.
	 *
	 * @return bool True if any items are reserved.
	 */
	private function has_reserved_items( array $stock_errors ): bool {
		foreach ( $stock_errors as $error ) {
			if ( isset( $error['is_reserved'] ) && $error['is_reserved'] ) {
				return true;
			}
		}
		return false;
	}
}
