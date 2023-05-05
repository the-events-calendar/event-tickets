<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\JSON_LD\JSON_LD_Abstract;

class Event_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $type = 'Event';

	/**
	 * The event object.
	 *
	 * @since TBD
	 *
	 * @var \WP_Post
	 */
	protected \WP_Post $event;

	/**
	 * Event_Schema constructor.
	 *
	 * @param \WP_Post $event The event object.
	 *
	 * @since TBD
	 */
	public function __construct( \WP_Post $event ) {
		$this->event = $event;
	}

	public function get_data(): array {
		$data = [
			"@type" => self::get_type(),
		];

		/**
		 * Filter the event data for the JSON-LD schema.
		 *
		 * @since TBD
		 *
		 * @param array $data The event data.
		 * @param \WP_Post $event The event object.
		 */
		return apply_filters( 'tec_tickets_email_json_ld_event_data', $data, $this->event );
	}
}