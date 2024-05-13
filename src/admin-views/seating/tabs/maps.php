<?php
/**
 * The template used to render the Controller Configurations tab.
 *
 * @since TBD
 *
 * @var Map_Card[] $cards The set of cards to display.
 * @var string $add_new_url The URL to add a new Controller Configuration.
 */
$cards = [
	[
		'id'        => 1,
		'name'      => 'Main Floor',
		'capacity'  => 100,
		'thumbnail' => 'https://placehold.co/400',
	],
	[
		'id'        => 2,
		'name'      => 'Balcony',
		'capacity'  => 50,
		'thumbnail' => 'https://placehold.co/400',
	],
	[
		'id'        => 3,
		'name'      => 'VIP',
		'capacity'  => 25,
		'thumbnail' => 'https://placehold.co/400',
	],
	[
		'id'        => 4,
		'name'      => 'Backstage',
		'capacity'  => 10,
		'thumbnail' => 'https://placehold.co/400',
	],
];
?>

<div class="tec-tickets__tab-heading__wrapper"><h2
		class="tec-tickets__tab-heading">
		<?php 
		echo esc_html_x(
			'Seating Maps',
			'Controller maps tab title',
			'event-tickets' 
		); 
		?>
	</h2>
	<a class="button button-secondary tec-tickets__tab-heading__button"
		type="button"
		href="<?php echo esc_url( $add_new_url ); ?>">
		<?php echo esc_html_x( 'Add New', 'Add new seating configuration button', 'event-tickets' ); ?>
	</a>
	<div class="tec-tickets__tab-heading__description">
		<p>
			<?php 
			echo wp_kses(
				sprintf(
				/* translators: %1$s: Documentation link */
					__(
						'Build different seat layouts on top of your configurations to create different sections and pricing tiers for use with tickets. %1$s',
						'event-tickets' 
					),
					'<a href="https://evnt.is" target="_blank">'
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
<div class="tec-tickets__tab-content__wrapper">
	<?php if ( empty( $cards ) ) : ?>
		<p><?php esc_html_e( 'No seating maps to show.', 'event-tickets' ); ?></p>
		<?php 
	else :
		?>
		<div class="tec-tickets__tab-content__cards">
		<?php
		foreach ( $cards as $card ) {
			$this->template( 'components/layout-card', [ 'card' => $card ] );
		} 
		?>
		</div>
	<?php endif; ?>
</div>

