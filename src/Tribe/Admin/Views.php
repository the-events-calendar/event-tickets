<?php
/**
 * Class Tribe__Tickets__Admin__Views
 *
 * Hooks view links handler for supported post types edit pages.
 *
 * "Views" are the links on top of a WordPress admin post list.
 * This class does not contain the business logic, it only hooks the classes
 * that will handle the logic.
 *
 * @link https://make.wordpress.org/docs/plugin-developer-handbook/10-plugin-components/custom-list-table-columns/#views
 */
class Tribe__Tickets__Admin__Views {

	/**
	 * @var Tribe__Tickets__Admin__Views__Ticketed
	 */
	protected $ticketed;

	/**
	 * Adds the view links on supported post types admin  lists.
	 *
	 * @param array $supported_types A list of the post types that can have tickets.
	 */
	public function add_view_links( array $supported_types = array() ) {
		if ( empty( $supported_types ) ) {
			return true;
		}

		foreach ( $supported_types as $supported_type ) {
			$ticketed_view = new Tribe__Tickets__Admin__Views__Ticketed( $supported_type );
			add_filter( 'views_edit-' . $supported_type, array( $ticketed_view, 'filter_edit_link' ) );
		}

		return true;
	}

	/**
	 * Allows for a Ticket Admin Views include or Render
	 *
	 * @since  TBD
	 *
	 * @param  string $name    Which template we are dealing with
	 * @param  array  $context Context to e Extracted (some views depende on variables)
	 * @param  bool   $echo    Show print the tempalte or just return it
	 *
	 * @return string
	 */
	public function template( $name, $context = array(), $echo = true ) {
		$base = trailingslashit( Tribe__Tickets__Main::instance()->plugin_path ) . 'src/admin-views';
		$base = (array) explode( '/', $base );

		// If name is String make it an Array
		if ( is_string( $name ) ) {
			$name = (array) explode( '/', $name );
		}

		// Clean this Variable
		$name = array_map( 'sanitize_title_with_dashes', $name );

		// Apply the .php to the last item on the name
		$name[ count( $name ) - 1 ] .= '.php';

		// Build the File Path
		$file = implode( DIRECTORY_SEPARATOR, array_merge( (array) $base, $name ) );

		if ( ! file_exists( $file ) ) {
			return false;
		}

		ob_start();

		// Only do this if really needed (by default it wont)
		if ( ! empty( $context ) ) {
			// Make any provided variables available in the template variable scope
			extract( $context );
		}

		include $file;

		$html = ob_get_clean();

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	public function hook() {
		$this->add_view_links( (array) tribe_get_option( 'ticket-enabled-post-types', array() ) );
	}
}
