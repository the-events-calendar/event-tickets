<?php
/**
 * The template used to render the Controller Configurations tab.
 *
 * @since 5.16.0
 *
 * @var Map_Card[] $cards The set of cards to display.
 * @var string $add_new_url The URL to add a new Controller Configuration.
 */

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
?>

<div class="tec-tickets__seating-tab-heading-wrapper">
	<h2
		class="tec-tickets__seating-tab-heading">
		<?php
		echo esc_html_x(
			'Seating Maps',
			'Controller maps tab title',
			'event-tickets'
		);
		?>
	</h2>
	<a class="button button-secondary tec-tickets__seating-tab-heading__button"
		type="button"
		href="<?php echo esc_url( $add_new_url ); ?>">
		<?php echo esc_html_x( 'Add New', 'Add new seating configuration button', 'event-tickets' ); ?>
	</a>
	<div class="tec-tickets__seating-tab-heading__description">
		<p>
			<?php
			echo wp_kses(
				sprintf(
				/* translators: %1$s: Documentation link */
					__(
						'Create a seating map that represents your room or venue.  Configurations are used to create layouts that allow purchasers choose specific seats and ticketing tiers. %1$s',
						'event-tickets'
					),
					'<a href="https://evnt.is/seating-map" target="_blank">'
					. __( 'Learn more', 'event-tickets' )
					. '</a>'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'title'  => [],
					],
				]
			);
			?>
		</p>
	</div>
</div>
<div class="tec-tickets__seating-tab-wrapper wrap">
	<?php
	$this->template(
		'components/maps/list',
		[
			'cards'       => $cards,
			'add_new_url' => $add_new_url,
		]
	);
	?>
</div>
