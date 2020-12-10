<?php

/**
 * The ORM/Repository class for Tribe Commerce (PayPal) attendees.
 *
 * @since 4.10.6
 */
class Tribe__Tickets__Repositories__Attendee__Commerce extends Tribe__Tickets__Attendee_Repository {

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @var string
	 */
	protected $key_name = 'tribe-commerce';

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		/** @var Tribe__Tickets__Commerce__PayPal__Main $provider */
		$provider = tribe( 'tickets.commerce.paypal' );

		// Add object specific aliases.
		$this->update_fields_aliases = array_merge( $this->update_fields_aliases, array(
			'ticket_id'       => $provider::ATTENDEE_PRODUCT_KEY,
			'product_id'      => $provider::ATTENDEE_PRODUCT_KEY,
			'event_id'        => $provider::ATTENDEE_EVENT_KEY,
			'post_id'         => $provider::ATTENDEE_EVENT_KEY,
			'security_code'   => $provider->security_code,
			'order_id'        => $provider->order_key,
			'optout'          => $provider->attendee_optout_key,
			'user_id'         => $provider->attendee_user_id,
			'price_paid'      => '_paid_price',
			'price_currency'  => '_price_currency_symbol',
			'full_name'       => $provider->full_name,
			'email'           => $provider->email,
			'attendee_status' => $provider->attendee_tpp_key,
			'refund_order_id' => $provider->refund_order_key,
		) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_types() {
		return $this->limit_list( $this->key_name, parent::attendee_types() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_event_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_event_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_ticket_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_ticket_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_to_order_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_to_order_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_name_keys() {
		return $this->limit_list( $this->key_name, parent::purchaser_name_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function purchaser_email_keys() {
		return $this->limit_list( $this->key_name, parent::purchaser_email_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function security_code_keys() {
		return $this->limit_list( $this->key_name, parent::security_code_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function attendee_optout_keys() {
		return $this->limit_list( $this->key_name, parent::attendee_optout_keys() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function checked_in_keys() {
		return $this->limit_list( $this->key_name, parent::checked_in_keys() );
	}

	/**
	 * Update Additional data after creation of attendee.
	 *
	 * @since TBD
	 *
	 * @param WP_Post                       $attendee Attendee Object.
	 * @param Tribe__Tickets__Ticket_Object $ticket Ticket Object.
	 * @param array                         $attendee_data Array of attendee data.
	 */
	public function update_additional_data( $attendee, $ticket, $attendee_data ) {

		$attendee_data = $this->format_attendee_data( $attendee, $ticket, $attendee_data );

		$query = $this->by( 'id', $attendee->ID );

		try {
			$query->set_args( $attendee_data );
		} catch ( Tribe__Repository__Usage_Error $e ) {
			do_action( 'tribe_log', 'error', __CLASS__, [ 'message' => $e->getMessage() ] );
			return;
		} finally {
			$query->save();

			$this->trigger_actions( $attendee, $ticket, $attendee_data );
		}
	}

	/**
	 * Format attendee data for meta updates.
	 *
	 * @param WP_Post                       $attendee Attendee Post Object.
	 * @param Tribe__Tickets__Ticket_Object $ticket Ticket Object.
	 * @param array                         $attendee_data Array of attendee data.
	 *
	 * @return array
	 */
	public function format_attendee_data( $attendee, $ticket, $attendee_data ) {

		/** @var Tribe__Tickets__Commerce__PayPal__Main $provider */
		$provider = tribe( 'tickets.commerce.paypal' );

		/** @var Tribe__Tickets__Commerce__Currency $currency */
		$currency        = tribe( 'tickets.commerce.currency' );
		$currency_symbol = $currency->get_currency_symbol( $ticket->ID, true );
		$event           = $provider->get_event_for_ticket( $ticket->ID );

		$defaults = [
			'ticket_id'         => $ticket->ID,
			'event_id'          => $event ? $event->ID : '',
			'security_code'     => $provider->generate_security_code( $attendee->ID ),
			'optout'            => 1,
			'attendee_status'   => 'completed',
			'price_paid'        => 0,
			'order_id'          => null,
			'user_id'           => 0,
			'order_attendee_id' => 0,
			'price_currency'    => $currency_symbol,
		];

		/**
		 * Filter the formatted defaults for TribeCommerce attendee.
		 *
		 * @since TBD
		 *
		 * @param array $data Attendee data.
		 * @param Tribe__Tickets__Ticket_Object $ticket Ticket for attendee.
		 */
		$attendee_data = apply_filters( 'tribe_tickets_attendee_tc_paypal_data_before_insert', wp_parse_args( $attendee_data, $defaults ), $ticket );

		return $attendee_data;
	}

	/**
	 * Trigger actions.
	 *
	 * @since TBD
	 *
	 * @param WP_Post                       $attendee Attendee Post Object.
	 * @param Tribe__Tickets__Ticket_Object $ticket Ticket Object.
	 * @param array                         $attendee_data Array of attendee data.
	 */
	public function trigger_actions( $attendee, $ticket, $attendee_data ) {

		$attendee_id           = $attendee->ID;
		$order_id              = $attendee_data['order_id'];
		$product_id            = $ticket->ID;
		$order_attendee_id     = $attendee_data['order_attendee_id'];
		$attendee_order_status = $attendee_data['attendee_status'];

		/**
		 * Action fired when an PayPal attendee ticket is created
		 *
		 * @since 4.7
		 *
		 * @param int    $attendee_id           Attendee post ID
		 * @param string $order_id              PayPal Order ID
		 * @param int    $product_id            PayPal ticket post ID
		 * @param int    $order_attendee_id     Attendee number in submitted order
		 * @param string $attendee_order_status The order status for the attendee.
		 */
		do_action( 'event_tickets_tpp_attendee_created', $attendee_id, $order_id, $product_id, $order_attendee_id, $attendee_order_status );
	}

}
