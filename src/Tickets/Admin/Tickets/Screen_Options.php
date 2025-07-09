<?php
/**
 * Class the handles the All Tickets screen options.
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin\Tickets
 */

namespace TEC\Tickets\Admin\Tickets;

/**
 * Class Screen_Options
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin\Tickets
 */
class Screen_Options {
	/**
	 * @var string The user option that will store how many attendees should be shown per page.
	 */
	public static $per_page_user_option = 'event_tickets_admin_tickets_per_page';

	/**
	 * Initialize the screen options.
	 *
	 * @since 5.14.0
	 */
	public function init() {
		$this->add_hooks();
	}

	/**
	 * Adds Screen Option hooks.
	 *
	 * @since 5.14.0
	 */
	public function add_hooks() {
		add_filter( 'manage_' . Page::$hook_suffix . '_columns', [ $this, 'filter_manage_columns' ] );
		add_filter( 'screen_options_show_screen', [ $this, 'filter_screen_options_show_screen' ], 10, 2 );
		add_filter( 'default_hidden_columns', [ $this, 'filter_default_hidden_columns' ], 10, 2 );
	}

	/**
	 * Filters the screen options show screen.
	 *
	 * @since 5.14.0
	 * @since 5.18.1 Fixed show screen options logic.
	 *
	 * @param boolean   $show   Whether to show the screen options.
	 * @param WP_Screen $screen The current screen.
	 *
	 * @return boolean
	 */
	public function filter_screen_options_show_screen( $show, $screen ) {
		// Only show the screen options on the Tickets screen, otherwise bail.
		if ( empty( $screen ) || Page::$hook_suffix !== $screen->id ) {
			return $show;
		}

		/**
		 * Filter the screen options show screen.
		 *
		 * @since 5.14.0
		 *
		 * @param boolean   $show   Whether to show the screen options.
		 */
		return apply_filters( 'tec_tickets_admin_tickets_screen_options_show_screen', true );
	}

	/**
	 * Adds the "Columns" screen option by simply listing the column headers and titles.
	 *
	 * @since 5.14.0
	 *
	 * @return array
	 */
	public function filter_manage_columns() {
		return tribe( List_Table::class )->get_table_columns();
	}

	/**
	 * Filters the save operations of screen options to save the ones the class manages.
	 *
	 * @since 5.14.0
	 *
	 * @param bool   $status Whether the option should be saved or not.
	 * @param string $option The user option slug.
	 * @param mixed  $value  The user option value.
	 *
	 * @return bool|mixed Either `false` if the user option is not one managed by the class or the user
	 *                    option value to save.
	 */
	public function filter_set_screen_options( $status, $option, $value ) {
		if ( $option === self::$per_page_user_option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Filters the default hidden columns.
	 *
	 * @since 5.14.0
	 *
	 * @param array     $hidden_columns The hidden columns.
	 * @param WP_Screen $screen         The current screen.
	 *
	 * @return array
	 */
	public function filter_default_hidden_columns( $hidden_columns, $screen ) {
		if ( empty( $screen ) || Page::$hook_suffix !== $screen->id ) {
			return $hidden_columns;
		}

		return List_Table::get_default_hidden_columns();
	}
}
