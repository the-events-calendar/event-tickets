<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\Email_Abstract;
use WP_Post;

/**
 * Class Event_Schema.
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
class Event_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	public static string $type = 'Event';

	/**
	 * The event object.
	 *
	 * @since 5.6.0
	 *
	 * @var WP_Post
	 */
	protected WP_Post $event;

	/**
	 * Build the schema object from an email.
	 *
	 * @since 5.6.0
	 *
	 * @param Email_Abstract $email The email instance.
	 *
	 * @return JSON_LD_Abstract The schema instance.
	 */
	public static function build_from_email( Email_Abstract $email ): JSON_LD_Abstract {
		// If this is a preview email, we need to use the preview schema.
		if ( $email->get( 'is_preview' ) ) {
			return Preview_Schema::build_from_email( $email );
		}

		$schema        = tribe( Event_Schema::class );
		$schema->event = get_post( $email->get( 'post_id' ) );

		return $schema->filter_schema_by_email( $email );
	}

	/**
	 * Build the schema object from an email and an event.
	 *
	 * @since 5.8.4
	 *
	 * @param Email_Abstract $email The email instance.
	 * @param int|null       $event The event post ID.
	 *
	 * @return JSON_LD_Abstract The schema instance.
	 */
	public static function build_from_email_and_post( Email_Abstract $email, ?int $event = null ): JSON_LD_Abstract {
		$schema        = tribe( Event_Schema::class );
		$schema->event = tribe_get_event( $event );

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