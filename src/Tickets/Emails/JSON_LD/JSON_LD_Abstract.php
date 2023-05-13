<?php
namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Emails\Email_Abstract;

/**
 * Abstract class for JSON LD schemas related to emails.
 *
 * @since TBD
 */
abstract class JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $type = 'Thing';

	/**
	 * Get the type of the schema.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return static::$type;
	}

	/**
	 * Get the data for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data for the schema.
	 */
	public function get_basic_data(): array {
		return [
			'@context' => 'https://schema.org',
			'@type'    => self::get_type(),
		];
	}

	/**
	 * Get the data for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data for the schema.
	 */
	public function get_merchant_data(): array {
		return [
			'merchant' => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			],
		];
	}

	/**
	 * Get the JSON data for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data for the schema.
	 */
	public function get_data(): array {
		$type = strtolower( static::$type );

		/**
		 * Filter the generated JSON-LD schema data.
		 *
		 * @since TBD
		 *
		 * @param array<string,mixed> $data The JSON-LD schema data.
		 * @param array<string,mixed> $args The arguments for the schema.
		 */
		return apply_filters( "tec_tickets_email_json_ld_{$type}_schema_data", $this->build_data(), $this->get_args() );
	}

	/**
	 * Filter the schema instance.
	 *
	 * @since TBD
	 *
	 * @return JSON_LD_Abstract The schema instance.
	 */
	public function filter_schema_instance( Email_Abstract $email ): JSON_LD_Abstract {
		/**
		 * Filter the JSON schema object for this email.
		 *
		 * @since TBD
		 *
		 * @param array $data The JSON data.
		 * @param \WP_Post $order The order object.
		 * @param Email_Abstract $email The email object.
		 */
		return apply_filters( "tec_tickets_emails_{$email->slug}_json_ld_schema", $this, $email );
	}

	/**
	 * Get JSON encode options.
	 *
	 * @since TBD
	 *
	 * @return int The JSON encode options.
	 */
	public function get_json_encode_options() {
		/**
		 * Allows the filtering of the JSON options for testing and development purposes of the JSON encode options for the
		 * view data JSON passed into the manager.
		 *
		 * @since TBD
		 *
		 * @param int $options The Options passed into the json_encode.
		 */
		return apply_filters( 'tec_tickets_emails_json_data_encode_options', 0 );
	}

	/**
	 * Build the data for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data for the schema.
	 */
	abstract public function build_data(): array;

	/**
	 * Get the arguments for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The arguments for the schema.
	 */
	abstract public function get_args(): array;
}