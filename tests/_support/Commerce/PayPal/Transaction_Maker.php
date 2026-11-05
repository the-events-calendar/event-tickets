<?php

namespace Tribe\Tickets\Test\Commerce\PayPal;

use Faker\Factory;
use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Builds PayPal Standard transactions the way production produces them: the checkout stores a cart under an
 * invoice, then PayPal reports a payment for it.
 */
trait Transaction_Maker {
	use Ticket_Maker;

	/**
	 * @var array<string,mixed> The `$_GET` superglobal before the test.
	 */
	private array $paypal_get_backup = [];

	/**
	 * @var int The number of emails `wp_mail()` was asked to send.
	 */
	private int $paypal_sent_emails = 0;

	/**
	 * @var string[] The locations the site redirected the buyer to.
	 */
	private array $paypal_redirects = [];

	/**
	 * @var array{0: array<string,string>|null, 1: array<string,object>} The status manager's active modules and statuses before the test.
	 */
	private array $paypal_status_backup = [ null, [] ];

	/**
	 * @var array{0: array<string,\WP_Post_Type>, 1: array<string,object>} The registered post types and statuses before the test.
	 */
	private array $paypal_registry_backup = [ [], [] ];

	/**
	 * @before
	 */
	public function set_up_paypal_transactions(): void {
		$this->paypal_get_backup  = $_GET;
		$this->paypal_sent_emails = 0;
		$this->paypal_redirects   = [];

		$status_manager             = tribe( 'tickets.status' );
		$this->paypal_status_backup = ( fn() => [ $this->active_modules, $this->statuses ] )->call( $status_manager );

		tribe_update_option( 'ticket-paypal-email', 'merchant@example.com' );
		tribe_update_option( 'ticket-commerce-currency-code', 'USD' );

		add_filter( 'tribe_tickets_post_types', static fn() => [ 'post' ] );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules',
			static function ( $modules ) {
				$modules[ PayPal::class ] = tribe( 'tickets.commerce.paypal' )->plugin_name;

				return $modules;
			}
		);
		add_filter( 'tribe_exit', static fn() => '__return_true' );
		/*
		 * Records the location and passes it on: the hooks are saved once per run, after the first test's `@before`,
		 * so the first test's callback stays hooked ahead of this one. The settings tabs drop it at 10.
		 */
		add_filter(
			'wp_redirect',
			function ( $location ) {
				$this->paypal_redirects[] = $location;

				return $location;
			},
			0
		);
		add_filter( 'wp_redirect', '__return_false', PHP_INT_MAX );
		add_filter(
			'pre_wp_mail',
			function () {
				++$this->paypal_sent_emails;

				return true;
			}
		);
		tribe_singleton( 'tickets.data_api', new Data_API() );
		/*
		 * Production reads the PayPal statuses and registers the order types and statuses on `init`, when the module
		 * is already active. Unregistered, a query for denied orders matches every order.
		 */
		$this->paypal_registry_backup = [ $GLOBALS['wp_post_types'], $GLOBALS['wp_post_statuses'] ];
		$status_manager->setup();
		tribe( 'tickets.commerce.paypal' )->register_types();
	}

	/**
	 * @after
	 */
	public function restore_paypal_request(): void {
		$_GET = $this->paypal_get_backup;

		( function ( array $backup ) {
			[ $this->active_modules, $this->statuses ] = $backup;
		} )->call( tribe( 'tickets.status' ), $this->paypal_status_backup );
		[ $GLOBALS['wp_post_types'], $GLOBALS['wp_post_statuses'] ] = $this->paypal_registry_backup;
	}

	/**
	 * Stores the cart, then builds the PayPal cart URL the checkout sends the buyer to.
	 *
	 * @param int $ticket_id The ticket in the cart.
	 * @param int $quantity  The quantity in the cart.
	 *
	 * @return string The invoice the cart is stored under.
	 */
	protected function check_out_with_paypal( int $ticket_id, int $quantity ): string {
		$invoice     = Factory::create()->regexify( '[A-Za-z0-9]{12}' );
		$use_invoice = static fn() => $invoice;
		$paypal      = tribe( 'tickets.commerce.paypal' );
		$cart        = tribe( 'tickets.commerce.paypal.cart' );

		$cart->set_id( $invoice );
		$cart->add_item( $ticket_id, $quantity );
		$cart->save();

		// The checkout reads the invoice from the buyer's cookie.
		add_filter( 'tribe_tickets_commerce_paypal_invoice_number', $use_invoice );
		tribe( 'tickets.commerce.paypal.gateway' )->get_paypal_cart_api_url( $paypal->get_event_for_ticket( $ticket_id )->ID );
		remove_filter( 'tribe_tickets_commerce_paypal_invoice_number', $use_invoice );

		return $invoice;
	}

	/**
	 * Builds a Completed payment PayPal reports for the cart, before PayPal's own URL encoding.
	 *
	 * @param Ticket_Object       $ticket    The ticket bought.
	 * @param int                 $quantity  The quantity bought.
	 * @param string              $invoice   The invoice of the cart.
	 * @param array<string,string|int|float|null> $overrides Fields replacing the generated ones; `null` removes a field.
	 *
	 * @return array<string,string|int|float> The transaction fields.
	 */
	protected function paypal_transaction( Ticket_Object $ticket, int $quantity, string $invoice, array $overrides = [] ): array {
		$faker = Factory::create();
		$total = number_format( $ticket->price * $quantity, 2, '.', '' );

		$transaction = array_merge(
			[
				'payment_status' => 'Completed',
				'txn_id'         => strtoupper( $faker->bothify( '#?#?#?#?#?#?#?#?#' ) ),
				'first_name'     => $faker->firstName(),
				'last_name'      => $faker->lastName(),
				'payer_email'    => $faker->safeEmail(),
				'receiver_email' => 'merchant@example.com',
				'business'       => 'merchant@example.com',
				'custom'         => Custom_Argument::encode(
					[
						'user_id'       => 0,
						'tribe_handler' => 'tpp',
						'pid'           => $ticket->get_event_id(),
						'invoice'       => $invoice,
					]
				),
				'item_number1'   => "{$ticket->get_event_id()}:{$ticket->ID}",
				'quantity1'      => $quantity,
				'mc_gross_1'     => $total,
				'mc_gross'       => $total,
				'mc_currency'    => 'USD',
			],
			$overrides
		);

		return array_filter( $transaction, static fn( $value ) => null !== $value );
	}

	/**
	 * Sends the buyer back from PayPal with a `tx` that PayPal's PDT lookup answers with SUCCESS and the transaction.
	 *
	 * @param array<string,string|int|float> $transaction The transaction fields PayPal reports.
	 */
	protected function return_from_paypal( array $transaction ): void {
		$body = 'SUCCESS';

		foreach ( $transaction as $key => $value ) {
			$body .= "\n{$key}=" . rawurlencode( $value );
		}

		add_filter(
			'pre_http_request',
			static function ( $response, $args, $url ) use ( $body ) {
				if ( false === strpos( $url, 'paypal.com/cgi-bin/webscr' ) || '_notify-synch' !== ( $args['body']['cmd'] ?? '' ) ) {
					return $response;
				}

				return [
					'headers'  => [],
					'body'     => $body,
					'response' => [ 'code' => 200, 'message' => 'OK' ],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);

		$_GET['tx'] = $transaction['txn_id'];

		( new PDT() )->check_response();
	}

	/**
	 * Posts the transaction to the site as a PayPal IPN notification PayPal confirms as VERIFIED.
	 *
	 * @param array<string,string|int|float> $transaction The transaction fields PayPal reports.
	 */
	protected function notify_from_paypal( array $transaction ): void {
		$post_backup = $_POST;
		$_POST       = wp_slash( $transaction );
		add_filter( 'tribe_tickets_commerce_paypal_validate_transaction', '__return_true' );

		try {
			( new IPN() )->check_response();
		} finally {
			$_POST = $post_backup;
			remove_filter( 'tribe_tickets_commerce_paypal_validate_transaction', '__return_true' );
		}
	}

	/**
	 * Returns the ticket as stored now, sales included.
	 *
	 * @param Ticket_Object $ticket The ticket.
	 *
	 * @return Ticket_Object The ticket read again.
	 */
	protected function stored_paypal_ticket( Ticket_Object $ticket ): Ticket_Object {
		return tribe( 'tickets.commerce.paypal' )->get_ticket( $ticket->get_event_id(), $ticket->ID );
	}
}
