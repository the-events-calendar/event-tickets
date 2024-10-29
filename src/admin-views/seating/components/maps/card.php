<?php
/**
 * Template to render a Map card.
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 *
 * @var Map_Card $card The Map card to render.
 */

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;

?>
<div class="tec-tickets__seating-tab__card">
	<div class="tec-tickets__seating-tab__card-thumbnail">
		<img src="<?php echo esc_url( $card->get_screenshot_url() ); ?>"
			alt="<?php echo esc_attr( $card->get_name() ); ?>">
	</div>
	<div class="tec-tickets__seating-tab__card-content">
		<div class="tec-tickets__seating-tab__card-title"><?php echo esc_html( $card->get_name() ); ?></div>
		<div class="tec-tickets__seating-tab__card-capacity">
			<?php
				echo esc_html(
					sprintf(
					/* translators: %s: the capacity of the map */
						_x( '%s seats', 'map seats count', 'event-tickets' ),
						number_format_i18n( $card->get_seats() )
					)
				);
				?>
		</div>
		<div class="tec-tickets__seating-tab__card-actions">
			<a class="button button-primary add-map" href="<?php echo esc_url( $card->get_create_layout_url() ); ?>">
				<?php esc_html_e( 'Create Layout', 'event-tickets' ); ?>
			</a>
			<a class="button button-secondary edit-map" href="<?php echo esc_url( $card->get_edit_url() ); ?>">
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
			<?php if ( ! $card->has_layouts() ) : ?>
			<a
				class="delete-map"
				data-map-id="<?php echo esc_attr( $card->get_id() ); ?>"
				href="#"
			>
				<?php esc_html_e( 'Delete', 'event-tickets' ); ?>
			</a>
			<?php endif; ?>
		</div>
	</div>
</div>
