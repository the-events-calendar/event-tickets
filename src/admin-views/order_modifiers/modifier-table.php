<?php
/**
 * Order Modifiers table template.
 *
 * This template is responsible for rendering the order modifiers table (Coupons, Fees, etc.)
 * including the search box, tabs, title, and the table itself.
 *
 * @since 5.18.0
 *
 * @var $this
 * @var $context array Context data passed to the template.
 * @var $order_modifier_table \TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Order_Modifier_Table The table instance for
 *      rendering.
 */

// Define form classes for the main form element.
$form_classes = [
	'topics-filter',
	'event-tickets__order-modifiers-admin-form',
];

?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Booking Fees', 'event-tickets' ); ?></h1>
	<form
		id="event-tickets__order-modifiers-admin-form"
		<?php tribe_classes( $form_classes ); ?>
		method="post"
	>
		<?php
		// Render the tabs for switching between different modifier types (Coupons, Fees, etc.).
		$order_modifier_table->render_tabs();

		// Render the title for the current modifier type (e.g., 'Coupons', 'Fees') with the "Add New" button.
		$order_modifier_table->render_title();

		// Render the explanation text about the order modifier you are currently viewing.
		echo wp_kses_post( $order_modifier_table->render_table_explain_text() );

		// Render the search box with a placeholder for searching through modifiers (e.g., Coupons, Fees).
		$order_modifier_table->search_box( __( 'Search', 'event-tickets' ), 'order-modifier-search' );

		// Finally, render the table itself.
		$order_modifier_table->display();
		?>
	</form>
</div>
