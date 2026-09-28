<?php
/**
 * Registers the Relative Sale Dates scripts.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\StellarWP\Assets\Assets as Asset_Registry;
use TEC\Common\StellarWP\Assets\Config;
use Tribe__Tickets__Main as Tickets_Plugin;
use WP_Screen;

/**
 * Registers the script of the sales window options in the classic ticket editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Assets extends Controller_Contract {
	/**
	 * The handle of the classic ticket editor script.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const CLASSIC_SCRIPT = 'tec-tickets-relative-sale-dates-classic';

	/**
	 * The group path of the Relative Sale Dates built scripts.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const GROUP_PATH = 'tec-tickets-relative-sale-dates';

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		Asset_Registry::init()->remove( self::CLASSIC_SCRIPT );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		Config::add_group_path( self::GROUP_PATH, Tickets_Plugin::instance()->plugin_path . 'build/', 'RelativeSaleDates/' );

		Asset::add( self::CLASSIC_SCRIPT, 'classic.js', Tickets_Plugin::VERSION )
			->add_to_group_path( self::GROUP_PATH )
			->set_dependencies( 'jquery' )
			->set_condition( fn(): bool => $this->is_event_edit_screen() )
			->enqueue_on( 'admin_enqueue_scripts' )
			->in_footer()
			->register();
	}

	/**
	 * Returns whether the current admin screen edits an event, where the classic ticket form can show.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current screen is the event edit screen.
	 */
	private function is_event_edit_screen(): bool {
		$screen = get_current_screen();

		return $screen instanceof WP_Screen && 'post' === $screen->base && 'tribe_events' === $screen->post_type;
	}
}
