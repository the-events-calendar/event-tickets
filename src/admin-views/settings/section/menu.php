<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since 5.3.0
 *
 * @var Tribe__Template $this     Template object.
 * @var array[]         $sections Array of section settings.
 */

use \TEC\Tickets\Commerce\Payments_Tab;

if ( empty( $sections ) ) {
	return;
}
?>
	<div class="tec-tickets__admin-settings-tickets-commerce-section-menu">
		<?php foreach ( $sections as $section ) {
			$this->template( 'section/link', $section );
		}
		?>
	</div>
<?php if ( ! empty( $selected_section ) ) : ?>
	<input
		type="hidden"
		name="<?php echo esc_attr( Payments_Tab::$key_current_section ); ?>"
		id="<?php echo esc_attr( Payments_Tab::$key_current_section ); ?>"
		value="<?php echo esc_attr( $selected_section ); ?>"
	/>
<?php endif;