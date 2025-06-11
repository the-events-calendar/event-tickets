<?php
/**
 * Creates an Event mock for the purpose of the Series Pass Email preview.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes\Emails;

use DateTimeImmutable;
use DateTimeZone;
use Tribe__Date_Utils as Dates;
use Tribe__Events__Main as TEC;
use Tribe__Timezones as Timezones;
use WP_Post;

/**
 * Class Mock_Event_Post.
 *
 * @since 5.8.4
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Mock_Event_Post {
	use Mock_Preview_Post;

	/**
	 * The mock Event start date.
	 *
	 * @since 5.8.4
	 *
	 * @var DateTimeImmutable
	 */
	private DateTimeImmutable $start;

	/**
	 * The mock Event end date.
	 *
	 * @since 5.8.4
	 *
	 * @var DateTimeImmutable
	 */
	private DateTimeImmutable $end;

	/**
	 * The mock Event title.
	 *
	 * @since 5.8.4
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * Returns the mock Event post data and meta.
	 *
	 * @since 5.8.4
	 *
	 * @return WP_Post[] The mock Events posts used in the Series Pass Email preview.
	 */
	public static function get_preview_events(): array {
		$year = date( 'Y' );

		return [
			( new Mock_Event_Post(
				"{$year}-10-20 7pm",
				"{$year}-10-20, 9pm",
				_x( 'Jaws', 'Series Pass Email preview event title', 'event-tickets' ) )
			)->get_post()->ID,
			( new Mock_Event_Post(
				"{$year}-10-22 7pm",
				"{$year}-10-22 9pm",
				_x( 'Rashomon', 'Series Pass Email preview event title', 'event-tickets' ) )
			)->get_post()->ID,
			( new Mock_Event_Post(
				"{$year}-10-28 2pm",
				"{$year}-10-28 4pm",
				_x( 'Rear Window', 'Series Pass Email preview event title', 'event-tickets' ) )
			)->get_post()->ID,
			( new Mock_Event_Post(
				"{$year}-10-30 7pm",
				"{$year}-10-30 9pm",
				_x( 'Indiana Jones and the Last Crusade', 'Series Pass Email preview event title', 'event-tickets' ) )
			)->get_post()->ID,
			( new Mock_Event_Post(
				"{$year}-11-02 7pm",
				"{$year}-11-02 9pm",
				_x( 'Mulholland Drive', 'Series Pass Email preview event title', 'event-tickets' ) )
			)->get_post()->ID,
		];
	}

	/**
	 * Mock_Event_Post constructor.
	 *
	 * @since 5.8.4
	 *
	 * @param string $start The Event start date in a `strtotime`-compatible format.
	 * @param string $end   The Event end date in a `strtotime`-compatible format.
	 * @param string $title The Event title.
	 */
	public function __construct( string $start, string $end, string $title ) {
		$this->start = Dates::immutable( $start );
		$this->end   = Dates::immutable( $end );
		$this->title = $title;
	}

	/**
	 * Builds the Series mock post data.
	 *
	 * @since 5.8.4
	 *
	 * @param int $mock_post_id The mock post ID.
	 *
	 * @return array{0: array<string,mixed>, 1: array<string,array<mixed>>} The mock post data and meta, respectively.
	 */
	private function get_post_data( int $mock_post_id ): array {
		$utc       = new DateTimeZone( 'UTC' );
		$post_meta = [
			'_EventStartDate'    => [ $this->start->format( Dates::DBDATETIMEFORMAT ) ],
			'_EventEndDate'      => [ $this->end->format( Dates::DBDATETIMEFORMAT ) ],
			'_EventStartDateUTC' => [ $this->start->setTimezone( $utc )->format( Dates::DBDATETIMEFORMAT ) ],
			'_EventEndDateUTC'   => [ $this->end->setTimezone( $utc )->format( Dates::DBDATETIMEFORMAT ) ],
			'_EventTimezone'     => [ Timezones::build_timezone_object()->getName() ],
		];

		$post_data = [
			'ID'             => $mock_post_id,
			'post_author'    => get_current_user_id(),
			'post_date'      => '2023-04-17 17:06:56',
			'post_date_gmt'  => '2023-04-17 17:06:56',
			'post_title'     => $this->title,
			'post_status'    => 'publish',
			'post_permalink' => '#',
			'post_name'      => "preview-series-{$mock_post_id}",
			'post_type'      => TEC::POSTTYPE,
			'filter'         => 'raw',
		];

		return [ $post_data, $post_meta ];
	}
}
