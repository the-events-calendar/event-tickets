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
			'ticket_name_note_default'  => sprintf(
			// Translators: %1$s: dynamic 'ticket' text.
				_x(
					'This is the name of your %1$s. It is displayed on the frontend of your website and within %1$s emails.',
					'admin edit ticket panel note',
					'event-tickets'
				),
				tribe_get_ticket_label_singular_lowercase( 'admin_edit_ticket_panel_note' )
			),
			'ticket_type_label_default' => _x( 'Type:', 'The label used in the ticket edit form for the type of the ticket.', 'event-tickets' ),
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
		$data = $this->data;

		// Filter before and during init, store and use the filtered data after init.
		if ( ! did_action( 'init' ) || doing_action( 'init' ) ) {
			/**
			 * Filter the data to be used in the editor.
			 *
			 * @since TBD
			 *
			 * @param array<string,string|int|float> $data The data to be used in the editor, in its HTML-escaped form.
			 */
			$data = apply_filters( 'tec_tickets_localized_editor_data', $data );
		}

		if ( ! is_array( $data ) ) {
			$data = $this->data;
		}

		// Store the filtered data.
		$this->data = $data;

		return $data;
	}
}
