<?php
/**
 * The template used to render the Layouts tab.
 *
 * @since 5.16.0
 *
 * @var Layout_Card[] $cards The set of cards to display.
 * @var string $add_new_url The URL to add a new Controller Configuration.
 * @var Map_Card[] $maps The set of maps to display.
 */

use TEC\Tickets\Seating\Admin\Tabs\Layout_Card;
use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
?>

<div class="tec-tickets__seating-tab-heading-wrapper"><h2
		class="tec-tickets__seating-tab-heading">
		<?php
		echo esc_html_x(
			'Seat Layouts',
			'Seat layouts tab title',
			'event-tickets'
		);
		?>
	</h2>

	<?php if ( ! empty( $maps ) ) : ?>
	<div class="tec-tickets-seating-modal-wrapper tribe-common tec-tickets__seating-tab-heading__button">
		<div class="tec-tickets-seating-layouts-modal__anchor"></div>
		<?php
		/** @var Tribe\Dialog\View $dialog_view */
		$dialog_view = tribe( 'dialog.view' );
		$content     = $this->template(
			'components/layouts/add-new',
			[ 'maps' => $maps ],
			false
		);

		$args = [
			'button_text'    => esc_html_x( 'Add New', 'Add new seat layout button text', 'event-tickets' ),
			'button_classes' => [ 'button-secondary', 'tec-tickets-seating-modal__button' ],
			'append_target'  => '.tec-tickets-seating-layouts-modal__anchor',
		];
		$dialog_view->render_modal( $content, $args, Layout_Edit::ADD_LAYOUT_MODAL_ID );
		?>
	</div>
	<?php endif; ?>

	<div class="tec-tickets__seating-tab-heading__description">
		<p>
			<?php
			echo esc_html__(
				'Seat layouts on top of your maps allow you to create different seating types. You can create a seat layout from one of the existing seating maps.',
				'event-tickets'
			);
			?>
		</p>
	</div>
</div>
<div class="tec-tickets__seating-tab-wrapper wrap">
	<?php
		$this->template(
			'components/layouts/list',
			[ 'cards' => $cards ]
		);
		?>
</div>
