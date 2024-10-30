<?php
/**
 * Template to display a featured gateway.
 *
 * @since 5.3.0
 *
 * @var Tribe__Template  $this    Template object.
 * @var boolean          $checked Toggle checked or not.
 */

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways-item-toggle">
	<label class="tec-tickets__admin-settings-toggle-large">
		<input
			type="checkbox"
			disabled="disabled"
			name="tickets_commerce_enabled"
			<?php checked( $checked, true ); ?>
			id="tickets-commerce-enable-input"
			class="tec-tickets__admin-settings-toggle-large-checkbox"
		/>
		<span class="tec-tickets__admin-settings-toggle-large-switch"></span>
	</label>
</div>
