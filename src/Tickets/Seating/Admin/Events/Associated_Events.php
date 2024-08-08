<?php

namespace TEC\Tickets\Seating\Admin\Events;

if ( ! class_exists( 'WP_List_Table' ) || ! class_exists( 'WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

use TEC\Tickets\Seating\Meta;
use Tribe__Tickets__Main as Tickets;
use WP_Posts_List_Table;
use WP_Post;

/**
 * Associated Events list table.
 *
 * @since TBD
 */
class Associated_Events extends WP_Posts_List_Table {
	/**
	 * @var int Items count.
	 */
	private int $item_count = 0;
	/**
	 * The constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'Event',
				'plural'   => 'Events',
			]
		);
	}
	
	/**
	 * Get the columns.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			'title'  => __( 'Title', 'event-tickets' ),
			'status' => __( 'Status', 'event-tickets' ),
			'date'   => __( 'Date', 'event-tickets' ),
		];
	}
	
	/**
	 * Get the default column.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $item The current WP_Post object.
	 * @param string  $column_name The column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
				return $item->post_status;
			case 'date':
				return $item->post_date;
			default:
				return $item->post_title;
		}
	}
	
	/**
	 * The sortable columns.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_sortable_columns() {
		return [
			'title'  => [ 'title', true ],
			'date'   => [ 'date', true ],
			'status' => [ 'status', true ],
		];
	}
	
	/**
	 * The screen setup.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function screen_setup() {
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		
		add_filter( 'quick_edit_enabled_for_post_type', '__return_false' );
	}
	
	/**
	 * Prepare the items.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->screen_setup();
		
		$layout_id    = tribe_get_request_var( 'layout' );
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;
		
		$ticketable_post_types = Tickets::instance()->post_types();
		$repository            = new class( $ticketable_post_types ) extends \Tribe__Repository {
			/**
			 * @param string[] $post_types The list of post types.
			 */
			public function __construct( array $post_types ) {
				$this->default_args['post_type'] = $post_types;
				parent::__construct();
			}
		};
		
		$this->item_count = $repository->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, $layout_id )->count();
		
		$this->set_pagination_args(
			[
				'total_items' => $this->item_count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->item_count / $per_page ),
			]
		);
		
		$this->items = $repository->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, $layout_id )->all( true );
	}
	
	/**
	 * @inheritDoc
	 */
	public function has_items(): bool {
		return $this->item_count > 0;
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
	public function get_bulk_actions(): array {
		return [];
	}
	
	/**
	 * @inheritDoc
	 */
	public function no_items() {
		echo esc_html__( 'No Associated Events found.', 'event-tickets' );
	}
	
	/**
	 * Get the slug.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'tec-tickets-seating-events';
	}
}
