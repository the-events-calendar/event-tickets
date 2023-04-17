<?php

namespace TEC\Tickets\Emails\JSON_LD;

class Invoice_Schema extends JSON_LD_Abstract {

	/**
	 * The type of the schema.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $type = 'Invoice';

	public function get_data() {
		$data = [
			'@context' => 'https://schema.org',
			'@type'    => $this->type,
		];

		return $data;
	}
}