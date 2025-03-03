<?php
/**
 * Seating tickets block template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/tickets-block.php
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 *
 * @var string $cost_range    The cost range of the tickets.
 * @var string $inventory     The inventory of the tickets.
 * @var string $modal_content The content of seat selection modal.
 */

?>

<div class="tribe-common event-tickets tribe-tickets__tickets-wrapper">
	<div class="tribe-tickets__tickets-form tec-tickets-seating__tickets-block">
		<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__tickets-title">
			<?php echo esc_html( tribe_get_ticket_label_plural( 'seat-form' ) ); ?>
		</h2>
		<div class="tec-tickets-seating__information">
			<span><?php echo esc_html( $cost_range ); ?></span>
			<span class="tec-tickets-seating__inventory">
				<?php echo esc_html( sprintf( '%s %s', $inventory, __( 'available', 'event-tickets' ) ) ); ?>
			</span>
		</div>
		<div class="tec-tickets-seating__action">
			<?php if ( empty( $inventory ) ) : ?>
				<button class="tribe-common-c-btn tribe-common-c-btn--small sold-out-button"
						type="submit"
						disabled="disabled"
						aria-disabled="true"
				>
					<?php echo esc_html_x( 'Sold Out', 'seating tickets sold out button', 'event-tickets' ); ?>
				</button>
				<?php
			else :
				echo $modal_content; // phpcs:ignore
				?>
			<?php endif; ?>
		</div>
	</div>
</div>
