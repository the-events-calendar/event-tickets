<?php
/**
 * Introduction section for the Tickets Commerce > PayPal Commerce gateway settings.
 *
 * @since 5.1.6
 *
 * @var string $plugin_url [Global] The plugin URL.
 */

// @todo Replace font awesome icon usages.

?>

<div id="give-paypal-commerce-introduction-wrap">
	<div class="hero-section">
		<div>
			<h2><?php esc_html_e( 'Accept payments with PayPal Commerce', 'event-tickets' ); ?></h2>
			<p class="give-field-description">
				<?php esc_html_e( 'Allow your customers to pay using Debit or Credit Cards directly on your website.', 'event-tickets' ); ?>
			</p>
		</div>
		<div class="paypal-logo">
			<img src="<?php echo esc_url( $plugin_url . 'src/resources/images/admin/paypal-logo.svg' ); ?>" width="316" height="84" alt="<?php esc_attr_e( 'PayPal Logo Image', 'event-tickets' ); ?>">
		</div>
	</div>
	<div class="feature-list">
		<div>
			<i class="fa fa-angle-right"></i> <?php esc_html_e( 'Credit and Debit Card payments', 'event-tickets' ); ?>
		</div>
		<div>
			<i class="fa fa-angle-right"></i> <?php esc_html_e( 'Easy no-API key connection', 'event-tickets' ); ?>
		</div>
		<div>
			<i class="fa fa-angle-right"></i> <?php esc_html_e( 'Accept payments from around the world', 'event-tickets' ); ?>
		</div>
		<div>
			<i class="fa fa-angle-right"></i> <?php esc_html_e( 'Supports 3D Secure payments', 'event-tickets' ); ?>
		</div>
	</div>
</div>
