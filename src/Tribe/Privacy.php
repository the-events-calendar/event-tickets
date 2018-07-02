<?php

/**
 * Class Tribe__Tickets__Privacy
 */
class Tribe__Tickets__Privacy {

	/**
	 * Class initialization
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ), 20 );

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ), 10 );
	}

	/**
	 * Add the suggested privacy policy text to the policy postbox.
	 *
	 * @since TBD
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
	 * @since TBD
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
		 * @since TBD
		 *
		 * @param $content string The default policy content.
		 */
		return apply_filters( 'tribe_tickets_default_privacy_policy_content', $content );

	}

	/**
	 * Register exporter for Tickets attendees saved data.
	 *
	 * @since TBD
	 * @param $exporters
	 *
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => __( 'Event Ticket RSVP Atendee' ),
			'callback'               => array( $this, 'rsvp_exporter' ),
		);

		$exporters[] = array(
			'exporter_friendly_name' => __( 'Event Ticket TribeCommerce Atendee' ),
			'callback'               => array( $this, 'tpp_exporter' ),
		);

		return $exporters;
	}

	/**
	 * Exporter for Events Ticket RSVP Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     TBD
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
			'limit'          => $number,
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
	 * Exporter for Events Ticket Tribe Commerce Attendee
	 *
	 * @param     $email_address
	 * @param int $page
	 * @since     TBD
	 *
	 * @return array
	 */
	public function tpp_exporter( $email_address, $page = 1 ) {
		$number = 500; // Limit us to avoid timing out
		$page   = (int) $page;

		$export_items = array();

		// Get the tribe commerce attendees/orders
		$tpp_attendees = new WP_Query( array(
			'post_type'      => 'tribe_tpp_attendees',
			'meta_key'       => '_tribe_tpp_email',
			'meta_value'     => $email_address,
			'page'           => $page,
			'limit'          => $number,
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
}