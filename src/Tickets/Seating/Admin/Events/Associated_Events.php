<?php

namespace TEC\Tickets\Seating\Admin\Events;

if ( ! class_exists( 'WP_List_Table' ) || ! class_exists( 'WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

use WP_Posts_List_Table;
use WP_Query;

class Associated_Events extends WP_Posts_List_Table {
	
	/**
	 * @var string
	 */
	public static $slug = 'tec-tickets-seating-events';
	
	function __construct() {
		parent::__construct(
			[
				'singular' => 'Event',
				'plural'   => 'Events',
			]
		);
	}
	
	function get_columns() {
		return [
			'title'  => __( 'Title', 'event-tickets' ),
			'status' => __( 'Status', 'event-tickets' ),
			'date'   => __( 'Date', 'event-tickets' ),
		];
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
				return $item->post_status;
			case 'date':
				return $item->post_date;
			default:
				return print_r( $item, true );
		}
	}

	function get_sortable_columns() {
		return [
			'title'  => [ 'title', true ],
			'date'   => [ 'date', true ],
			'status' => [ 'status', true ],
		];
	}
	
	function prepare_items() {
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;
		
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		
		add_filter( 'quick_edit_enabled_for_post_type', '__return_false' );
		
		$args = [
			'post_type'      => 'tribe_events',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
		];
		
		$query       = new WP_Query( $args );
		$total_items = $query->found_posts;
		
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
		
		$this->items = $query->posts;
	}
	
	/**
	 * @inheritDoc
	 */
	public function has_items() {
		return count( $this->items );
	}
	
	/**
	 * Override display rows to avoid global query usages.
	 *
	 * @since TBD
	 *
	 * @param array $posts The posts array.
	 * @param int   $level The level.
	 *
	 * @return void
	 */
	public function display_rows( $posts = [], $level = 0 ) {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
	}
	
	/**
	 * Skip bulk actions as we don't need it.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [];
	}
}
