<?php
/**
 * Add theme compatibility things here.
 *
 * @since   4.11.4
 *
 * @since 5.3.1 Make use of Common Theme_Compatibility class.
 */
use Tribe\Utils\Body_Classes;
use Tribe\Utils\Theme_Compatibility as Compat;

class Tribe__Tickets__Theme_Compatibility extends Compat {

	/**
	 * Add the theme to the body class.
	 *
	 * @since 4.11.4
	 *
	 * @deprecated  5.3.1
	 *
	 * @param  array $classes List of body classes.
	 *
	 * @return array $classes List of body classes, modified if compatibility is required.
	 */
	public function filter_body_class( array $classes ) {
		_deprecated_function( __FUNCTION__, '5.3.1', 'Theme_Compatibility::add_body_classes()' );

		if ( ! static::is_compatibility_required() ) {
			return $classes;
		}

		return array_merge( $classes, static::get_compatibility_classes() );
	}

	/**
	 * Fetches the correct class strings for theme and child theme if available.
	 *
	 * @since 4.11.4
	 *
	 * @deprecated 5.3.1
	 *
	 * @return array $classes List of body classes with parent and child theme classes included.
	 */
	public function get_body_classes() {
		_deprecated_function( __FUNCTION__, '5.3.1', 'Tribe\Utils\Theme_Compatibility::get_compatibility_classes()' );
		return static::get_compatibility_classes();
	}

	/**
	 * Add body classes.
	 *
	 * @since 5.3.1
	 *
	 * @return array $classes List of body classes.
	 */
	public function add_body_classes( $classes ) {
		return array_merge( $classes, static::get_compatibility_classes() );
	}
}
