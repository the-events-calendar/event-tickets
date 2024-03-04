<?php
/**
 * Displays a list of the Series Upcoming Events.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Tribe\Events\Views\V2\Widgets\Widget_List;
use WP_Post;

/**
 * Class Upcoming_Series_Events_List.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
class Upcoming_Series_Events_List {
	/**
	 * The series ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $series_id;

	/**
	 * The event IDs.
	 *
	 * @since TBD
	 *
	 * @var int[]
	 */
	private array $event_ids = [];

	/**
	 * Upcoming_Series_Events_List constructor.
	 *
	 * @since TBD
	 *
	 * @param int $series_id The series ID.
	 */
	public function __construct(int $series_id){
		$this->series_id = $series_id;
	}


	public function render(): void {
		/**
		 * Filters the number of upcoming Events to show in the Series Pass Email Upcoming Events section.
		 * Returning an empty value, e.g. `0` or `false`, will never print the Upcoming Events section in the Email.
		 *
		 * @since TBD
		 *
		 * @param int $num_events_to_show The number of upcoming Events to show in the Series Pass Email Upcoming Events section.
		 * @param int $series_id The series the upcoming Events list is being printed for.
		 */
		$num_events_to_show = apply_filters(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events_list_count',
			5,
			$this->series_id
		);

		if ( empty( $num_events_to_show ) ) {
			// Do not print.
			return;
		}

		[ $event_ids, $found ] = $this->fetch_events( $num_events_to_show );

		if ( $found === 0 ) {
			// Nothing to print.
			return;
		}

		$widget_list = new Widget_List();

		$this->event_ids = $event_ids;

		add_filter( 'tribe_events_views_v2_view_widget-events-list_repository_args', [
			$this,
			'filter_repository_args'
		], 1000 );

		// @todo not picking up settings, why?
		$widget_list->setup( [
			"featured_events_only" => false,
			"jsonld_enable"        => false,
			"no_upcoming_events"   => false,
			"cost"                 => false,
			"venue"                => false,
			"street"               => false,
			"city"                 => false,
			"region"               => false,
			"zip"                  => false,
			"country"              => false,
			"phone"                => false,
			"organizer"            => false,
			"website"              => false,
		] );
		$widget_list->setup_view( [] );
		$html = $widget_list->get_html();

		?>

		<tr>
			<td class="tec-tickets__email-table-content-upcoming-events-list-container">
				<?php echo $html ?>
			</td>
		</tr>

		<?php
	}

	/**
	 * Fetches up to limit number of upcoming Events part of the Series the Email is being sent for.
	 *
	 * @since TBD
	 *
	 * @param int $limit The maximum number of upcoming Events to fetch.
	 *
	 * @return array{0: array<int>, 1: int} The fetched Event IDs, the number of total Events found.
	 */
	private function fetch_events( int $limit ): array {
		/**
		 * Filters the Upcoming Events for a Series in the context of a Series Email before the default logic runs.
		 * Returning a non-null value from this filter will override the default logic and return the fitered result.
		 *
		 * @since TBD
		 *
		 * @param array{0: array<int>, 1: int}|null $fetched The fetched Event IDs, the number of total Events found.
		 * @param int $series_id The series the upcoming Events list is being printed for.
		 * @param int $limit The limit to the number of Upcoming Events to fetch.
		 */
		$fetched = apply_filters(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events',
			null,
			$this->series_id,
			$limit
		);

		if ( $fetched !== null ) {
			return $fetched;
		}

		// @todo implement real logic.

		return [ [], 0 ];
	}

	/**
	 * Filters the Widget List View repository arguments to inject the Series Upcoming Events IDs.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The modified repository arguments.
	 */
	public function filter_repository_args(): array {
		remove_filter( current_filter(), [ $this, 'filter_repository_args' ], 1000 );

		return [
			'post_in' => $this->event_ids,
		];
	}
}