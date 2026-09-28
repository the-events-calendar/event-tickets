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

use DateTimeImmutable;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\StellarWP\Assets\Assets as Asset_Registry;
use TEC\Common\StellarWP\Assets\Config;
use Tribe__Tickets__Main as Tickets_Plugin;
use Tribe__Timezones as Timezones;
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
			->set_dependencies( 'jquery', 'event-tickets-admin-js', 'tribe-events-dynamic', 'tec-common-php-date-formatter' )
			->set_condition( fn(): bool => $this->is_event_edit_screen() )
			->add_localize_script( 'tec.tickets.relativeSaleDates.classicData', fn(): array => $this->get_classic_script_data() )
			->enqueue_on( 'admin_enqueue_scripts' )
			->in_footer()
			->register();
	}

	/**
	 * Gets the data the classic ticket editor script reads the event dates and writes the helper text with.
	 *
	 * @since TBD
	 *
	 * @return array{timeFormat: string, timezones: array<string,string>, allDay: array{start: string, end: string, endDays: int}, text: array{start: string, end: string, invalidWindow: string}} The script data.
	 */
	private function get_classic_script_data(): array {
		$time_format = get_option( 'time_format' );

		return [
			'timeFormat' => is_string( $time_format ) && '' !== $time_format ? $time_format : 'g:i a',
			'timezones'  => $this->get_manual_offset_zones(),
			'allDay'     => $this->get_all_day_times(),
			'text'       => [
				// Translators: %1$s is the date sales start on, %2$s the time.
				'start'         => __( 'Sales start %1$s at %2$s', 'event-tickets' ),
				// Translators: %1$s is the date sales end on, %2$s the time.
				'end'           => __( 'Sales end %1$s at %2$s', 'event-tickets' ),
				'invalidWindow' => __( 'Ticket sales cannot end before they start. Please adjust the sales window.', 'event-tickets' ),
			],
		];
	}

	/**
	 * Gets the zone the server resolves each manual UTC offset of the timezone field to.
	 *
	 * The server does not read a manual offset as a fixed one: it guesses a named zone, which may keep daylight saving
	 * time, so the browser needs the same zone to calculate the same dates.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The zone names, keyed by the offset option value.
	 */
	private function get_manual_offset_zones(): array {
		preg_match_all( '/value="(UTC[+-][^"]*)"/', wp_timezone_choice( '' ), $matches );

		$zones = [];
		foreach ( $matches[1] as $offset ) {
			$zones[ $offset ] = Timezones::build_timezone_object( $offset )->getName();
		}

		return $zones;
	}

	/**
	 * Gets the times TEC saves for an all-day event, which follow the multi-day cutoff setting.
	 *
	 * @since TBD
	 *
	 * @return array{start: string, end: string, endDays: int} The start and end times, `H:i:s`, and the days the end
	 *                                                         falls after the event's last day.
	 */
	private function get_all_day_times(): array {
		$day   = '2000-01-01';
		$start = new DateTimeImmutable( tribe_beginning_of_day( $day ) );
		$end   = new DateTimeImmutable( tribe_end_of_day( $day ) );

		return [
			'start'   => $start->format( 'H:i:s' ),
			'end'     => $end->format( 'H:i:s' ),
			'endDays' => ( new DateTimeImmutable( $day ) )->diff( $end->setTime( 0, 0 ) )->days,
		];
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
