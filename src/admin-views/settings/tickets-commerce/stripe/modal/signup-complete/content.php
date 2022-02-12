<?php
/**
 * The Template for displaying the Tickets Commerce Stripe modal content when connected.
 *
 * @version TBD
 *
 * @since   TBD
 */

// @todo: @juanfra: Add information about what to do with Webhooks and a Stripe user primer (currencies and such).
?>
<div class="tec-tickets__admin-settings-tickets-commerce-stripe-modal-content tec-tickets__admin-modal tribe-common-b2">

	<?php $this->template( 'settings/tickets-commerce/stripe/modal/signup-complete/notice-test-mode' ); ?>


	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>

	<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>


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
