<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers
 */

namespace TEC\Tickets\Order_Modifiers;

use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;

/**
 * Class Modifier_Settings.
 *
 * Manages the admin settings UI in relation to Order Modifiers.
 *
 * @since TBD
 */
class Modifier_Settings {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets Order Modifiers page slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-order-modifiers';

	/**
	 * Event Tickets Order Modifiers page hook suffix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-order-modifiers';

	/**
	 * Defines whether the current page is the Event Tickets Order Modifiers page.
	 *
	 * @since TBD
	 *
	 * @return bool True if on the Order Modifiers page, false otherwise.
	 */
	public function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}

	/**
	 * Returns the main admin order modifiers URL.
	 *
	 * @since TBD
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string The URL for the Order Modifiers admin page.
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$slug,
		];

		// Merge default args and passed args.
		$args = wp_parse_args( $args, $defaults );

		// Generate the admin URL.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		/**
		 * Filters the URL to the Event Tickets Order Modifiers page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Order Modifiers page.
		 */
		return apply_filters( 'tec_tickets_order_modifiers_page_url', $url );
	}

	/**
	 * Adds the Event Tickets Order Modifiers page.
	 *
	 * @since TBD
	 */
	public function add_tec_tickets_order_modifiers_page(): void {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Coupon &amp; Fees', 'event-tickets' ),
				'position' => 1.5,
				'callback' => [ $this, 'render_tec_order_modifiers_page' ],
			]
		);
	}

	/**
	 * Render the `Order Modifiers` page for the selected strategy.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_tec_order_modifiers_page(): void {
		// Enqueue required assets for the page.
		tribe_asset_enqueue_group( 'event-tickets-admin-order-modifiers' );

		// Get and sanitize request vars for modifier and modifier_id.
		$modifier_type = sanitize_key( tribe_get_request_var( 'modifier', 'coupon' ) );
		$modifier_id   = absint( tribe_get_request_var( 'modifier_id', '0' ) );

		// Prepare the context for the page.
		$context = [
			'event_id'    => 0,
			'modifier'    => $modifier_type,
			'modifier_id' => $modifier_id,
		];

		// Check if form is submitted and process the save.
		$this->handle_form_submission( $context );

		// Get the appropriate strategy for the selected modifier.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// If the strategy doesn't exist, show an error message.
		if ( ! $modifier_strategy ) {
			$this->render_invalid_modifier_message();
			return;
		}

		// Create a Modifier Manager with the selected strategy.
		$manager = new Modifier_Manager( $modifier_strategy );

		// Determine if we are in edit or table mode.
		if ( ! empty( $modifier_id ) ) {
			$this->render_edit_view( $manager, $context );
		} else {
			$this->render_table_view( $manager, $context );
		}
	}

	/**
	 * Retrieves the modifier data by ID.
	 *
	 * @since TBD
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 *
	 * @return array|null The modifier data or null if not found.
	 */
	protected function get_modifier_data_by_id( int $modifier_id ): ?array {
		// Get the modifier type from the request or default to 'coupon'.
		$modifier_type = tribe_get_request_var( 'modifier', 'coupon' );

		// Get the appropriate strategy for the selected modifier type.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		if ( ! $modifier_strategy ) {
			return null; // Return null if the strategy is not found.
		}

		$test = $modifier_strategy->get_modifier_by_id( $modifier_id );

		printr($test,'Modifier by id');

		// Use the strategy to retrieve the modifier data by ID.
		return $test;
	}

	/**
	 * Render the table view for the selected modifier.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Manager $manager The modifier manager.
	 * @param array            $context The context for rendering the table.
	 *
	 * @return void
	 */
	protected function render_table_view( Modifier_Manager $manager, array $context ): void {
		echo '<h2>' . esc_html( ucfirst( $context['modifier'] ) ) . '</h2>';
		echo $manager->render_table( $context );
	}

	/**
	 * Render the edit view for the selected modifier.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Manager $manager The modifier manager.
	 * @param array            $context The context for rendering the edit screen.
	 *
	 * @return void
	 */
	protected function render_edit_view( Modifier_Manager $manager, array $context ): void {
		// Get modifier ID from the context.
		$modifier_id = (int) $context['modifier_id'];

		// Merge the modifier data into the context to be passed to the form rendering logic.
		// If a valid modifier ID is provided, fetch the data to populate the form.
		if ( $modifier_id > 0 ) {
			$modifier_data = $this->get_modifier_data_by_id( $modifier_id );

			// Only merge if modifier data is not null.
			if ( ! is_null( $modifier_data ) ) {
				$context = array_merge( $context, $modifier_data );
			}
		}
		// @todo redscar - If a modifier ID is sent, and we are unable to find the data, do we display a message?

		// Render the edit screen, passing the populated context.
		echo '<h2>' . esc_html( ucfirst( $context['modifier'] ) . ' Edit' ) . '</h2>';
		echo $manager->render_edit_screen( $context );
	}

	/**
	 * Render an error message for invalid modifiers.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function render_invalid_modifier_message(): void {
		echo '<p>' . esc_html__( 'Invalid modifier selected.', 'event-tickets' ) . '</p>';
	}

	/**
	 * Handles the form submission and saves the modifier data.
	 *
	 * Checks if the form was submitted and verifies the nonce before proceeding with
	 * data sanitization and saving the modifier.
	 *
	 * @since TBD
	 *
	 * @param array $context The context for rendering the page.
	 *
	 * @return void
	 */
	protected function handle_form_submission( array $context ): void {
		// Check if the form was submitted and verify nonce.
		if ( isset( $_POST['order_modifier_form_save'] ) && check_admin_referer( 'order_modifier_save_action', 'order_modifier_save_action' ) ) {

			// Get the appropriate strategy for the selected modifier.
			$modifier_strategy = tribe( Controller::class )->get_modifier( $context['modifier'] );

			// If the strategy doesn't exist, show an error message.
			if ( ! $modifier_strategy ) {
				$this->render_invalid_modifier_message();
				return;
			}

			// Use the strategy to sanitize the form data.
			$modifier_data = $modifier_strategy->sanitize_data( $_POST );

			// Use the Modifier Manager to save the data.
			$manager = new Modifier_Manager( $modifier_strategy );
			$result  = $manager->save_modifier( $modifier_data );
			printr( $result, 'result of saving' );

			// Display success or error message based on result.
			if ( ! empty( $result ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Modifier saved successfully!', 'event-tickets' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to save modifier.', 'event-tickets' ) . '</p></div>';
			}
		}
	}

}
