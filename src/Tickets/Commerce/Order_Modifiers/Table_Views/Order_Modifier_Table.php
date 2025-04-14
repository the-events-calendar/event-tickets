<?php
/**
 * Abstract class for handling common functionality in Order Modifier tables.
 *
 * The Order_Modifier_Table class provides the base functionality for rendering table views of different
 * types of order modifiers, such as coupons or fees. This class defines the structure, sortable columns,
 * and common behaviors such as rendering actions for each row in the table and managing pagination.
 *
 * Specific implementations (e.g., Coupon_Table, Fee_Table) extend this class to define their own
 * column data and item rendering logic.
 *
 * The class also includes methods for rendering specific columns like the "status" column and
 * allows for the inclusion of search boxes and filters for modifier data.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Table_Views
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Table_Views;

use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use WP_List_Table;

/**
 * Abstract class for Order Modifier Table (Coupons/Fees).
 *
 * @since 5.18.0
 */
abstract class Order_Modifier_Table extends WP_List_Table {

	use Valid_Types;

	/**
	 * The current page number.
	 *
	 * @since 5.18.1
	 *
	 * @var int
	 */
	protected int $current_page = 1;

	/**
	 * Modifier class for the table (e.g., Coupon or Fee).
	 *
	 * @since 5.18.0
	 *
	 * @var Modifier_Strategy_Interface
	 */
	protected $modifier;

	/**
	 * Repository for handling operations related to the `order_modifiers` table.
	 *
	 * @since 5.18.0
	 *
	 * @var Order_Modifiers
	 */
	public Order_Modifiers $order_modifier_repository;

	/**
	 * Repository for handling operations related to the `order_modifiers_meta` table.
	 *
	 * @since 5.18.0
	 *
	 * @var Order_Modifiers_Meta
	 */
	public Order_Modifiers_Meta $order_modifier_meta_repository;

	/**
	 * Repository for handling operations related to the `order_modifier_relationship_repository` table.
	 *
	 * @since 5.18.0
	 *
	 * @var Order_Modifier_Relationship
	 */
	public Order_Modifier_Relationship $order_modifier_relationship;

	/**
	 * Constructor for the Order Modifier Table.
	 *
	 * @since 5.18.0
	 *
	 * @param Modifier_Strategy_Interface $modifier The modifier class to use for data fetching and logic.
	 * @param Order_Modifiers_Meta        $order_modifier_meta_repository The repository for order modifier meta data.
	 * @param Order_Modifier_Relationship $order_modifier_relationship The repository for order modifier relationships.
	 */
	public function __construct(
		Modifier_Strategy_Interface $modifier,
		Order_Modifiers_Meta $order_modifier_meta_repository,
		Order_Modifier_Relationship $order_modifier_relationship
	) {
		$this->order_modifier_meta_repository = $order_modifier_meta_repository;
		$this->order_modifier_relationship    = $order_modifier_relationship;

		parent::__construct(
			[
				'singular' => $modifier->get_modifier_display_name(),
				'plural'   => $modifier->get_modifier_display_name( true ),
				'ajax'     => false,
			]
		);
	}

