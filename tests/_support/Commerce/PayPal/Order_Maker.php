<?php

namespace Tribe\Tickets\Test\Commerce\PayPal;

use Faker;
use Tribe__Tickets__Commerce__PayPal__Stati as Stati;
use Tribe__Tickets__Commerce__PayPal__Main as Main;
use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;

trait Order_Maker {

	/**
	 * Generates the PayPal orders for a post.
	 *
	 * @param int         $post_id
	 * @param int|array   $ticket_ids
	 * @param int         $ticket_count
	 * @param int         $orders_count
	 * @param string|null $order_status
	 * @param bool        $dont_send_emails
	 *
	 * @return array
	 */
	protected function create_paypal_orders( $post_id, $ticket_ids, $ticket_count, $orders_count = 1, $order_status = null, $dont_send_emails = true ) {
		$order_status           = $this->parse_order_status( $order_status );
		$this->dont_send_emails = $dont_send_emails;

		$ticket_ids = (array) $ticket_ids;

		$generated = [];

		for ( $k = 0; $k < $orders_count; $k ++ ) {
			$user_id    = 0;
			$ticket_qty = [];

			foreach ( $ticket_ids as $ticket_id ) {
				$ticket_qty[ $ticket_id ] = $ticket_count;
			}

			$items_data    = [];
			$items_index   = 1;
			$payment_gross = 0;

			/** @var Main $paypal */
			$paypal = tribe( 'tickets.commerce.paypal' );

			foreach ( $ticket_ids as $ticket_id ) {
				$this_ticket_qty = $ticket_qty[ $ticket_id ];
				//$post_id         = (int) get_post_meta( $ticket_id, $paypal->event_key, true );

				$ticket = $paypal->get_ticket( $post_id, $ticket_id );
				$post   = get_post( $post_id );
				//$this->backup_ticket_total_sales( $ticket_id );
				//$this->backup_ticket_stock( $ticket_id );

				add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_true' );
				$inventory = $ticket->inventory();
				add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false' );

				$this_ticket_qty = - 1 === $inventory ? $this_ticket_qty : min( $this_ticket_qty, (int) $inventory );

				if ( 0 === $this_ticket_qty ) {
					continue;
				}

				$ticket_qty[ $ticket_id ] = $this_ticket_qty;

				$mc_gross      = $ticket->price * $this_ticket_qty;
				$payment_gross += $mc_gross;

				$items_data[] = [
					"item_name{$items_index}"   => "{$ticket->name} - {$post->post_title}",
					"item_number{$items_index}" => "{$post_id}:{$ticket_id}",
					"mc_handling{$items_index}" => '0.00',
					"mc_shipping{$items_index}" => '0.00',
					"mc_gross_{$items_index}"   => $this->signed_value( $mc_gross ),
					"tax{$items_index}"         => '0.00',
					"quantity{$items_index}"    => $this_ticket_qty,
				];
				$items_index ++;
			}

			if ( empty( $items_data ) ) {
				continue;
			}

			$items_data = call_user_func_array( 'array_merge', $items_data );

			$this->order_status = $order_status;

			$faker = \Faker\Factory::create();
			$faker->addProvider( new Faker\Provider\en_US\Address( $faker ) );

			$transaction_id = strtoupper( substr( md5( $faker->sentence ), 0, 17 ) );
			$receiver_id    = strtoupper( substr( md5( $faker->sentence ), 0, 13 ) );
			$payer_id       = strtoupper( substr( md5( $faker->sentence ), 0, 13 ) );
			$ipn_track_id   = substr( md5( $faker->sentence ), 0, 13 );

			$receiver_email = 'merchant@' . parse_url( home_url(), PHP_URL_HOST );
			$payment_date   = $faker->date( 'H:i:s M d, Y e' );

			$data = [
				'last_name'              => $faker->lastName,
				'shipping_method'        => 'Default',
				'address_state'          => $faker->stateAbbr,
				'receiver_email'         => $receiver_email,
				'custom'                 => '{"user_id":' . $user_id . ',"tribe_handler":"tpp"}',
				'shipping_discount'      => '0.00',
				'receiver_id'            => $receiver_id,
				'charset'                => 'windows-1252',
				'payer_email'            => $faker->email,
				'protection_eligibility' => 'Eligible',
				'address_zip'            => $faker->postcode,
				'payment_fee'            => $this->signed_value( 0.09 ),
				'transaction_subject'    => '',
				'txn_id'                 => $transaction_id,
				'residence_country'      => 'US',
				'payment_status'         => ucwords( $order_status ),
				'mc_fee'                 => $this->signed_value( 0.09 ),
				'mc_gross'               => $this->signed_value( $payment_gross ),
				'insurance_amount'       => '0.00',
				'address_country'        => 'United States',
				'mc_currency'            => 'USD',
				'verify_sign'            => 'Au138tmgDC7.8B8qKvd-30AoY8IgAFfYkrYMbXOdLJmWDmKOip2XAIyQ',
				'business'               => $receiver_email,
				'address_city'           => $faker->city,
				'first_name'             => $faker->firstName,
				'address_name'           => $faker->name,
				'mc_shipping'            => '0.00',
				'notify_version'         => '3.8',
				'test_ipn'               => '1',
				'ipn_track_id'           => $ipn_track_id,
				'payment_gross'          => $this->signed_value( $payment_gross ),
				'address_country_code'   => 'US',
				'address_street'         => $faker->streetAddress,
				'payment_type'           => 'instant',
				'payer_id'               => $payer_id,
				'discount'               => '0.00',
				'payment_date'           => $payment_date,
				'mc_handling'            => '0.00',
			];

			$data = array_merge( $data, $items_data );

			if ( Stati::$refunded === $order_status ) {
				// complete the order to be refunded before the refund
				$data['payment_status'] = ucwords( Stati::$completed );
				$this->order_status     = Stati::$completed;
				$this->update_fees( $data );
				$this->place_order( Stati::$completed, $data );

				$data['payment_status'] = ucwords( Stati::$refunded );
				$this->order_status     = Stati::$refunded;
				$this->update_fees( $data );
				$data['reason_code']   = 'refund';
				$data['parent_txn_id'] = $transaction_id;
				$data['txn_id']        = strtoupper( substr( md5( $faker->sentence ), 0, 17 ) );
			}

			$this->place_order( $order_status, $data );

			$generated[] = [
				'Order ID'        => $transaction_id,
				'Attendees count' => $ticket_qty,
			];
		}

		return $generated;
	}

