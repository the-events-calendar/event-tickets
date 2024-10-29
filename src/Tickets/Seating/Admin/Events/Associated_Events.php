<?php
/**
 * Associated Events list table handler.
 *
 * @package TEC\Tickets\Seating\Admin\Events
 */

namespace TEC\Tickets\Seating\Admin\Events;

if ( ! class_exists( 'WP_List_Table' ) || ! class_exists( 'WP_Posts_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Meta;
use Tribe__Tickets__Admin__Columns__Tickets;
use Tribe__Tickets__Main as Tickets;
use WP_Posts_List_Table;
use WP_Post;

/**
 * Associated Events list table.
 *
 * @since 5.16.0
 */
class Associated_Events extends WP_Posts_List_Table {
	/**
	 * @var int Items count.
	 */
	private int $item_count = 0;

	/**
	 * The slug to display in the list table page.
	 *
	 * @var string
	 */
	public const SLUG = 'tec-tickets-seating-events';

	/**
	 * The page to display in the list table page.
	 *
	 * @var string
	 */
	public const PAGE = 'admin_page_tec-tickets-seating-events';

	/**
	 * The per page option.
	 *
	 * @var string
	 */
	public const OPTION_PER_PAGE = 'tec_tickets_seating_events_per_page';

	/**
	 * The constructor.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			'title'   => _x( 'Title', 'Post title for associated events list', 'event-tickets' ),
			'tags'    => _x( 'Tags', 'Post tags for associated events list', 'event-tickets' ),
			'tickets' => _x( 'Attendees', 'Attendee count for associated events list', 'event-tickets' ),
			'date'    => _x( 'Date', 'Post date for associated events list', 'event-tickets' ),
		];
	}

	/**
	 * Render the tags column.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->screen_setup();

		$per_page  = get_user_meta( get_current_user_id(), self::OPTION_PER_PAGE, true );
		$layout_id = tribe_get_request_var( 'layout' );
		$page      = absint( tribe_get_request_var( 'paged', 0 ) );
		$orderby   = tribe_get_request_var( 'orderby' );
		$order     = tribe_get_request_var( 'order' );
		$status    = tribe_get_request_var( 'post_status' );
		$search    = tribe_get_request_var( 's' );

		$arguments = [
			'status'         => self::get_supported_status_list(),
			'paged'          => $page,
			'posts_per_page' => $per_page,
		];

		if ( ! empty( $orderby ) ) {
			$arguments['orderby'] = $orderby;
		}

		if ( ! empty( $order ) ) {
			$arguments['order'] = $order;
		}

		if ( ! empty( $search ) ) {
			$arguments['s'] = $search;
		}

		if ( ! empty( $status ) ) {
			$arguments['status'] = [ $status ];
		}

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

		$repository->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, $layout_id )->by_args( $arguments );

		$this->item_count = $repository->found();
		$this->items      = $repository->all( true );

		$this->set_pagination_args(
			[
				'total_items' => $this->item_count,
				'per_page'    => $per_page,
			]
		);
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
	 * @since 5.16.0
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
	 * Gets the list of views available on this table.
	 *
	 * The format is an associative array:
	 * - `'id' => 'link'`
	 *
	 * @since 5.16.0
	 *
	 * @return array<string, string> The list of views.
	 */
	protected function get_views(): array {
		$post_status = tribe_get_request_var( 'post_status', '' );
		$layout_id   = tribe_get_request_var( 'layout', false );
		$statuses    = self::get_supported_status_list();
		$counts      = self::get_status_count_for_layout( $layout_id );

		$views = [];

		foreach ( $statuses as $status ) {
			$class         = $post_status === $status ? 'current' : '';
			$status_object = get_post_status_object( $status );

			$views[ $status ] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				add_query_arg( [ 'post_status' => $status ] ),
				$class,
				$status_object->label,
				$counts[ $status ] ?? 0
			);
		}

		$all['all'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			add_query_arg(
				[
					'page'   => self::SLUG,
					'layout' => $layout_id,
				],
				admin_url( 'admin.php' )
			),
			empty( $post_status ) ? 'current' : '',
			_x( 'All', 'Associated events post filter list label for all status', 'event-tickets' ),
			array_sum( $counts )
		);

		return array_merge( $all, $views );
	}

	/**
	 * Skip bulk actions as we don't need it.
	 *
	 * @since 5.16.0
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
		echo esc_html( _x( 'No Associated Events found.', 'Associated events list no items', 'event-tickets' ) );
	}

	/**
	 * Get the supported status list.
	 *
	 * @since 5.16.0
	 *
	 * @return string[] The supported status list.
	 */
	public static function get_supported_status_list(): array {
		/**
		 * Filter the list of supported status list.
		 *
		 * @since 5.16.0
		 *
		 * @param string[] $status_list The list of supported status list.
		 */
		return apply_filters(
			'tec_tickets_seating_associated_events_status_list',
			[
				'publish',
				'future',
				'draft',
				'pending',
				'private',
			]
		);
	}

	/**
	 * Get the status count for layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The layout ID.
	 *
	 * @return array<string, int> The status count.
	 */
	public static function get_status_count_for_layout( string $layout_id ): array {
		global $wpdb;

		$ticketable_post_types = tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( empty( $ticketable_post_types ) ) {
			return [];
		}

		$post_types = DB::prepare(
			implode( ', ', array_fill( 0, count( $ticketable_post_types ), '%s' ) ),
			...$ticketable_post_types
		);

		$supported_status_list = self::get_supported_status_list();

		$status_list = DB::prepare(
			implode( ', ', array_fill( 0, count( $supported_status_list ), '%s' ) ),
			...$supported_status_list
		);

		try {
			$counts_by_status = DB::get_results(
				DB::prepare(
					"SELECT posts.post_status, COUNT(posts.ID) AS post_count
					FROM %i AS posts
					INNER JOIN %i AS layout_meta ON posts.ID = layout_meta.post_id
					WHERE posts.post_type IN ({$post_types})
					AND posts.post_status IN ({$status_list})
					AND layout_meta.meta_key = %s
					AND layout_meta.meta_value = %s
					GROUP BY posts.post_status",
					$wpdb->posts,
					$wpdb->postmeta,
					Meta::META_KEY_LAYOUT_ID,
					$layout_id
				),
			);
		} catch ( \Exception $e ) {
			return [];
		}

		return wp_list_pluck( $counts_by_status, 'post_count', 'post_status' );
	}
}
