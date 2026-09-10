<?php
/**
 * Promoter Observer for Tickets Commerce.
 *
 * @since 5.3.2
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe\Tickets\Promoter\Triggers\Contracts\Attendee_Model;
use Tribe\Tickets\Promoter\Triggers\Models\Attendee;

/**
 * Class Promoter_Observer
 *
 * Handles Promoter triggers for Tickets Commerce attendees.
 *
 * @since 5.3.2
 */
class Promoter_Observer {

	/**
	 * @since 4.12.0
	 *
	 * @var \Tribe__Tickets__Promoter__Observer $observer ET Observer reference.
	 */
	private $observer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->observer = tribe( \Tribe__Tickets__Promoter__Observer::class );
		$this->hook();
	}

	/**
	 * Attach hooks for trigger messages.
	 *
	 * @since 5.3.2
	 * @since TBD Watch the RSVP status meta, so an Attendee changing their going/not-going answer
	 *            after the order is placed triggers too.
	 */
	public function hook() {

		add_action( 'tec_tickets_commerce_flag_action_generated_attendee', [ $this, 'attendee_created' ], 10, 5 );
		add_action( 'tec_tickets_commerce_ticket_deleted', tribe_callback( 'tickets.promoter.observer', 'notify_event_id' ), 10, 2 );
		add_action( 'event_tickets_checkin', [ $this, 'checkin' ], 10, 2 );
		add_action( 'updated_post_meta', [ $this, 'rsvp_status_updated' ], 10, 4 );
	}

	/**
	 * Action fired when a TC attendee is created.
	 *
	 * @since 5.3.2
	 * @since TBD Report TC-RSVP Attendees as an RSVP response rather than a purchase, so Promoter's
	 *            RSVP triggers match. Previously every generated Attendee reported `ticket_purchased`.
	 *
	 * @param \WP_Post $attendee Attendee object.
	 */
	public function attendee_created( \WP_Post $attendee ) {
		$this->trigger( $this->attendee_trigger_type( $attendee->ID ), $attendee->ID );
	}

	/**
	 * Responds to an Attendee changing their going/not-going answer after the fact, from the My
	 * Tickets page or the Attendees screen. That answer never touches the order, so the
	 * attendee-generated action cannot cover it.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id    ID of the updated metadata entry.
	 * @param int    $object_id  The post ID the meta belongs to.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The new meta value.
	 */
	public function rsvp_status_updated( $meta_id, $object_id, $meta_key, $meta_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( RSVP_V2_Constants::RSVP_STATUS_META_KEY !== $meta_key ) {
			return;
		}

		$this->trigger( tribe_is_truthy( $meta_value ) ? 'rsvp_going' : 'rsvp_not_going', (int) $object_id );
	}

	/**
	 * Returns the trigger type an Attendee's creation should report.
	 *
	 * TC-RSVP Attendees travel this same Tickets Commerce pipeline as purchases, so reporting every
	 * one of them as a purchase would leave Promoter's RSVP triggers permanently unmatched. The
	 * presence of the RSVP status meta is what separates the two throughout Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The ID of the Attendee.
	 *
	 * @return string The trigger type.
	 */
	private function attendee_trigger_type( int $attendee_id ): string {
		if ( ! metadata_exists( 'post', $attendee_id, RSVP_V2_Constants::RSVP_STATUS_META_KEY ) ) {
			return 'ticket_purchased';
		}

		return tribe_is_truthy( get_post_meta( $attendee_id, RSVP_V2_Constants::RSVP_STATUS_META_KEY, true ) )
			? 'rsvp_going'
			: 'rsvp_not_going';
	}

	/**
	 * Responds to a checkin action.
	 *
	 * @since 5.3.2
	 *
	 * @param int       $attendee_id The ID of the attendee utilized.
	 * @param bool|null $qr          Whether it's from a QR scan.
	 */
	public function checkin( $attendee_id, $qr ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->trigger( 'checkin', $attendee_id );
	}

	/**
	 * Fire a trigger action using Tickets Commerce as main source of the ticket data.
	 *
	 * @since 5.3.2
	 *
	 * @param string $type        The trigger type.
	 * @param int    $attendee_id The ID of the attendee utilized.
	 */
	private function trigger( $type, $attendee_id ) {

		$attendee = tec_tc_get_attendee( $attendee_id );
		$ticket   = tribe( Module::class );
		$attendee = new Attendee( (array) $attendee );

		/**
		 * Create a new action to listen for a trigger associated with an attendee.
		 *
		 * @since 5.3.2
		 *
		 * @param string                  $type     The type of trigger fired.
		 * @param Attendee_Model          $attendee The attendee associated with the trigger.
		 * @param Tribe__Tickets__Tickets $ticket   The ticket where the attendee was created.
		 */
		do_action( 'tribe_tickets_promoter_trigger_attendee', $type, $attendee, $ticket );
	}
}
