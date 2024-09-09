<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers
 */

namespace TEC\Tickets\Order_Modifiers;

use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;

/**
 * Class Page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers
 */

/**
 * Manages the admin settings UI in relation to Order Modifiers.
 */
class Modifier_Settings {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets Order Modifiers page slug.
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-order-modifiers';

	/**
	 * Event Tickets Order Modifiers page hook suffix.
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-order-modifiers';

	/**
	 * Defines whether the current page is the Event Tickets Order Modifiers page.
	 *
	 * @since TBD
	 *
	 * @return boolean
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
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$slug,
		];

		// Allow the link to be "changed" on the fly.
		$args = wp_parse_args( $args, $defaults );

		// Keep the resulting URL args clean.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		/**
		 * Filters the URL to the Event Tickets Order Modifiers page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Event Tickets Order Modifiers page.
		 */
		return apply_filters( 'tec_tickets_order_modifiers_page_url', $url );
	}

	/**
	 * Adds the Event Tickets Order Modifiers page.
	 *
	 * @since 5.9.1
	 */
	public function add_tec_tickets_order_modifiers_page() {
		$admin_pages = tribe( 'admin.pages' );

		$order_modifiers_page = $admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Coupon &amp; Fees', 'event-tickets' ),
				'position' => 1.5,
				'callback' => [
					$this,
					'render_tec_order_modifiers_page',
				],
			]
		);
	}

	/**
	 * Render the `Order Modifiers` page of our selected strategy.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_tec_order_modifiers_page() {
		// Enqueue required assets for the page.
		tribe_asset_enqueue_group( 'event-tickets-admin-order-modifiers' );

		// @todo redscar - Should coupon be the default view?
		// Get the selected modifier type (default to 'coupon').
		$modifier_type = tribe_get_request_var( 'modifier', 'coupon' );
		$modifier_id   = tribe_get_request_var( 'modifier_id', '0' );

		// Prepare the context for the page.
		$context = [
			'event_id'    => 0,
			'modifier'    => $modifier_type,
			'modifier_id' => $modifier_id,
		];

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
		if ( ! empty( $modifier_id ) && (int) $modifier_id > 0 ) {
			$this->render_edit_view( $manager, $context );
			return;
		}

		$this->render_table_view( $manager, $context );
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

}
