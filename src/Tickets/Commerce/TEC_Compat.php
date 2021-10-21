<?php
/**
 * Handles registering and setup for Tickets Commerce compatibility with The Events Calendar.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

/**
 * Class TEC_Compat.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
class TEC_Compat extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->add_filters();
	}

	/**
	 * Adds the filters required to handle legacy compatibility.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'wp_redirect', [ $this, 'prevent_filter_redirect_canonical' ], 10, -1 );
	}

	/**
	 * In cases where ET is running alongside TEC and the home page is set to be the Events page, this
	 * redirect will trigger a hook in TEC that was designed to prevent funky page loads out of context.
	 * We don't need those checks to run when redirecting to the Cart page in Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param string $location the URL we're redirecting to.
	 * @param int $status The redirect status code.
	 *
	 * @return string
	 */
	public function prevent_filter_redirect_canonical( $location, $status ) {

		if ( 302 !== $status || false === strpos( $location, 'tec-tc-cookie=' ) ) {
			return $location;
		}

		if ( is_plugin_active( 'the-events-calendar' ) ) {
			remove_filter( 'wp_redirect', 'Tribe\Events\Views\V2\Hooks\filter_redirect_canonical' );
		}

		return $location;
	}
}