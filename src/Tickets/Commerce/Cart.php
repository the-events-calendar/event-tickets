<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce;
use \Tribe__Utils__Array as Arr;

/**
 * Class Cart
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Cart {

	/**
	 * Which URL param we use to identify a given page as the cart.
	 * Keep in mind this is not the only way, please use `is_current_page()` to determine that.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $url_query_arg = 'tec-tc-cart';

	/**
	 * Which URL param we use to tell the checkout page to set a cookie, since you cannot set a cookie on a 302
	 * redirect.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $cookie_query_arg = 'tec-tc-cookie';

	/**
	 * Redirect mode string, which will be used to determine which kind of cart the repository might be.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const REDIRECT_MODE = 'redirect';

	/**
	 * Which URL param we use to identify a given page as the cart.
	 * Keep in mind this is not the only way, please use `is_current_page()` to determine that.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $available_modes = [ self::REDIRECT_MODE ];

	/**
	 * Which cookie we will store the invoice number.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $invoice_cookie_name = 'tec-tickets-commerce-invoice';

	/**
	 * Which invoice number we are using here.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $invoice_number;

	/**
	 * From the current active cart repository we fetch it's mode.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_mode() {
		return $this->get_repository()->get_mode();
	}

	/**
	 * Gets the list of available modes we can use for the cart.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public function get_available_modes() {
		return $this->available_modes;
	}

	/**
	 * If a given string is a valid and available mode.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_current_page() {
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
	 * Returns the name of the transient used by the cart.
	 *
	 * @since TBD
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function get_transient_name( $id ) {
		return Commerce::ABBR . '-cart-' . md5( $id );
	}

	/**
	 * Returns the name of the transient used by the cart for invoice numbers
	 *
	 * @since TBD
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function get_invoice_transient_name( $id ) {
		return Commerce::ABBR . '-invoice-' . md5( $id );
	}

	/**
	 * Determine the Current cart Transient Key based on invoice number.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_current_cart_transient() {
		$invoice_number = $this->get_invoice_number();

		return static::get_transient_name( $invoice_number );
	}

	/**
	 * Determine the Current cart URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );

		$url = add_query_arg( [ static::$url_query_arg => $this->get_mode() ], $url );

		/**
		 * Allows modifications to the cart url for Tickets Commerce.
		 *
		 * @since TBD
		 *
		 * @param string $url URL for the cart.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_cart_url', $url );
	}

	/**
	 * Reads the invoice number from the invoice cookie.
	 *
	 * @since TBD
	 *
	 * @return string|bool The invoice number or `false` if not found.
	 */
	public function get_invoice_number( $generate = false ) {
		$invoice_length = 12;

		$invoice = $this->invoice_number;

		if (
			! empty( $_COOKIE[ static::$invoice_cookie_name ] )
			&& strlen( $_COOKIE[ static::$invoice_cookie_name ] ) === $invoice_length
		) {
			$invoice = $_COOKIE[ static::$invoice_cookie_name ];

			$invoice_transient = get_transient( static::get_transient_name( $invoice ) );

			if ( empty( $invoice_transient ) ) {
				$invoice = false;
			}
		}

		if ( empty( $invoice ) && $generate ) {
			$invoice = wp_generate_password( $invoice_length, false );
		}

		/**
		 * Filters the invoice number used for the Cart.
		 *
		 * @since TBD
		 *
		 * @param string $invoice Invoice number.
		 */
		$this->invoice_number = apply_filters( 'tec_tickets_commerce_cart_invoice_number', $invoice );

		return $this->invoice_number;
	}

	/**
	 * Sets the invoice cookie or resets the cookie.
	 *
	 * @since TBD
	 *
	 * @parem string $value Value used for the cookie or empty to purge the cookie.
	 */
	public function set_cookie_invoice_number( $value = '' ) {
		if ( empty( $value ) && empty( $_COOKIE[ static::$invoice_cookie_name ] ) ) {
			return;
		}

		if ( empty( $value ) ) {
			$invoice = $_COOKIE[ static::$invoice_cookie_name ];
			unset( $_COOKIE[ static::$invoice_cookie_name ] );
			$deleted = delete_transient( static::get_invoice_transient_name( $invoice ) );
		}

		if ( ! headers_sent() ) {

			/**
			 * Filters the life span of the Cart Cookie.
			 *
			 * @since TBD
			 *
			 * @param int $expires The expiry time, as passed to setcookie().
			 */
			$expire  = apply_filters( 'tec_tickets_commerce_cart_expiration', time() + 1 * HOUR_IN_SECONDS );
			$referer = wp_get_referer();

			if ( $referer ) {
				$secure = ( 'https' === parse_url( $referer, PHP_URL_SCHEME ) );
			} else {
				$secure = false;
			}

			$is_cookie_set = setcookie( static::$invoice_cookie_name, $value, $expire, COOKIEPATH ?: '/', COOKIE_DOMAIN, $secure );
		}
	}

	/**
	 * Gets the current instance of cart handling that we are using.
	 *
	 * @since TBD
	 *
	 * @return Commerce\Cart\Cart_Interface
	 */
	public function get_repository() {
		$default_cart = tribe( Cart\Unmanaged_Cart::class );

		/**
		 * Filters the cart repository, by default we use Unmanaged Cart.
		 *
		 * @since TBD
		 *
		 * @param Cart\Cart_Interface $cart Instance of the cart repository managing the cart.
		 */
		return apply_filters( 'tec_tickets_commerce_cart_repository', $default_cart );
	}

	/**
	 * Get the tickets currently in the cart for a given provider.
	 *
	 * @since TBD
	 *
	 * @param string $provider Provider of tickets to get (if set).
	 *
	 * @return array List of tickets.
	 */
	public function get_tickets_in_cart( $provider = null ) {
		$cart = $this->get_repository();

		return $cart->get_items();
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id  Ticket ID.
	 * @param int   $quantity   Ticket quantity to add.
	 * @param array $extra_data Extra data to send to the cart item.
	 */
	public function add_ticket( $ticket_id, $quantity = 1, array $extra_data = [] ) {
		$cart = $this->get_repository();

		// Enforces that the min to add is 1.
		$quantity = max( 1, (int) $quantity );

		$optout = isset( $extra_data[ Attendee::$optout_meta_key ] ) ? $extra_data[ Attendee::$optout_meta_key ] : false;
		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
		$optout = $optout ? 'yes' : 'no';

		$extra_item_data = [
			Attendee::$optout_meta_key => $optout,
		];

		// Add to / update quantity in cart.
		$cart->add_item( $ticket_id, $quantity, $extra_item_data );
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $quantity  Ticket quantity to remove.
	 */
	public function remove_ticket( $ticket_id, $quantity = 1 ) {
		$cart = $this->get_repository();

		// Enforces that the min to remove is 1.
		$quantity = max( 1, (int) $quantity );

		$cart->remove_item( $ticket_id, $quantity );
	}

	/**
	 * If product cache parameter is found, delete saved products from temporary cart.
	 *
	 * @filter wp_loaded 0
	 *
	 * @since  TBD
	 */
	public function maybe_delete_expired_products() {
		$delete = tribe_get_request_var( 'clear_product_cache', null );

		if ( empty( $delete ) ) {
			return;
		}

		$transient_key = $this->get_current_cart_transient();

		// Bail if we have no data key.
		if ( false === $transient_key ) {
			return;
		}

		$transient = get_transient( $transient_key );

		// Bail if we have no data to delete.
		if ( empty( $transient ) ) {
			return;
		}

		// Bail if ET+ is not in place.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' ) ) {
			return;
		}
		$storage = new \Tribe__Tickets_Plus__Meta__Storage();

		foreach ( $transient as $ticket_id => $data ) {
			$storage->delete_cookie( $ticket_id );
		}
	}

	/**
	 * Prepare the data for cart processing.
	 *
	 * @since TBD
	 *
	 * @param array $request_data Request Data to be prepared.
	 *
	 * @return array
	 */
	public function prepare_data( $request_data ) {
		/**
		 * Filters the Cart data before sending to the prepare method.
		 *
		 * @since TBD
		 *
		 * @param array $request_data The cart data before processing.
		 */
		$request_data = apply_filters( 'tec_tickets_commerce_cart_pre_prepare_data', $request_data );

		if ( empty( $request_data['tribe_tickets_ar_data'] ) ) {
			return [];
		}

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$raw_data = $request_data['tribe_tickets_ar_data'];

		// Attempt to JSON decode data if needed.
		if ( ! is_array( $raw_data ) ) {
			$raw_data = stripslashes( $raw_data );
			$raw_data = json_decode( $raw_data, true );
		}

		$raw_data = array_merge( $request_data, $raw_data );

		$data             = [];
		$data['post_id']  = absint( Arr::get( $raw_data, 'tribe_tickets_post_id' ) );
		$data['provider'] = sanitize_text_field( Arr::get( $raw_data, 'tribe_tickets_provider', Module::class ) );
		$data['tickets']  = Arr::get( $raw_data, 'tribe_tickets_tickets' );
		$data['meta']     = Arr::get( $raw_data, 'tribe_tickets_meta', [] );

		$default_ticket = [
			'ticket_id' => 0,
			'quantity'  => 0,
			'optout'    => false,
			'iac'       => 'none',
			'extra'     => [],
		];

		$data['tickets'] = array_map( static function ( $ticket ) use ( $default_ticket, $handler ) {
			if ( empty( $ticket['quantity'] ) ) {
				return false;
			}

			$ticket = array_merge( $default_ticket, $ticket );

			$ticket['quantity'] = (int) $ticket['quantity'];

			if ( $ticket['quantity'] < 0 ) {
				return false;
			}

			$ticket['extra']['optout'] = tribe_is_truthy( $ticket['optout'] );
			unset( $ticket['optout'] );

			$ticket['extra']['iac'] = sanitize_text_field( $ticket['iac'] );
			unset( $ticket['iac'] );

			$ticket['obj'] = \Tribe__Tickets__Tickets::load_ticket_object( $ticket['ticket_id'] );

			if ( ! $handler->is_ticket_readable( $ticket['ticket_id'] ) ) {
				return false;
			}

			return $ticket;
		}, $data['tickets'] );

		// Remove empty items.
		$data['tickets'] = array_filter( $data['tickets'] );

		/**
		 * Filters the Meta on the Data before processing.
		 *
		 * @since TBD
		 *
		 * @param array $meta Meta information on the cart.
		 * @param array $data Data used for the cart.w
		 */
		$data['meta'] = apply_filters( 'tec_tickets_commerce_cart_prepare_data_meta', $data['meta'], $data );

		/**
		 * Filters the Cart data before sending to to the Cart Repository.
		 *
		 * @since TBD
		 *
		 * @param array $data The cart data after processing.
		 */
		return apply_filters( 'tec_tickets_commerce_cart_prepare_data', $data );
	}

	/**
	 * Prepares the data from the Tickets form.
	 *
	 * @since TBD
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
		 * @since TBD
		 *
		 * @param array $data Data used to process the cart.
		 */
		do_action( 'tec_tickets_commerce_cart_before_process', $data );

		$processed = $this->process( $data );

		/**
		 * Hook to inject behavior after cart is processed.
		 *
		 * @since TBD
		 *
		 * @param array $data      Data used to process the cart.
		 * @param bool  $processed Whether or not we processed the data.
		 */
		do_action( 'tec_tickets_commerce_cart_after_process', $data, $processed );

		if ( static::REDIRECT_MODE === $this->get_mode() ) {
			$redirect_url = tribe( Checkout::class )->get_url();

			if ( ! $_COOKIE[ $this->get_invoice_number() ] ) {
				$redirect_url = add_query_arg( [ static::$cookie_query_arg => $this->get_invoice_number() ], $redirect_url );
			}

			/**
			 * Which url it redirects after the processing of the cart.
			 *
			 * @since TBD
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
	 * @since TBD
	 *
	 * @param array $data
	 *
	 * @return array|boolean Boolean true when it was a success or an array of errors.
	 */
	public function process( array $data = [] ) {
		if ( empty( $data ) ) {
			return false;
		}

		/** @var \Tribe__Tickets__REST__V1__Messages $messages */
		$messages = tribe( 'tickets.rest-v1.messages' );

		// Get the number of available tickets.
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$errors = [];

		foreach ( $data['tickets'] as $ticket ) {
			$available = $tickets_handler->get_ticket_max_purchase( $ticket['ticket_id'] );

			// Bail if ticket does not have enough available capacity.
			if ( ( - 1 !== $available && $available < $ticket['quantity'] ) || ! $ticket['obj']->date_in_range() ) {
				$error_code = 'ticket-capacity-not-available';

				$errors[] = new \WP_Error( $error_code, sprintf( $messages->get_message( $error_code ), $ticket['obj']->name ), [
					'ticket'        => $ticket,
					'max_available' => $available,
				] );
				continue;
			}

			$this->add_ticket( $ticket['ticket_id'], $ticket['quantity'], $ticket['extra'] );
		}

		// Saved added items to the cart.
		$this->get_repository()->save();

		if ( ! empty( $errors ) ) {
			return $errors;
		}

		return true;
	}

}