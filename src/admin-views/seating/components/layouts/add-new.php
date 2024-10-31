<?php
/**
 * The template used to render the Layouts tab.
 *
 * @since 5.16.0
 *
 * @var Map_Card[] $maps The set of maps to display.
 *
 * @version 5.16.0
 */

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
?>
<div class="tec-tickets-seating__new-layout-wrapper">
	<label class="tec-tickets-seating__new-layout-header" for="tec-tickets-seating__select-map">
		<?php esc_html_e( 'Choose a Seating Map for this Layout', 'event-tickets' ); ?>
	</label>
	<select class="tec-tickets-seating__select-map" name="select-map" id="tec-tickets-seating__select-map">
		<?php foreach ( $maps as $map_object ) : ?>
			<option value="<?php echo esc_attr( $map_object->get_id() ); ?>"
					data-seats-count="<?php echo esc_attr( $map_object->get_seats() ); ?>"
					data-screenshot-url="<?php echo esc_url( $map_object->get_screenshot_url() ); ?>">
				<?php echo esc_html( $map_object->get_name() ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<div class="tec-tickets-seating__new-layout-map-preview">
		<img id="tec-tickets-seating__new-layout-map-preview-img" class="tec-tickets-seating__new-layout-map-preview-img" src="<?php echo esc_url( $maps[0]->get_screenshot_url() ); ?>" alt="<?php echo esc_attr( $maps[0]->get_name() ); ?>">
	</div>
	<div class="tec-tickets-seating__new-layout-map-info">
		<span class="tec-tickets-seating__new-layout-map-name">
			<?php
				echo esc_html( $maps[0]->get_name() );
			?>
		</span>
		<span class="tec-tickets-seating__new-layout-map-seats-count">
			<?php echo esc_html( $maps[0]->get_seats() ); ?>
		</span>
		<span class="tec-tickets-seating__new-layout-map-seats-label">
			<?php echo esc_html__( 'seats', 'event-tickets' ); ?>
		</span>
	</div>
	<div class="tec-tickets-seating__new-layout-buttons">
		<button class="tec-tickets-seating__new-layout-button-cancel button button-secondary">
			<?php echo esc_html__( 'Cancel', 'event-tickets' ); ?>
		</button>
		<button class="tec-tickets-seating__new-layout-button-add button button-primary">
			<?php echo esc_html__( 'Use this Map', 'event-tickets' ); ?>
		</button>
	</div>
</div>
