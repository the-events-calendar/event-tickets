<?php

namespace TEC\Tickets\Commerce\Reports;

use TEC\Tickets\Commerce\Module;

/**
 * Class Event Report management.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Reports
 */
class Event {


	/**
	 * Links to sales report for all tickets for this event.
	 *
	 * @since 5.1.9
	 *
	 * @param int  $event_id
	 * @param bool $url_only
	 *
	 * @return string
	 */
	public function get_link( $event_id, $url_only = false ) {
		$ticket_ids = (array) tribe( Module::class )->get_tickets_ids( $event_id );
		if ( empty( $ticket_ids ) ) {
			return '';
		}

		$query = array(
			'page'    => 'tpp-orders',
			'post_id' => $event_id,
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		/**
		 * Filter the PayPal Ticket Orders (Sales) Report URL
		 *
		 * @var string $report_url Report URL
		 * @var int    $event_id   The post ID
		 * @var array  $ticket_ids An array of ticket IDs
		 *
		 * @return string
		 */
		$report_url = apply_filters( 'tribe_tickets_paypal_report_url', $report_url, $event_id, $ticket_ids );

		return $url_only
			? $report_url
			: '<small> <a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Sales report', 'event-tickets' ) . '</a> </small>';
	}
}