	/**
	 * Parses and validate the user-provided PayPal order status.
	 *
	 * @param string $order_status
	 *
	 * @return string
	 */
	protected function parse_order_status( $order_status ) {
		if ( null === $order_status ) {
			$order_status = Stati::$completed;
		}

		$order_status = trim( $order_status );

		$supported_stati = [
			Stati::$completed,
			Stati::$pending,
			Stati::$refunded,
			Stati::$denied,
		];

		if ( ! in_array( $order_status, $supported_stati, true ) ) {
			return "The {$order_status} order status is not valid or supported";
		}

		return $order_status;
	}

	/**
	 * Hijack some PayPal related hooks to make all work.
	 */
	protected function hijack_request_flow() {
		// all transactions are valid, we are generating fake numbers
		add_filter( 'tribe_tickets_commerce_paypal_validate_transaction', '__return_true' );

		// mark all generated attendees as generated
		add_action( 'event_tickets_tpp_attendee_created', function ( $attendee_id ) {
			update_post_meta( $attendee_id, '_tribe_tests_generated', 1 );
		} );

		add_filter( 'tribe_tickets_tpp_order_postarr', function ( $postarr ) {
			$postarr['meta_input']['_tribe_tests_generated'] = 1;

			return $postarr;
		} );

		if ( $this->dont_send_emails ) {
			add_filter( 'tribe_tickets_tpp_send_mail', '__return_false' );
		}

		// do not `die` after generating tickets
		add_filter( 'tribe_exit', function () {
			return '__return_true';
		} );
	}

	/**
	 * Applies a signum to a number depending on the order status.
	 *
	 * Some order stati will require a negative value, e.g. refunds.
	 *
	 * @param int $fee
	 *
	 * @return string
	 */
	protected function signed_value( $fee ) {
		if ( Stati::$refunded === $this->order_status ) {
			return '-' . $fee;
		}

		return '' . $fee;
	}

	/**
	 * Updates the fees in the data depending on the current order status.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function update_fees( array $data ) {
		$fee_fields = [
			'payment_fee',
			'mc_fee',
			'mc_gross',
			'payment_gross',
		];

		foreach ( $fee_fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				continue;
			}

			$data[ $field ] = $this->signed_value( abs( (float) $data[ $field ] ) );
		}

		return $data;
	}

	/**
	 * Places an Order using the PayPal code API.
	 *
	 * @param array  $transaction_data
	 * @param string $order_status
	 */
	protected function place_order( $order_status, $transaction_data ) {
		$this->hijack_request_flow();

		/** @var Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		/** @var Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$gateway->set_raw_transaction_data( $transaction_data );
		$fake_transaction_data = $gateway->parse_transaction( $transaction_data );

		add_filter( 'tribe_tickets_commerce_paypal_get_transaction_data', function () use ( $fake_transaction_data ) {
			return $fake_transaction_data;
		} );

		$paypal->generate_tickets( $order_status, false );
	}
}
