<?php
/**
 * Block: Tickets
 * Commerce Fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/commerce/fields.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */


$provider     = $this->get( 'provider' );
$provider_id  = $this->get( 'provider_id' );
$this->template( 'blocks/tickets/commerce/fields-' . $provider_id, array( 'provider' => $provider, 'provider_id' => $provider_id ) );
?>
<input name="provider" value="<?php echo esc_attr( $provider->class_name ); ?>" class="tribe-tickets-provider" type="hidden">