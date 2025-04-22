<?php
/**
 * Event Item object for Square synchronization.
 *
 * This class represents an Event as an Item in Square's catalog. It handles
 * the mapping between a WordPress Event post and its representation in Square.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;

/**
 * Class Event_Item
 *
 * Handles the representation of a WordPress Event as a Square catalog item.
 * Events in Square contain tickets as variations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class Event_Item extends Item {
	/**
	 * The type of Square catalog item this class represents.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const ITEM_TYPE = 'ITEM';

	// phpcs:disable Squiz.PHP.CommentedOutCode.Found

	/**
	 * The data structure for the Square catalog item.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $data = [
		'event_id'                 => null,
		'type'                     => self::ITEM_TYPE,
		'id'                       => null,
		'is_deleted'               => false,
		'present_at_all_locations' => false,
		'present_at_location_ids'  => [],
		// 'absent_at_location_ids'   => [],
		'item_data'                => [
			'name'                 => '', // max 512 characters
			// 'abbreviation'         => '', // max 24 chars only first 5 are visible in POS.
			// 'label_color'          => '',
			// 'is_taxable'           => true,
			// 'tax_ids'              => [],
			'variations'           => [],
			'product_type'         => 'EVENT',
			'skip_modifier_screen' => true,
			// 'categories'           => [],
			// 'description_html'     => '', // max 65535 characters
			// 'image_ids'            => [],
		],
	];

	// phpcs:enable Squiz.PHP.CommentedOutCode.Found

	/**
	 * The WordPress event post.
	 *
	 * @since TBD
	 *
	 * @var WP_Post|null
	 */
	protected ?WP_Post $event = null;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id The event post ID.
	 * @param array $tickets The tickets associated with this event.
	 */
	public function __construct( int $post_id, array $tickets = [] ) {
		$this->data['event_id'] = $post_id;
		$this->event            = get_post( $post_id );
		$this->set_tickets( $tickets );
		$this->register_hooks();
	}

	/**
	 * Get the WordPress ID of the event.
	 *
	 * @since TBD
	 *
	 * @return int The event post ID.
	 */
	public function get_wp_id(): int {
		return $this->data['event_id'];
	}

	/**
	 * Set the object values for synchronization with Square.
	 *
	 * @since TBD
	 *
	 * @return array The data array prepared for Square synchronization.
	 */
	protected function set_object_values(): array {
		$this->set( 'is_deleted', null === $this->event || $this->event->post_status === 'trash' );
		$this->set_item_data( 'name', $this->event->post_title ? $this->event->post_title : __( 'Untitled Event', 'event-tickets' ) );

		/**
		 * Filters the event item data before it is sent to Square.
		 *
		 * @since TBD
		 *
		 * @param array $data The event item data.
		 * @param WP_Post $event The event post object.
		 *
		 * @return array The filtered event item data.
		 */
		$data = (array) apply_filters( 'tec_tickets_commerce_square_event_item_data', $this->data, $this->event );

		unset( $data['event_id'] );

		return $data;
	}

	/**
	 * Set the tickets as variations of this event item.
	 *
	 * @since TBD
	 *
	 * @param array $tickets An array of Ticket_Object instances to set as variations.
	 *
	 * @return void
	 */
	public function set_tickets( array $tickets ): void {
		$this->data['item_data']['variations'] = array_map(
			static fn( Ticket_Object $ticket ) => new Ticket_Item( $ticket ),
			$tickets
		);
	}
}
