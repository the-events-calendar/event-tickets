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
use TEC\Common\StellarWP\Assets\Assets as Asset_Registry;
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
		tec_asset(
			Tickets_Plugin::instance(),
			self::CLASSIC_SCRIPT,
			'RelativeSaleDates/classic.js',
			[ 'jquery' ],
			'admin_enqueue_scripts',
			[
				'group_path'   => Tickets_Plugin::class . '-packages',
				'conditionals' => fn(): bool => $this->is_classic_event_edit_screen(),
				'in_footer'    => true,
			]
		);
	}

	/**
	 * Returns whether the current admin screen edits an event in the classic editor, where the classic ticket form shows
	 * the sales window options: only for Tickets Commerce tickets, so only with Tickets Commerce on.
	 *
	 * The script bundles its own copy of moment-timezone, which would replace the one the block editor's dates rely on.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current screen is the classic event edit screen, with Tickets Commerce on.
	 */
	private function is_classic_event_edit_screen(): bool {
		return $this->is_event_edit_screen() && ! get_current_screen()->is_block_editor() && tec_tickets_commerce_is_enabled();
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
