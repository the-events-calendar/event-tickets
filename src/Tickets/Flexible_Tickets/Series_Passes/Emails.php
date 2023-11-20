<?php
/**
 * Handles the emails integrations for the Series Passes.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes;
 */
namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Events\Custom_Tables\V1\Tables\Events;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use Tribe__Date_Utils as Dates;

/**
 * Class Emails.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Emails {
	/**
	 * Renders the series events permalink for the legacy ticket email.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The series post ID.
	 *
	 * @return void
	 */
	public function render_series_events_permalink_for_legacy_ticket_email( int $post_id ): void {
		?>
		<a href="<?php echo esc_url( get_post_permalink( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
			<?php echo esc_html( __( 'See all the events in this series.', 'event-tickets' ) ); ?>
		</a>
		<?php
	}

	/**
	 * Renders the series events permalink for the ticket email.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The series post ID.
	 *
	 * @return void
	 */
	public function render_series_events_permalink_for_ticket_emails( int $post_id ): void {
		?>
		<tr>
			<td class="tec-tickets__email-table-content__series-list">
				<p>
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( __( 'See all the events in this series.', 'event-tickets' ) ); ?>
					</a>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the series date range for the ticket email.
	 *
	 * @since TBD
	 *
	 * @param int $series_id The series ID.
	 *
	 * @return void
	 */
	public function render_series_events_date_range( int $series_id ) {
		$dates = [];
		/** @var Series_Relationship $series_relationship */
		$first_event = Series_Relationship::where( 'series_post_id', $series_id )
												  ->join( Events::table_name( true ), 'event_id', 'event_id' )
												  ->order_by( 'start_date' )
												  ->first();
		if ( $first_event !== null ) {
			$start_date = Dates::immutable( $first_event->start_date, $first_event->timezone );
			$format     = tribe_get_date_format( true );
			$dates[]    = esc_html( $start_date->format( $format ) );
		}

		$last_event = Series_Relationship::where( 'series_post_id', $series_id )
												  ->join( Events::table_name( true ), 'event_id', 'event_id' )
												  ->order_by( 'start_date', 'DESC' )
												  ->first();

		if ( $last_event !== null ) {
			$end_date = Dates::immutable( $last_event->start_date, $last_event->timezone );
			$format   = tribe_get_date_format( true );
			$dates[]  = esc_html( $end_date->format( $format ) );
		}

		if ( empty( $dates ) ) {
			return;
		}
		?>
		<tr>
			<td class="tec-tickets__email-table-content__series-date">
				<?php echo esc_html( implode( ' - ', $dates ) ); ?>
			</td>
		</tr>
		<?php
	}
}