<?php

/**
 * Class Tribe__Tickets__Privacy
 */
class Tribe__Tickets__Privacy {

	/**
	 * Class initialization
	 *
	 * @since 4.7.5
	 */
	public function hook() {
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ), 20 );

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ), 10 );
	}

	/**
	 * Add the suggested privacy policy text to the policy postbox.
	 *
	 * @since 4.7.5
	 */
	public function privacy_policy_content() {

		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return false;
		}

		$content = $this->default_privacy_policy_content();
		wp_add_privacy_policy_content( __( 'Event Tickets', 'event-tickets' ), $content );
	}

	/**
	 * Return the default suggested privacy policy content.
	 *
	 * @param bool $descr Whether to include the descriptions under the section headings. Default false.
	 *
	 * @since 4.7.5
	 *
	 * @return string The default policy content.
	 */
	public function default_privacy_policy_content() {

		ob_start();
		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/privacy.php';
		$content = ob_get_clean();

		/**
		 * Filters the default content suggested for inclusion in a privacy policy.
		 *
		 * @since 4.7.5
		 *
		 * @param $content string The default policy content.
		 */
		return apply_filters( 'tribe_tickets_default_privacy_policy_content', $content );

	}

	/**
	 * Register exporter for Tickets attendees saved data.
	 *
	 * @since 4.7.5
	 * @param $exporters
	 *
	 * @return array
	 */
	public function register_exporters( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => __( 'Event Tickets RSVP Attendee', 'event-tickets' ),
			'callback'               => array( $this, 'rsvp_exporter' ),
		);

		$exporters[] = array(
			'exporter_friendly_name' => __( 'Event Tickets TribeCommerce Attendee', 'event-tickets' ),
			'callback'               => array( $this, 'tpp_attendee_exporter' ),
		);

		$exporters[] = array(
			'exporter_friendly_name' => __( 'Event Tickets TribeCommerce Order', 'event-tickets' ),
			'callback'               => array( $this, 'tpp_order_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Register erasers for Tickets attendees saved data.
	 *
	 * @since 4.7.6
	 * @param $erasers
	 *
	 * @return array
	 */
	public function register_erasers( $erasers ) {
		$erasers[] = array(
			'eraser_friendly_name' => __( 'Event Tickets RSVP Attendee', 'event-tickets' ),
			'callback'             => array( $this, 'rsvp_eraser' ),
		);

		$erasers[] = array(
			'eraser_friendly_name' => __( 'Event Tickets TribeCommerce Attendee', 'event-tickets' ),
			'callback'             => array( $this, 'tpp_attendee_eraser' ),
		);

		$erasers[] = array(
			'eraser_friendly_name' => __( 'Event Tickets TribeCommerce Order', 'event-tickets' ),
			'callback'             => array( $this, 'tpp_order_eraser' ),
		);

		return $erasers;
	}

	/**
	 * Exporter for Events Ticket RSVP Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.5
	 *
	 * @return array
	 */
	public function rsvp_exporter( $email_address, $page = 1 ) {
		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		$export_items = array();

		// Get the attendees RSVPs for the given email.
		$rsvp_attendees = new WP_Query( array(
			'post_type'      => Tribe__Tickets__RSVP::ATTENDEE_OBJECT,
			'meta_key'       => '_tribe_rsvp_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $rsvp_attendees->posts as $attendee ) {

			$item_id = "tribe_rsvp_attendees-{$attendee->ID}";

			// Set our own group for RSVP attendees
			$group_id = 'rsvp-attendees';

			// Set a label for the group
			$group_label = __( 'Event Tickets RSVP Attendee Data', 'event-tickets' );

			$data = array();

			$data[] = array(
				'name'  => __( 'RSVP Title', 'event-tickets' ),
				'value' => get_the_title( $attendee->ID ),
			);

			$data[] = array(
				'name'  => __( 'Full Name', 'event-tickets' ),
				'value' => get_post_meta( $attendee->ID, '_tribe_rsvp_full_name', true ),
			);

			$data[] = array(
				'name'  => __( 'Email', 'event-tickets' ),
				'value' => get_post_meta( $attendee->ID, '_tribe_rsvp_email', true ),
			);

			$data[] = array(
				'name'  => __( 'Date', 'event-tickets' ),
				'value' => $attendee->post_date,
			);

			/**
			 * Allow filtering for the rsvp attendee data export.
			 *
			 * @since 4.7.6
			 * @param array  $data      The data array to export
			 * @param object $attendee  The attendee object
			 */
			$data = apply_filters( 'tribe_tickets_personal_data_export_rsvp', $data, $attendee );

			$export_items[] = array(
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $item_id,
				'data'        => $data,
			);
		}

		// Tell core if we have more comments to work on still
		$done = count( $rsvp_attendees->posts ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Eraser for Events Ticket RSVP Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.6
	 *
	 * @return array
	 */
	public function rsvp_eraser( $email_address, $page = 1 ) {
		if ( empty( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$messages       = array();
		$items_removed  = false;
		$items_retained = false;

		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		// Get the attendees RSVPs for the given email.
		$rsvp_attendees = new WP_Query( array(
			'post_type'      => Tribe__Tickets__RSVP::ATTENDEE_OBJECT,
			'meta_key'       => '_tribe_rsvp_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $rsvp_attendees->posts as $rsvp ) {

			$event_id = get_post_meta( $rsvp->ID, Tribe__Tickets__RSVP::ATTENDEE_EVENT_KEY, true );
			$deleted  = wp_delete_post( $rsvp->ID );

			if ( $deleted ) {
				$items_removed = true;
				if ( $event_id ) {
					Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
				}
			} else {
				$items_retained = true;
				$messages[]     = __( 'RSVP information was not removed. A database error may have occurred during deletion.', 'event-tickets' );
			}
		}

		// Tell core if we have more elements to work on still
		$done = count( $rsvp_attendees->posts ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * Eraser for Events Ticket Tribe Commerce Attendees
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.6
	 *
	 * @return array
	 */
	public function tpp_attendee_eraser( $email_address, $page = 1 ) {
		if ( empty( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$messages       = array();
		$items_removed  = false;
		$items_retained = false;

		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		// Get the tribe commerce attendees/orders
		$tpp_attendees = new WP_Query( array(
			'post_type'      => 'tribe_tpp_attendees',
			'meta_key'       => '_tribe_tpp_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $tpp_attendees->posts as $attendee ) {

			$event_id = get_post_meta( $attendee->ID, Tribe__Tickets__RSVP::ATTENDEE_EVENT_KEY, true );
			$deleted  = wp_delete_post( $attendee->ID );

			if ( $deleted ) {
				$items_removed = true;
				if ( $event_id ) {
					Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
				}
			} else {
				$items_retained = true;
				$messages[]     = __( 'TribeCommerce attendee information was not removed. A database error may have occurred during deletion.', 'event-tickets' );
			}
		}

		// Tell core if we have more elements to work on still
		$done = count( $tpp_attendees->posts ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * Eraser for Events Ticket Tribe Commerce Order
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.6
	 *
	 * @return array
	 */
	public function tpp_order_eraser( $email_address, $page = 1 ) {
		if ( empty( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$messages       = array();
		$items_removed  = false;
		$items_retained = false;

		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		// Get the tribe commerce orders
		$tpp_orders = new WP_Query( array(
			'post_type'      => 'tribe_tpp_orders',
			'meta_key'       => '_tribe_paypal_payer_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $tpp_orders->posts as $order ) {

			// Get the order
			$tpp_order = Tribe__Tickets__Commerce__PayPal__Order::from_order_id( $order->ID, true );
			$event_id  = get_post_meta( $order->ID, '_tribe_paypal_post', true );

			// Delete the order (with attendees, because the user who did the order inserted these values)
			$deleted = $tpp_order->delete();

			if ( $deleted ) {
				$items_removed = true;
				if ( $event_id ) {
					// Delete the transient so the site admin see the list updated
					Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
				}
			} else {
				$items_retained = true;
				$messages[]     = __( 'TribeCommerce order information was not removed. A database error may have occurred during deletion.', 'event-tickets' );
			}
		}

		// Tell core if we have more elements to work on still
		$done = count( $tpp_orders->posts ) < $number;

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	/**
	 * Exporter for Events Ticket Tribe Commerce Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.5
	 *
	 * @return array
	 */
	public function tpp_attendee_exporter( $email_address, $page = 1 ) {
		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		$export_items = array();

		// Get the tribe commerce attendees/orders
		$tpp_attendees = new WP_Query( array(
			'post_type'      => 'tribe_tpp_attendees',
			'meta_key'       => '_tribe_tpp_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $tpp_attendees->posts as $attendee ) {

			$item_id = "tribe_tpp_attendees-{$attendee->ID}";

			// Set our own group for Tribe Commerce attendees
			$group_id = 'tpp-attendees';

			// Set a label for the group
			$group_label = __( 'Event Tickets TribeCommerce Attendee Data', 'event-tickets' );

			$data = array();

			$data[] = array(
				'name'  => __( 'Order Title', 'event-tickets' ),
				'value' => get_the_title( $attendee->ID ),
			);

			$data[] = array(
				'name'  => __( 'Full Name', 'event-tickets' ),
				'value' => get_post_meta( $attendee->ID, '_tribe_tpp_full_name', true ),
			);

			$data[] = array(
				'name'  => __( 'Email', 'event-tickets' ),
				'value' => get_post_meta( $attendee->ID, '_tribe_tpp_email', true ),
			);

			$data[] = array(
				'name'  => __( 'Date', 'event-tickets' ),
				'value' => $attendee->post_date,
			);

			/**
			 * Allow filtering for the tribecommerce attendee data export.
			 *
			 * @since 4.7.6
			 * @param array  $data      The data array to export
			 * @param object $attendee  The attendee object
			 */
			$data = apply_filters( 'tribe_tickets_personal_data_export_tpp', $data, $attendee );

			$export_items[] = array(
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $item_id,
				'data'        => $data,
			);
		}

		// Tell core if we have more comments to work on still
		$done = count( $tpp_attendees->posts ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Exporter for Events Ticket Tribe Commerce Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     4.7.6
	 *
	 * @return array
	 */
	public function tpp_order_exporter( $email_address, $page = 1 ) {
		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		$export_items = array();

		// Get the tribe commerce orders
		$tpp_orders = new WP_Query( array(
			'post_type'      => 'tribe_tpp_orders',
			'meta_key'       => '_tribe_paypal_payer_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'posts_per_page' => $number,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $tpp_orders->posts as $order ) {

			$item_id = "order-{$order->ID}";

			// Set our own group for Tribe Commerce orders
			$group_id = 'tpp-orders';

			// Set a label for the group
			$group_label = __( 'Event Tickets TribeCommerce Order Data', 'event-tickets' );

			$data = array();

			$data[] = array(
				'name'  => __( 'Order Number', 'event-tickets' ),
				'value' => $order->ID,
			);

			$data[] = array(
				'name'  => __( 'Order Total', 'event-tickets' ),
				'value' => get_post_meta( $order->ID, '_tribe_paypal_mc_gross', true ),
			);

			$meta     = get_post_meta( $order->ID, '_paypal_hashed_meta', true );
			$address  = isset( $meta['address_name'] ) ? $meta['address_name'] : '';
			$address .= isset( $meta['address_street'] ) ? ', ' . $meta['address_street'] : '';
			$address .= isset( $meta['address_city'] ) ? ', ' . $meta['address_city'] : '';
			$address .= isset( $meta['address_zip'] ) ? ', ' . $meta['address_zip'] : '';
			$address .= isset( $meta['address_country'] ) ? ', ' . $meta['address_country'] : '';

			$data[] = array(
				'name'  => __( 'Billing Address', 'event-tickets' ),
				'value' => $address,
			);

			$data[] = array(
				'name'  => __( 'Email', 'event-tickets' ),
				'value' => get_post_meta( $order->ID, '_tribe_paypal_payer_email', true ),
			);

			$data[] = array(
				'name'  => __( 'Date', 'event-tickets' ),
				'value' => get_post_meta( $order->ID, '_tribe_paypal_payment_date', true ),
			);

			/**
			 * Allow filtering for the tribecommerce order data export.
			 *
			 * @since 4.7.6
			 * @param array  $data   The data array to export
			 * @param object $order  The order object
			 */
			$data = apply_filters( 'tribe_tickets_personal_data_export_tpp_order', $data, $order );

			$export_items[] = array(
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $item_id,
				'data'        => $data,
			);
		}

		// Tell core if we have more orders to work on still
		$done = count( $tpp_orders->posts ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

}