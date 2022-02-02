<?php
/**
 * The Template for displaying the Tickets Commerce Stripe status.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

if ( false === $merchant_status['connected'] ) {
	return;
}

$errors       = $merchant_status['errors'];
$capabilities = $merchant_status['capabilities'];

$charges_class = 'dashicons-yes';
if ( ! $merchant_status['charges_enabled'] ) {
	$charges_class = 'dashicons-no';
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col1">
		<?php esc_html_e( 'Stripe Status:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col2">
		<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-text--connected">
			<?php esc_html_e( 'Connected' ); ?> <span class="dashicons dashicons-saved"></span>
		</span><br>
		<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connect-text--connected">
			<?php esc_html_e( 'Charges Enabled' ); ?><span
					class="dashicons <?php echo esc_attr( $charges_class ); ?>"></span>
		</span>

		<?php if ( ! empty( $capabilities ) && is_array( $capabilities ) ) : ?>
			<h3><?php echo esc_html_e( 'Capabilities:', 'event-tickets' ); ?></h3>
			<ul>
				<?php foreach ( $capabilities as $capability => $status ) :
					$capability_class = 'dashicons-yes';
					if ( 'inactive' === $status ) {
						$capability_class = 'dashicons-no';
					}
					?>
					<li>
						<span class="dashicons <?php echo esc_attr( $capability_class ); ?>" style="color: red;"></span>
						<?php echo esc_html( $capability ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $errors['requirements'] ) && is_array( $errors['requirements'] ) ) : ?>
			<h3><?php echo esc_html_e( 'Requirements', 'event-tickets' ); ?></h3>
			<ul>
				<?php foreach ( $errors['requirements'] as $error ) : ?>
					<li>
						<span class="dashicons dashicons-warning" style="color: red;"></span>
						<span class="error-title"><?php echo esc_html( $error['requirement'] ); ?></span><br>
						<span class="error-description"><?php echo esc_html( $error['reason'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $errors['future_requirements'] ) && is_array( $errors['future_requirements'] ) ) : ?>
			<h3><?php echo esc_html_e( 'Future Requirements', 'event-tickets' ); ?></h3>
			<ul>
				<?php foreach ( $errors['future_requirements'] as $error ) : ?>
					<li>
						<span class="dashicons dashicons-warning" style="color: red;"></span>
						<span class="error-title"><?php echo esc_html( $error['requirement'] ); ?></span>
						<span class="error-description"><?php echo esc_html( $error['reason'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

</div>
