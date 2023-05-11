<?php

namespace TEC\Tickets\Emails\JSON_LD;

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

	/**
	 * @inheritDoc
	 */
	public function build_data(): array {
		$data = [
			"@type" => self::get_type(),
		];

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [
			'event' => $this->event,
		];
	}
}