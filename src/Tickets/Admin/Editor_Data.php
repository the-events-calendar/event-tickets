<?php
/**
 * A class to store and provided logic less data for the admin editors.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin;
 */

namespace TEC\Tickets\Admin;

/**
 * Class Editor_Data.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin;
 */
class Editor_Data {

	/**
	 * ${CARET}
	 *
	 * @since TBD
	 *
	 * @var array<string,string|int|float>
	 */
	private array $data = [];

	/**
	 * Editor_Data constructor.
	 */
	public function __construct() {
		$this->data = [
			'ticket_name_label_default' => _x( 'Type:', 'The label used in the ticket edit form for the name of the ticket.', 'event-tickets' ),
		];
	}

	/**
	 * Returns the data in its HTML-escaped form.
	 *
	 * @since TBD
	 *
	 * @return array<string,string|int|float> The data in its HTML-escaped form.
	 */
	public function get_html_escaped_data(): array {
		return array_map( 'esc_html', $this->get_raw_data() );
	}

	/**
	 * Returns the data entry for the specified key in its unescaped form.
	 *
	 * @since TBD
	 *
	 * @param string $key The key to fetch the data for.
	 *
	 * @return int|string|float|null The data entry for the specified key, or null if not found.
	 */
	public function get_raw_data_entry( string $key ) {
		return $this->get_raw_data()[ $key ] ?? null;
	}

	/**
	 * Returns the data in its unescaped form..
	 *
	 * @since TBD
	 *
	 * @return array<string,string|int|float> The data in its unescaped form.
	 */
	private function get_raw_data(): array {
		return $this->data;
	}

	/**
	 * Adds a data entry to the data.
	 *
	 * @since TBD
	 *
	 * @param string $key   The key to add the data entry for.
	 * @param mixed  $value The value to add to the data entry.
	 *
	 * @return void The data entry is added to the data.
	 */
	public function add_data_entry( string $key, $value ): void {
		$this->data[ $key ] = $value;
	}
}