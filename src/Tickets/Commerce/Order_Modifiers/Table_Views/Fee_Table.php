<?php
/**
 * Fee_Table class for displaying Fee data in the table.
 *
 * This class defines the structure and behavior for rendering fee-related data in a table format,
 * including columns for fee name, code, amount, usage, and status. It extends the Order_Modifier_Table
 * class and provides specific logic for handling fee-specific data display.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Table_Views
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Table_Views;

use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Meta_Keys;

/**
 * Class for displaying Fee data in the table.
 *
 * @since 5.18.0
 */
class Fee_Table extends Order_Modifier_Table {

	use Meta_Keys;

	/**
	 * Fee_Table constructor.
	 *
	 * @param Fee                         $modifier       The modifier strategy instance.
	 * @param Fees                        $order_modifier The order modifier repository.
	 * @param Order_Modifiers_Meta        $order_modifier_meta_repository The order modifier meta repository.
	 * @param Order_Modifier_Relationship $order_modifier_relationship The order modifier relationship repository.
	 */
	public function __construct(
		Fee $modifier,
		Fees $order_modifier,
		Order_Modifiers_Meta $order_modifier_meta_repository,
		Order_Modifier_Relationship $order_modifier_relationship
	) {
		$this->modifier                  = $modifier;
		$this->order_modifier_repository = $order_modifier;
		parent::__construct( $modifier, $order_modifier_meta_repository, $order_modifier_relationship );
	}

	/**
	 * Define the columns for the table.
	 *
	 * @since 5.18.0
	 *
	 * @return array An array of columns.
	 */
	public function get_columns() {
		return [
			'display_name' => __( 'Fee Name', 'event-tickets' ),
			'raw_amount'   => __( 'Amount', 'event-tickets' ),
			'active_on'    => __( 'Active on', 'event-tickets' ),
		];
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
				'_wpnonce'    => wp_create_nonce( 'delete_modifier_' . $item->id ),
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
	 * Renders the "Active On" column for a specific order modifier.
	 *
	 * This method determines where the modifier is active (e.g., on all tickets, per ticket, specific venues, or
	 * organizers) and delegates the rendering logic to the corresponding method based on the modifier's application.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item from the table, typically an Order_Modifier object.
	 *
	 * @return string The HTML output for the "Active On" column, depending on where the modifier is applied.
	 */
	protected function render_active_on_column( $item ): string {
		// Look for the applied_to property first, then check the meta value if that's not there.
		if ( property_exists( $item, 'meta_value' ) ) {
			$relationship_type = $item->meta_value;
		} else {
			// Get the meta value, or default to 'all'.
			$relationship_type = $this->modifier->get_order_modifier_meta_by_key(
				$item->id,
				$this->get_applied_to_key( 'fee' )
			)->meta_value ?? 'all';
		}

		switch ( $relationship_type ) {
			case 'all':
				return $this->display_all_tickets();
			case 'per':
				return $this->display_per_tickets();
			case 'venue':
			case 'organizer':
			default:
				return '-';
		}
	}

	/**
	 * Displays a message indicating the modifier is applied to all tickets.
	 *
	 * This method is used when the modifier is applied across all tickets without specific conditions.
	 *
	 * @since 5.18.0
	 *
	 * @return string A message indicating the modifier applies to all tickets.
	 */
	protected function display_all_tickets(): string {
		return __( 'All tickets', 'event-tickets' );
	}

	/**
	 * Displays a message indicating the modifier is applied at a per ticket level.
	 *
	 * @since 5.18.0
	 *
	 * @return string
	 */
	protected function display_per_tickets(): string {
		return __( 'Per ticket', 'event-tickets' );
	}

	/**
	 * Displays a message indicating the modifier is applied to a specific organizer.
	 *
	 * This method retrieves the first organizer related to the given modifier and returns
	 * a translated message indicating the organizer's name. If no organizer is found, it returns early.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the order modifier.
	 *
	 * @return string A message displaying the organizer's name or an empty string if no organizer is found.
	 */
	protected function display_organizers( int $modifier_id ): string {
		// Get the relationships associated with the modifier for organizers.
		// @todo redscar - We shouldn't make the post-type hard coded.
		$get_relationship = $this->order_modifier_relationship->find_by_modifier_and_post_type( $modifier_id, 'tribe_organizer' );

		// Early return if no organizer is found or if post_id is missing.
		if ( empty( $get_relationship ) || empty( $get_relationship->post_id ) ) {
			return '';
		}

		// Retrieve the organizer name using the post ID.
		$organizer_name = get_the_title( $get_relationship->post_id );

		// Early return if the organizer name is not available.
		if ( empty( $organizer_name ) ) {
			return '';
		}

		// Return the translated message displaying the organizer's name.
		return sprintf(
		/* translators: %s is the organizer's name */
			__( 'Organizer: %s', 'event-tickets' ),
			$organizer_name
		);
	}

	/**
	 * Displays a message indicating the modifier is applied to a specific venue.
	 *
	 * This method retrieves the first venue related to the given modifier and returns
	 * a translated message indicating the venue's name. If no venue is found, it returns early.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the order modifier.
	 *
	 * @return string A message displaying the venue's name or an empty string if no venue is found.
	 */
	protected function display_venues( int $modifier_id ): string {
		// Get the relationships associated with the modifier for venues.
		// @todo redscar - We shouldn't make the post-type hard coded.
		$get_relationship = $this->order_modifier_relationship->find_by_modifier_and_post_type( $modifier_id, 'tribe_venue' );

		// Early return if no venue is found or if post_id is missing.
		if ( empty( $get_relationship ) || empty( $get_relationship->post_id ) ) {
			return '';
		}

		// Retrieve the venue name using the post ID.
		$venue_name = get_the_title( $get_relationship->post_id );

		// Early return if the venue name is not available.
		if ( empty( $venue_name ) ) {
			return '';
		}

		// Return the translated message displaying the venue's name.
		return sprintf(
		/* translators: %s is the venue's name */
			__( 'Venue: %s', 'event-tickets' ),
			$venue_name
		);
	}

	/**
	 * Render the "status" column.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	protected function render_status_column( $item ): string {
		return $this->modifier->get_status_display( $item->status );
	}

	/**
	 * Renders the fee amount column for the current item.
	 *
	 * This method uses the modifier's `display_amount_field` to display the fee amount in the appropriate format
	 * based on the sub_type (e.g., 'flat' or 'percent'). The fee amount is passed in cents and is converted
	 * accordingly.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item being rendered. This should contain `raw_amount` and `sub_type`
	 *     fields.
	 *
	 * @return string The formatted fee amount to be displayed in the table.
	 */
	protected function render_raw_amount_column( $item ): string {
		return $this->modifier->display_amount_field( $item->raw_amount, $item->sub_type );
	}

	/**
	 * Define sortable columns.
	 *
	 * @since 5.18.0
	 *
	 * @return array An array of sortable columns.
	 */
	protected function get_sortable_columns(): array {
		return [
			'display_name' => [ 'display_name', true ],
			'raw_amount'   => [ 'raw_amount', false ],
		];
	}

	/**
	 * Renders the explanation text for the table.
	 *
	 * This method returns a description related to the current table context, providing users with information
	 * about the functionality of the modifiers they are viewing or editing.
	 *
	 * @since 5.18.0
	 *
	 * @return string The explanation text with a clickable "Learn More" link.
	 */
	public function render_table_explain_text(): string {
		return esc_html__( 'Fees will be applied to the cart at checkout. Fees can only be used with Tickets Commerce transactions.', 'event-tickets' );
	}
}
