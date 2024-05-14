<?php
/**
 * Template to render a Map card.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Map_Card $card The Map card to render.
 */

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;

?>
<div class="tec-tickets__tab__cards__item">
	<div class="tec-tickets__tab__cards__item-thumbnail">
		<img src="<?php echo esc_url( $card->get_screen_shot_url() ); ?>"
			alt="<?php echo esc_attr( $card->get_name() ); ?>">
	</div>
	<div class="tec-tickets__tab__cards__item-content">
		<div class="tec-tickets__tab__cards__item-title"><?php echo esc_html( $card->get_name() ); ?></div>
		<div class="tec-tickets__tab__cards__item-capacity">
			<?php
				echo esc_html(
					sprintf(
					/* translators: %s: the capacity of the layout */
						_x( '%s seats', 'map seats count', 'event-tickets' ),
						number_format_i18n( $card->get_seats() )
					)
				);
				?>
		</div>
		<div class="tec-tickets__tab__cards__item-actions">
			<a class="button button-secondary add-map" href="#">
				<?php esc_html_e( 'Create Layout', 'event-tickets' ); ?>
			</a>
			<a class="button button-secondary edit-map" href="#">
				<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
			</a>
			<a class="delete-map" href="#">
				<?php esc_html_e( 'Delete', 'event-tickets' ); ?>
			</a>
		</div>
	</div>
</div>
