<?php

/**
 * Display dismissible banner for STellar Sale.
 * 
 * @since TBD
 *
 */

$subheader_text = tribe_is_truthy( $is_premium ) ?
	esc_html__( 'Save 30% on all StellarWP products.', 'event-tickets' ) :
	esc_html__( 'Save 30% on all Events Calendar products.', 'event-tickets' );

?>
<div class="tec-tickets__admin-stellar-sale-banner-container">
	<div class="tec-tickets__admin-stellar-sale-banner-column-one">
		<h3 class="tec-tickets__admin-stellar-sale-header">
			<?php esc_html_e( 'Make it stellar.', 'event-tickets' ); ?>
		</h3>
		<h4 class="tec-tickets__admin-stellar-sale-subheader">
			<?php echo $subheader_text; ?>
		</h4>
		<a class="tec-tickets__admin-stellar-sale-button" href="">
			<?php esc_html_e( 'Shop Now', 'event-tickets' ); ?>
		</a>
	</div>
	<div class="tec-tickets__admin-stellar-sale-banner-column-two">
		<p class="tec-tickets__admin-stellar-sale-text">
			<?php esc_html_e( 'Purchase any StellarWP product during the sale and get 100% off WP Business Reviews and take 40% off all other brands.', 'event-tickets' ); ?>
		</p>
		<a class="tec-tickets__admin-stellar-sale-link" href="">
			<?php esc_html_e( 'View all StellarWP Deals', 'event-tickets' ); ?>
		</a>
	</div>
</div>