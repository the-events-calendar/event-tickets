<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce;

/**
 * Class Attendee
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce
 */
class Attendee {
	/**
	 * Tickets Commerce Attendee Post Type slug.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tc_attendee';

	/**
	 * Which meta holds the Relation ship between an attendee and which user it's registered to.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $user_relation_meta_key = '_tribe_tickets_attendee_user_id';

	/**
	 * Which meta holds the Relation ship between an attendee and which event it's registered to.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $event_relation_meta_key = '_tec_tickets_commerce_event';

	/**
	 * Which meta holds the Relation ship between an attendee and which ticket it was created from.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $ticket_relation_meta_key = '_tec_tickets_commerce_ticket';

	/**
	 * Which meta holds the Relation ship between an attendee and which order it belongs to.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $order_relation_meta_key = '_tec_tickets_commerce_order';

	/**
	 * Which meta holds the purchaser name for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_name_meta_key = '_tec_tickets_commerce_purchaser_name';

	/**
	 * Which meta holds the purchaser email for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $purchaser_email_meta_key = '_tec_tickets_commerce_purchaser_email';

	/**
	 * Which meta holds the security code for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $security_code_meta_key = '_tec_tickets_commerce_security_code';

	/**
	 * Which meta holds the status value for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $status_meta_key = '_tec_tickets_commerce_status';

	/**
	 * Which meta holds the optout value for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $optout_meta_key = '_tec_tickets_commerce_optout';

	/**
	 * Which meta holds the checked in status for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $checked_in_meta_key = '_tec_tickets_commerce_checked_in';

	/**
	 * Which meta holds the checked in status for an attendee.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $deleted_ticket_meta_key = '_tribe_deleted_product_name';

	/**
	 * Indicates if a ticket for this attendee was sent out via email.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $ticket_sent_meta_key = '_tec_tickets_commerce_attendee_ticket_sent';

	/**
	 * Meta key holding an indication if this attendee was subscribed.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $subscribed_meta_key = '_tribe_tickets_subscribed';

	/**
	 * Meta key holding the first name for the attendee. (not purchaser)
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $first_name_meta_key = '_tec_tickets_commerce_first_name';

	/**
	 * Meta key holding the last name for the attendee. (not purchaser)
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $last_name_meta_key = '_tec_tickets_commerce_last_name';

	/**
	 * Meta key holding the email for the attendee. (not purchaser)
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $email_meta_key = '_tec_tickets_commerce_email';


	/**
	 * Register this Class post type into WP.
	 *
	 * @since 5.1.9
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
		 * @since 5.1.9
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
	 * @since 5.1.9
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
	 * @since 5.1.9
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
	 * @since 5.1.9
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
	 * @since 5.1.9
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
	 * Whether a specific attendee is valid toward inventory decrease or not.
	 *
	 * By default only attendees generated as part of a Completed order will count toward
	 * an inventory decrease but, if the option to reserve stock for Pending Orders is activated,
	 * then those attendees generated as part of a Pending Order will, for a limited time after the
	 * order creation, cause the inventory to be decreased.
	 *
	 * @todo  TribeCommerceLegacy: Move this method a Flag action.
	 *
	 * @since 5.1.9
	 *
	 * @param array $attendee
	 *
	 * @return bool
	 */
	public function decreases_inventory( $attendee ) {
		$order_status = \Tribe__Utils__Array::get( $attendee, 'order_status', 'undefined' );
		$order_id     = \Tribe__Utils__Array::get( $attendee, 'order_id', false );
		$attendee_id  = \Tribe__Utils__Array::get( $attendee, 'attendee_id', false );

		/**
		 * Whether the pending Order stock reserve logic should be ignored completely or not.
		 *
		 * If set to `true` then the behaviour chosen in the Settings will apply, if `false`
		 * only Completed tickets will count to decrease the inventory. This is useful when
		 *
		 * @todo  TribeCommerceLegacy: Move this method a Flag action.
		 *
		 * @since 5.1.9
		 *
		 * @param bool  $ignore_pending
		 * @param array $attendee An array of data defining the current Attendee
		 */
		$ignore_pending = apply_filters( 'tec_tickets_commerce_pending_stock_ignore', false );

		$purchase_time = false;
		$order         = false;

		if (
			'on-pending' === tribe_get_option( 'ticket-paypal-stock-handling', 'on-complete' )
			&& ! $ignore_pending
			&& Order_Statuses::$pending === $order_status
			&& false !== $order_id
		) {
			$purchase_time = \Tribe__Utils__Array::get( $attendee, 'purchase_time', false );

			$order = \Tribe__Tickets__Commerce__PayPal__Order::from_attendee_id(
				$attendee_id,
				[
					// Get no meta fields.
				]
			);

			if ( false !== $order ) {
				$purchase_time = $order->get_creation_date();
			}
		}

		if ( $purchase_time ) {
			$date = \Tribe__Date_Utils::build_date_object( $purchase_time );

			$date->setTimezone( new \DateTimeZone( 'UTC' ) );

			$order_creation_timestamp = $date->getTimestamp();

			/**
			 * Filters the amount of time a part of the stock will be reserved by a pending Order.
			 *
			 * The time applies from the Order creation time.
			 * In the unlikely scenario that an Order goes from Completed to Pending then, if the
			 * reservation time allows it, a part of the stock will be reserved for it.
			 *
			 * @since 4.7
			 *
			 * @param int                                      $pending_stock_reservation_time The amount of seconds, from the Order creation time,
			 *                                                                                 part of the stock will be reserved for the Order;
			 *                                                                                 defaults to 30 minutes.
			 * @param array                                    $attendee                       An array of data defining the current Attendee
			 * @param \Tribe__Tickets__Commerce__PayPal__Order $order                          The object representing the Order that generated
			 *                                                                                 the Attendee
			 */
			$pending_stock_reservation_time = (int) apply_filters( 'tec_tickets_commerce_pending_stock_reserve_time', 30 * 60, $attendee, $order );

			return time() <= ( $order_creation_timestamp + $pending_stock_reservation_time );
		}

		return Completed::SLUG === $order_status;
	}

	/**
	 * Get attendee data for attendee.
	 *
	 * @since 5.1.9
	 */
	public function get_attendee() {
		/**
		 * @todo Determine if this meta piece can be moved into the ET+ codebase.
		 */
		$meta = '';
		if ( class_exists( 'Tribe__Tickets_Plus__Meta', false ) ) {
			$meta = get_post_meta( $attendee->ID, \Tribe__Tickets_Plus__Meta::META_KEY, true );

			// Process Meta to include value, slug, and label
			if ( ! empty( $meta ) ) {
				$meta = tribe( Module::class )->process_attendee_meta( $attendee['product_id'], $meta );
			}
		}
	}
}