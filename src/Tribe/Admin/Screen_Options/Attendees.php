<?php


/**
 * Class Tribe__Tickets__Admin__Screen_Options__Attendees
 *
 * Handles the Attendees report table screen options.
 */
class Tribe__Tickets__Admin__Screen_Options__Attendees {

	/**
	 * @var string The screen id these screen options should render on.
	 */
	protected $screen_id;

	/**
	 * @var WP_Screen Either the globally defined WP_Screen instance or an injected dependency.
	 */
	protected $screen;

	/**
	 * Tribe__Tickets__Admin__Screen_Options__Attendees constructor.
	 *
	 * @param string         $screen_id The slug of the screen this screen options should apply to.
	 * @param WP_Screen|null $screen    An injectable instance of the WP_Screen object.
	 */
	public function __construct( $screen_id, $screen = null ) {
		$this->screen_id = $screen_id;
		$this->screen    = $screen;
	}

	/**
	 * Adds the screen options required on the current screen.
	 *
	 * @return bool Whether the screen options were added or not.
	 */
	public function add_options() {
		$this->screen = $this->screen ? $this->screen : get_current_screen();

		if ( ! is_object( $this->screen ) || $this->screen->id !== $this->screen_id ) {
			return false;
		}

		$this->add_column_headers_options();

		return true;
	}

	protected function add_column_headers_options() {
		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'filter_manage_columns' ) );
	}

	/**
	 * Adds the "Columns" screen option by simply listing the column headers and titles.
	 *
	 * @param array $columns The Attendee table columns and titles, def. empty array.
	 *
	 * @return array
	 */
	public function filter_manage_columns( array $columns ) {
		return Tribe__Tickets__Attendees_Table::get_table_columns();
	}
}