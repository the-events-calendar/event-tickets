<?php

namespace TEC\Tickets\Commerce\Reports;

/**
 * Class Ticket Report management.
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Reports
 */
class Ticket {


	/**
	 * Links to the sales report for this product.
	 *
	 * @since 5.1.9
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return string
	 */
	public function get_link( $event_id, $ticket_id ) {
		if ( empty( $ticket_id ) ) {
			return '';
		}

		$query = array(
			'page'        => 'tpp-orders',
			'product_ids' => $ticket_id,
			'post_id'     => $event_id,
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		return '<span><a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Report', 'event-tickets' ) . '</a></span>';
	}
}