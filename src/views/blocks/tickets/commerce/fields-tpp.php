<?php
/**
 * Block: Tickets
 * Commerce Fields TPP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/commerce/fields-tpp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$provider     = $this->get( 'provider' );
$provider_id  = $this->get( 'provider_id' );
?>
<input
	type="hidden"
	id="add"
	name="add"
	value="1"
/>
