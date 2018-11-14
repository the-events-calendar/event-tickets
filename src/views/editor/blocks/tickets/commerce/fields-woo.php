<?php
/**
 * This template renders the form fields for WooCommerce
 *
 * @version 0.3.0-alpha
 *
 */
$provider     = $this->get( 'provider' );
$provider_id  = $this->get( 'provider_id' );
?>
<input
	type="hidden"
	id="wootickets_process"
	name="wootickets_process"
	value="1"
/>