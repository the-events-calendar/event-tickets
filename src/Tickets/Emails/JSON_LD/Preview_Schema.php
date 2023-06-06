<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Preview_Schema.
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
class Preview_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since 5.6.0
	 *
	 * @var string
	 */
	public static string $type = 'PreviewThing';

	/**
	 * Build the schema object from an email.
	 *
	 * @since 5.6.0
	 *
	 * @param Email_Abstract $email The email instance.
	 *
	 * @return Preview_Schema The schema instance.
	 */
	public static function build_from_email( Email_Abstract $email ): Preview_Schema {
		$schema        = tribe( Preview_Schema::class );
		return $schema->filter_schema_by_email( $email );
	}

	/**
	 * @inheritDoc
	 */
	public function build_data(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function get_args(): array {
		return [];
	}
}