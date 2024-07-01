<?php
/**
 * Template to render a Layout card.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Layout_Card $card The Layout card to render.
 */
	
use TEC\Tickets\Seating\Admin\Tabs\Layout_Card;

?>
<div class="tec-tickets__seating-tab__card">
	<div class="tec-tickets__seating-tab__card-thumbnail">
		<img src="<?php echo esc_url( $card->get_screenshot_url() ); ?>"
			alt="<?php echo esc_attr( $card->get_name() ); ?>">
	</div>
	<div class="tec-tickets__seating-tab__card-content">
		<div class="tec-tickets__seating-tab__card-title"><?php echo esc_html( $card->get_name() ); ?></div>
		<div class="tec-tickets__seating-tab__card-info">
			<?php
			$count = $card->get_associated_posts_count();
			
			if ( 0 === $count ) {
				echo esc_html__( 'No associated events', 'event-tickets' );
			} else {
				echo esc_html(
					sprintf(
						/* translators: %d: Number of associated events for the layout */
						_n( '%d associated event', '%d associated events', $count, 'event-tickets' ),
						$count,
						'event-tickets' 
					)
				);
			}
			?>
		</div>
		<div class="tec-tickets__seating-tab__card-actions">
			<a class="button button-secondary edit-layout" href="<?php echo esc_url( $card->get_edit_url() ); ?>">
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
			<?php if ( 0 === $count ) : ?>
			<a class="delete-layout" href="<?php echo esc_url( $card->get_delete_url() ); ?>">
				<?php esc_html_e( 'Delete', 'event-tickets' ); ?>
			</a>
			<?php endif; ?>
		</div>
	</div>
</div>
