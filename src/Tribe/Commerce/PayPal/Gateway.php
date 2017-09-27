<?php

class Tribe__Tickets__Commerce__PayPal__Gateway {
	/**
	 * @var string
	 */
	public $identity_token;

	/**
	 * @var array
	 */
	protected $transaction_data;

	/**
	 * @var string
	 */
	public static $invoice_cookie_name = 'event-tickets-tpp-invoice';

	/**
	 * Tribe__Tickets__Commerce__PayPal__Gateway constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->hook();
		$this->identity_token = tribe_get_option( 'ticket-paypal-identity-token' );

		if ( $this->identity_token ) {
			// if there's an identity token set, we handle payment confirmation with PDT
			tribe( 'tickets.commerce.paypal.handler.pdt' );
		} else {
			// if there isn't an identity token set, we use IPN
			tribe( 'tickets.commerce.paypal.handler.ipn' );
		}
	}

	/**
	 * Set up hooks for the gateway
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'template_redirect', array( $this, 'add_to_cart' ) );
	}

	/**
	 * Handles adding tickets to cart
	 *
	 * @since TBD
	 */
	public function add_to_cart() {
		global $post;

		// bail if this isn't a Tribe Commerce PayPal ticket
		if (
			empty( $_POST['product_id'] )
			|| empty( $_POST['provider'] )
			|| 'Tribe__Tickets__Commerce__PayPal__Main' !== $_POST['provider']
		) {
			return;
		}

		$url           = $this->get_cart_url( '_cart' );
		$now           = time();
		$post_url      = get_permalink( $post );
		$currency_code = tribe_get_option( 'ticket-paypal-currency-code' );

		// @TODO: set to the success page
		$notify_url    = add_query_arg( array( 'bacon' => 1 ), get_permalink( $post ) );

		$args = array(
			'cmd'           => '_cart',
			'add'           => 1,
			'business'      => urlencode( tribe_get_option( 'ticket-paypal-email' ) ),
			'bn'            => 'ModernTribe_SP',
			'notify_url'    => urlencode( $notify_url ),
			'shopping_url'  => urlencode( $post_url ),
			'currency_code' => $currency_code ? $currency_code : 'USD',
			'custom'        => 'user_id=' . get_current_user_id(),
			'invoice'       => $this->get_invoice_number(),
		);

		foreach ( $_POST['product_id'] as $ticket_id ) {
			$ticket   = tribe( 'tickets.commerce.paypal' )->get_ticket( $post->ID, $ticket_id );
			$quantity = absint( $_POST[ "quantity_{$ticket_id}" ] );

			// skip if the ticket in no longer in stock or is not sellable
			if (
				! $ticket->is_in_stock()
				|| ! $ticket->date_in_range( $now )
			) {
				continue;
			}

			// if the requested amount is greater than remaining, use remaining instead
			if ( $quantity > $ticket->remaining() ) {
				$quantity = $ticket->remaining();
			}

			// enforce purchase limit
			if ( $ticket->purchase_limit && $quantity > $ticket->purchase_limit ) {
				$quantity = $ticket->purchase_limit;
			}

			// if the ticket doesn't have a quantity, skip it
			if ( empty( $quantity ) ) {
				continue;
			}

			$args['quantity']    = $quantity;
			$args['amount']      = $ticket->price;
			$args['item_number'] = "{$post->ID}:{$ticket->ID}";
			$args['item_name']   = urlencode( $this->get_product_name( $ticket, $post ) );

			// we can only submit one product at a time. Bail if we get to here because we have a product
			// with a requested quantity
			break;
		}

		// If there isn't a quantity at all, then there's nothing to purchase. Redirect with an error
		if ( empty( $args['quantity'] ) ) {
			// @TODO: add an error for display
			wp_safe_redirect( $post_url );
			die;
		}

		/**
		 * Filters the arguments passed to PayPal while adding items to the cart
		 *
		 * @since TBD
		 *
		 * @param array   $args
		 * @param array   $data POST data from buy now submission
		 * @param WP_Post $post Post object that has tickets attached to it
		 */
		$args = apply_filters( 'tribe_tickets_commerce_paypal_add_to_cart_args', $args, $_POST, $post );

		$url = add_query_arg( $args, $url );

		wp_redirect( $url );
		die;
	}

