<?php

namespace TEC\Tickets\Seating\Admin\Events;

if ( ! class_exists( 'WP_List_Table' ) || ! class_exists( 'WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

use TEC\Tickets\Seating\Meta;
use Tribe__Tickets__Admin__Columns__Tickets;
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
			'title'   => __( 'Title', 'event-tickets' ),
			'tags'    => __( 'Tags', 'event-tickets' ),
			'tickets' => __( 'Attendees', 'event-tickets' ),
			'date'    => __( 'Date', 'event-tickets' ),
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
			default:
				return $item->post_title;
		}
	}
	
	/**
	 * Render the tags column.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The current WP_Post object.
	 *
	 * @return void
	 */
	public function column_tags( WP_Post $post ): void {
		$taxonomy        = 'post_tag';
		$taxonomy_object = get_taxonomy( $taxonomy );
		$terms           = get_the_terms( $post->ID, $taxonomy );
		if ( is_array( $terms ) ) {
			$term_links = [];
			
			foreach ( $terms as $t ) {
				$posts_in_term_qv = [];
				
				if ( 'post' !== $post->post_type ) {
					$posts_in_term_qv['post_type'] = $post->post_type;
				}
				
				if ( $taxonomy_object->query_var ) {
					$posts_in_term_qv[ $taxonomy_object->query_var ] = $t->slug;
				} else {
					$posts_in_term_qv['taxonomy'] = $taxonomy;
					$posts_in_term_qv['term']     = $t->slug;
				}
				
				$label = esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) );
				
				$term_links[] = $this->get_edit_link( $posts_in_term_qv, $label );
			}
			
			echo wp_kses(
				implode( wp_get_list_item_separator(), $term_links ),
				[
					'a' => [
						'href' => [],
					],
				]
			);
		}
	}
	
	/**
	 * Render the attendee count column.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $item The current WP_Post object.
	 *
	 * @return void
	 */
	public function column_tickets( $item ): void {
		$attendee_column = new Tribe__Tickets__Admin__Columns__Tickets( $item->ID );
		$attendee_column->render_column( 'tickets', $item->ID );
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
			'title' => [ 'title', true ],
			'date'  => [ 'date', true ],
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
