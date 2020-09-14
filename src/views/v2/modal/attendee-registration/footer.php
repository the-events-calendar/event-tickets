<?php
/**
 * Modal: Attendee Registration > Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/attendee-registration/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $has_tpp True if it is tribe commerce.
 */

?>

<div class="tribe-tickets__item__attendee__fields__footer">
	<?php if ( $has_tpp ) : ?>
		<button
			type="submit"
			name="checkout-button"
		>
			<?php esc_html_e( 'Save and Checkout', 'event-tickets' ); ?>
		</button>
	<?php else : ?>
		<button
			type="submit"
			class="tribe-common-c-btn-link tribe-common-c-btn--small tribe-block__tickets__item__attendee__fields__footer_submit tribe-tickets__attendee__fields__footer_cart-button tribe-validation-submit"
			name="cart-button"
		>
				<?php esc_html_e( 'Save and View Cart', 'event-tickets' ); ?>
		</button>
		<span class="tribe-block__tickets__item__attendee__fields__footer__divider"><?php esc_html_e( 'or', 'event-tickets' ); ?></span>
		<button
			type="submit"
			class="tribe-common-c-btn tribe-common-c-btn--small tribe-block__tickets__item__attendee__fields__footer_submit tribe-tickets__attendee__fields__footer_checkout-button tribe-validation-submit"
			name="checkout-button"
		>
				<?php esc_html_e( 'Checkout Now', 'event-tickets' ); ?>
		</button>
	<?php endif; ?>
</div>
