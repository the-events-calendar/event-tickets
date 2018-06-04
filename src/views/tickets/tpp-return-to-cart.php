<?php
$link = tribe( 'tickets.commerce.paypal.links' )->return_to_cart();
?>

<a href="<?php echo esc_url( $link ) ?>" target="_self" class="tribe-commerce return-to-cart">
	<?php esc_html_e( 'Return to Cart', 'event-tickets' ) ?>
</a>
