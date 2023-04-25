<?php
namespace TEC\Tickets\Emails\JSON_LD;

/**
 * Abstract class for JSON LD schemas related to emails.
 *
 * @since TBD
 */
abstract class JSON_LD_Abstract {

	// @todo @rafsuntaskin Fill in later if needed.

	/**
	 * The type of the schema.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Get the data for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data for the schema.
	 */
	public function get_basic_data(): array {
		$data = [
			'@context' => 'https://schema.org',
			'@type'    => $this->type,
			'merchant' => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			],
		];

		return $data;
	}

	/**
	 * Get the JSON data for the schema.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data for the schema.
	 */
	abstract public function get_data() : array;
}