<?php
/**
 * The template used to render a Seat Layout card in the Seat Layouts page.
 *
 * @since TBD
 *
 * @var array $card The Seat Layout card to render.
 */

?>

<div class="tec-tickets__tab__cards__item">
	<div class="tec-tickets__tab__cards__item-thumbnail">
		<img src="<?php echo esc_url( $card['thumbnail'] ); ?>" alt="<?php echo esc_attr( $card['name'] ); ?>">
	</div>
	<div class="tec-tickets__tab__cards__item-content">
		<div class="tec-tickets__tab__cards__item-title"><?php echo esc_html( $card['name'] ); ?></div>
		<div class="tec-tickets__tab__cards__item-capacity">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: the capacity of the layout */
					_x( '%s seats', 'map seats count', 'event-tickets' ),
					number_format_i18n( $card['capacity'] )
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
