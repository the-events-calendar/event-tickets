<?php
/**
 * Modifier Status Dropdown for Order Modifiers.
 *
 * This file is used to display a dropdown for the status of an order modifier.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $order_modifier_status The status of the order modifier (active, inactive, draft).
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers
 */

declare( strict_types=1 );

$modifier_statuses = [
	'active'   => _x( 'Active', 'Modifier Status', 'event-tickets' ),
	'inactive' => _x( 'Inactive', 'Modifier Status', 'event-tickets' ),
	'draft'    => _x( 'Draft', 'Modifier Status', 'event-tickets' ),
];

?>

<div class="form-field form-required">
	<label for="order_modifier_status">
		<?php echo esc_html_x( 'Status', 'Modifier status dropdown label', 'event-tickets' ); ?>
	</label>
	<select name="order_modifier_status" id="order_modifier_status">
		<?php foreach ( $modifier_statuses as $status => $label ) : ?>
			<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $order_modifier_status ?? '', $status ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