	/**
	 * Parses PayPal transaction data into a more organized structure
	 *
	 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
	 *
	 * @param array $transaction Transaction data from PayPal in key/value pairs
	 *
	 * @return array
	 */
	public function parse_transaction( $transaction ) {
		$item_indexes = array(
			'item_number',
			'item_name',
			'quantity',
			'mc_handling',
			'mc_shipping',
			'tax',
			'mc_gross_',
		);

		$item_indexes_regex = '/(' . implode( '|', $item_indexes ) . ')(\d)/';

		$data = array(
			'items' => array(),
		);

		foreach ( $transaction as $key => $value ) {
			if ( ! preg_match( $item_indexes_regex, $key, $matches ) ) {
				$data[ $key ] = $value;
				continue;
			}

			$index = $matches[2];
			$name = trim( $matches[1], '_' );

			if ( ! isset( $data['items'][ $index ] ) ) {
				$data['items'][ $index ] = array();
			}

			$data['items'][ $index ][ $name ] = $value;
		}

		foreach ( $data['items'] as &$item ) {
			if ( ! isset( $item['item_number'] ) ) {
				continue;
			}

			list( $item['post_id'], $item['ticket_id'] ) = explode( ':', $item['item_number'] );

			$item['ticket'] = tribe( 'tickets.commerce.paypal' )->get_ticket( $item['post_id'], $item['ticket_id'] );
		}

		return $data;
	}

	/**
	 * Sets transaction data from PayPal as a class property
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	public function set_transaction_data( $data ) {
		/**
		 * Filters the transaction data as it is being set
		 *
		 * @since TBD
		 *
		 * @param array $data
		 */
		$this->transaction_data = apply_filters( 'tribe_tickets_commerce_paypal_set_transaction_data', $data );
	}

	/**
	 * Gets PayPal transaction data
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	public function get_transaction_data() {
		/**
		 * Filters the transaction data as it is being retrieved
		 *
		 * @since TBD
		 *
		 * @param array $transaction_data
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_get_transaction_data', $this->transaction_data );
	}

	/**
	 * Gets the full PayPal product name
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket Ticket whose name is being generated
	 * @param WP_Post $post Post that the tickets are attached to
	 *
	 * @return string
	 */
	public function get_product_name( $ticket, $post ) {
		$title = get_the_title( $post->ID );
		$name  = $ticket->name;

		$product_name = "{$name} - {$title}";

		/**
		 * Filters the product name for PayPal's cart
		 *
		 * @since TBD
		 *
		 * @param string $product_name
		 * @param Tribe__Tickets__Ticket_Object
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_product_name', $product_name, $ticket );
	}

	/**
	 * Retrieves an invoice number (generating it if one doesn't exist)
	 */
	public function get_invoice_number() {
		$invoice_length = 127;

		if (
			! empty( $_COOKIE[ self::$invoice_cookie_name ] )
			&& strlen( $_COOKIE[ self::$invoice_cookie_name ] ) === $invoice_length
		) {
			$invoice = $_COOKIE[ self::$invoice_cookie_name ];
		} else {
			$invoice = wp_generate_password( $invoice_length, false );
		}

		// set the cookie (if it was already set, it'll extend the lifetime)
		setcookie( self::$invoice_cookie_name, $invoice, DAY_IN_SECONDS );
	}

	/**
	 * Purges an invoice cookie
	 */
	public function reset_invoice_number() {
		if ( empty( $_COOKIE[ self::$invoice_cookie_name ] ) ) {
			return;
		}

		unset( $_COOKIE[ self::$invoice_cookie_name ] );
		setcookie( self::$invoice_cookie_name, null, -1 );
	}

	/**
	 * Returns a PayPal URL
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_cart_url() {
		$paypal_url  = 'https://www.paypal.com/cgi-bin/webscr';
		$sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

		if ( tribe_get_option( 'ticket-paypal-sandbox' ) ) {
			return $sandbox_url;
		}

		return $paypal_url;
	}
}