<?php
/**
 * A row as Tickets Commerce reads a ticket post.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Commerce
 */

namespace TEC\Tickets\Recurring_Tickets\Commerce;

use DateTimeInterface;
use TEC\Tickets\Commerce\Models\Ticket_Model;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use WP_Post;

/**
 * Builds an in-memory ticket post for a row, where Tickets Commerce starts from a post.
 *
 * The post is never put in WordPress' post cache: `get_post()` on a table ticket ID still returns nothing, as for a
 * deleted ticket. Its meta reads go through the meta shim.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Commerce
 */
final class Row_Post {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * Row_Post constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Returns the ticket post of a row.
	 *
	 * @since TBD
	 *
	 * @param mixed $ticket_id A ticket ID.
	 *
	 * @return WP_Post|null The row's ticket post, or null when the ID is no row's.
	 */
	public function get( $ticket_id ): ?WP_Post {
		if ( ! Ticket_ID::is_table_ticket( $ticket_id ) ) {
			return null;
		}

		$row = $this->rows->find( Ticket_ID::to_row_id( (int) $ticket_id ) );

		if ( ! $row instanceof Ticket ) {
			return null;
		}

		$created = $this->utc( $row->created_at );
		$updated = $this->utc( $row->updated_at ) ?: $created;

		return new WP_Post(
			(object) [
				'ID'                => (int) $ticket_id,
				'post_type'         => Commerce_Ticket::POSTTYPE,
				'post_title'        => (string) $row->name,
				'post_excerpt'      => (string) $row->description,
				'post_content'      => '',
				'post_status'       => (string) $row->status,
				'post_name'         => 'recurring-event-ticket-' . (int) $row->id,
				'post_author'       => (int) get_post_field( 'post_author', (int) $row->parent_id ),
				'post_parent'       => 0,
				'post_password'     => '',
				'menu_order'        => (int) $row->menu_order,
				'post_date'         => get_date_from_gmt( $created ),
				'post_date_gmt'     => $created,
				'post_modified'     => get_date_from_gmt( $updated ),
				'post_modified_gmt' => $updated,
				'comment_status'    => 'closed',
				'ping_status'       => 'closed',
				'filter'            => 'raw',
			]
		);
	}

	/**
	 * Answers `tec_tc_get_ticket()` for a row, before it looks the ID up as a post.
	 *
	 * @since TBD
	 *
	 * @param mixed       $value  The value to return instead of the ticket's, null to build it.
	 * @param mixed       $ticket The ticket ID or post.
	 * @param string|null $output The return type: OBJECT, ARRAY_A or ARRAY_N.
	 * @param string      $filter The filter, or context of the fetch.
	 *
	 * @return mixed The row's decorated ticket post in the asked type, or the value passed for any other ticket.
	 */
	public function filter_ticket( $value, $ticket, $output = OBJECT, $filter = 'raw' ) {
		$post = null === $value ? $this->get( $ticket instanceof WP_Post ? $ticket->ID : $ticket ) : null;

		if ( ! $post instanceof WP_Post ) {
			return $value;
		}

		$post = Ticket_Model::from_post( $post )->to_post( OBJECT, $filter );

		if ( OBJECT !== $output ) {
			return ARRAY_A === $output ? (array) $post : array_values( (array) $post );
		}

		return $post;
	}

	/**
	 * Formats a row's datetime, written in UTC.
	 *
	 * @since TBD
	 *
	 * @param mixed $date The datetime, as the model holds it.
	 *
	 * @return string The datetime as `Y-m-d H:i:s`, or an empty string.
	 */
	private function utc( $date ): string {
		if ( $date instanceof DateTimeInterface ) {
			return $date->format( 'Y-m-d H:i:s' );
		}

		return (string) $date;
	}
}
