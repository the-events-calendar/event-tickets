<?php

/**
 * The ORM/Repository class for RSVP attendees.
 *
 * @since 4.10.6
 */
class Tribe__Tickets__Repositories__Attendee__RSVP extends Tribe__Tickets__Attendee_Repository {

	/**
	 * Key name to use when limiting lists of keys.
	 *
	 * @var string
	 */
	protected $key_name = 'rsvp';

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		/** @var Tribe__Tickets__RSVP $provider */
		$provider = tribe( 'tickets.rsvp' );

		$this->create_args['post_type'] = 'tribe_rsvp_attendees';

		// Add object specific aliases.
		$this->update_fields_aliases = array_merge( $this->update_fields_aliases, array(
			'ticket_id'       => $provider::ATTENDEE_PRODUCT_KEY,
			'product_id'      => $provider::ATTENDEE_PRODUCT_KEY,
			'event_id'        => $provider::ATTENDEE_EVENT_KEY,
			'post_id'         => $provider::ATTENDEE_EVENT_KEY,
			'security_code'   => $provider->security_code,
			'order_id'        => $provider->order_key,
			'optout'          => $provider::ATTENDEE_OPTOUT_KEY,
			'user_id'         => $provider->attendee_user_id,
			'price_paid'      => '_paid_price',
			'full_name'       => $provider->full_name,
			'email'           => $provider->email,
			'attendee_status' => $provider::ATTENDEE_RSVP_KEY,
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
	 * {@inheritDoc}
	 */
	public function update_additional_data( $attendee, $ticket, $attendee_data ) {

		$attendee_data = $this->format_attendee_data( $attendee, $ticket, $attendee_data );

		try {
			$query = $this->where( 'id', $attendee->ID )
			              ->set( 'ticket_id', $ticket->ID )
						  ->set( 'full_name', $attendee_data['full_name'] )
						  ->set( 'email', $attendee_data['email'] )
						  ->set( 'event_id', $attendee_data['event_id'] )
						  ->set( 'security_code', $attendee_data['security_code'] )
						  ->set( 'order_id', $attendee_data['order_id'] )
						  ->set( 'optout', $attendee_data['optout'] )
						  ->set( 'order_status', $attendee_data['order_status'] )
						  ->set( 'price_paid', 0 )
						  ->set( 'user_id', $attendee_data['user_id'] );
		}
		catch ( Tribe__Repository__Usage_Error $e ) {
			do_action( 'tribe_log', 'error', __CLASS__, [
				'message' => $e->getMessage(),
			] );
		}
		finally {
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

		/** @var Tribe__Tickets__RSVP $provider */
		$provider = tribe( 'tickets.rsvp' );

		$defaults = [
			'event_id'      => $provider->get_event_for_ticket( $ticket->ID )->ID,
			'security_code' => $provider->generate_security_code( $attendee->ID ),
			'order_id'      => $provider->generate_order_id(),
			'optout'        => 1,
			'order_status'  => 'yes',
			'price_paid'    => 0,
			'user_id'       => 0,
		];

		/**
		 * Filter the formatted defaults for RSVP attendee.
		 *
		 * @since TBD
		 *
		 * @param array $data Attendee data.
		 * @param Tribe__Tickets__Ticket_Object $ticket Ticket for attendee.
		 */
		$attendee_data = apply_filters( 'event_tickets_attendee_rsvp_data_before_insert', wp_parse_args( $attendee_data, $defaults ), $ticket );

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

		$attendee_id       = $attendee->ID;
		$post_id           = $attendee['event_id'];
		$order_id          = $attendee['order_id'];
		$product_id        = $ticket->ID;
		$order_attendee_id = $attendee_data['order_attendee_id'];

		/**
		 * RSVP specific action fired when a RSVP-driven attendee ticket for an event is generated.
		 * Used to assign a unique ID to the attendee.
		 *
		 * @param int    $attendee_id ID of attendee ticket.
		 * @param int    $post_id     ID of event.
		 * @param string $order_id    RSVP order ID (hash).
		 * @param int    $product_id  RSVP product ID.
		 */
		do_action( 'event_tickets_rsvp_attendee_created', $attendee_id, $post_id, $order_id, $product_id );

		/**
		 * Action fired when an RSVP attendee ticket is created.
		 * Used to store attendee meta.
		 *
		 * @param int $attendee_id       ID of the attendee post.
		 * @param int $post_id           Event post ID.
		 * @param int $product_id        RSVP ticket post ID.
		 * @param int $order_attendee_id Attendee # for order.
		 */
		do_action( 'event_tickets_rsvp_ticket_created', $attendee_id, $post_id, $product_id, $order_attendee_id );

		if ( null === $order_attendee_id ) {
			/**
			 * Action fired when an RSVP ticket has had attendee tickets generated for it.
			 *
			 * @param int    $product_id RSVP ticket post ID.
			 * @param string $order_id   ID (hash) of the RSVP order.
			 * @param int    $qty        Quantity ordered.
			 */
			do_action( 'event_tickets_rsvp_tickets_generated_for_product', $product_id, $order_id, 1 );

			$ticket->get_provider()->clear_attendees_cache( $post_id );
		}
	}
}
