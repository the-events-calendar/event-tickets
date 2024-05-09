<?php
/**
 * Seating tickets block template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/tickets-block.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var $cost_range string The cost range of the tickets.
 * @var $inventory  string The inventory of the tickets.
 */

?>

<div class="tribe-common event-tickets tribe-tickets__tickets-wrapper">
	<div class="tribe-tickets__tickets-form tec-tickets-sld__tickets-block">
		<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__tickets-title">
			<?php echo esc_html( tribe_get_ticket_label_plural( 'seat-form' ) ); ?>
		</h2>
		<div class="tec-tickets-seating__tickets-block__information">
			<span><?php echo esc_html( $cost_range ); ?></span>
			<span class="tec-tickets-seating__tickets-block__inventory">
				<?php echo esc_html( sprintf( '%s %s', $inventory, __( 'available', 'event-tickets' ) ) ); ?>
			</span>
		</div>
		<div class="tec-tickets-seating__tickets-block__action">
		<?php
			/** @var Tribe\Dialog\View $dialog_view */
			$dialog_view = tribe( 'dialog.view' );
			$content     = '<p>test</p>';
			$args        = [
				'button_text'    => esc_html_x( 'Find Seats', 'Find seats button text', 'event-tickets' ),
				'button_classes' => [ 'tribe-common-c-btn', 'tribe-common-c-btn--small' ],
				'append_target'  => '.tec-tickets-seating__tickets-block__information',
			];
			$dialog_view->render_modal( $content, $args, 'tec-tickets-seating__tickets-block__action--submit' );
			?>
		</div>
	</div>
</div>