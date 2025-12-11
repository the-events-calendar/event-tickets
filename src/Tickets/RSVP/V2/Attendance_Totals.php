<?php
/**
 * Handles RSVP V2 attendance totals display.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Commerce\Attendee as TC_Attendee;

/**
 * Class Attendance_Totals.
 *
 * Provides attendance totals for RSVP tickets (going vs not going).
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Attendance_Totals {

	/**
	 * Gets the total number of "going" attendees for a post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int The count of going attendees.
	 */
	public function get_total_going( int $post_id ): int {
		$count = $this->get_attendee_count_by_status( $post_id, Meta::STATUS_GOING );

		/**
		 * Filters the total going count for RSVP.
		 *
		 * V1 backwards compatibility filter.
		 *
		 * @since 4.10.9
		 *
		 * @param int $count   The going count.
		 * @param int $count   The going count (duplicate for BC).
		 * @param int $post_id The post ID.
		 */
		$count = (int) apply_filters( 'tribe_tickets_rsvp_get_total_going', $count, $count, $post_id );

		/**
		 * Filters the total going count for RSVP V2.
		 *
		 * @since TBD
		 *
		 * @param int $count   The going count.
		 * @param int $post_id The post ID.
		 */
		return (int) apply_filters( 'tec_tickets_rsvp_v2_get_total_going', $count, $post_id );
	}

	/**
	 * Gets the total number of "not going" attendees for a post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int The count of not going attendees.
	 */
	public function get_total_not_going( int $post_id ): int {
		$count = $this->get_attendee_count_by_status( $post_id, Meta::STATUS_NOT_GOING );

		/**
		 * Filters the total not going count for RSVP.
		 *
		 * V1 backwards compatibility filter.
		 *
		 * @since 4.10.9
		 *
		 * @param int $count   The not going count.
		 * @param int $count   The not going count (duplicate for BC).
		 * @param int $post_id The post ID.
		 */
		$count = (int) apply_filters( 'tribe_tickets_rsvp_get_total_not_going', $count, $count, $post_id );

		/**
		 * Filters the total not going count for RSVP V2.
		 *
		 * @since TBD
		 *
		 * @param int $count   The not going count.
		 * @param int $post_id The post ID.
		 */
		return (int) apply_filters( 'tec_tickets_rsvp_v2_get_total_not_going', $count, $post_id );
	}

	/**
	 * Gets the total number of all RSVP attendees for a post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int The total count of all RSVP attendees.
	 */
	public function get_total_rsvps( int $post_id ): int {
		return $this->get_total_going( $post_id ) + $this->get_total_not_going( $post_id );
	}

	/**
	 * Renders the attendance totals on the attendees screen.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function render_totals( int $post_id ): void {
		$going     = $this->get_total_going( $post_id );
		$not_going = $this->get_total_not_going( $post_id );
		$total     = $going + $not_going;

		// Don't render if there are no RSVP attendees.
		if ( 0 === $total ) {
			return;
		}

		$html = $this->get_totals_html( $going, $not_going, $post_id );

		/**
		 * Filters the attendance totals HTML output.
		 *
		 * V1 backwards compatibility filter.
		 *
		 * @since 4.10.9
		 *
		 * @param string $html The HTML output.
		 */
		$html = apply_filters( 'tribe_tickets_rsvp_print_totals_html', $html );

		/**
		 * Filters the attendance totals HTML output for RSVP V2.
		 *
		 * @since TBD
		 *
		 * @param string $html      The HTML output.
		 * @param int    $going     The going count.
		 * @param int    $not_going The not going count.
		 * @param int    $post_id   The post ID.
		 */
		$html = apply_filters( 'tec_tickets_rsvp_v2_print_totals_html', $html, $going, $not_going, $post_id );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Gets the attendance totals HTML.
	 *
	 * @since TBD
	 *
	 * @param int $going     The going count.
	 * @param int $not_going The not going count.
	 * @param int $post_id   The post ID.
	 *
	 * @return string The HTML output.
	 */
	protected function get_totals_html( int $going, int $not_going, int $post_id ): string {
		ob_start();
		?>
		<div class="tribe-tickets-rsvp-v2-totals">
			<h4 class="tribe-tickets-rsvp-v2-totals__title">
		<?php esc_html_e( 'RSVP Totals', 'event-tickets' ); ?>
			</h4>
			<ul class="tribe-tickets-rsvp-v2-totals__list">
				<li class="tribe-tickets-rsvp-v2-totals__item tribe-tickets-rsvp-v2-totals__item--going">
					<span class="tribe-tickets-rsvp-v2-totals__label">
		<?php esc_html_e( 'Going:', 'event-tickets' ); ?>
					</span>
					<span class="tribe-tickets-rsvp-v2-totals__count">
		<?php echo esc_html( number_format_i18n( $going ) ); ?>
					</span>
				</li>
				<li class="tribe-tickets-rsvp-v2-totals__item tribe-tickets-rsvp-v2-totals__item--not-going">
					<span class="tribe-tickets-rsvp-v2-totals__label">
		<?php esc_html_e( 'Not Going:', 'event-tickets' ); ?>
					</span>
					<span class="tribe-tickets-rsvp-v2-totals__count">
		<?php echo esc_html( number_format_i18n( $not_going ) ); ?>
					</span>
				</li>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gets the count of attendees by RSVP status for a post.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID.
	 * @param string $status  The RSVP status ('yes' or 'no').
	 *
	 * @return int The count.
	 */
	protected function get_attendee_count_by_status( int $post_id, string $status ): int {
		global $wpdb;

		// Get all RSVP tickets for this post.
		$ticket_ids = $this->get_rsvp_ticket_ids_for_post( $post_id );

		if ( empty( $ticket_ids ) ) {
			return 0;
		}

		$ticket_ids_placeholder = implode( ',', array_fill( 0, count( $ticket_ids ), '%d' ) );

     // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm_ticket ON p.ID = pm_ticket.post_id
					AND pm_ticket.meta_key = %s
				INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
					AND pm_status.meta_key = %s
					AND pm_status.meta_value = %s
				WHERE p.post_type = %s
					AND p.post_status = 'publish'
					AND pm_ticket.meta_value IN ($ticket_ids_placeholder)",
				array_merge(
					[
						TC_Attendee::$ticket_relation_meta_key,
						Meta::RSVP_STATUS_KEY,
						$status,
						TC_Attendee::POSTTYPE,
					],
					$ticket_ids
				)
			)
		);
     // phpcs:enable

		return (int) $count;
	}

	/**
	 * Gets RSVP ticket IDs for a post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int[] Array of ticket IDs.
	 */
	protected function get_rsvp_ticket_ids_for_post( int $post_id ): array {
		$ticket = tribe( Ticket::class );

		return $ticket->get_tickets_for_post( $post_id );
	}
}
