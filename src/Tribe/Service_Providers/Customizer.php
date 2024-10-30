<?php
/**
 * Handles the customizer CSS overrides from TEC
 *
 * @since   4.12.3
 * @package Tribe\Tickets\Service_Providers
 */

namespace Tribe\Tickets\Service_Providers;

use Tribe__Customizer;
use Tribe__Utils__Color;

/**
 * Class Customizer.
 *
 * @since 4.12.3
 */
class Customizer extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.12.3
	 */
	public function register() {
		if ( ! class_exists( 'Tribe__Events__Main' ) ) {
			return;
		}
	}

	/**
	 * Handle accent color customizations for Event Tickets.
	 *
	 * @since 4.12.3
	 *
	 * @deprecated 5.5.0
	 *
	 * @param string $template The original CSS template.
	 *
	 * @return string $template The resulting CSS template.
	 */
	public function filter_accent_color_css( $template ) {
		_deprecated_function( __METHOD__, '5.5.0', 'Accent color is no longer available on Views V2.' );

		return $template;
	}
}
