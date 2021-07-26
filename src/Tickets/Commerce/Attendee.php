<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Attendee
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Attendee {
	/**
	 * Tickets Commerce Attendee Post Type slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tc_attendee';

	/**
	 * Which meta holds the Relation ship between an attendee and which user it's registered to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $user_relation_meta_key = '_tribe_tickets_attendee_user_id';

	/**
	 * Which meta holds the Relation ship between an attendee and which event it's registered to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $event_relation_meta_key = '_tec_tickets_commerce_event';

	/**
	 * Which meta holds the Relation ship between an attendee and which ticket it was created from.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $ticket_relation_meta_key = '_tec_tickets_commerce_ticket';

	/**
	 * Which meta holds the Relation ship between an attendee and which order it belongs to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $order_relation_meta_key = '_tec_tickets_commerce_order';

	/**
	 * Which meta holds the purchaser name for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $purchaser_name_meta_key = '_tec_tickets_commerce_purchaser_name';

	/**
	 * Which meta holds the purchaser email for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $purchaser_email_meta_key = '_tec_tickets_commerce_purchaser_email';

	/**
	 * Which meta holds the security code for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $security_code_meta_key = '_tec_tickets_commerce_security_code';

	/**
	 * Which meta holds the status value for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $status_meta_key = '_tec_tickets_commerce_status';

	/**
	 * Which meta holds the optout value for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $optout_meta_key = '_tec_tickets_commerce_optout';

	/**
	 * Which meta holds the checked in status for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $checked_in_meta_key = '_tec_tickets_commerce_checked_in';

	/**
	 * Which meta holds the checked in status for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $deleted_ticket_meta_key = '_tribe_deleted_product_name';

	/**
	 * Indicates if a ticket for this attendee was sent out via email.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $ticket_sent_meta_key = '_tec_tickets_commerce_attendee_ticket_sent';

	/**
	 * Meta key holding an indication if this attendee was subscribed.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $subscribed_meta_key = '_tribe_tickets_subscribed';

	/**
	 * Meta key holding the first name for the attendee. (not purchaser)
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $first_name_meta_key = '_tec_tickets_commerce_first_name';

	/**
	 * Meta key holding the last name for the attendee. (not purchaser)
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $last_name_meta_key = '_tec_tickets_commerce_last_name';

	/**
	 * Meta key holding the email for the attendee. (not purchaser)
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $email_meta_key = '_tec_tickets_commerce_email';


	/**
	 * Register this Class post type into WP.
	 *
	 * @since TBD
	 */
	public function register_post_type() {
		$post_type_args = [
			'label'           => __( 'Attendees', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
		];

		/**
		 * Filter the arguments that craft the attendee post type.
		 *
		 * @see   register_post_type
		 *
		 * @since TBD
		 *
		 * @param array $post_type_args Post type arguments, passed to register_post_type()
		 */
		$post_type_args = apply_filters( 'tec_tickets_commerce_attendee_post_type_args', $post_type_args );

		register_post_type( static::POSTTYPE, $post_type_args );
	}

	/**
	 * If the post that was moved to the trash was an PayPal Ticket attendee post type, redirect to
	 * the Attendees Report rather than the PayPal Ticket attendees post list (because that's kind of
	 * confusing)
	 *
	 * @since TBD
	 *
	 * @param int $post_id WP_Post ID
	 */
	public function maybe_redirect_to_attendees_report( $post_id ) {
		$post = get_post( $post_id );

		if ( static::POSTTYPE !== $post->post_type ) {
			return;
		}

		$args = array(
			'post_type' => 'tribe_events',
			'page'      => \Tribe__Tickets__Tickets_Handler::$attendees_slug,
			'event_id'  => get_post_meta( $post_id, static::$event_relation_meta_key, true ),
		);

		$url = add_query_arg( $args, admin_url( 'edit.php' ) );
		$url = esc_url_raw( $url );

		wp_redirect( $url );
		tribe_exit();
	}

	/**
	 * Update the Ticket Commerce values for this user.
	 *
	 * Note that, within this method, $order_id refers to the attendee or ticket ID
	 * (it does not refer to an "order" in the sense of a transaction that may include
	 * multiple tickets, as is the case in some other methods for instance).
	 *
	 * @todo  Adjust to the Ticket Commerce data.
	 *
	 * @since TBD
	 *
	 * @param array $attendee_data Information that we are trying to save.
	 * @param int   $attendee_id   The attendee ID.
	 * @param int   $post_id       The event/post ID.
	 */
	public function update_attendee_data( $attendee_data, $attendee_id, $post_id ) {
		// Bail if the user is not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		$ticket_attendees    = $this->tickets_view->get_post_ticket_attendees( $post_id, $user_id );
		$ticket_attendee_ids = wp_list_pluck( $ticket_attendees, 'attendee_id' );

		// This makes sure we don't save attendees for attendees that are not from this current user and event.
		if ( ! in_array( $attendee_id, $ticket_attendee_ids, true ) ) {
			return;
		}

		$attendee_data_to_save = [];

		// Only update full name if set.
		if ( ! empty( $attendee_data['full_name'] ) ) {
			$attendee_data_to_save['full_name'] = sanitize_text_field( $attendee_data['full_name'] );
		}

		// Only update email if set.
		if ( ! empty( $attendee_data['email'] ) ) {
			$attendee_data['email'] = sanitize_email( $attendee_data['email'] );

			// Only update email if valid.
			if ( is_email( $attendee_data['email'] ) ) {
				$attendee_data_to_save['email'] = $attendee_data['email'];
			}
		}

		// Only update optout if set.
		if ( isset( $attendee_data['optout'] ) ) {
			$attendee_data_to_save['optout'] = (int) tribe_is_truthy( $attendee_data['optout'] );
		}

		// Only update if there's data to set.
		if ( empty( $attendee_data_to_save ) ) {
			return;
		}

		tribe( Module::class )->update_attendee( $attendee_id, $attendee_data_to_save );
	}

	/**
	 * Triggers the sending of ticket emails after PayPal Ticket information is updated.
	 *
	 * This is useful if a user initially suggests they will not be attending
	 * an event (in which case we do not send tickets out) but where they
	 * incrementally amend the status of one or more of those tickets to
	 * attending, at which point we should send tickets out for any of those
	 * newly attending persons.
	 *
	 * @since TBD
	 *
	 * @param int $event_id
	 */
	public function maybe_send_tickets_after_status_change( $event_id ) {
		$transaction_ids = array();

		foreach ( tribe( Module::class )->get_event_attendees( $event_id ) as $attendee ) {
			$transaction = get_post_meta( $attendee['attendee_id'], static::$order_relation_meta_key, true );

			if ( ! empty( $transaction ) ) {
				$transaction_ids[ $transaction ] = $transaction;
			}
		}

		foreach ( $transaction_ids as $transaction ) {
			// This method takes care of intelligently sending out emails only when
			// required, for attendees that have not yet received their tickets
			tribe( Module::class )->send_tickets_email( $transaction, $event_id );
		}
	}

	/**
	 * Add our class to the list of classes for the attendee registration form
	 *
	 * @since TBd
	 *
	 * @param array $classes existing array of classes
	 *
	 * @return array $classes with our class added
	 */
	public function registration_form_class( $classes ) {
		$classes[ static::POSTTYPE ] = \TEC\Tickets\Commerce::ABBR;

		return $classes;
	}

	/**
	 * Filter the provider object to return this class if tickets are for this provider.
	 *
	 * @since TBD
	 *
	 * @param object $provider_obj
	 * @param string $provider
	 *
	 * @return object
	 */
	public function registration_cart_provider( $provider_obj, $provider ) {
		$options = [
			\TEC\Tickets\Commerce::ABBR,
			static::POSTTYPE,
			\TEC\Tickets\Commerce::PROVIDER,
			static::class,
		];

		if ( in_array( $provider, $options, true ) ) {
			return tribe( Module::class );
		}

		return $provider_obj;
	}

	/**
	 * Fetches the full name for attendee, based on first and last.
	 *
	 * @since TBD
	 *
	 * @param string|int|\WP_Post $attendee Attendee we are getting the full name from.
	 *
	 * @return string|null
	 */
	public function get_full_name( $attendee ) {
		if ( is_numeric( $attendee ) ) {
			/** @var \WP_Post $attendee */
			$attendee = get_post( $attendee );
		}

		if ( ! $attendee instanceof \WP_Post || static::POSTTYPE !== $attendee->post_type ) {
			return null;
		}

		$first_name = get_post_meta( $attendee->ID, static::$first_name_meta_key, true );
		$last_name  = get_post_meta( $attendee->ID, static::$last_name_meta_key, true );

		return $first_name . ' ' . $last_name;
	}

	/**
	 * Get attendee data for attendee.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post|int $attendee Attendee object or ID.
	 * @param int          $post_id  Parent post ID.
	 *
	 * @return array|false The attendee data or false if the ticket is invalid.
	 */
	public function get_attendee( $attendee, $post_id ) {
		if ( is_numeric( $attendee ) ) {
			/** @var \WP_Post $attendee */
			$attendee = get_post( $attendee );
		}

		if ( ! $attendee instanceof \WP_Post || static::POSTTYPE !== $attendee->post_type ) {
			return false;
		}

		$product_id = get_post_meta( $attendee->ID, static::$ticket_relation_meta_key, true );

		if ( empty( $product_id ) ) {
			return false;
		}

		$product            = get_post( $product_id );
		$is_product_deleted = empty( $product );

		$order_id = get_post_meta( $attendee->ID, static::$order_relation_meta_key, true );
		$event_id = get_post_meta( $attendee->ID, static::$event_relation_meta_key, true );
		$user_id  = get_post_meta( $attendee->ID, static::$user_relation_meta_key, true );

		$checkin              = get_post_meta( $attendee->ID, static::$checked_in_meta_key, true );
		$security             = get_post_meta( $attendee->ID, static::$security_code_meta_key, true );
		$optout               = tribe_is_truthy( get_post_meta( $attendee->ID, static::$optout_meta_key, true ) );
		$status               = get_post_meta( $attendee->ID, static::$status_meta_key, true );
		$ticket_sent          = (int) get_post_meta( $attendee->ID, static::$ticket_sent_meta_key, true );
		$deleted_ticket_title = get_post_meta( $attendee->ID, static::$deleted_ticket_meta_key, true );
		$full_name            = $this->get_full_name();
		$email                = get_post_meta( $attendee->ID, static::$email_meta_key, true );
		$is_subscribed        = tribe_is_truthy( get_post_meta( $attendee->ID, static::$subscribed_meta_key, true ) );

		// Tries to determine an Attendee Unique ID.
		$ticket_unique_id = get_post_meta( $attendee->ID, '_unique_id', true );
		$ticket_unique_id = empty( $ticket_unique_id ) ? $attendee->ID : $ticket_unique_id;

		$product_title = ( $is_product_deleted ? $product->post_title : $deleted_ticket_title . ' ' . __( '(deleted)', 'event-tickets' ) );

		$meta = '';
		if ( class_exists( 'Tribe__Tickets_Plus__Meta', false ) ) {
			$meta = get_post_meta( $attendee->ID, \Tribe__Tickets_Plus__Meta::META_KEY, true );

			// Process Meta to include value, slug, and label
			if ( ! empty( $meta ) ) {
				$meta = tribe( Module::class )->process_attendee_meta( $product_id, $meta );
			}
		}

		$attendee_data = array_merge(
			$this->get_order_data( $attendee->ID ),
			[
				'optout'        => $optout,
				'ticket'        => $product_title,
				'attendee_id'   => $attendee->ID,
				'security'      => $security,
				'product_id'    => $product_id,
				'check_in'      => $checkin,
				'order_status'  => $status,
				'user_id'       => $user_id,
				'ticket_sent'   => $ticket_sent,

				// This is used to find existing attendees.
				'post_title'    => $attendee->post_title,

				// Fields for Email Tickets.
				'event_id'      => $event_id,
				'ticket_name'   => ! empty( $product ) ? $product->post_title : false,
				'holder_name'   => $full_name,
				'holder_email'  => $email,
				'order_id'      => $attendee->ID,
				'order_hash'    => $order_id,
				'ticket_id'     => $ticket_unique_id,
				'qr_ticket_id'  => $attendee->ID,
				'security_code' => $security,

				// Attendee Meta.
				'attendee_meta' => $meta,

				// Handle initial Attendee flags.
				'is_subscribed' => $is_subscribed,
				'is_purchaser'  => true,
			]
		);

		$attendee_data['is_purchaser'] = $attendee_data['holder_email'] === $attendee_data['purchaser_email'];

		/**
		 * Allow filtering the attendee information to return.
		 *
		 * @since 4.7
		 *
		 * @param array   $attendee_data The attendee information.
		 * @param string  $provider_slug The provider slug.
		 * @param WP_Post $attendee      The attendee post object.
		 * @param int     $post_id       The post ID of the attendee ID.
		 *
		 */
		return apply_filters( 'tribe_tickets_attendee_data', $attendee_data, 'tpp', $attendee, $post_id );
	}
}