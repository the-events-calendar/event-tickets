<?php
/**
 * Template to render a Layout card.
 *
 * @since 5.16.0
 *
 * @version 5.16.0
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
				echo esc_html( _x( 'No associated events', 'Layout card no associated events', 'event-tickets' ) );
			} else {
				$link_label = sprintf(
					/* translators: %d: Number of associated events for the layout */
					_n( '%d associated event', '%d associated events', $count, 'event-tickets' ),
					$count,
				);

				$link_html = sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( $card->get_associated_posts_url() ),
					esc_html( $link_label )
				);

				echo wp_kses(
					$link_html,
					[
						'a' => [
							'href'   => true,
							'target' => true,
							'rel'    => true,
						],
					]
				);
			}
			?>
		</div>
		<div class="tec-tickets__seating-tab__card-actions">
			<a
				class="button button-secondary edit-layout"
				href="<?php echo esc_url( $card->get_edit_url() ); ?>"
				data-event-count="<?php echo esc_attr( $count ); ?>">
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
			<button
				class="button button-secondary duplicate-layout"
				data-layout-id="<?php echo esc_attr( $card->get_id() ); ?>"
			>
				<?php esc_html_e( 'Duplicate', 'event-tickets' ); ?>
			</button>
			<?php if ( 0 === $count ) : ?>
			<a
				class="delete-layout"
				data-layout-id="<?php echo esc_attr( $card->get_id() ); ?>"
				data-map-id="<?php echo esc_attr( $card->get_map() ); ?>"
				href="#">
				<?php esc_html_e( 'Delete', 'event-tickets' ); ?>
			</a>
			<?php endif; ?>
		</div>
	</div>
</div>
