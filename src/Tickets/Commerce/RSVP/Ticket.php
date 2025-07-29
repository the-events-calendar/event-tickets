<?php
/**
 * Handles modifications to Ticket Objects for RSVP in Tickets Commerce.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP;

/**
 * Class Ticket.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */
class Ticket {

	/**
	 * Meta key that holds the "not going" option visibility status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $show_not_going = '_tribe_ticket_show_not_going';

	/**
	 * Filters RSVP ticket object to add "not going" option visibility.
	 *
	 * @since TBD
	 *
	 * @param object $return     The RSVP ticket object being filtered.
	 * @param int    $event_id   The ID of the event the ticket belongs to.
	 * @param int    $ticket_id  The ID of the RSVP ticket.
	 *
	 * @return object The modified RSVP ticket object.
	 */
	public function filter_rsvp( $return, $event_id, $ticket_id ) {
		if ( $return->type !== 'tc-rsvp' ) {
			return $return;
		}

		$return->show_not_going = get_post_meta( $ticket_id, $this->show_not_going, true );

		return $return;
	}

	/**
	 * Saves the "not going" option status for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id     The post ID of the RSVP ticket.
	 * @param object $ticket      The RSVP ticket object.
	 * @param array  $raw_data    Raw data from the form submission.
	 * @param string $ticket_class The class type of the ticket.
	 */
	public function save_rsvp( $post_id, $ticket, $raw_data, $ticket_class ) {
		if ( $ticket->type !== 'tc-rsvp' ) {
			return;
		}

		$show_not_going = 'no';

		if ( isset( $raw_data['tec_tickets_rsvp_enable_cannot_go'] ) ) {
			$show_not_going = $raw_data['tec_tickets_rsvp_enable_cannot_go'];
		}

		$show_not_going = tribe_is_truthy( $show_not_going ) ? 'yes' : 'no';
		update_post_meta( $ticket->ID, $this->show_not_going, $show_not_going );

		// Ensure rsvp_id is available for IAC and meta processing.
		// Use the ticket ID from the ticket object if rsvp_id is not in raw_data.
		if ( ! isset( $raw_data['rsvp_id'] ) || empty( $raw_data['rsvp_id'] ) ) {
			$raw_data['rsvp_id'] = $ticket->ID;
		}

		// Handle IAC (Individual Attendee Collection) settings.
		if ( isset( $raw_data['ticket_iac'] ) && ! empty( $raw_data['ticket_iac'] ) ) {
			$this->save_iac_settings( $ticket->ID, $raw_data['ticket_iac'] );
		}

		// Handle meta fields.
		if ( isset( $raw_data['meta_fields'] ) && is_array( $raw_data['meta_fields'] ) ) {
			$this->save_meta_fields( $ticket->ID, $raw_data['meta_fields'] );
		}
	}

	/**
	 * Saves IAC (Individual Attendee Collection) settings for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id The ID of the RSVP ticket.
	 * @param string $iac_setting The IAC setting (none, allowed, required).
	 */
	protected function save_iac_settings( $ticket_id, $iac_setting ) {
		// Validate IAC setting.
		$valid_settings = [ 'none', 'allowed', 'required' ];
		if ( ! in_array( $iac_setting, $valid_settings, true ) ) {
			return;
		}

		// Get IAC service from tickets-plus plugin.
		if ( ! class_exists( 'Tribe\Tickets\Plus\Attendee_Registration\IAC' ) ) {
			return;
		}

		$iac_service = tribe( 'tickets-plus.attendee-registration.iac' );
		if ( $iac_service ) {
			// Get the meta key and save directly to post meta.
			$meta_key = $iac_service->get_iac_setting_ticket_meta_key();
			update_post_meta( $ticket_id, $meta_key, sanitize_text_field( $iac_setting ) );
		}
	}

	/**
	 * Saves meta fields for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id The ID of the RSVP ticket.
	 * @param array $meta_fields Array of meta field definitions.
	 */
	protected function save_meta_fields( $ticket_id, $meta_fields ) {
		if ( empty( $meta_fields ) ) {
			return;
		}

		// Get meta service from tickets-plus plugin.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Meta' ) ) {
			return;
		}

		$meta_service = tribe( 'tickets-plus.meta' );
		if ( ! $meta_service ) {
			return;
		}

		// Enable meta for the ticket if we have fields.
		if ( ! empty( $meta_fields ) ) {
			update_post_meta( $ticket_id, \Tribe__Tickets_Plus__Meta::ENABLE_META_KEY, 'yes' );
		}

		// Create ticket object with ID.
		$ticket = (object) [ 'ID' => $ticket_id ];

		// Format meta fields for save_meta method - it expects 'tribe-tickets-input' key.
		$formatted_data = [
			'tribe-tickets-input' => $meta_fields,
		];

		// Call save_meta with correct parameters.
		$meta_service->save_meta( 0, $ticket, $formatted_data );
	}
}
