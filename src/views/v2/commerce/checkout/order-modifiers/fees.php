<div class="tribe-tickets__commerce-checkout-cart-footer-order-modifier-fees">
	<ul>
	<?php foreach ( $active_fees

					as $fee ) : ?>
	<li>
		<span
			class="tribe-tickets__commerce-checkout-cart-footer-quantity-label"><?php echo $fee['display_name']; ?>:</span>
		<span
			class="tribe-tickets__commerce-checkout-cart-footer-quantity-number"><?php echo $fee['fee_amount']; ?></span>
	</li>
	<?php endforeach; ?>
	</ul>
</div>
