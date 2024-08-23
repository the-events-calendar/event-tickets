<?php
/**
 * Table column price.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Template $this          The template object.
 * @var string          $regular_price Ticket regular price.
 * @var string          $price         Ticket current price.
 * @var bool            $on_sale       Whether the ticket is on sale.
 *
 */

?>
<?php if ( ! empty( $on_sale ) ) : ?>
	<span class="tec-tickets-all-tickets-table-regular-price">
		<?php echo esc_html( $regular_price ); ?>
	</span>
<?php endif; ?>
<span class="tec-tickets-all-tickets-table-price">
	<?php echo esc_html( $price ); ?>
</span>
