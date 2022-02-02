<?php
/**
 * The Template for displaying the Tickets Commerce Stripe status.
 *
 * @version TBD
 *
 * @since   TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

if ( empty( $is_merchant_connected ) ) {
	return;
}

$errors = $signup->get_errors_from_on_boarded_data();
?>
<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-col1">
		<?php esc_html_e( 'Stripe Status:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected-col2">
		<span class="tec-tickets__admin-settings-tickets-commerce-stripe-connect-text--connected">
			<?php esc_html_e( 'Connected' ); ?> <span class="dashicons dashicons-saved"></span>
		</span>

		<?php if ( is_array( $errors ) ) : ?>
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><span class="dashicons dashicons-warning" style="color: red;"></span> <?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

</div>
