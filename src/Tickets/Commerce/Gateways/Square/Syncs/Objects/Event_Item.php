<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
class Event_Item extends Item {
	protected const ITEM_TYPE = 'ITEM';

	protected array $data = [
		'event_id'                 => null,
		'type'                     => self::ITEM_TYPE,
		'id'                       => null,
		'is_deleted'               => false,
		// 'present_at_all_locations' => true,
		// 'present_at_location_ids'  => [],
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

	protected ?WP_Post $event = null;

	public function __construct( int $post_id, array $tickets = [] ) {
		$this->data['event_id'] = $post_id;
		$this->event            = get_post( $post_id );
		$this->set_tickets( $tickets );
		$this->register_hooks();
	}

	public function get_wp_id(): int {
		return $this->data['event_id'];
	}

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

	public function set_tickets( array $tickets ): void {
		$this->data['item_data']['variations'] = array_map(
			static fn( Ticket_Object $ticket ) => new Ticket_Item( $ticket ),
			$tickets
		);
	}
}
