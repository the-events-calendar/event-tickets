<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Event_Schema.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
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
	 * Build the schema object from an email.
	 *
	 * @since TBD
	 *
	 * @param Email_Abstract $email The email instance.
	 *
	 * @return Event_Schema The schema instance.
	 */
	public static function build_from_email( Email_Abstract $email ): Event_Schema {
		$schema        = tribe( Event_Schema::class );
		$schema->event = get_post( $email->get( 'post_id' ) );

		return $schema->filter_schema_by_email( $email );
	}

	/**
	 * @inheritDoc
	 */
	public function build_data(): array {

		if ( ! tec_tickets_tec_events_is_active() || ! tribe_is_event( $this->event ) ) {
			return [];
		}

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