<?php
/**
 * The template used to render the Layouts tab.
 *
 * @since TBD
 *
 * @var Layout_Card[] $cards The set of cards to display.
 * @var string $add_new_url The URL to add a new Controller Configuration.
 */

?>

<div class="tec-tickets__tab-heading__wrapper"><h2
		class="tec-tickets__tab-heading">
		<?php echo esc_html_x( 'Seat Layouts',
			'Seat layouts tab title',
			'event-tickets' ); ?>
	</h2>

	<div class="tec-tickets-seating-modal-wrapper tribe-common tec-tickets__tab-heading__button">
		<div class="tec-tickets-seating-layouts-modal__anchor"></div>
		<?php
		/** @var Tribe\Dialog\View $dialog_view */
		$dialog_view = tribe( 'dialog.view' );
		$content     = '<p>test</p>';
		$args        = [
			'button_text' => esc_html_x( 'Add New', 'Add new seat layout button text','event-tickets' ),
			'button_classes' => ['button-secondary','tec-tickets-seating-modal__button'],
			'append_target' => '.tec-tickets-seating-layouts-modal__anchor',
		];
		$dialog_view->render_modal( $content, $args, 'tec-tickets-seating-layouts-modal');
		?>
	</div>

	<div class="tec-tickets__tab-heading__description">
		<p>
			<?php echo wp_kses(
				sprintf(
				/* translators: %1$s: Documentation link */
					__( 'Create a seating configuration that represents your room or venue. Configurations are used to create layouts that allow purchasers choose specific seats and ticketing tiers. %1$s',
						'event-tickets' ),
					'<a href="https://evnt.is" target="_blank">'
					. __( 'Learn more', 'event-tickets' )
					. '</a>' ),
				[ 'a' => [ 'href' => [], 'target' => [], 'title' => [] ] ] ); ?>
		</p>
	</div>
</div>
<div class="tec-tickets__tab-content__wrapper">
	<?php if ( empty( $cards ) ) : ?>
		<p><?php esc_html_e( 'No seat layouts to show.', 'event-tickets' ); ?></p>
	<?php else : foreach ( $cards as $card ) {
		$this->template->template( 'components/map-card', $card );
	} ?>
	<?php endif; ?>
</div>
