<?php
/**
 * RSVP Attendee Repository.
 *
 * @since TBD
 * @package Tribe\Tickets\Repositories\Attendee
 */

use Tribe__Utils__Array as Arr;

/**
 * The ORM/Repository class for RSVP attendees.
 *
 * Class name follows TEC naming convention with double underscores.
 *
 * @since 4.10.6
 *
 * @property Tribe__Tickets__RSVP $attendee_provider
 */
// phpcs:ignore StellarWP.Classes.ValidClassName.NotSnakeCase, Squiz.Commenting.ClassComment.Missing
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

		$this->attendee_provider = tribe( 'tickets.rsvp' );

		$this->create_args['post_type'] = $this->attendee_provider->attendee_object;

		// Use a regular variable so we can get constants from it in a PHP <7.0 compatible way.
		$attendee_provider = $this->attendee_provider;

		// Add object specific aliases.
		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases,
			[
				'ticket_id'       => $attendee_provider::ATTENDEE_PRODUCT_KEY,
				'event_id'        => $attendee_provider::ATTENDEE_EVENT_KEY,
				'post_id'         => $attendee_provider::ATTENDEE_EVENT_KEY,
				'security_code'   => $attendee_provider->security_code,
				'order_id'        => $attendee_provider->order_key,
				'optout'          => $attendee_provider::ATTENDEE_OPTOUT_KEY,
				'user_id'         => $attendee_provider->attendee_user_id,
				'price_paid'      => $attendee_provider->price_paid,
				'full_name'       => $attendee_provider->full_name,
				'email'           => $attendee_provider->email,
				'attendee_status' => $attendee_provider::ATTENDEE_RSVP_KEY,
			]
		);
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
	 * Set up the arguments to set for the attendee for this provider.
	 *
	 * @since 5.1.0
	 *
	 * @param array                         $args          List of arguments to set for the attendee.
	 * @param array                         $attendee_data List of additional attendee data.
	 * @param Tribe__Tickets__Ticket_Object $ticket        The ticket object or null if not relying on it.
	 *
	 * @return array List of arguments to set for the attendee.
	 */
	public function setup_attendee_args( $args, $attendee_data, $ticket = null ) {
		// Set default order ID.
		if ( empty( $args['order_id'] ) ) {
			// Use a regular variable so we can call static methods from it in a PHP <7.0 compatible way.
			$attendee_provider = $this->attendee_provider;

			$args['order_id'] = $attendee_provider::generate_order_id();
		}

		// Set default attendee status.
		if ( ! isset( $args['attendee_status'] ) ) {
			$args['attendee_status'] = 'yes';
		}

		return $args;
	}

	/**
	 * Handle backwards compatible creation actions for RSVPs.
	 *
	 * @since 5.1.0
	 *
	 * @param WP_Post                       $attendee      The attendee object.
	 * @param array                         $attendee_data List of additional attendee data.
	 * @param Tribe__Tickets__Ticket_Object $ticket        The ticket object.
	 */
	public function trigger_create_actions( $attendee, $attendee_data, $ticket ) {
		$attendee_id       = $attendee->ID;
		$post_id           = Arr::get( $attendee_data, 'post_id' );
		$order_id          = $attendee_data['order_id'];
		$product_id        = $ticket->ID;
		$order_attendee_id = Arr::get( $attendee_data, 'order_attendee_id' );
		$attendee_status   = Arr::get( $attendee_data, 'attendee_status', 'yes' );

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
		}

		// Update the ticket sales numbers.
		if ( $post_id && 'yes' === $attendee_status ) {
			$this->attendee_provider->increase_ticket_sales_by( $product_id, 1 );
		}

		parent::trigger_create_actions( $attendee, $attendee_data, $ticket );
	}

	/**
	 * Handle backwards compatible update actions for RSVPs.
	 *
	 * @since 5.1.0
	 *
	 * @param array $attendee_data List of attendee data to be saved.
	 */
	public function trigger_update_actions( $attendee_data ) {
		parent::trigger_update_actions( $attendee_data );

		$attendee_id     = (int) Arr::get( $attendee_data, 'attendee_id' );
		$post_id         = (int) Arr::get( $attendee_data, 'post_id' );
		$attendee_status = Arr::get( $attendee_data, 'attendee_status' );

		/**
		 * An Action fired when an RSVP is updated.
		 *
		 * @since 4.11.0
		 *
		 * @param int    $attendee_id     The attendee ID.
		 * @param int    $post_id         The event/post ID.
		 * @param string $attendee_status The status of the attendee, either yes or no.
		 */
		do_action( 'event_tickets_rsvp_after_attendee_update', $attendee_id, $post_id, $attendee_status );
	}

	/**
	 * Get a single field value without loading full attendee object.
	 *
	 * @since TBD
	 *
	 * @param int    $attendee_id Attendee ID.
	 * @param string $field       Field name (alias-aware).
	 * @return mixed Field value or null if not found.
	 */
	public function get_field( int $attendee_id, string $field ) {
		// Resolve field alias to actual meta key.
		$meta_key = Arr::get( $this->update_fields_aliases, $field, $field );

		if ( ! $meta_key ) {
			return null;
		}

		$value = get_post_meta( $attendee_id, $meta_key, true );

		// Return null if meta doesn't exist (empty string or false).
		return ( '' === $value || false === $value ) ? null : $value;
	}

	/**
	 * Bulk update multiple attendees with the same field values.
	 *
	 * More efficient than calling update() in a loop.
	 *
	 * @since TBD
	 *
	 * @param array $attendee_ids Attendee IDs.
	 * @param array $updates      Fields to update (e.g., ['attendee_status' => 'yes']).
	 * @return array Results indexed by attendee ID (true = success, false = failure).
	 */
	public function bulk_update( array $attendee_ids, array $updates ) {
		$results = [];

		foreach ( $attendee_ids as $attendee_id ) {
			// Check if attendee exists first.
			$attendee = get_post( $attendee_id );
			if ( ! $attendee ) {
				$results[ $attendee_id ] = false;
				continue;
			}

			$result = $this->by( 'id', $attendee_id )
							->set_args( $updates )
							->save();

			$results[ $attendee_id ] = false !== $result;
		}

		return $results;
	}

	/**
	 * Get attendee counts grouped by RSVP status.
	 *
	 * @since TBD
	 *
	 * @param int $event_id Event ID.
	 * @return array Status counts (e.g., ['yes' => 10, 'no' => 5]).
	 */
	public function get_status_counts( int $event_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm2.meta_value as status, COUNT(*) as count
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
				 INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
				 WHERE p.post_type = %s
				 AND pm1.meta_value = %d
				 GROUP BY pm2.meta_value",
				'_tribe_rsvp_event',
				'_tribe_rsvp_status',
				'tribe_rsvp_attendees',
				$event_id
			)
		);

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row->status ] = (int) $row->count;
		}

		return $counts;
	}
}
