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
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Table_Views
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Table_Views;

use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Factory;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use WP_List_Table;

/**
 * Abstract class for Order Modifier Table (Coupons/Fees).
 *
 * @since TBD
 */
abstract class Order_Modifier_Table extends WP_List_Table {

	use Valid_Types;

	/**
	 * Modifier class for the table (e.g., Coupon or Fee).
	 *
	 * @since TBD
	 *
	 * @var Modifier_Strategy_Interface
	 */
	protected $modifier;

	/**
	 * Repository for handling operations related to the `order_modifiers` table.
	 *
	 * @since TBD
	 *
	 * @var Order_Modifiers
	 */
	public Order_Modifiers $order_modifier_repository;

	/**
	 * Repository for handling operations related to the `order_modifiers_meta` table.
	 *
	 * @since TBD
	 *
	 * @var Order_Modifiers_Meta
	 */
	public Order_Modifiers_Meta $order_modifier_meta_repository;

	/**
	 * Repository for handling operations related to the `order_modifier_relationship_repository` table.
	 *
	 * @since TBD
	 *
	 * @var Order_Modifier_Relationship
	 */
	public Order_Modifier_Relationship $order_modifier_relationship;

	/**
	 * Constructor for the Order Modifier Table.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Strategy_Interface $modifier The modifier class to use for data fetching and logic.
	 */
	public function __construct( Modifier_Strategy_Interface $modifier ) {
		$this->modifier                       = $modifier;
		$this->order_modifier_repository      = Factory::get_repository_for_type( $modifier->get_modifier_type() );
		$this->order_modifier_meta_repository = new Order_Modifiers_Meta();
		$this->order_modifier_relationship    = new Order_Modifier_Relationship();

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
	 * @since TBD
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Handle search.
		$search = tribe_get_request_var( 's', '' );

		// Capture sorting parameters.

		$orderby = sanitize_text_field( tribe_get_request_var( 'orderby', 'display_name' ) );
		$order   = sanitize_text_field( tribe_get_request_var( 'order', 'asc' ) );

		// Fetch the data from the modifier class, including sorting.
		$data = $this->modifier->find_by_search(
			[
				'search_term'   => $search,
				'orderby'       => $orderby,
				'order'         => $order,
				'modifier_type' => $this->modifier->get_modifier_type(),
			]
		);

		// Pagination.
		$per_page     = $this->get_items_per_page( $this->modifier->get_modifier_type() . '_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$data = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

		// Set the items for the table.
		$this->items = $data;

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Render the default column.
	 *
	 * This method dynamically calls a dedicated method to render the specific column.
	 * If no specific method exists for the column, it falls back to a generic column handler.
	 *
	 * @since TBD
	 *
	 * @param object $item The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		// Build the method name dynamically based on the column name.
		$method = 'render_' . $column_name . '_column';

		// If a specific method exists for the column, call it.
		if ( method_exists( $this, $method ) ) {
			return $this->$method( $item );
		}

		// Fallback to a default rendering method if no specific method is found.
		return $this->render_generic_column( $item, $column_name );
	}

	/**
	 * Fallback method for rendering generic columns.
	 *
	 * @since TBD
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
	 * Helper to render actions for a column. The `edit` action is used for the label link as well.
	 *
	 * @since TBD
	 *
	 * @param string $label The display label for the item (e.g., the name of the coupon or fee).
	 * @param array  $actions Array of actions, where the key is a readable action label (e.g., 'Edit', 'Delete')
	 *                        and the value is the URL.
	 *
	 * @return string Rendered HTML for actions.
	 */
	protected function render_actions( string $label, array $actions ): string {
		$action_links = [];

		// Loop through the actions and build both the label and action links.
		foreach ( $actions as $action_label => $data ) {
			$action_links[ $action_label ] = sprintf( '<a href="%s">%s</a>', esc_url( $data['url'] ), esc_html( $data['label'] ) );
		}

		$label_html = isset( $actions['edit'] ) ?
			sprintf( '<a href="%s">%s</a>', esc_url( $actions['edit']['url'] ), esc_html( $label ) ) :
			sprintf( '<a href="%s">%s</a>', esc_url( array_values( $actions )[0]['url'] ?? '#' ), esc_html( $label ) );

		// Join the action links and append them to the label with the row actions.
		return sprintf( '%1$s %2$s', $label_html, $this->row_actions( $action_links ) );
	}

	/**
	 * Adds a search box with a custom placeholder to the table.
	 *
	 * @since TBD
	 *
	 * @param string $text The text to display in the submit button.
	 * @param string $input_id The input ID.
	 * @param string $placeholder The placeholder text to display in the search input.
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id, $placeholder = '' ) {
		// Check if nonce is valid. The nonce value does not need sanitization, as wp_verify_nonce handles it.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$maybe_nonce = tribe_get_request_var( '_wpnonce' );
		$nonce_valid = ! empty( $maybe_nonce ) && wp_verify_nonce( $maybe_nonce, 'search_order_modifier' );

		// If nonce is invalid, set search term to empty; otherwise, get the value from the request.
		$search_value = $nonce_valid ? sanitize_text_field( tribe_get_request_var( 's', '' ) ) : '';

		// Set the input ID.
		$input_id = $input_id . '-search-input';

		// If no placeholder is provided, default to the display_name column.
		if ( empty( $placeholder ) ) {
			$placeholder = $this->get_columns()['display_name'];
		}

		// Output the search form with nonce for security.
		echo '<p class="search-box">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . '</label>';
		echo '<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="' . esc_attr( $search_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';

		// Output the nonce field for verification.
		wp_nonce_field( 'search_order_modifier', '_wpnonce' );

		submit_button( $text, '', '', false );
		echo '</p>';
	}

	/**
	 * Render the navigation tabs for available modifiers dynamically.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_tabs(): void {
		$modifiers = $this->get_modifiers();

		// If we don't have multiple modifiers, don't render tabs.
		if ( count( $modifiers ) < 2 ) {
			return;
		}

		// Determine the current modifier, falling back to the default.
		$current_modifier = tribe_get_request_var( 'modifier', $this->get_default_type() );

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
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_title(): void {
		// Get the display name of the current modifier type.
		$modifier = tribe( Controller::class )->get_modifier_display_name( $this->modifier->get_modifier_type() );

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
			'<h3>%s <a href="%s" class="page-title-action button">%s</a></h3>',
			esc_html( $modifier ),
			esc_url( $add_new_url ),
			esc_html__( 'Add New', 'event-tickets' )
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_table_explain_text() {
	}
}