	/**
	 * Prepares the items for display in the table.
	 *
	 * @since 5.18.0
	 */
	public function prepare_items() {
		$this->_column_headers = [
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		];

		// Pagination parameters.
		$per_page           = $this->get_items_per_page( "{$this->modifier->get_modifier_type()}_per_page", 10 );
		$this->current_page = $this->get_pagenum();

		// Query parameters.
		$parameters = [
			'limit'       => $this->get_items_per_page( "{$this->modifier->get_modifier_type()}_per_page", 10 ),
			'order'       => tec_get_request_var( 'order', 'asc' ),
			'orderby'     => tec_get_request_var( 'orderby', 'display_name' ),
			'page'        => $this->current_page,
			'search_term' => tec_get_request_var( 's', '' ),
		];

		$total_items = $this->setup_items( $parameters, $per_page );

		// Set the pagination args.
		$this->set_pagination_args(
			[
				'per_page'    => $per_page,
				'total_items' => $total_items,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Setup the items for the table.
	 *
	 * @since 5.18.1
	 *
	 * @param array $params   The query parameters.
	 * @param int   $per_page The number of items to display per page.
	 *
	 * @return int The total number of items.
	 */
	protected function setup_items( array $params, int $per_page ): int {
		$this->items = $this->get_items( $params );

		// Get the total number of items.
		if ( count( $this->items ) < $per_page && $this->current_page === 1 ) {
			return count( $this->items );
		}

		unset( $params['limit'], $params['page'] );

		return count( $this->get_items( $params ) );
	}

	/**
	 * Get the modifier items.
	 *
	 * @since 5.21.0
	 *
	 * @param array $params The query parameters.
	 *
	 * @return array The items that were retrieved.
	 */
	protected function get_items( array $params ): array {
		return $this->modifier->get_modifiers( $params );
	}

	/**
	 * Render the default column.
	 *
	 * This method dynamically calls a dedicated method to render the specific column.
	 * If no specific method exists for the column, it falls back to a generic column handler.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		// Build the method name dynamically based on the column name.
		$method = "render_{$column_name}_column";

		// If a specific method exists for the column, call it.
		if ( method_exists( $this, $method ) && is_callable( [ $this, $method ] ) ) {
			return $this->$method( $item );
		}

		// Fallback to a default rendering method if no specific method is found.
		return $this->render_generic_column( $item, $column_name );
	}

	/**
	 * Fallback method for rendering generic columns.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	protected function render_generic_column( $item, $column_name ) {
		return ! empty( $item->$column_name ) ? esc_html( $item->$column_name ) : '-';
	}

	/**
	 * Renders the "display_name" column with "Edit" and "Delete" actions, including nonces for security.
	 *
	 * This method generates the display content for the "Name" column, including an "Edit" link
	 * and the "Delete" link. The edit link directs the user to the admin page where
	 * they can edit the specific modifier, passing the necessary parameters for the page,
	 * modifier type, modifier ID, and a nonce for security.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item from the table, typically an Order_Modifier object.
	 *
	 * @return string The HTML output for the "display_name" column, including row actions.
	 */
	protected function render_display_name_column( $item ): string {
		$edit_link = add_query_arg(
			[
				'page'        => $this->modifier->get_page_slug(),
				'modifier'    => $this->modifier->get_modifier_type(),
				'edit'        => 1,
				'modifier_id' => $item->id,
			],
			admin_url( 'admin.php' )
		);

		// Replace with actual delete URL and include nonce.
		$delete_link = add_query_arg(
			[
				'action'      => 'delete_modifier',
				'modifier_id' => $item->id,
				'_wpnonce'    => wp_create_nonce( "delete_modifier_{$item->id}" ),
				'modifier'    => $this->modifier->get_modifier_type(),
			],
			admin_url( 'admin.php' )
		);

		$actions = [
			'edit'   => [
				'label' => __( 'Edit', 'event-tickets' ),
				'url'   => $edit_link,
			],
			'delete' => [
				'label' => __( 'Delete', 'event-tickets' ),
				'url'   => $delete_link,
			],
		];

		return $this->render_actions( $item->display_name, $actions );
	}

	/**
	 * Helper to render actions for a column. The `edit` action is used for the label link as well.
	 *
	 * @since 5.18.0
	 *
	 * @param string $label The display label for the item (e.g., the name of the coupon or fee).
	 * @param array  $actions Array of actions, where the key is a readable action label (e.g., 'Edit', 'Delete')
	 *                        and the value is the URL.
	 *
	 * @return string Rendered HTML for actions.
	 */
	protected function render_actions( string $label, array $actions ): string {
		// Loop through the actions and build both the label and action links.
		$action_links = array_map(
			function ( $data ) {
				return sprintf( '<a href="%s">%s</a>', esc_url( $data['url'] ), esc_html( $data['label'] ) );
			},
			$actions
		);

		$url = isset( $actions['edit'] ) ? $actions['edit']['url'] : ( array_values( $actions )[0]['url'] ?? '#' );

		return sprintf( '<a href="%s">%s</a> %s', esc_url( $url ), esc_html( $label ), $this->row_actions( $action_links ) );
	}

	/**
	 * Adds a search box with a custom placeholder to the table.
	 *
	 * @since 5.18.0
	 *
	 * @param string $text The text to display in the submit button.
	 * @param string $input_id The input ID.
	 * @param string $placeholder The placeholder text to display in the search input.
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id, $placeholder = '' ) {
		$search_value = tec_get_request_var( 's', '' );

		// Set the input ID.
		$input_id = "{$input_id}-search-input";

		// If no placeholder is provided, default to the display_name column.
		if ( empty( $placeholder ) ) {
			$placeholder = $this->get_columns()['display_name'];
		}

		// Output the search form with nonce for security.
		echo '<p class="search-box">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . '</label>';
		echo '<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="' . esc_attr( $search_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';

		submit_button( $text, '', '', false );
		echo '</p>';
	}

	/**
	 * Render the navigation tabs for available modifiers dynamically.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function render_tabs(): void {
		$modifiers = self::get_modifier_types();

		// If we don't have multiple modifiers, don't render tabs.
		if ( count( $modifiers ) < 2 ) {
			return;
		}

		// Determine the current modifier, falling back to the default.
		$current_modifier = tec_get_request_var( 'modifier', $this->get_default_type() );

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $modifiers as $modifier_slug => $modifier_data ) {
			// Check if the current tab is active.
			$active_class = ( $current_modifier === $modifier_slug ) ? 'nav-tab-active' : '';

			// Generate the URL for the tab.
			$url = add_query_arg(
				[
					'page'     => $this->modifier->get_page_slug(),
					'modifier' => $modifier_slug,
				],
				admin_url( 'admin.php' )
			);

			// Output the tab.
			printf(
				'<a href="%s" class="nav-tab %s">%s</a>',
				esc_url( $url ),
				esc_attr( $active_class ),
				esc_html( $modifier_data['display_name'] )
			);
		}
		echo '</h2>';
	}

	/**
	 * Renders the title with the "Add New" button for the current modifier type.
	 *
	 * This method displays the title of the current modifier (e.g., 'Coupons', 'Fees') and an "Add New" button
	 * to allow users to create a new modifier.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function render_title(): void {
		// Get the display name of the current modifier type.
		$modifier = Controller::get_modifier_display_name( $this->modifier->get_modifier_type() );

		// Create the URL for the "Add New" button.
		$add_new_url = add_query_arg(
			[
				'page'     => $this->modifier->get_page_slug(),
				'modifier' => $this->modifier->get_modifier_type(),
				'edit'     => 1,
			],
			admin_url( 'admin.php' )
		);

		// Output the title and the "Add New" button.
		printf(
			'<h3 class="tec-tickets__modifier-page-title">%s <a href="%s" class="page-title-action">%s</a></h3>',
			esc_html( $modifier ),
			esc_url( $add_new_url ),
			esc_html_x( 'Add New', 'Add New Order modifier link text', 'event-tickets' )
		);
	}

	/**
	 * Displays explanatory text below the tab title on the current page.
	 *
	 * This method allows for custom explanatory text to be rendered
	 * under the title of the active tab in the table view.
	 * It can be overridden by subclasses to provide specific content
	 * based on the modifier type or context.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function render_table_explain_text() {}

	/**
	 * Retrieves hidden columns for the table.
	 *
	 * @since 5.18.1
	 *
	 * @return array
	 */
	protected function get_hidden_columns(): array {
		return [];
	}
}
