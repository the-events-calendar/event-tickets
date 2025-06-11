<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Webhook_Endpoint;

/**
 * Class Payment Intent Handler
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Payment_Intent_Handler {
	/**
	 * Store the Payment Intent for the duration of the page load.
	 *
	 * @since 5.19.3
	 *
	 * @var array $payment_intent The Payment Intent.
	 */
	protected array $payment_intent = [];

	/**
	 * Calls the Stripe API and returns a new PaymentIntent object, used to authenticate
	 * front-end payment requests.
	 *
	 * @since 5.3.0
	 *
	 * @param mixed $_deprecated Deprecated.
	 */
	public function create_payment_intent_for_cart( $_deprecated = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( null !== $_deprecated ) {
			_deprecated_argument( __METHOD__, '5.19.3', esc_html__( 'This method no longer uses the 1st param.', 'event-tickets' ) );
		}

		// Somehow we already have a payment intent.
		if ( $this->get() ) {
			return;
		}

		// Let us look into the cookie.
		$payment_intent = $this->get_existing_if_valid();
		if ( ! $payment_intent ) {
			// If it all fails lets create a new one.
			$payment_intent = Payment_Intent::create_from_cart( tribe( Cart::class ) );

			if ( isset( $payment_intent['id'] ) && empty( $payment_intent['errors'] ) ) {
				$this->store_payment_intent_cookie( $payment_intent['id'] );
			}
		}

		$this->set( $payment_intent );
	}

	/**
	 * Gets the existing Payment Intent if it is valid.
	 *
	 * @since 5.19.3
	 *
	 * @return array|null
	 */
	protected function get_existing_if_valid(): ?array {
		$existing_payment_intent_id = $this->get_payment_intent_cookie();
		if ( ! $existing_payment_intent_id ) {
			return null;
		}

		$payment_intent = Payment_Intent::get( $existing_payment_intent_id );
		if ( is_wp_error( $payment_intent ) ) {
			return null;
		}

		if ( ! $payment_intent ) {
			return null;
		}

		if ( ! empty( $payment_intent['errors'] ) ) {
			return null;
		}

		return $payment_intent;
	}

	/**
	 * Store the Payment Intent for the duration of the page load.
	 *
	 * @since 5.19.3
	 *
	 * @param array $payment_intent The Payment Intent.
	 *
	 * @return void
	 */
	public function set( array $payment_intent ): void {
		// Do some real basic validation.
		if ( empty( $payment_intent['id'] ) ) {
			return;
		}

		$this->payment_intent = $payment_intent;

		// If the existing cookie is different, update it.
		if ( $this->get_payment_intent_cookie() !== $payment_intent['id'] ) {
			$this->store_payment_intent_cookie( $payment_intent['id'] );
		}
	}

	/**
	 * Gets the stored Payment Intent.
	 *
	 * @since 5.19.3
	 *
	 * @return array
	 */
	public function get(): array {
		return $this->payment_intent;
	}

	/**
	 * Where we store the payment intent ID in a cookie.
	 *
	 * @since 5.19.3
	 *
	 * @return string
	 */
	public function get_payment_intent_cookie_name(): string {
		return Gateway::get_provider_key() . '-payment-intent-' . tribe( Cart::class )->get_cart_hash();
	}

	/**
	 * Retrieve the payment intent ID from a cookie.
	 *
	 * @since 5.19.3
	 *
	 * @return ?string
	 */
	public function get_payment_intent_cookie(): ?string {
		return ! empty( $_COOKIE[ $this->get_payment_intent_cookie_name() ] ) ? sanitize_key( $_COOKIE[ $this->get_payment_intent_cookie_name() ] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
	}

	/**
	 * Store the payment intent ID in a cookie.
	 *
	 * @since 5.19.3
	 *
	 * @param mixed $payment_intent_id The payment intent ID.
	 *
	 * @return bool
	 */
	public function store_payment_intent_cookie( $payment_intent_id ): bool {
		if ( headers_sent() ) {
			return false;
		}

		$expire = tribe( Cart::class )->get_cart_expiration();

		// When null means we are deleting.
		if ( null === $payment_intent_id ) {
			$expire = 1;
		}

		/**
		 * Filter the cookie options for the payment intent cookie.
		 *
		 * @since 5.19.3
		 *
		 * @param array $cookie_options The cookie options.
		 *
		 * @return array
		 */
		$cookie_options = (array) apply_filters(
			'tec_tickets_commerce_stripe_payment_intent_cookie_options',
			[
				'expires'  => $expire,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
			]
		);

		$cookie_name = $this->get_payment_intent_cookie_name();

		$is_cookie_set = setcookie( $cookie_name, $payment_intent_id ?? '', $cookie_options ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie

		if ( $is_cookie_set ) {
			// Overwrite local variable, so we can use it right away.
			$_COOKIE[ $cookie_name ] = $payment_intent_id; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		}

		if ( ! $payment_intent_id ) {
			unset( $_COOKIE[ $cookie_name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		}

		return $is_cookie_set;
	}

	/**
	 * Updates an existing payment intent to add any necessary data before confirming the purchase.
	 *
	 * @since 5.3.0
	 * @since 5.8.1   Added customer's name / event name to the payment intent description
	 *
	 * @param array    $data  The purchase data received from the front-end.
	 * @param \WP_Post $order The order object.
	 *
	 * @return array|\WP_Error|null
	 */
	public function update_payment_intent( $data, \WP_Post $order ) {
		$body = [];

		// Attempt to avoid an extra request by using the existing payment intent.
		$payment_intent = $this->get();

		if ( empty( $payment_intent['id'] ) || empty( $data['payment_intent']['id'] ) || $data['payment_intent']['id'] !== $payment_intent['id'] ) {
			$payment_intent = Payment_Intent::get( $data['payment_intent']['id'] );
		}

		$stripe_receipt_emails = tribe_get_option( Settings::$option_stripe_receipt_emails );
		$body['metadata']      = $this->get_updated_metadata( $order, $payment_intent );

		if ( $stripe_receipt_emails ) {
			if ( is_user_logged_in() ) {
				$user                  = wp_get_current_user();
				$body['receipt_email'] = $user->get( 'user_email' );
			}

			if ( ! empty( $data['purchaser']['email'] ) ) {
				$body['receipt_email'] = $data['purchaser']['email'];
			}

			$body['description'] = $this->get_payment_intent_description( $order, $data, $body, $payment_intent );
		}

		return Payment_Intent::update( $payment_intent['id'], $body );
	}

	/**
	 * Assembles basic data about the payment intent created at page-load to use in javascript.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function get_publishable_payment_intent_data() {
		$pi = $this->get();

		if ( empty( $pi ) ) {
			return [];
		}

		if ( ! empty( $pi['errors'] ) ) {
			return $pi;
		}

		return [
			'id'   => $pi['id'],
			'key'  => $pi['client_secret'],
		];
	}

	/**
	 * Get the additional metadata for the payment intent.
	 *
	 * @since 5.5.0
	 *
	 * @param \WP_Post $order The Order data.
	 * @param array $payment_intent The Payment intent.
	 *
	 * @return array
	 */
	protected function get_updated_metadata( \WP_Post $order, array $payment_intent ) {
		// Add the Order ID as metadata to the Payment Intent.
		$metadata               = $payment_intent['metadata'];
		$metadata['order_id']   = $order->ID;
		$metadata['return_url'] = tribe( Webhook_Endpoint::class )->get_route_url();

		$events_in_order  = array_unique( array_filter( wp_list_pluck( $order->items, 'event_id' ) ) );
		$tickets_in_order = array_unique( array_filter( wp_list_pluck( $order->items, 'ticket_id' ) ) );
		$ticket_names     = array_map(
			static function ( $item ) {
				return get_the_title( $item );
			},
			$tickets_in_order
		);

		$metadata['purchaser_name']  = $order->purchaser_name;
		$metadata['purchaser_email'] = $order->purchaser_email;
		$metadata['event_name']      = get_post( current( $events_in_order ) )->post_title;
		$metadata['event_url']       = get_post_permalink( current( $events_in_order ) );
		$metadata['ticket_names']    = implode( ', ', $ticket_names );

		/**
		 * Filter the updated metadata for a completed order's payment intent.
		 *
		 * @since 5.5.0
		 *
		 * @param \WP_Post $order The Order data.
		 * @param array $payment_intent The Payment intent.
		 */
		return apply_filters( 'tec_tickets_commerce_stripe_update_payment_intent_metadata', $metadata, $order, $payment_intent );
	}

	/**
	 * Get the description for the payment intent.
	 *
	 * @since 5.8.1
	 *
	 * @param \WP_Post $order The Order data.
	 * @param array    $data The purchase data received from the front-end.
	 * @param array    $body The body used to update the payment intent.
	 * @param array    $payment_intent The Payment intent.
	 *
	 * @return array
	 */
	protected function get_payment_intent_description( \WP_Post $order, $data, $body, array $payment_intent ) {
		$purchaser_name = $order->purchaser_name;

		if ( is_user_logged_in() ) {
			$user           = wp_get_current_user();
			$purchaser_name = $user->get( 'first_name' ) ? $user->get( 'first_name' ) . ' ' . $user->get( 'last_name' ) : $user->get( 'display_name' );
		}

		$tickets_in_order           = implode( ', ', array_unique( array_filter( wp_list_pluck( $order->items, 'ticket_id' ) ) ) );
		$events_in_order            = array_unique( array_filter( wp_list_pluck( $order->items, 'event_id' ) ) );
		$post_id                    = current( $events_in_order );
		$post                       = get_post( $post_id );
		$post_labels                = get_post_type_labels( get_post_type_object( $post->post_type ) );
		$payment_intent_description = sprintf(
			'[%1$s: %2$s] [%3$s: %4$s] [%5$s: %6$s] %7$s - %8$s - %9$s',
			$post_labels->singular_name,
			$post_id,
			tribe_get_ticket_label_singular( 'stripe_payment_intent_description' ),
			$tickets_in_order,
			__( 'Order', 'event-tickets' ),
			$order->ID,
			$body['metadata']['event_name'],
			$body['metadata']['ticket_names'],
			$purchaser_name
		);

		/**
		 * Filters the payment intent description
		 *
		 * @since 5.8.1
		 *
		 * @param string   $payment_intent_description Default payment intent description.
		 * @param \WP_Post $order The Order data.
		 * @param array    $data The purchase data received from the front-end.
		 * @param array    $body The body used to update the payment intent.
		 * @param array    $payment_intent The Payment intent.
		 */
		$payment_intent_description = apply_filters( 'tec_tickets_commerce_stripe_update_payment_description', $payment_intent_description, $order, $data, $body, $payment_intent );

		return $payment_intent_description;
	}

	// phpcs:disable
	/**************************************
	 *
	 * Deprecated methods and Properties
	 *
	 *************************************/

	/**
	 * Base string to use when composing payment intent transient names.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @var string
	 */
	public $payment_intent_transient_prefix = 'paymentintent-';

	/**
	 * Transient name to store payment intents.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @var string
	 */
	public $payment_intent_transient_name;

	/**
	 * Counter for how many times we've re-tried creating a PaymentIntent.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @var int
	 */
	protected $payment_element_fallback_retries = 0;

	/**
	 * Max number of retries to create a PaymentIntent.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @var int
	 */
	protected $payment_intent_max_retries = 2;

	/**
	 * Increment the retry counter if under max_retries.
	 *
	 * @deprecated 5.19.3
	 *
	 * @return bool True if incremented, false if no more retries are allowed.
	 */
	public function count_retries() {
		if ( $this->payment_intent_max_retries <= $this->payment_element_fallback_retries ) {
			return false;
		}

		$this->payment_element_fallback_retries ++;

		return true;
	}

	/**
	 * Compose the transient name used for payment intent transients.
	 *
	 * @deprecated 5.19.3
	 *
	 * @since 5.3.0
	 */
	public function set_payment_intent_transient_name() {
		$this->payment_intent_transient_name = $this->payment_intent_transient_prefix . md5( tribe( Cart::class )->get_cart_hash() );
	}

	/**
	 * Returns the transient name used for payment intent transients.
	 *
	 * @deprecated 5.19.3
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_payment_intent_transient_name() {

		if ( empty( $this->payment_intent_transient_name ) ) {
			$this->set_payment_intent_transient_name();
		}

		return $this->payment_intent_transient_name;
	}

	/**
	 * Retrieve a stored payment intent referring to the current cart.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @return array|false
	 */
	public function get_payment_intent_transient() {
		return get_transient( $this->get_payment_intent_transient_name() );
	}

	/**
	 * Delete the payment intent transient.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @return bool
	 */
	public function delete_payment_intent_transient() {
		return delete_transient( $this->get_payment_intent_transient_name() );
	}

	/**
	 * Store a payment intent array in a transient.
	 *
	 * @since 5.3.0
	 * @deprecated 5.19.3
	 *
	 * @param array $payment_intent Payment intent data from Stripe.
	 */
	public function store_payment_intent( $payment_intent ) {
		set_transient( $this->get_payment_intent_transient_name(), $payment_intent, 6 * HOUR_IN_SECONDS );
	}
	// phpcs:enable
}
