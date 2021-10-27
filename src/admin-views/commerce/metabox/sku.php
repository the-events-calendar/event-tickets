<?php

use TEC\Tickets\Commerce\Module;
/**
 * Filters the boolean value that controls whether a sku is displayed or not
 *
 * @since 4.7
 *
 * @param boolean $display_sku
 */
$display_sku = apply_filters( 'tribe_events_tickets_tpp_display_sku', true );
$html_safe_provider_class = sanitize_html_class( Module::class );

if ( ! $display_sku ) {
	return;
}
?>

<div
	class="ticket_advanced_<?php echo $html_safe_provider_class; ?> input_block tribe-dependent"
	data-depends="#provider_TEC_Tickets_Commerce_Module_radio"
	data-condition-is-checked
>
	<label for="ticket_tpp_sku" class="ticket_form_label ticket_form_left"><?php esc_html_e( 'SKU:', 'event-tickets' ); ?></label>
	<input
		type="text"
		id="ticket_sku"
		name="ticket_sku"
		class="ticket_field sku_input ticket_form_right"
		size="14"
		value="<?php echo esc_attr( $sku ); ?>"
	>
	<p class="description ticket_form_right">
		<?php
		echo esc_html(
			sprintf(
				__( 'A unique identifying code for each %s type you\'re selling', 'event-tickets' ),
				tribe_get_ticket_label_singular_lowercase( 'sku' )
			)
		);
		?>
	</p>
</div>
