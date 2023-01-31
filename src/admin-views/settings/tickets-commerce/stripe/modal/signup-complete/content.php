<?php
/**
 * The Template for displaying the Tickets Commerce Stripe modal content when connected.
 *
 * @version 5.3.0
 *
 * @since   5.3.0
 */

?>
<div class="tec-tickets__admin-settings-tickets-commerce-stripe-modal-content tec-tickets__admin-modal tribe-common-b2">

	<?php $this->template( 'settings/tickets-commerce/stripe/modal/signup-complete/notice-test-mode' ); ?>

	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-modal-content-section">
		<strong><?php esc_html_e( 'Currency', 'event-tickets' ); ?></strong> &mdash;
		<?php
			esc_html_e( 'Be sure that your Stripe currency matches the currency you have configured for Tickets Commerce, to avoid any issues or unexpected conversion fees.', 'event-tickets' );
		?>
	</div>

	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-modal-content-section">
		<strong><?php esc_html_e( 'Payment methods', 'event-tickets' ); ?></strong> &mdash;
		<?php
		printf(
			// Translators: %1$s: opening `a` tag to stripe's dashboard. %2$s: closing `a` tag.
			esc_html__( 'You will have to confirm that the payments methods you have selected to sell tickets are enabled on the %1$sStripe payment methods section%2$s.', 'event-tickets' ),
			'<a href="https://dashboard.stripe.com/settings/payment_methods" target="_blank" rel="noopener noreferrer" class="tribe-common-anchor-alt">',
			'</a>'
		);
		?>
	</div>

	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-modal-content-section">
		<strong><?php esc_html_e( 'Webhooks', 'event-tickets' ); ?></strong> &mdash;
		<?php
		printf(
			// Translators: %1$s: opening `a` tag to the knowledge base article. %2$s: closing `a` tag.
			esc_html__( 'In order for ticket sales to be marked as complete for some payment methods on your Stripe gateway for your Event Tickets site, you must configure the webhook at Stripe. %1$sLearn how to set up webhooks here%2$s.', 'event-tickets' ),
			'<a href="https://evnt.is/1b3p" target="_blank" rel="noopener noreferrer" class="tribe-common-anchor-alt">',
			'</a>'
		);
		?>
	</div>

	<div class="tec-tickets__admin-settings-tickets-commerce-stripe-modal-content-section">
		<strong><?php esc_html_e( 'PCI Compliance', 'event-tickets' ); ?></strong> &mdash;
		<?php
		printf(
			// Translators: %1$s: opening `a` tag to the knowledge base article. %2$s: closing `a` tag.
			esc_html__( 'Stripe allows you to accept credit or debit cards directly on your website. Because of this, your site needs to maintain %1$sPCI-DSS compliance%2$s.', 'event-tickets' ),
			'<a href="https://theeventscalendar.com/knowledgebase/k/pci-compliance/" target="_blank" rel="noopener noreferrer" class="tribe-common-anchor-alt">',
			'</a>'
		);
		?>
	</div>


	<div class="tec-tickets__admin-modal-buttons">

		<button
			data-js="a11y-close-button"
			class="tribe-common-c-btn tribe-common-b1 tribe-common-b2--min-medium tribe-modal__close-button"
			type="button"
			aria-label="<?php esc_attr_e( 'Close this modal window', 'event-tickets' ); ?>"
		>
			<?php esc_html_e( 'Got it, thanks!', 'event-tickets' ); ?>
		</button>

	</div>

</div>
