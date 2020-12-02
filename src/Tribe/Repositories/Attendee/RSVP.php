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

		$this->create_args['post_type'] = $provider->attendee_object;

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
		}
		catch ( Tribe__Repository__Usage_Error $e ) {
			do_action( 'tribe_log', 'error', __CLASS__, [ 'message' => $e->getMessage() ] );
			return;
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
		$event    = $provider->get_event_for_ticket( $ticket->ID );

		$defaults = [
			'ticket_id'         => $ticket->ID,
			'event_id'          => $event ? $event->ID : '',
			'security_code'     => $provider->generate_security_code( $attendee->ID ),
			'order_id'          => $provider->generate_order_id(),
			'optout'            => 1,
			'attendee_status'   => 'yes',
			'price_paid'        => 0,
			'user_id'           => 0,
			'order_attendee_id' => null,
		];

		/**
		 * Filter the formatted defaults for RSVP attendee.
		 *
		 * @since TBD
		 *
		 * @param array $data Attendee data.
		 * @param Tribe__Tickets__Ticket_Object $ticket Ticket for attendee.
		 */
		$attendee_data = apply_filters( 'tribe_tickets_attendee_rsvp_data_before_insert', wp_parse_args( $attendee_data, $defaults ), $ticket );

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
		$post_id           = $attendee_data['event_id'];
		$order_id          = $attendee_data['order_id'];
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
