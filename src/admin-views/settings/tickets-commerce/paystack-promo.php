<?php


?>
<div class="tec-tickets__admin-after-pay-promo-container">
	<div class="tec-tickets__admin-after-pay-promo-icon-container">
		<img
			class="tec-tickets__admin-after-pay-promo-icon"
			src="<?php echo esc_url( tribe_resource_url( 'images/admin/paystack-logo.svg', false, null, $main ) ); ?>"
			alt="<?php esc_attr_e( 'Afterpay', 'event-tickets' ); ?>"
		/>
	</div>
	<div class="tec-tickets__admin-after-pay-promo-content-container">
		<div class="tec-tickets__admin-after-pay-promo-content-title">
			<?php esc_html_e( 'Add Paystack checkout to Tickets Commerce', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets__admin-after-pay-promo-description">
			<?php echo wp_kses( esc_html_e( 'Accept payments for your ticket sales in Nigeria, Ghana, South Africa, and Kenya with Paystack.  Install the free plugin to get started.', 'event-tickets' ), 'post' ); ?>
		</div>
		<div class="tec-tickets__admin-after-pay-promo-links-container">
			<a href="#" class="tec-tickets__admin-after-pay-promo-link">
				<?php esc_html_e( 'Download the plugin', 'event-tickets' ); ?>
			</a>
			<a href="#" class="tec-tickets__admin-after-pay-promo-link">
				<?php esc_html_e( 'Learn more in the knowledgebase', 'event-tickets' ); ?>
			</a>
		</div>
	</div>
</div>